<?php

namespace Weiming\Libs\Payments;

interface WMPay
{
    //获得实例
    public static function getInstance($conf = []);

    //支付类型
    public function getPayType();

    //签名
    public function signature($type, $money, $orderId);

    //付款
    public function payment();

    //验证签名
    public function verifySign($params = []);

    //查询
    public function query($orderId);
}
