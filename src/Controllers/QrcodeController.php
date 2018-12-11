<?php

namespace Weiming\Controllers;

use \Illuminate\Pagination\Paginator;
use \OSS\Core\OssException;
use \OSS\OssClient;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\UploadedFile;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\PayQrcode;
use \Weiming\Models\Qrcode;
use \Weiming\Models\Setting;

class QrcodeController extends BaseController
{
    /**
     * @api {get} /getWechatQrcode 获取收款二维码
     * @apiName GetWechatQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": {
     *           "url": "http://wm2028.oss-cn-hangzhou.aliyuncs.com/755559a5053ff9bb5d458b7cab707faa.jpg"
     *       }
     *   }
     */
    public function getWechatQrcode(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '请上传二维码', 'data' => []];
        $setting = Setting::where('key', '=', 'personal_merchant_qrcode')->first();
        $type    = $setting && in_array($setting->val, [1, 2]) ? $setting->val : null;
        if ($type) {
            $qrcode = Qrcode::where('type', '=', $type)->orderBy('money', 'ASC')->orderBy('count', 'ASC')->orderBy('limit', 'DESC')->first();
            if ($qrcode) {
                $result['status']      = 0;
                $result['msg']         = '';
                $result['data']['url'] = $qrcode->url;
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/addQrcode 添加二维码
     * @apiName AddQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {String} wechat 微信号
     * @apiParam {String} content 图片 Base64 格式内容，如：data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS ...
     * @apiParam {Number} type 类型 1、个人收款 2、商户收款
     * @apiParam {Float} limit 限额
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "添加成功",
     *       "data": []
     *   }
     */
    public function addQrcode(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '添加失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $wechat    = $postDatas['wechat'] ?? '';
        $content   = $postDatas['content'] ?? '';
        $limit     = $postDatas['limit'] ?? 0;
        $type      = $postDatas['type'] ?? 1;
        if ($wechat && $content) {
            // 判断二维码是否存在
            $isHasQrcode = Qrcode::where('wechat_id', '=', trim($wechat))->count();
            if ($isHasQrcode > 0) {
                $result['msg'] = '二维码已存在，您不能添加';
            } else {
                $qrcodeInfo = Utils::parseBase64Image($content);
                if ($qrcodeInfo) {
                    $res             = null;
                    $accessKeyId     = $this->settings['oss']['accessKeyId'];
                    $accessKeySecret = $this->settings['oss']['accessKeySecret'];
                    $endpoint        = $this->settings['oss']['endpoint'];
                    $bucket          = $this->settings['oss']['bucket'];
                    $object          = md5(uniqid('', true)) . '.' . $qrcodeInfo['ext'];
                    try {
                        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                        $res       = $ossClient->putObject($bucket, $object, base64_decode($qrcodeInfo['content']));
                    } catch (OssException $e) {
                        $result['msg'] = $e->getMessage();
                        return $response->withJson($result);
                    }
                    $qrcodeUrl = $res['oss-request-url'] ?? ($res['info']['url'] ?? null);
                    if ($qrcodeUrl) {
                        if (Qrcode::create([
                            'wechat_id' => $wechat,
                            'url'       => str_replace('http://', 'https://', $qrcodeUrl),
                            'type'      => $type,
                            'limit'     => $limit,
                        ])) {
                            $result['status'] = 0;
                            $result['msg']    = '添加成功';
                        }
                    }
                }
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateQrcode/:id 更新二维码
     * @apiName UpdateQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id
     * @apiParam {String} wechat 微信号
     * @apiParam {String} content 图片 Base64 格式内容，如：data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS ...
     * @apiParam {Number} type 类型 1、个人收款 2、商户收款
     * @apiParam {Float} limit 限额
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function updateQrcode(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败', 'data' => []];
        $id        = $args['id'];
        $postDatas = $request->getParsedBody();
        $wechat    = $postDatas['wechat'] ?? '';
        $content   = $postDatas['content'] ?? '';
        $limit     = $postDatas['limit'] ?? 0;
        $type      = $postDatas['type'] ?? 1;
        $data      = [];
        if ($id > 0 && $wechat && $content) {
            // 有过收款的不能修改微信号和二维码图片，只能修改限额
            $qrcodeUrl = '';
            $payQrcode = PayQrcode::where('qrcode_id', '=', $id)->count();
            if ($payQrcode == 0) {
                $qrcodeInfo = Utils::parseBase64Image($content);
                if ($qrcodeInfo) {
                    $res             = null;
                    $accessKeyId     = $this->settings['oss']['accessKeyId'];
                    $accessKeySecret = $this->settings['oss']['accessKeySecret'];
                    $endpoint        = $this->settings['oss']['endpoint'];
                    $bucket          = $this->settings['oss']['bucket'];
                    $object          = md5(uniqid('', true)) . '.' . $qrcodeInfo['ext'];
                    try {
                        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                        $res       = $ossClient->putObject($bucket, $object, base64_decode($qrcodeInfo['content']));
                    } catch (OssException $e) {
                        $result['msg'] = $e->getMessage();
                        return $response->withJson($result);
                    }
                    $qrcodeUrl = $res['oss-request-url'] ?? ($res['info']['url'] ?? null);
                }
            }
            if ($qrcodeUrl) {
                $data['url'] = str_replace('http://', 'https://', $qrcodeUrl);
            }
            if ($wechat) {
                $data['wechat_id'] = $wechat;
            }
        }
        if ($type) {
            $data['type'] = $type;
        }
        if ($limit) {
            $data['limit'] = $limit;
        }
        if (count($data) > 0 && Qrcode::where('id', '=', $id)->update($data)) {
            $result['status'] = 0;
            $result['msg']    = '更新成功';
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/qrcodes 二维码列表
     * @apiName Qrcodes
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} page 分页，不提交默认为 1
     * @apiParam {Number} perPage 每页数据总数
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "current_page": 1,
     *       "data": [
     *           {
     *               "id": 9,
     *               "wechat_id": "1003",  // 微信号
     *               "url": "http://wm2028.oss-cn-hangzhou.aliyuncs.com/890cbf0b8652dd5ba1816730230d2e5b.jpg", // 二维码图片
     *               "limit": "3000.00", // 限额
     *               "count": 0, // 支付次数
     *               "type": 1, // 类型，1 个人、2 商户
     *               "company_id": 1, // 业务平台ID
     *               "created_at": "2017-10-05 11:05:19",
     *               "updated_at": "2017-10-05 11:30:10"
     *           }
     *       ],
     *       "first_page_url": "/admin/qrcodes?page=1",
     *       "from": 1,
     *       "last_page": 9,
     *       "last_page_url": "/admin/qrcodes?page=9",
     *       "next_page_url": "/admin/qrcodes?page=2",
     *       "path": "/admin/qrcodes",
     *       "per_page": 1,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 9
     *   }
     */
    public function qrcodes(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();
        $page     = isset($getDatas['page']) ? intval($getDatas['page']) : 1;
        $perPage  = isset($getDatas['perPage']) ? intval($getDatas['perPage']) : 20;
        // 数据权限
        $companyIds      = $this->jwt->userInfo->company_ids;
        $currentUserType = $this->jwt->userInfo->type;
        Paginator::currentPathResolver(function () {
            return "/admin/qrcodes";
        });
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        // 管理员看全部业务平台
        if ($currentUserType == 1) {
            $result = Qrcode::whereRaw("`id` > 0");
        } else {
            $result = Qrcode::whereRaw("`company_id` IN ({$companyIds})");
        }
        $result = $result->orderBy('id', 'DESC')->paginate($perPage);
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/deleteQrcode/:id 删除二维码
     * @apiName DeleteQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "删除成功",
     *       "data": []
     *   }
     */
    public function deleteQrcode(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '删除失败', 'data' => []];
        $id     = $args['id'];
        if ($id > 0) {
            if (PayQrcode::where('qrcode_id', '=', $id)->count() == 0) {
                if (Qrcode::where('id', '=', $id)->delete()) {
                    $result['status'] = 0;
                    $result['msg']    = '删除成功';
                }
            } else {
                $result['msg'] = '该二维码下有支付记录，您不能删除';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/disableQrcode/:id 启用禁用二维码
     * @apiName DisableQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 二维码id
     * @apiParam {Number} disable 类型 0、启用 1、禁用，默认启用
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "更新成功",
     *       "data": []
     *   }
     */
    public function disableQrcode(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id        = $args['id'];
        $disable   = $postDatas['disable'] ?? 0;
        if ($id > 0 && $disable >= 0) {
            if (Qrcode::where('id', '=', $id)->update(['disable' => $disable])) {
                $result['status'] = 0;
                $result['msg']    = '成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/batchUploadQrcode 批量上传二维码
     * @apiName BatchUploadQrcode
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {File} file 上传zip压缩包文件，上传采用 form-data 方式上传，即常见表单方式上传文件。压缩包内文件名格式为：微信号_限额_类型1为个人二维码、2为商户二维码，如：1001_10000_1.jpg
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "有27张图片上传成功，其中27张图片被更新",
     *       "data": []
     *   }
     */
    public function batchUploadQrcode(Request $request, Response $response, $args)
    {
        $result        = ['status' => 1, 'msg' => '上传失败', 'data' => []];
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile  = $uploadedFiles['file'] ?? null;
        $uploadDir     = $this->settings['upload'];
        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            $flag     = 0;
            $newPic   = 0;
            $filename = $this->moveUploadedFile($uploadDir, $uploadedFile);
            $zip      = zip_open($uploadDir . DIRECTORY_SEPARATOR . $filename);
            if ($zip) {
                $accessKeyId     = $this->settings['oss']['accessKeyId'];
                $accessKeySecret = $this->settings['oss']['accessKeySecret'];
                $endpoint        = $this->settings['oss']['endpoint'];
                $bucket          = $this->settings['oss']['bucket'];
                $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                while ($zip_entry = zip_read($zip)) {
                    // $compressedsize = zip_entry_compressedsize($zip_entry);
                    // $compressionmethod = zip_entry_compressionmethod($zip_entry);
                    $name          = zip_entry_name($zip_entry);
                    $filesize      = zip_entry_filesize($zip_entry);
                    $fileInfo      = pathinfo($name);
                    $fileExt       = $fileInfo['extension'] ?? null;
                    $fileExt       = strtolower($fileExt);
                    $parseFilename = explode('_', str_replace($fileInfo['dirname'] . '/', '', $fileInfo['filename'])); // 文件名格式：{微信号}_{限额}_{类型，1为个人二维码、2为商户二维码}
                    if ($fileExt && in_array($fileExt, ['png', 'jpg', 'jpeg', 'gif', 'bmp']) && count($parseFilename) > 1 && count($parseFilename) <= 3 && zip_entry_open($zip, $zip_entry, "r")) {
                        $object = md5(uniqid('', true)) . '.' . $fileExt;
                        $buf    = zip_entry_read($zip_entry, $filesize);
                        try {
                            $res = $ossClient->putObject($bucket, $object, $buf);
                        } catch (OssException $e) {
                            $result['msg'] = $e->getMessage();
                            return $response->withJson($result);
                        }
                        zip_entry_close($zip_entry);
                        $qrcodeUrl = $res['oss-request-url'] ?? ($res['info']['url'] ?? null);
                        if ($qrcodeUrl) {
                            $qrcode = Qrcode::updateOrCreate([
                                'wechat_id' => $parseFilename[0],
                            ], [
                                'wechat_id' => $parseFilename[0],
                                'url'       => str_replace('http://', 'https://', $qrcodeUrl),
                                'limit'     => $parseFilename[1],
                                'type'      => $parseFilename[2] ?? 1,
                            ]);
                            if ($qrcode) {
                                $flag++;
                                if (!$qrcode->wasRecentlyCreated) {
                                    $newPic++;
                                }
                            }
                        }
                    }
                }
                if ($flag > 0) {
                    $result['status'] = 0;
                    $result['msg']    = '有' . $flag . '张图片上传成功，其中' . $newPic . '张图片被更新';
                }
                zip_close($zip);
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /resetQrcodeLimit 每天支付金额0点清0
     * @apiName ResetQrcodeLimit
     * @apiGroup Qrcode
     * @apiVersion 1.0.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "success",
     *       "data": []
     *   }
     */
    public function resetQrcodeLimit(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => 'fail', 'data' => []];
        if (Qrcode::where('disable', '=', 0)->update(['day_money' => 0])) {
            $result['status'] = 0;
            $result['msg']    = 'success';
        }
        return $response->withJson($result);
    }

    /**
     * form 表单上传文件
     * @param  String       $directory    上传目录
     * @param  UploadedFile $uploadedFile 上传文件
     * @return String                     文件名
     */
    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename  = bin2hex(random_bytes(8));
        $filename  = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }
}
