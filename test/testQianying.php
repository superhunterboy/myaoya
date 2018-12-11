<?php
$getwayUrl = 'http://localhost/selfTest/qianye_pay_demo/my_send.php';
$params =[
    'mer_id'=> '1796',
    'totalAmount'=> '102',
    'money'=> mt_rand(10,99),
    'order_no'=> date('YmdHis') . mt_rand(1000,9999),
    'callback'=> 'http://47.90.98.83/payment/wangfutong/callback',
    'pay_add'=> 'http://47.90.98.83:8088',
    'notify'=> 'http://47.90.98.83/payment/wangfutong/callback',
    'pri_key'=> '397b488e215546f9b8cfe04209904cdf',
    'pay_gateway'=> 'http://www.qianyingnet.com/pay/',
];

$tmp = '<html>';
$tmp .= '<head>';
$tmp .= '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
$tmp .= '<title>Pay Page</title>';
$tmp .= '</head>';
$tmp .= '<body style="display:none;">';
$tmp .= '<form action="' . $getwayUrl . '" method="post" name="orderForm">';
foreach ($params as $key => $value) {
    $tmp .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
}
$tmp .= '</form>';
$tmp .= '<script type="text/javascript">';
$tmp .= 'document.orderForm.submit();';
$tmp .= '</script>';
$tmp .= '</body>';
$tmp .= '</html>';

echo $tmp;