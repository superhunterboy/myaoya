<?php

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';

// Redis php-resque 队列
Resque::setBackend($settings['redis'], $settings['redis'][0]['index']);

$app       = new \Slim\App(["settings" => $settings]);
$container = $app->getContainer();

// 注释：中间件执行顺序遵循"先进后出"栈操作规则htmlentities
// 数据验证中间件
// $app->add(new \Weiming\Middlewares\ValidateMiddleware($container));
// 访问权限验证中间件
// $app->add(new \Weiming\Middlewares\PermissionMiddleware($container));
// 系统操作日志
$app->add(new \Weiming\Middlewares\SystemLogMiddleware($container));
// 用户登录状态检查
$app->add(new \Weiming\Middlewares\UserCheckMiddleware($container));
// 白名单
$app->add(new \Weiming\Middlewares\WhelloteListMiddleware($container));

// 注入jwt
$container["jwt"] = function ($container) {
    return new StdClass;
};
$app->add(new \Slim\Middleware\JwtAuthentication([
    "path"        => "/admin",
    "passthrough" => ["/admin/auth/login"],
    "cookie"      => "token",
    "attribute"   => "jwtoken",
    "secure"      => false,
    // "logger"      => new \Monolog\Logger('WM_JWT_logger', [
    //     new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/jwt-' . date('Ymd') . '.log'),
    // ]),
    "secret"      => "35a7102186059ae8a1557f1e9c90ca47075d7c4e",
    "callback"    => function ($request, $response, $arguments) use ($container) {
        $container["jwt"] = $arguments["decoded"];
    },
]));

// 注入eloquent orm
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};

// 注入日志
$container['logger'] = function ($container) {
    $logger       = new \Monolog\Logger('WM_logger');
    $file_handler = new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/system-' . date('Ymd') . '.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

// Redis 锁机制
$container['redisLock'] = function ($container) {
    return new \Weiming\Libs\RedisLock([
        'host'           => $container['settings']['redis'][0]['host'],
        'port'           => $container['settings']['redis'][0]['port'],
        'index'          => $container['settings']['redis'][0]['index'],
        'auth'           => '',
        'timeout'        => 1,
        'reserved'       => null,
        'retry_interval' => 100,
    ]);
};

// Redis 缓存
$container['redis'] = function ($container) {
    return new \Predis\Client([
        'scheme' => 'tcp',
        'host'   => $container['settings']['redis'][0]['host'],
        'port'   => $container['settings']['redis'][0]['port'],
    ], [
        'prefix' => 'wm_caches:',
    ]);
};

// 注入验证
$container['validator'] = function ($container) {
    $messages = require __DIR__ . '/../lang/messages.php';
    return new Overtrue\Validation\Factory(new Overtrue\Validation\Translator($messages));
};

// 注入控制器
$container['\Weiming\Controllers\BaseController'] = function ($container) {
    return new \Weiming\Controllers\BaseController($container);
};

// 新增用户
$app->post('/admin/users', \Weiming\Controllers\UserController::class . ':addUser');

// 删除用户
$app->delete('/admin/users[/{id:.*}]', \Weiming\Controllers\UserController::class . ':deleteUser');

// 禁用，启用用户
$app->put('/admin/endisableUser/{id:[0-9]+}', \Weiming\Controllers\UserController::class . ':endisableUser');

// 更新用户
$app->put('/admin/users/{id:[0-9]+}', \Weiming\Controllers\UserController::class . ':updateUser');

// 获取当前登录用户信息
$app->get('/admin/getCurrentUser', \Weiming\Controllers\UserController::class . ':getCurrentUser');

// 查询用户
$app->get('/admin/users', \Weiming\Controllers\UserController::class . ':queryUsers');

// 修改密码
$app->put('/admin/modifyPassword', \Weiming\Controllers\UserController::class . ':modifyPassword');

//重置密码
$app->get('/admin/resetPassword/{id:[0-9]+}', \Weiming\Controllers\UserController::class . ':resetPassword');

// 绑定Google OTP
$app->get('/admin/bindOneTimePwd', \Weiming\Controllers\UserController::class . ':bindOneTimePwd');

// 绑定验证OTP是否正确
$app->post('/admin/verifyBindOneTimePwd', \Weiming\Controllers\UserController::class . ':verifyBindOneTimePwd');

// 解绑验证OTP是否正确
$app->post('/admin/verifyUnbindOneTimePwd', \Weiming\Controllers\UserController::class . ':verifyUnbindOneTimePwd');

// 是否开启OTP验证
$app->get('/isEnableOtp', \Weiming\Controllers\UserController::class . ':isEnableOtp');

// 删除业务平台
$app->delete('/admin/companies[/{id:.*}]', \Weiming\Controllers\CompanyController::class . ':deleteCompany');

// 新增业务平台
$app->post('/admin/companies', \Weiming\Controllers\CompanyController::class . ':addCompany');

// 更新业务平台
$app->put('/admin/companies/{id:[0-9]+}', \Weiming\Controllers\CompanyController::class . ':updateCompany');

// 查询业务平台
$app->get('/admin/companies', \Weiming\Controllers\CompanyController::class . ':queryCompanies');

// 当前业务平台下可用的支付平台接口
$app->get('/admin/getVendorsByCompanyId/{id:[0-9]+}/{type:[0-9]+}', \Weiming\Controllers\CompanyController::class . ':getVendorsByCompanyId');

// 业务平台切换支付平台接口
$app->put('/admin/changeVendorByCompanyId/{id:[0-9]+}', \Weiming\Controllers\CompanyController::class . ':changeVendorByCompanyId');

// 通过当前用户所属业务平台id获取业务平台接口
$app->get('/admin/getCompaniesByCurrentUser', \Weiming\Controllers\CompanyController::class . ':getCompaniesByCurrentUser');

// 新增支付平台接口
$app->post('/admin/vendors', \Weiming\Controllers\VendorController::class . ':addVendor');

// 删除支付平台
$app->delete('/admin/vendors[/{id:.*}]', \Weiming\Controllers\VendorController::class . ':deleteVendor');

// 更新支付平台
$app->put('/admin/vendors/{id:[0-9]+}', \Weiming\Controllers\VendorController::class . ':updateVendor');

// 查询支付平台
$app->get('/admin/vendors', \Weiming\Controllers\VendorController::class . ':queryVendors');

// 查询账单列表接口
$app->get('/admin/pays', \Weiming\Controllers\PayController::class . ':queryPays');

// 入款操作接口
$app->put('/admin/pay/{id:[0-9]+}', \Weiming\Controllers\PayController::class . ':doPay');

$app->get('/admin/notOperatOrders', \Weiming\Controllers\PayController::class . ':notOperatOrders');

// 后台用户登录
$app->post('/admin/auth/login', \Weiming\Controllers\AuthController::class . ':login');

// 后台用户退出
$app->get('/admin/auth/logout', \Weiming\Controllers\AuthController::class . ':logout');

// 前台充值页面数据渲染接口
$app->get('/payment/getPayInfo/{id:[0-9]+}', \Weiming\Controllers\PaymentController::class . ':getPayInfo');

// 前台充值页面数据渲染接口，版本 2
$app->get('/payment/getPaymentInfo/{id:[0-9]+}', \Weiming\Controllers\PaymentController::class . ':getPaymentInfo');

// 前端支付接口
$app->post('/payment', \Weiming\Controllers\PaymentController::class . ':payment');

// 二维码加好友支付订单状态修改
$app->post('/updateQrcodeStatus', \Weiming\Controllers\PaymentController::class . ':updateQrcodeStatus');

// 增加公告
$app->post('/admin/addAffiche', \Weiming\Controllers\AfficheController::class . ':addAffiche');

// 更新公告
$app->post('/admin/updateAffiche', \Weiming\Controllers\AfficheController::class . ':updateAffiche');

// 删除公告
$app->get('/admin/deleteAffiche/{id:[0-9]+}', \Weiming\Controllers\AfficheController::class . ':deleteAffiche');

// 后台获取公告信息
$app->get('/admin/affiches', \Weiming\Controllers\AfficheController::class . ':affiches');

// 前台获取公告信息
$app->get('/affiche', \Weiming\Controllers\AfficheController::class . ':affiche');

// 支付后端回调接口
$app->map(['GET', 'POST'], '/payment/callback', \Weiming\Controllers\PaymentController::class . ':callbackUrl');

// 支付前端回调接口
$app->map(['GET', 'POST'], '/payment/notify', \Weiming\Controllers\PaymentController::class . ':notifyUrl');

// 扫码支付
$app->get('/payment/scancode', \Weiming\Controllers\PaymentController::class . ':scancode');

// 扫码支付回调
$app->map(['GET', 'POST'], '/payment/scancode/callback', \Weiming\Controllers\PaymentController::class . ':scancodeCallback');

// 沃雷特支付回调
$app->map(['GET', 'POST'], '/payment/wallet/callback', \Weiming\Controllers\PaymentController::class . ':walletCallback');

// 沃雷特支付通知
$app->map(['GET', 'POST'], '/payment/wallet/notify', \Weiming\Controllers\PaymentController::class . ':walletNotify');

// 金海哲支付回调
$app->map(['GET', 'POST'], '/payment/jinhaizhe/callback', \Weiming\Controllers\PaymentController::class . ':jinhaizheCallback');

// 金海哲支付通知
$app->map(['GET', 'POST'], '/payment/jinhaizhe/notify', \Weiming\Controllers\PaymentController::class . ':jinhaizheNotify');

// 华仁支付回调
$app->map(['GET', 'POST'], '/payment/huaren/callback', \Weiming\Controllers\PaymentController::class . ':huarenCallback');

// 华仁支付通知
$app->map(['GET', 'POST'], '/payment/huaren/notify', \Weiming\Controllers\PaymentController::class . ':huarenNotify');

// 荷包支付回调
$app->map(['GET', 'POST'], '/payment/hebao/callback', \Weiming\Controllers\PaymentController::class . ':hebaoCallback');

// 荷包支付通知
$app->map(['GET', 'POST'], '/payment/hebao/notify', \Weiming\Controllers\PaymentController::class . ':hebaoNotify');

// 雅付支付回调
$app->map(['GET', 'POST'], '/payment/yafu/callback', \Weiming\Controllers\PaymentController::class . ':yafuCallback');

// 雅付支付通知
$app->map(['GET', 'POST'], '/payment/yafu/notify', \Weiming\Controllers\PaymentController::class . ':yafuNotify');

// 立刻付回调
$app->map(['GET', 'POST'], '/payment/likefu/callback', \Weiming\Controllers\PaymentController::class . ':likefuCallback');

// 立刻付通知
$app->map(['GET', 'POST'], '/payment/likefu/notify', \Weiming\Controllers\PaymentController::class . ':likefuNotify');

//多多支付异步回调
$app->map(['GET', 'POST'], '/payment/duoduo/callback', \Weiming\Controllers\DuoduoController::class . ':callback');

//多多支付同步通知
$app->map(['GET', 'POST'], '/payment/duoduo/notify', \Weiming\Controllers\DuoduoController::class . ':notify');

// 金海哲支付回调
$app->map(['GET', 'POST'], '/payment/jinhaizheNew/callback', \Weiming\Controllers\JinhaizheNewController::class . ':callback');

// 金海哲支付通知
$app->map(['GET', 'POST'], '/payment/jinhaizheNew/notify', \Weiming\Controllers\JinhaizheNewController::class . ':notify');

// 仁信支付回调
$app->map(['GET', 'POST'], '/payment/renxin/callback', \Weiming\Controllers\RenxinController::class . ':callback');

// 仁信支付通知
$app->map(['GET', 'POST'], '/payment/renxin/notify', \Weiming\Controllers\RenxinController::class . ':notify');

// 天付宝回调
$app->map(['GET', 'POST'], '/payment/tianfubao/callback', \Weiming\Controllers\TianfubaoController::class . ':callback');

// 天付宝通知
$app->map(['GET', 'POST'], '/payment/tianfubao/notify', \Weiming\Controllers\TianfubaoController::class . ':notify');

// 高通回调
$app->map(['GET', 'POST'], '/payment/gaotong/callback', \Weiming\Controllers\GaotongController::class . ':callback');

// 高通通知
$app->map(['GET', 'POST'], '/payment/gaotong/notify', \Weiming\Controllers\GaotongController::class . ':notify');

// 新雅付支付回调
$app->map(['GET', 'POST'], '/payment/yafuNew/callback', \Weiming\Controllers\YafuNewController::class . ':callback');

// 新雅付支付通知
$app->map(['GET', 'POST'], '/payment/yafuNew/notify', \Weiming\Controllers\YafuNewController::class . ':notify');

// 先行付支付回调
$app->map(['GET', 'POST'], '/payment/xianxingfu/callback', \Weiming\Controllers\XianxingfuController::class . ':callback');

// 先行付支付通知
$app->map(['GET', 'POST'], '/payment/xianxingfu/notify', \Weiming\Controllers\XianxingfuController::class . ':notify');

// 我付支付回调
$app->map(['GET', 'POST'], '/payment/wofu/callback', \Weiming\Controllers\WofuController::class . ':callback');

// 我付支付通知
$app->map(['GET', 'POST'], '/payment/wofu/notify', \Weiming\Controllers\WofuController::class . ':notify');

// 汇达付支付回调
$app->map(['GET', 'POST'], '/payment/huida/callback', \Weiming\Controllers\HuidaController::class . ':callback');

// 汇达付支付通知
$app->map(['GET', 'POST'], '/payment/huida/notify', \Weiming\Controllers\HuidaController::class . ':notify');

// 泽圣付支付回调
$app->map(['GET', 'POST'], '/payment/zesheng/callback', \Weiming\Controllers\ZeshengController::class . ':callback');

// 泽圣付支付通知
$app->map(['GET', 'POST'], '/payment/zesheng/notify', \Weiming\Controllers\ZeshengController::class . ':notify');

// 钱袋支付回掉
$app->map(['GET', 'POST'], '/payment/qiandai/callback', \Weiming\Controllers\QiandaiController::class . ':callback');

// 钱袋支付通知
$app->map(['GET', 'POST'], '/payment/qiandai/notify', \Weiming\Controllers\QiandaiController::class . ':notify');

// 五福支付回掉
$app->map(['GET', 'POST'], '/payment/wufu/callback', \Weiming\Controllers\WufuController::class . ':callback');

// 五福支付通知
$app->map(['GET', 'POST'], '/payment/wufu/notify', \Weiming\Controllers\WufuController::class . ':notify');

// pppay支付回掉
$app->map(['GET', 'POST'], '/payment/pppay/callback', \Weiming\Controllers\PppayController::class . ':callback');

// pppay支付通知
$app->map(['GET', 'POST'], '/payment/pppay/notify', \Weiming\Controllers\PppayController::class . ':notify');

// 顺达通支付回掉
$app->map(['GET', 'POST'], '/payment/shundatong/callback', \Weiming\Controllers\ShundatongController::class . ':callback');

// 顺达通支付通知
$app->map(['GET', 'POST'], '/payment/shundatong/notify', \Weiming\Controllers\ShundatongController::class . ':notify');

// 银信支付回掉
$app->map(['GET', 'POST'], '/payment/yinxin/callback', \Weiming\Controllers\YinxinController::class . ':callback');

// 银信支付通知
$app->map(['GET', 'POST'], '/payment/yinxin/notify', \Weiming\Controllers\YinxinController::class . ':notify');

// 新自由付支付回掉
$app->map(['GET', 'POST'], '/payment/ziyounew/callback', \Weiming\Controllers\ZiyoufuNewController::class . ':callback');

// 新自由付支付通知(商家暂不支持通知)
$app->map(['GET', 'POST'], '/payment/ziyounew/notify', \Weiming\Controllers\ZiyoufuNewController::class . ':notify');

// 金阳支付回掉
$app->map(['GET', 'POST'], '/payment/jinyang/callback', \Weiming\Controllers\JinyangController::class . ':callback');

// 金阳支付通知
$app->map(['GET', 'POST'], '/payment/jinyang/notify', \Weiming\Controllers\JinyangController::class . ':notify');

// 个人支付宝支付回掉
$app->map(['GET', 'POST'], '/payment/singleqr/callback', \Weiming\Controllers\SingleQrPayController::class . ':callback');

// 旺富通支付回掉
$app->map(['GET', 'POST'], '/payment/wangfutong/callback', \Weiming\Controllers\WangfutongController::class . ':callback');

// 旺富通支付通知
$app->map(['GET', 'POST'], '/payment/wangfutong/notify', \Weiming\Controllers\WangfutongController::class . ':notify');

// 千应支付回掉
$app->map(['GET', 'POST'], '/payment/qianying/callback', \Weiming\Controllers\QianyingController::class . ':callback');

// 千应支付通知
$app->map(['GET', 'POST'], '/payment/qianying/notify', \Weiming\Controllers\QianyingController::class . ':notify');

// 优付支付回掉
$app->map(['GET', 'POST'], '/payment/youfu/callback', \Weiming\Controllers\YoufuController::class . ':callback');

// 优付支付通知
$app->map(['GET', 'POST'], '/payment/youfu/notify', \Weiming\Controllers\YoufuController::class . ':notify');

// 商码付回掉
$app->map(['GET', 'POST'], '/payment/shangma/callback', \Weiming\Controllers\ShangmaController::class . ':callback');

// 商码付通知
$app->map(['GET', 'POST'], '/payment/shangma/notify', \Weiming\Controllers\ShangmaController::class . ':notify');

// 恒辰付回掉
$app->map(['GET', 'POST'], '/payment/hengchen/callback', \Weiming\Controllers\HengchenController::class . ':callback');

// 恒辰付通知
$app->map(['GET', 'POST'], '/payment/hengchen/notify', \Weiming\Controllers\HengchenController::class . ':notify');

// 成沃回掉
$app->map(['GET', 'POST'], '/payment/chengwo/callback', \Weiming\Controllers\ChengwoController::class . ':callback');

// 成沃通知
$app->map(['GET', 'POST'], '/payment/chengwo/notify', \Weiming\Controllers\ChengwoController::class . ':notify');

// 开联通回掉
$app->map(['GET', 'POST'], '/payment/kailiantong/callback', \Weiming\Controllers\KailiantongController::class . ':callback');

// 开联通通知
$app->map(['GET', 'POST'], '/payment/kailiantong/notify', \Weiming\Controllers\KailiantongController::class . ':notify');

// 点付云回掉
$app->map(['GET', 'POST'], '/payment/dianfuyun/callback', \Weiming\Controllers\DianfuyunController::class . ':callback');

// 点付云通知
$app->map(['GET', 'POST'], '/payment/dianfuyun/notify', \Weiming\Controllers\DianfuyunController::class . ':notify');

// 芯富回掉
$app->map(['GET', 'POST'], '/payment/xinfu/callback', \Weiming\Controllers\XinfuController::class . ':callback');

// 芯富通知
$app->map(['GET', 'POST'], '/payment/xinfu/notify', \Weiming\Controllers\XinfuController::class . ':notify');

// 滕坤回掉
$app->map(['GET', 'POST'], '/payment/tengkun/callback', \Weiming\Controllers\TengkunController::class . ':callback');

// 天吉回掉
$app->map(['GET', 'POST'], '/payment/tianji/callback', \Weiming\Controllers\TianjiController::class . ':callback');

// 众点回调
$app->map(['GET', 'POST'], '/payment/zhongdian/callback', \Weiming\Controllers\ZhongdianController::class . ':callback');

// 云支付回调
$app->map(['GET', 'POST'], '/payment/yunzhifu/callback', \Weiming\Controllers\YunzhifuController::class . ':callback');

// 云支付通知
$app->map(['GET', 'POST'], '/payment/yunzhifu/notify', \Weiming\Controllers\YunzhifuController::class . ':notify');

// 云支付2.0回调
$app->map(['GET', 'POST'], '/payment/yunzhifu2/callback', \Weiming\Controllers\Yunzhifu2Controller::class . ':callback');

// 云支付2.0通知
$app->map(['GET', 'POST'], '/payment/yunzhifu2/notify', \Weiming\Controllers\Yunzhifu2Controller::class . ':notify');

// 喜付回调
$app->map(['GET', 'POST'], '/payment/xifu/callback', \Weiming\Controllers\XifuController::class . ':callback');

// 喜付通知
$app->map(['GET', 'POST'], '/payment/xifu/notify', \Weiming\Controllers\XifuController::class . ':notify');

// 艾付回调
$app->map(['GET', 'POST'], '/payment/aifu/callback', \Weiming\Controllers\AifuController::class . ':callback');

// 艾付通知
$app->map(['GET', 'POST'], '/payment/aifu/notify', \Weiming\Controllers\AifuController::class . ':notify');

// Nong付回调
$app->map(['GET', 'POST'], '/payment/nongfu/callback', \Weiming\Controllers\NongfuController::class . ':callback');

// Nong付通知
$app->map(['GET', 'POST'], '/payment/nongfu/notify', \Weiming\Controllers\NongfuController::class . ':notify');

// 顺心付回调
$app->map(['GET', 'POST'], '/payment/shunxin/callback', \Weiming\Controllers\ShunxinController::class . ':callback');

// 米达付回调
$app->map(['GET', 'POST'], '/payment/mida/callback', \Weiming\Controllers\MidaController::class . ':callback');

// 米达付通知
$app->map(['GET', 'POST'], '/payment/mida/notify', \Weiming\Controllers\MidaController::class . ':notify');

// wpay回调
$app->map(['GET', 'POST'], '/payment/wpay/callback', \Weiming\Controllers\WpayController::class . ':callback');

// wpay通知
$app->map(['GET', 'POST'], '/payment/wpay/notify', \Weiming\Controllers\WpayController::class . ':notify');

// 恒星闪付回调
$app->map(['GET', 'POST'], '/payment/hengxing/callback', \Weiming\Controllers\HengxingController::class . ':callback');

// 众信支付回调
$app->map(['GET', 'POST'], '/payment/zhongxin/callback', \Weiming\Controllers\ZhongxinController::class . ':callback');

// 众信支付通知
$app->map(['GET', 'POST'], '/payment/zhongxin/notify', \Weiming\Controllers\ZhongxinController::class . ':notify');

// 星捷支付回调
$app->map(['GET', 'POST'], '/payment/xingjie/callback', \Weiming\Controllers\XingjieController::class . ':callback');

// 星捷支付通知
$app->map(['GET', 'POST'], '/payment/xingjie/notify', \Weiming\Controllers\XingjieController::class . ':notify');

// 云通支付回调
$app->map(['GET', 'POST'], '/payment/yuntong/callback/{orderId:[0-9]+}', \Weiming\Controllers\YuntongController::class . ':callback'); // /201805171757547865754

// 云通支付通知
$app->map(['GET', 'POST'], '/payment/yuntong/notify', \Weiming\Controllers\YuntongController::class . ':notify');

// 迅捷付回调
$app->map(['GET', 'POST'], '/payment/xunjie/callback', \Weiming\Controllers\XunjieController::class . ':callback');

// 迅捷付通知
$app->map(['GET', 'POST'], '/payment/xunjie/notify', \Weiming\Controllers\XunjieController::class . ':notify');

// 盖亚付回调
$app->map(['GET', 'POST'], '/payment/gaiya/callback', \Weiming\Controllers\GaiyaController::class . ':callback');

// 盖亚付通知
$app->map(['GET', 'POST'], '/payment/gaiya/notify', \Weiming\Controllers\GaiyaController::class . ':notify');

// A付回调
$app->map(['GET', 'POST'], '/payment/af/callback', \Weiming\Controllers\AfController::class . ':callback');

// A付通知
$app->map(['GET', 'POST'], '/payment/af/notify', \Weiming\Controllers\AfController::class . ':notify');

// AZ回调
$app->map(['GET', 'POST'], '/payment/az/callback', \Weiming\Controllers\AzController::class . ':callback');

// AZ通知
$app->map(['GET', 'POST'], '/payment/az/notify', \Weiming\Controllers\AzController::class . ':notify');

// 闪亿回调
$app->map(['GET', 'POST'], '/payment/shanyi/callback', \Weiming\Controllers\ShanyiController::class . ':callback');

// 闪亿通知
$app->map(['GET', 'POST'], '/payment/shanyi/notify', \Weiming\Controllers\ShanyiController::class . ':notify');

// 随意付回调
$app->map(['GET', 'POST'], '/payment/suiyifu/callback', \Weiming\Controllers\SuiyifuController::class . ':callback');

// 随意付通知
$app->map(['GET', 'POST'], '/payment/suiyifu/notify', \Weiming\Controllers\SuiyifuController::class . ':notify');

// 畅支付回调
$app->map(['GET', 'POST'], '/payment/changzhifu/callback', \Weiming\Controllers\ChangzhifuController::class . ':callback');

// 畅支付通知
$app->map(['GET', 'POST'], '/payment/changzhifu/notify', \Weiming\Controllers\ChangzhifuController::class . ':notify');

// 恒通回调
$app->map(['GET', 'POST'], '/payment/hengtong/callback', \Weiming\Controllers\HengtongController::class . ':callback');

// 恒通通知
$app->map(['GET', 'POST'], '/payment/hengtong/notify', \Weiming\Controllers\HengtongController::class . ':notify');

// Bingo回调
$app->map(['GET', 'POST'], '/payment/bingo/callback', \Weiming\Controllers\BingoController::class . ':callback');

// Bingo通知
$app->map(['GET', 'POST'], '/payment/bingo/notify', \Weiming\Controllers\BingoController::class . ':notify');

// 乐享回调
$app->map(['GET', 'POST'], '/payment/lexiang/callback', \Weiming\Controllers\LexiangController::class . ':callback');

// 乐享通知
$app->map(['GET', 'POST'], '/payment/lexiang/notify', \Weiming\Controllers\LexiangController::class . ':notify');

// ylsl回调
$app->map(['GET', 'POST'], '/payment/ylsl/callback', \Weiming\Controllers\YlslController::class . ':callback');

// ylsl通知
$app->map(['GET', 'POST'], '/payment/ylsl/notify', \Weiming\Controllers\YlslController::class . ':notify');

// $app->get('/admin/testLogger', \Weiming\Controllers\TestController::class . ':testLogger');
// $app->get('/testPayOut', \Weiming\Controllers\PayOutController::class . ':testPayOut');

$app->get('/admin/getFailOrders', \Weiming\Controllers\TestController::class . ':getFailOrders');

$app->post('/admin/addPicture', \Weiming\Controllers\PictureController::class . ':addPicture');

$app->delete('/admin/deletePicture/{id:[0-9]+}', \Weiming\Controllers\PictureController::class . ':deletePicture');

$app->put('/admin/updatePicture/{id:[0-9]+}', \Weiming\Controllers\PictureController::class . ':updatePicture');

$app->get('/admin/getPictures', \Weiming\Controllers\PictureController::class . ':getPictures');

$app->get('/getEnabledPicture/{companyId:[0-9]+}', \Weiming\Controllers\PictureController::class . ':getEnabledPicture');

$app->post('/addMembers', \Weiming\Controllers\MemberController::class . ':addMembers');

$app->post('/updateMembersLevel', \Weiming\Controllers\MemberController::class . ':updateMembersLevel');

$app->get('/memberIsExists/{account:[a-zA-Z0-9_@]+}', \Weiming\Controllers\MemberController::class . ':memberIsExists');

$app->get('/admin/pullHistoryMembers', \Weiming\Controllers\MemberController::class . ':pullHistoryMembers');

$app->get('/getNotLevelMembers', \Weiming\Controllers\MemberController::class . ':getNotLevelMembers');

$app->post('/getMemberRecharge', \Weiming\Controllers\MemberController::class . ':getMemberRecharge');

$app->map(['GET', 'POST'], '/addPayQrcode/{id:\w+}', \Weiming\Controllers\PayQrcodeController::class . ':addPayQrcode');

$app->get('/admin/getPayQrcodes', \Weiming\Controllers\PayQrcodeController::class . ':getPayQrcodes');

$app->post('/admin/updatePayQrcodeStatus/{id:[0-9]+}', \Weiming\Controllers\PayQrcodeController::class . ':updatePayQrcodeStatus');

$app->post('/admin/updatePayQrcodeRemark/{id:[0-9]+}', \Weiming\Controllers\PayQrcodeController::class . ':updatePayQrcodeRemark');

$app->get('/getWechatQrcode', \Weiming\Controllers\QrcodeController::class . ':getWechatQrcode');

$app->get('/admin/qrcodes', \Weiming\Controllers\QrcodeController::class . ':qrcodes');

$app->post('/admin/addQrcode', \Weiming\Controllers\QrcodeController::class . ':addQrcode');

$app->post('/admin/updateQrcode/{id:[0-9]+}', \Weiming\Controllers\QrcodeController::class . ':updateQrcode');

$app->get('/admin/deleteQrcode/{id:[0-9]+}', \Weiming\Controllers\QrcodeController::class . ':deleteQrcode');

$app->post('/admin/disableQrcode/{id:[0-9]+}', \Weiming\Controllers\QrcodeController::class . ':disableQrcode');

// 每天收款金额，0点后要清0
$app->get('/resetQrcodeLimit', \Weiming\Controllers\QrcodeController::class . ':resetQrcodeLimit');

$app->post('/admin/batchUploadQrcode', \Weiming\Controllers\QrcodeController::class . ':batchUploadQrcode');

$app->get('/admin/getPermissions', \Weiming\Controllers\PermissionController::class . ':getPermissions');

$app->get('/admin/updatePermissions', \Weiming\Controllers\PermissionController::class . ':updatePermissions');

$app->post('/addReportDatas', \Weiming\Controllers\ReportController::class . ':addReportDatas');

$app->get('/admin/getPayCompanyReport', \Weiming\Controllers\ReportController::class . ':getPayCompanyReport');

$app->get('/admin/getPayOnlineReport', \Weiming\Controllers\ReportController::class . ':getPayOnlineReport');

$app->get('/admin/getArtificialDepositReport', \Weiming\Controllers\ReportController::class . ':getArtificialDepositReport');

$app->get('/admin/getReportItems', \Weiming\Controllers\ReportController::class . ':getReportItems');

$app->get('/admin/pullReportDatas', \Weiming\Controllers\ReportController::class . ':pullReportDatas');

$app->post('/addOfflinePay', \Weiming\Controllers\OfflinePayController::class . ':add');

$app->get('/admin/offlinePays', \Weiming\Controllers\OfflinePayController::class . ':query');

$app->post('/admin/updateOfflinePayStatus/{id:[0-9]+}', \Weiming\Controllers\OfflinePayController::class . ':updateStatus');

//获取QQ二维码
$app->get('/getSingleQQ', \Weiming\Controllers\SingleQrcodeController::class . ':getSingleQQ');
//获取微信二维码
$app->get('/getSingleWechat', \Weiming\Controllers\SingleQrcodeController::class . ':getSingleWechat');
//获取支付宝收款二维码
$app->get('/getSingleAlipay/{type:[0-9]+}', \Weiming\Controllers\SingleQrcodeController::class . ':getSingleAlipay');
//添加个人二维码
$app->post('/admin/addSingleQrcode', \Weiming\Controllers\SingleQrcodeController::class . ':addSingleQrcode');
//更新个人二维码
$app->post('/admin/updateSingleQrcode/{id:[0-9]+}', \Weiming\Controllers\SingleQrcodeController::class . ':updateSingleQrcode');
//个人二维码列表
$app->get('/admin/singleQrcodes/{type:[1-2-3]}', \Weiming\Controllers\SingleQrcodeController::class . ':singleQrcodes');
//删除个人二维码
$app->get('/admin/deleteSingleQrcode/{id:[0-9,]+}', \Weiming\Controllers\SingleQrcodeController::class . ':deleteSingleQrcode');
//添加商户
$app->get('/addMerchant', \Weiming\Controllers\MerchantController::class . ':addMerchant');
//更新商户
$app->post('/admin/updateMerchant/{id:[0-9]+}', \Weiming\Controllers\MerchantController::class . ':updateMerchant');
//商户列表
$app->get('/admin/merchants', \Weiming\Controllers\MerchantController::class . ':merchants');
//商户支付回掉
$app->map(['GET', 'POST'], '/merchant/callback', \Weiming\Controllers\PayMerchantController::class . ':checkOrder');
//添加商户支付记录
$app->post('/addOrder', \Weiming\Controllers\PayMerchantController::class . ':addOrder');
//更新支付状态
$app->post('/admin/updateOrder/{id:[0-9]+}', \Weiming\Controllers\PayMerchantController::class . ':updateOrder');
//商户支付列表
$app->get('/admin/PayMerchants', \Weiming\Controllers\PayMerchantController::class . ':PayMerchants');
//获取收款银行卡信息
$app->get('/getBankCard', \Weiming\Controllers\BankCardController::class . ':getBankCard');
//添加银行卡
$app->post('/admin/addBankCard', \Weiming\Controllers\BankCardController::class . ':addBankCard');
//批量添加银行卡
$app->post('/admin/addBankCards', \Weiming\Controllers\BankCardController::class . ':addBankCards');
//更新银行卡
$app->post('/admin/updateBankCard/{id:[0-9]+}', \Weiming\Controllers\BankCardController::class . ':updateBankCard');
//批量更新银行卡
$app->post('/admin/updateBankCards', \Weiming\Controllers\BankCardController::class . ':updateBankCards');
//银行卡列表
$app->get('/admin/bankCards', \Weiming\Controllers\BankCardController::class . ':bankCards');
//删除银行卡
$app->get('/admin/deleteBankCard/{id:[0-9]+}', \Weiming\Controllers\BankCardController::class . ':deleteBankCard');
//获取会员层级表
$app->get('/admin/levels', \Weiming\Controllers\LevelController::class . ':levels');
//添加个人二维码支付记录
$app->post('/addSinglePay', \Weiming\Controllers\PaySingleController::class . ':addSinglePay');
//更新个人二维码支付状态
$app->post('/admin/updatePaySingleState/{id:[0-9]+}', \Weiming\Controllers\PaySingleController::class . ':updatePaySingleState');
//个人二维码支付列表
$app->get('/admin/paySingles', \Weiming\Controllers\PaySingleController::class . ':paySingles');
//所有人工存款列表
$app->get('/admin/manuals', \Weiming\Controllers\AllManualController::class . ':manuals');

//自动调整支付平台开关
$app->get('/admin/switchJudgment', \Weiming\Controllers\AutoJudgmentController::class . ':switchJudgment');
//选择可用出款平台，自调用
$app->get('/autoJudgment', \Weiming\Controllers\AutoJudgmentController::class . ':start');

// 以下为出款接口
$app->post('/admin/addPlatform', \Weiming\Controllers\PlatformController::class . ':add');

$app->get('/admin/deletePlatform/{id:[0-9]+}', \Weiming\Controllers\PlatformController::class . ':delete');

$app->post('/admin/editPlatform/{id:[0-9]+}', \Weiming\Controllers\PlatformController::class . ':edit');

$app->get('/admin/platforms', \Weiming\Controllers\PlatformController::class . ':query');

$app->post('/admin/changePlatform/{id:[0-9]+}', \Weiming\Controllers\PlatformController::class . ':changePlatform');

$app->post('/admin/updateAmountLimit/{id:[0-9]+}', \Weiming\Controllers\PlatformController::class . ':updateAmountLimit');

// 后台出款平台充值表单
$app->get('/admin/platformRecharge', \Weiming\Controllers\PlatformController::class . ':platformRecharge');

// 前台出款平台充值表单
$app->get('/platformRecharge/{token:[a-z0-9]{32}}', \Weiming\Controllers\PlatformController::class . ':platformRecharge');

$app->get('/admin/queryBalance/{id:[0-9]+}', \Weiming\Controllers\PlatformController::class . ':queryBalance');

// 后台出款平台充值处理
$app->post('/admin/recharge', \Weiming\Controllers\RechargeController::class . ':recharge');

// 前台出款平台充值处理
$app->post('/recharge', \Weiming\Controllers\RechargeController::class . ':recharge');

$app->post('/admin/withdrawals', \Weiming\Controllers\WithdrawalsController::class . ':withdrawals');

$app->get('/admin/manualWithdrawals', \Weiming\Controllers\WithdrawalsController::class . ':manualWithdrawals');

$app->get('/admin/getRecharges', \Weiming\Controllers\RechargeController::class . ':getRecharges');

// BBIN出款
$app->post('/addPayOut', \Weiming\Controllers\PayOutController::class . ':add');

// GPK出款
$app->post('/addGPKPayOut', \Weiming\Controllers\PayOutController::class . ':addGPK');

$app->get('/admin/queryPayOut', \Weiming\Controllers\PayOutController::class . ':query');

$app->get('/admin/queryPayOutStatus', \Weiming\Controllers\PayOutController::class . ':queryPayOutStatus');

$app->post('/admin/updateRemark/{id:[0-9]+}', \Weiming\Controllers\PayOutController::class . ':updateRemark');

$app->post('/admin/updateStatus/{id:[0-9]+}', \Weiming\Controllers\PayOutController::class . ':updateStatus');

$app->get('/admin/settings', \Weiming\Controllers\SettingController::class . ':settings');

$app->get('/getOfflinePayBank', \Weiming\Controllers\SettingController::class . ':getOfflinePayBank');

$app->get('/getPayBankByMemberLevel/{account:[a-zA-Z0-9_@]+}', \Weiming\Controllers\BankCardController::class . ':getPayBankByMemberLevel');

$app->post('/admin/updateSetting/{id:[0-9]+}', \Weiming\Controllers\SettingController::class . ':updateSetting');

$app->post('/admin/batchUpdateSetting', \Weiming\Controllers\SettingController::class . ':batchUpdateSetting');
//获取微信配置信息信息
$app->get('/admin/getWechat', \Weiming\Controllers\SettingController::class . ':getWechat');
//获取支付宝配置信息信息
$app->get('/getAliPay', \Weiming\Controllers\SettingController::class . ':getAliPay');

// 泽圣付充值回调
$app->map(['GET', 'POST'], '/recharge/zesheng/callback', \Weiming\Controllers\Recharges\ZeshengController::class . ':callback');

// 泽圣付充值通知
$app->map(['GET', 'POST'], '/recharge/zesheng/notify', \Weiming\Controllers\Recharges\ZeshengController::class . ':notify');

// 新金海哲充值回调
$app->map(['GET', 'POST'], '/recharge/jinhaizhe/callback', \Weiming\Controllers\Recharges\JinhaizheNewController::class . ':callback');

// 新金海哲充值通知
$app->map(['GET', 'POST'], '/recharge/jinhaizhe/notify', \Weiming\Controllers\Recharges\JinhaizheNewController::class . ':notify');

// 天付宝充值回调
$app->map(['GET', 'POST'], '/recharge/tianfubao/callback', \Weiming\Controllers\Recharges\TianfubaoController::class . ':callback');

// 天付宝充值通知
$app->map(['GET', 'POST'], '/recharge/tianfubao/notify', \Weiming\Controllers\Recharges\TianfubaoController::class . ':notify');

// 新雅付充值回调
$app->map(['GET', 'POST'], '/recharge/yafu/callback', \Weiming\Controllers\Recharges\YafuNewController::class . ':callback');

// 新雅付充值通知
$app->map(['GET', 'POST'], '/recharge/yafu/notify', \Weiming\Controllers\Recharges\YafuNewController::class . ':notify');

// 商码付回掉
$app->map(['GET', 'POST'], '/recharge/shangma/callback', \Weiming\Controllers\Recharges\ShangmaController::class . ':callback');

// 商码付通知
$app->map(['GET', 'POST'], '/recharge/shangma/notify', \Weiming\Controllers\Recharges\ShangmaController::class . ':notify');

// 喜付回调
$app->map(['GET', 'POST'], '/recharge/xifu/callback', \Weiming\Controllers\Recharges\XifuController::class . ':callback');

// 喜付通知
$app->map(['GET', 'POST'], '/recharge/xifu/notify', \Weiming\Controllers\Recharges\XifuController::class . ':notify');

// 艾付回调
$app->map(['GET', 'POST'], '/recharge/aifu/callback', \Weiming\Controllers\Recharges\AifuController::class . ':callback');

// 艾付通知
$app->map(['GET', 'POST'], '/recharge/aifu/notify', \Weiming\Controllers\Recharges\AifuController::class . ':notify');

// Nong付回调
$app->map(['GET', 'POST'], '/recharge/nongfu/callback', \Weiming\Controllers\Recharges\NongfuController::class . ':callback');

// Nong付通知
$app->map(['GET', 'POST'], '/recharge/nongfu/notify', \Weiming\Controllers\Recharges\NongfuController::class . ':notify');

// 顺心付
$app->map(['GET', 'POST'], '/recharge/shunxin/callback', \Weiming\Controllers\Recharges\ShunxinController::class . ':callback');

// 天吉
$app->map(['GET', 'POST'], '/recharge/tianji/callback', \Weiming\Controllers\Recharges\TianjiController::class . ':callback');

// 迅捷付回调
$app->map(['GET', 'POST'], '/recharge/xunjie/callback', \Weiming\Controllers\Recharges\XunjieController::class . ':callback');

// 迅捷付通知
$app->map(['GET', 'POST'], '/recharge/xunjie/notify', \Weiming\Controllers\Recharges\XunjieController::class . ':notify');

// 盖亚付回调
$app->map(['GET', 'POST'], '/recharge/gaiya/callback', \Weiming\Controllers\Recharges\GaiyaController::class . ':callback');

// 盖亚付通知
$app->map(['GET', 'POST'], '/recharge/gaiya/notify', \Weiming\Controllers\Recharges\GaiyaController::class . ':notify');

// Bingo回调
$app->map(['GET', 'POST'], '/recharge/bingo/callback', \Weiming\Controllers\Recharges\BingoController::class . ':callback');

// Bingo通知
$app->map(['GET', 'POST'], '/recharge/bingo/notify', \Weiming\Controllers\Recharges\BingoController::class . ':notify');

$app->post('/addLevels', \Weiming\Controllers\LevelController::class . ':addLevels');

$app->get('/admin/getPayOutLimit', \Weiming\Controllers\PayOutLimitController::class . ':getPayOutLimit');

$app->get('/admin/getAvailableMemberLevels', \Weiming\Controllers\PayOutLimitController::class . ':getAvailableMemberLevels');

$app->post('/admin/addPayOutLimit', \Weiming\Controllers\PayOutLimitController::class . ':addPayOutLimit');

$app->post('/admin/editPayOutLimit/{id:[0-9]+}', \Weiming\Controllers\PayOutLimitController::class . ':editPayOutLimit');

$app->get('/admin/deletePayOutLimit/{id:[0-9]+}', \Weiming\Controllers\PayOutLimitController::class . ':deletePayOutLimit');

// 全自动出款，Linux crontab定时扫描处理中的单子，加入队列中处理
$app->get('/checkPayOutStatus', \Weiming\Controllers\PayOutController::class . ':checkPayOutStatus');

$app->post('/getWhelloteList', \Weiming\Controllers\ConfigController::class . ':getWhelloteList');

$app->post('/updateWhelloteList', \Weiming\Controllers\ConfigController::class . ':updateWhelloteList');

$app->post('/getSms', \Weiming\Controllers\ConfigController::class . ':getSms');

$app->post('/updateSms', \Weiming\Controllers\ConfigController::class . ':updateSms');

$app->get('/admin/rechargeLinks', \Weiming\Controllers\RechargeLinkController::class . ':rechargeLinks');

$app->get('/admin/deleteLink/{id:[0-9]+}', \Weiming\Controllers\RechargeLinkController::class . ':deleteLink');

$app->get('/admin/updateLinkStatus/{id:[0-9]+}', \Weiming\Controllers\RechargeLinkController::class . ':updateLinkStatus');

$app->post('/admin/addLink', \Weiming\Controllers\RechargeLinkController::class . ':addLink');

$app->post('/admin/editLink/{id:[0-9]+}', \Weiming\Controllers\RechargeLinkController::class . ':editLink');

$app->get('/admin/sendCode', \Weiming\Controllers\SmsController::class . ':sendCode');

$app->get('/admin/verifyCode', \Weiming\Controllers\SmsController::class . ':verifyCode');

$app->get('/admin/isSmsVerify', \Weiming\Controllers\SmsController::class . ':isSmsVerify');

$app->run();
