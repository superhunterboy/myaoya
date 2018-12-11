<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\Setting;

class SettingController extends BaseController
{
    /**
     * @api {get} /getOfflinePayBank 获取线下收款银行信息
     * @apiName QetOfflinePayBank
     * @apiGroup Setting
     * @apiVersion 1.1.0
     * @apiPermission none
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": [
     *           {
     *               "id": 2,
     *               "key": "offline_pay_bank_name",
     *               "val": "招商银行",
     *               "created_at": "2017-10-06 09:51:05",
     *               "updated_at": "2017-10-06 09:41:49"
     *           },
     *           {
     *               "id": 3,
     *               "key": "offline_pay_bank_no",
     *               "val": "6226356456887564",
     *               "created_at": "2017-10-06 09:51:09",
     *               "updated_at": "2017-10-06 09:43:01"
     *           }
     *       ]
     *   }
     */
    public function getOfflinePayBank(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '', 'data' => []];
        $setting = Setting::where('key', '=', 'offline_pay_bank_name')->orWhere('key', '=', 'offline_pay_bank_no')->orWhere('key', '=', 'offline_pay_username')->get();
        if ($setting) {
            $result['status'] = 0;
            $result['msg']    = '';
            $result['data']   = $setting;
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/getWechat 获取微信配置信息信息
     * @apiName GetWechat
     * @apiGroup Setting
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": [
     *           {
     *               "id": 6,
     *               "key": "wechat_single_qrcode",
     *               "val": "2",
     *               "created_at": "2017-11-16 19:33:41",
     *               "updated_at": null
     *           }
     *       ]
     *   }
     */
    public function getWechat(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '', 'data' => []];
        $setting = Setting::where('key', '=', 'wechat_single_qrcode')->get();
        if ($setting) {
            $result['status'] = 0;
            $result['msg']    = '';
            $result['data']   = $setting;
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /getAliPay 获取支付宝配置信息信息
     * @apiName GetAliPay
     * @apiGroup Setting
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": [
     *           {
     *               "id": 7,
     *               "key": "alipay_single_qrcode",
     *               "val": "1,2,3",
     *               "created_at": "2017-11-16 19:33:41",
     *               "updated_at": null
     *           }
     *       ]
     *   }
     */
    public function getAliPay(Request $request, Response $response, $args)
    {
        $result  = ['status' => 1, 'msg' => '', 'data' => []];
        $setting = Setting::where('key', '=', 'alipay_single_qrcode')->get();
        if ($setting) {
            $result['status'] = 0;
            $result['msg']    = '';
            $result['data']   = $setting;
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/updateSetting/:id 修改配置
     * @apiName UpdateSetting
     * @apiGroup Setting
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiParam {Number} id 配置项id 1:支付限额 2,3,4:线下银行配置 5:群控二维码配置 6:微信支付设置 7:支付宝支付设置
     *
     * @apiParam {String} val 配置项值1，2，3，4，5:略 6:1个人 2:商家 7:1转到银行卡 2转到支付宝(个人) 3扫码转账(商家)
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function updateSetting(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $id        = $args['id'];
        $val       = $postDatas['val'] ?? '';
        if ($id > 0 && Setting::where('id', '=', $id)->update(['val' => $val])) {
            $result['status'] = 0;
            $result['msg']    = '修改成功';
        }
        return $response->withJson($result);
    }

    /**
     * @api {post} /admin/batchUpdateSetting 批量修改配置
     * @apiName BatchUpdateSetting
     * @apiGroup Setting
     * @apiVersion 1.0.0
     * @apiPermission jwt
     *
     * @apiParam {Json} jsonData json数据，如：{"offline_pay_bank_name":"\u5efa\u8bbe\u94f6\u884c","offline_pay_bank_no":"00000000000","offline_pay_username":"\u674e\u56db"}
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "修改成功",
     *       "data": []
     *   }
     */
    public function batchUpdateSetting(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '修改失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $jsonData  = $postDatas['jsonData'] ?? '';
        if ($jsonData) {
            $tmp = 0;
            $dataArr = json_decode($jsonData, true);
            foreach ($dataArr as $key => $val) {
                if (Setting::where('key', '=', $key)->update(['val' => $val])) {
                    $tmp += 1;
                }
            }
            if ($tmp == count($dataArr)) {
                $result['status'] = 0;
                $result['msg']    = '修改成功';
            }
        }
        return $response->withJson($result);
    }

    /**
     * @api {get} /admin/settings 配置项列表
     * @apiName Settings
     * @apiGroup Setting
     * @apiVersion 1.1.0
     * @apiPermission jwt
     *
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *       "status": 0,
     *       "msg": "",
     *       "data": [
     *           {
     *               "id": 1,
     *               "key": "pay_out_limit", // 出款最大限额，固定值
     *               "val": "5000",
     *               "created_at": "2017-09-22 09:53:26",
     *               "updated_at": "2017-09-22 09:53:28"
     *           },
     *           {
     *               "id": 2,
     *               "key": "offline_pay_bank_name", // 下线转账银行名称，固定值
     *               "val": "招商银行",
     *               "created_at": "2017-10-06 09:51:05",
     *               "updated_at": "2017-10-06 09:41:49"
     *           },
     *           {
     *               "id": 3,
     *               "key": "offline_pay_bank_no", // 下线转账银行账户，固定值
     *               "val": "6226356456887564",
     *               "created_at": "2017-10-06 09:51:09",
     *               "updated_at": "2017-10-06 09:43:01"
     *           },
     *           {
     *               "id": 5,
     *               "key": "personal_merchant_qrcode", // 1 为个人收款二维码、2 为商户收款二维码
     *               "val": "1",
     *               "created_at": "2017-10-06 09:59:43",
     *               "updated_at": "2017-10-06 09:54:30"
     *           },
     *           {
     *               "id": 6,
     *               "key": "wechat_single_qrcode", //微信二维码设置 1个人 2商家(单选)
     *               "val": "2",
     *               "created_at": "2017-11-16 19:33:41",
     *               "updated_at": null
     *           },
     *           {
     *               "id": 7,
     *               "key": "alipay_single_qrcode", //支付宝设置 1转到银行卡 2个人二维码 3商家二维码(多选) 例:1,2,3
     *               "val": "1,2,3",
     *               "created_at": null,
     *               "updated_at": null
     *           }
     *       ]
     *   }
     */
    public function settings(Request $request, Response $response, $args)
    {
        return $response->withJson(['status' => 0, 'msg' => '', 'data' => Setting::all()]);
    }
}
