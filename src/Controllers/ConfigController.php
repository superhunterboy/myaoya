<?php

namespace Weiming\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Weiming\Controllers\BaseController;
use Weiming\Libs\Utils;
use Weiming\Models\User;

class ConfigController extends BaseController
{
    public function getSms(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '获取接收短信验证码手机号失败!', 'data' => []];
        $postDatas = $request->getParsedBody();
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        if (!$this->isSuperAdministrator($username, $password)) {
            $result['msg'] = '用户名或密码错误!';
        } else {
            $sms              = $this->settings['sms']['mobile'];
            $result['status'] = 0;
            $result['msg']    = '';
            $result['data']   = $sms;
        }
        return $response->withJson($result);
    }

    public function updateSms(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败!', 'data' => []];
        $postDatas = $request->getParsedBody();
        $mobile    = $postDatas['mobile'];
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        if (!preg_match("/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/", $mobile)) {
            $result['msg'] = '手机号格式错误!';
        } elseif (!$this->isSuperAdministrator($username, $password)) {
            $result['msg'] = '用户名或密码错误!';
        } else {
            $smsFile = __DIR__ . '/../../config/sms.php';
            if (is_file($smsFile) && is_writable($smsFile)) {
                $smsContent = $this->settings['sms'];
                $phpContent = '<?php' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL . "\t'sms' => [" . PHP_EOL;
                if (count($smsContent) > 0) {
                    foreach ($smsContent as $key => $val) {
                        if ($key == 'mobile') {
                            $mobile = trim($mobile);
                            $val    = "'{$mobile}'";
                        } elseif ($key == 'isOpen') {
                            $val = ($val === false ? 'false' : ($val === true ? 'true' : ''));
                        } else {
                            $val = "'{$val}'";
                        }
                        $phpContent .= "\t\t'{$key}'\t=> {$val}," . PHP_EOL;
                    }
                }
                $phpContent .= "\t]," . PHP_EOL . "];" . PHP_EOL . PHP_EOL;
                if (file_put_contents($smsFile, $phpContent, LOCK_EX) !== false) {
                    $sms              = $this->settings['sms']['mobile'];
                    $result['status'] = 0;
                    $result['msg']    = '更新成功!';
                    $result['data']   = $sms;
                }
            } else {
                $result['msg'] = '手机验证码配置文件无写入权限, 请联系管理员!';
            }
        }
        return $response->withJson($result);
    }

    public function getWhelloteList(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '获取白名单失败!', 'data' => []];
        $postDatas = $request->getParsedBody();
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        $ip        = Utils::getIp();
        if (!$this->isSuperAdministrator($username, $password)) {
            $result['msg'] = '用户名或密码错误!';
        } elseif (!in_array($ip, $this->settings['whelloteList'])) {
            $result['msg'] = '你必须在白名单内!';
        } else {
            $whelloteList     = $this->settings['whelloteList'];
            $result['status'] = 0;
            $result['msg']    = Utils::getIp();
            $result['data']   = $whelloteList;
        }
        return $response->withJson($result);
    }

    public function updateWhelloteList(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '更新失败!', 'data' => []];
        $postDatas = $request->getParsedBody();
        $ips       = $postDatas['ips'];
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        $ip        = Utils::getIp();
        if (!$this->isSuperAdministrator($username, $password)) {
            $result['msg'] = '用户名或密码错误!';
        } elseif (!in_array($ip, $this->settings['whelloteList'])) {
            $result['msg'] = '你必须在白名单内!';
        } else {
            $whelloteListFile = __DIR__ . '/../../config/whelloteList.php';
            if (is_file($whelloteListFile) && is_writable($whelloteListFile)) {
                $phpContent = '<?php' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL . "\t'whelloteList' => [" . PHP_EOL;
                if (count($ips) > 0) {
                    foreach ($ips as $ip) {
                        $ip = trim($ip);
                        $phpContent .= "\t\t'{$ip}'," . PHP_EOL;
                    }
                }
                $phpContent .= "\t]," . PHP_EOL . "];" . PHP_EOL . PHP_EOL;
                if (file_put_contents($whelloteListFile, $phpContent, LOCK_EX) !== false) {
                    $whelloteList     = $this->settings['whelloteList'];
                    $result['status'] = 0;
                    $result['msg']    = '更新成功!';
                    $result['data']   = $whelloteList;
                }
            } else {
                $result['msg'] = '白名单文件无写入权限, 请联系管理员!';
            }
        }
        return $response->withJson($result);
    }

    private function isSuperAdministrator($username, $password)
    {
        $password = md5(sha1($password));
        $user     = User::whereRaw("`status` = 1 AND `type` = 1 AND `username` = '{$username}'")->first();
        if ($user && $user->password === $password) {
            return true;
        }
        return false;
    }
}
