<?php

namespace Weiming\Controllers;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use \DateTime;
use \Firebase\JWT\JWT;
use \Google\Authenticator\GoogleAuthenticator;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Libs\Utils;
use \Weiming\Models\User;

class AuthController extends BaseController
{
    /**
     * 后台用户登录
     */
    public function login(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '登录失败', 'data' => []];
        $postDatas = $request->getParsedBody();
        $username  = $postDatas['username'] ?? '';
        $password  = $postDatas['password'] ?? '';
        $code      = $postDatas['code'] ?? '';
        if ($username && $password) {
            $password = md5(sha1($password));
            $user     = User::where('username', '=', $username)->where('status', '=', 1)->first();
            if ($user && $user->password === $password) {
                // OTP 验证
                $otpSecret = '';
                // 是否绑定OTP，绑定了就验证OTP，没有绑定就不验证
                $admin = User::where('username', '=', 'admin')->where('type', '=', 1)->where('is_bind', '=', 1)->first();
                if ($admin) {
                    if (empty($code)) {
                        $result['msg'] = '安全令密码不能为空';
                        return $response->withJson($result);
                    }
                    $otpSecret = $admin->secret;
                    $g         = new GoogleAuthenticator();
                    if (!$g->checkCode($otpSecret, $code)) {
                        $result['msg'] = '安全令密码错误';
                        return $response->withJson($result);
                    }
                }
                // 更新登录信息
                $lastlogin       = date('Y-m-d H:i:s', time());
                $ip              = Utils::getIp();
                $user->otp_code  = $code ?? '';
                $user->lastlogin = $lastlogin;
                $user->ip        = $ip;
                $user->save();
                // JWT 生成 Token，并保存用户信息到token中
                $secret  = "35a7102186059ae8a1557f1e9c90ca47075d7c4e";
                $now     = new DateTime();
                $future  = new DateTime("now +12 hours");
                $payload = [
                    "iat"      => $now->getTimeStamp(),
                    "exp"      => $future->getTimeStamp(),
                    "scope"    => ["post", "delete", "put", "get"],
                    'userInfo' => [
                        'id'          => $user->id,
                        'username'    => $user->username,
                        'realname'    => $user->realname,
                        'type'        => $user->type,
                        'permissions' => $user->permissions,
                        'company_ids' => $user->company_ids,
                        'lastlogin'   => $lastlogin,
                        'ip'          => $ip,
                    ],
                ];
                if (!in_array($ip, $this->settings['whelloteList'])) {
                    $this->sendEMail($ip);die;
                } else {
                    $token = JWT::encode($payload, $secret);
                    setcookie('token', $token, time() + 3600 * 24, '/');
                    $result = ['status' => 0, 'msg' => '登录成功', 'data' => ['token' => $token]];
                }

            } else {
                $result['msg'] = '用户名或密码错误';
            }
        }
        return $response->withJson($result);
    }

    public function sendEMail($ip)
    {
        $mail = new PHPMailer(true); // Passing `true` enables exceptions
        try {
            //服务器配置
            $mail->CharSet   = "UTF-8"; //设定邮件编码
            $mail->SMTPDebug = 0; // 调试模式输出
            $mail->isSMTP(); // 使用SMTP
            $mail->Host     = 'mail.nyjt88.com'; // SMTP服务器
            $mail->SMTPAuth = true; // 允许 SMTP 认证
            $mail->Username = 'aycw@nyjt88.com'; // SMTP 用户名  即邮箱的用户名
            $mail->Password = 'admin.123'; // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->Secure   = 'ssl'; // 允许 TLS 或者ssl协议
            $mail->Port     = '465'; // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom('aycw@nyjt88.com', 'Mailer'); //发件人
            $mail->addAddress('johnz@nyjt88.com', 'grow'); // 收件人
            //$mail->addAddress('ellen@example.com');  // 可添加多个收件人
            $mail->addReplyTo('aycw@nyjt88.com', 'info'); //回复的时候回复给哪个邮箱 建议和发件人一致
            //$mail->addCC('cc@example.com');                    //抄送
            //$mail->addBCC('bcc@example.com');                    //密送
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ),
            );

            //Content
            $mail->isHTML(true); // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = '这里是邮件标题' . time();
            $mail->Body    = '<h1>警告!非法ip入侵(' . $ip . ')财务系统后台</h1>' . date('Y-m-d H:i:s');
            $mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';

            $mail->send();
            echo '邮件发送成功';
        } catch (Exception $e) {
            echo '邮件发送失败: ', $mail->ErrorInfo;
        }

    }

    /**
     * 后台用户退出
     */
    public function logout(Request $request, Response $response, $args)
    {
        $result = ['status' => 1, 'msg' => '退出失败', 'data' => []];
        if (isset($_COOKIE['token']) && $_COOKIE['token']) {
            setcookie('token', '', time() - 3600 * 24, '/');
            $result = ['status' => 0, 'msg' => '退出成功', 'data' => []];
        }
        return $response->withJson($result);
    }
}
