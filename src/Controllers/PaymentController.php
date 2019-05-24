<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Resque;
use \Weiming\Controllers\BaseController;
use \Weiming\Jobs\AutoRechargeJob;
use \Weiming\Libs\Payments\Af;
use \Weiming\Libs\Payments\Aibide;
use \Weiming\Libs\Payments\Aifu;
use \Weiming\Libs\Payments\Az;
use \Weiming\Libs\Payments\Baisheng;
use \Weiming\Libs\Payments\Bh;
use \Weiming\Libs\Payments\Bingo;
use \Weiming\Libs\Payments\Changzhifu;
use \Weiming\Libs\Payments\Chengwo;
use \Weiming\Libs\Payments\Cy;
use \Weiming\Libs\Payments\Daniu;
use \Weiming\Libs\Payments\Dianfuyun;
use \Weiming\Libs\Payments\Duoduo;
use \Weiming\Libs\Payments\FhPay;
use \Weiming\Libs\Payments\Gaiya;
use \Weiming\Libs\Payments\Gaotong;
use \Weiming\Libs\Payments\Gefu;
use \Weiming\Libs\Payments\Gft;
use \Weiming\Libs\Payments\Haitu;
use \Weiming\Libs\Payments\Hebao;
use \Weiming\Libs\Payments\Hengchen;
use \Weiming\Libs\Payments\Hengtong;
use \Weiming\Libs\Payments\Hengxing;
use \Weiming\Libs\Payments\Huaren;
use \Weiming\Libs\Payments\Huida;
use \Weiming\Libs\Payments\Jinhaizhe;
use \Weiming\Libs\Payments\JinhaizheNew;
use \Weiming\Libs\Payments\Jinyang;
use \Weiming\Libs\Payments\JrsjPay;
use \Weiming\Libs\Payments\Kailiantong;
use \Weiming\Libs\Payments\Lexiang;
use \Weiming\Libs\Payments\Leying;
use \Weiming\Libs\Payments\Likefu;
use \Weiming\Libs\Payments\Ls;
use \Weiming\Libs\Payments\Ly;
use \Weiming\Libs\Payments\Mida;
use \Weiming\Libs\Payments\NHH;
use \Weiming\Libs\Payments\Nongfu;
use \Weiming\Libs\Payments\Pppay;
use \Weiming\Libs\Payments\Qiandai;
use \Weiming\Libs\Payments\Qianying;
use \Weiming\Libs\Payments\Renxin;
use \Weiming\Libs\Payments\Shanfu;
use \Weiming\Libs\Payments\Shangma;
use \Weiming\Libs\Payments\Shanrong;
use \Weiming\Libs\Payments\Shanyi;
use \Weiming\Libs\Payments\Shundatong;
use \Weiming\Libs\Payments\Shunxin;
use \Weiming\Libs\Payments\SingleQrPay;
use \Weiming\Libs\Payments\Suiyifu;
use \Weiming\Libs\Payments\Tengkun;
use \Weiming\Libs\Payments\Tianfubao;
use \Weiming\Libs\Payments\Tianji;
use \Weiming\Libs\Payments\VPay;
use \Weiming\Libs\Payments\Wallet;
use \Weiming\Libs\Payments\Wande;
use \Weiming\Libs\Payments\Wangfutong;
use \Weiming\Libs\Payments\WenPay;
use \Weiming\Libs\Payments\Wofu;
use \Weiming\Libs\Payments\WoPay;
use \Weiming\Libs\Payments\Wpay;
use \Weiming\Libs\Payments\Wufu;
use \Weiming\Libs\Payments\Xianxingfu;
use \Weiming\Libs\Payments\Xifu;
use \Weiming\Libs\Payments\Xinfu;
use \Weiming\Libs\Payments\Xingjie;
use \Weiming\Libs\Payments\Xunbao;
use \Weiming\Libs\Payments\Xunjie;
use \Weiming\Libs\Payments\Yafu;
use \Weiming\Libs\Payments\YafuNew;
use \Weiming\Libs\Payments\Yf;
use \Weiming\Libs\Payments\Yinxin;
use \Weiming\Libs\Payments\Ylsl;
use \Weiming\Libs\Payments\Youfu;
use \Weiming\Libs\Payments\Yuntong;
use \Weiming\Libs\Payments\Yunzhifu2;
use \Weiming\Libs\Payments\Yunzhifu;
use \Weiming\Libs\Payments\Zesheng;
use \Weiming\Libs\Payments\Zhongdian;
use \Weiming\Libs\Payments\Zhongxin;
use \Weiming\Libs\Payments\Zhongxinnew;
use \Weiming\Libs\Payments\Ziyoufu;
use \Weiming\Libs\Payments\ZiyoufuNew;
use \Weiming\Libs\Utils;
use \Weiming\Models\Channel;
use \Weiming\Models\Company;
use \Weiming\Models\Pay;
use \Weiming\Models\PaymentChannel;
use \Weiming\Models\Paystatus;
use \Weiming\Models\Vendor;

class PaymentController extends BaseController
{
    /**
     * 更新微信、支付宝加好友支付记录状态
     */
    public function updateQrcodeStatus(Request $request, Response $response, $args)
    {
        $result    = ['status' => 1, 'msg' => '参数错误', 'data' => []];
        $postDatas = $request->getParsedBody();
        $order     = $postDatas['order'];
        $status    = $postDatas['status'];
        if ($order) {
            if (Pay::where('order_no', '=', $order)->update(['status' => $status])) {
                $result['status'] = 0;
                $result['msg']    = '';
            }
        }
        return $response->withJson($result);
    }

    /**
     * 前台充值页面数据渲染接口，版本 2
     */
    public function getPaymentInfo(Request $request, Response $response, $args)
    {

        $result = ['status' => 1, 'msg' => '参数错误', 'type' => 0, 'data' => []];

        if ($args['id']) {

            $company = Company::where('no', '=', $args['id'])->first();
            if ($company) {

                // 是否在前台页面显示
                $wechat    = 1;
                $wapWechat = 1;
                $alipay    = 1;
                $wapAlipay = 1;
                $netpay    = 1;
                $qq        = 1;
                $wapQq     = 1;
                $jd        = 1;
                $wapJd     = 1;
                $baidu     = 1;
                $wapBaidu  = 1;
                $union     = 1;
                $wapUnion  = 1;
                $yun       = 1;
                $wapYun    = 1;
                // 支付平台 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19 汇达、20 泽圣 21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 61、盖亚 62、Ylsl 63 A付
                $wechatPayType    = 0;
                $wapWechatPayType = 0;
                $alipayPayType    = 0;
                $wapAlipayPayType = 0;
                $netpayPayType    = 0;
                $qqPayType        = 0;
                $wapQqPayType     = 0;
                $jdPayType        = 0;
                $wapJdPayType     = 0;
                $baiduPayType     = 0;
                $wapBaiduPayType  = 0;
                $unionPayType     = 0;
                $wapUnionPayType  = 0;
                $yunPayType       = 0;
                $wapYunPayType    = 0;
                $res              = [];
                $data             = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => [], 9 => [], 10 => [], 11 => [], 12 => [], 13 => [], 14 => [], 15 => []];

                $wechat_vendor_id     = $company->wechat_vendor_id;
                $wap_wechat_vendor_id = $company->wap_wechat_vendor_id;
                $alipay_vendor_id     = $company->alipay_vendor_id;
                $wap_alipay_vendor_id = $company->wap_alipay_vendor_id;
                $netbank_vendor_id    = $company->netbank_vendor_id;
                $qq_vendor_id         = $company->qq_vendor_id;
                $wap_qq_vendor_id     = $company->wap_qq_vendor_id;
                $jd_vendor_id         = $company->jd_vendor_id;
                $wap_jd_vendor_id     = $company->wap_jd_vendor_id;
                $baidu_vendor_id      = $company->baidu_vendor_id;
                $wap_baidu_vendor_id  = $company->wap_baidu_vendor_id;
                $union_vendor_id      = $company->union_vendor_id;
                $wap_union_vendor_id  = $company->wap_union_vendor_id;
                $yun_vendor_id        = $company->yun_vendor_id;
                $wap_yun_vendor_id    = $company->wap_yun_vendor_id;
                $is_5qrcode           = $company->is_5qrcode;

                if ($wechat_vendor_id > 0) {
                    //微信扫码
                    $res           = $this->getVendorPaymentInfo($wechat_vendor_id);
                    $wechatPayType = $res['payType'];
                    $data[1]       = $res['data'][1];
                } else {
                    $wechat = 0;
                }

                if ($alipay_vendor_id > 0) {
                    //支付宝扫码
                    $res           = $this->getVendorPaymentInfo($alipay_vendor_id);
                    $alipayPayType = $res['payType'];
                    $data[2]       = $res['data'][2];
                } else {
                    $alipay = 0;
                }

                if ($netbank_vendor_id > 0) {
                    //网上银行
                    $res           = $this->getVendorPaymentInfo($netbank_vendor_id);
                    $netpayPayType = $res['payType'];
                    $data[3]       = $res['data'][3];
                } else {
                    $netpay = 0;
                }

                if ($qq_vendor_id > 0) {
                    //QQ扫码
                    $res       = $this->getVendorPaymentInfo($qq_vendor_id);
                    $qqPayType = $res['payType'];
                    $data[4]   = $res['data'][4];
                } else {
                    $qq = 0;
                }

                if ($jd_vendor_id > 0) {
                    //京东扫码
                    $res       = $this->getVendorPaymentInfo($jd_vendor_id);
                    $jdPayType = $res['payType'];
                    $data[5]   = $res['data'][5];
                } else {
                    $jd = 0;
                }

                if ($baidu_vendor_id > 0) {
                    //百度扫码
                    $res          = $this->getVendorPaymentInfo($baidu_vendor_id);
                    $baiduPayType = $res['payType'];
                    $data[6]      = $res['data'][6];
                } else {
                    $baidu = 0;
                }

                if ($union_vendor_id > 0) {
                    //银联扫码
                    $res          = $this->getVendorPaymentInfo($union_vendor_id);
                    $unionPayType = $res['payType'];
                    $data[7]      = $res['data'][7];
                } else {
                    $union = 0;
                }

                if ($wap_wechat_vendor_id > 0) {
                    //微信wap
                    $res              = $this->getVendorPaymentInfo($wap_wechat_vendor_id);
                    $wapWechatPayType = $res['payType'];
                    $data[8]          = $res['data'][8];
                } else {
                    $wapWechat = 0;
                }

                if ($wap_alipay_vendor_id > 0) {
                    //支付宝wap
                    $res              = $this->getVendorPaymentInfo($wap_alipay_vendor_id);
                    $wapAlipayPayType = $res['payType'];
                    $data[9]          = $res['data'][9];
                } else {
                    $wapAlipay = 0;
                }

                if ($wap_qq_vendor_id > 0) {
                    //QQwap
                    $res          = $this->getVendorPaymentInfo($wap_qq_vendor_id);
                    $wapQqPayType = $res['payType'];
                    $data[10]     = $res['data'][10];
                } else {
                    $wapQq = 0;
                }

                if ($wap_jd_vendor_id > 0) {
                    //京东wap
                    $res          = $this->getVendorPaymentInfo($wap_jd_vendor_id);
                    $wapJdPayType = $res['payType'];
                    $data[11]     = $res['data'][11];
                } else {
                    $wapJd = 0;
                }

                if ($wap_baidu_vendor_id > 0) {
                    //百度wap
                    $res             = $this->getVendorPaymentInfo($wap_baidu_vendor_id);
                    $wapBaiduPayType = $res['payType'];
                    $data[12]        = $res['data'][12];
                } else {
                    $wapBaidu = 0;
                }

                if ($wap_union_vendor_id > 0) {
                    //银联wap
                    $res             = $this->getVendorPaymentInfo($wap_union_vendor_id);
                    $wapUnionPayType = $res['payType'];
                    $data[13]        = $res['data'][13];
                } else {
                    $wapUnion = 0;
                }

                if ($yun_vendor_id > 0) {
                    //银联wap
                    $res        = $this->getVendorPaymentInfo($yun_vendor_id);
                    $yunPayType = $res['payType'];
                    $data[14]   = $res['data'][14];
                } else {
                    $yun = 0;
                }

                if ($wap_yun_vendor_id > 0) {
                    //银联wap
                    $res           = $this->getVendorPaymentInfo($wap_yun_vendor_id);
                    $wapYunPayType = $res['payType'];
                    $data[15]      = $res['data'][15];
                } else {
                    $wapYun = 0;
                }

                $wechat_op    = Paystatus::where('keys', 'wechat')->first(); //微信
                $onlinepay_op = Paystatus::where('keys', 'onlinepay')->first(); //在线支付
                $yunpay_op    = Paystatus::where('keys', 'yunpay')->first(); //云闪付
                $trance_op    = Paystatus::where('keys', 'trance')->first(); //转帐汇款
                $alipay_op    = Paystatus::where('keys', 'alipay')->first(); //支付宝支付

                // 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19 汇达、20 泽圣 21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 61、盖亚 62、ylsl 63、Az 64、A 65、百盛
                $result['status'] = 0;
                $result['msg']    = '';
                $result['type']   = [
                    'wechat'    => $wechatPayType,
                    'alipay'    => $alipayPayType,
                    'netpay'    => $netpayPayType,
                    'qq'        => $qqPayType,
                    'jd'        => $jdPayType,
                    'baidu'     => $baiduPayType,
                    'union'     => $unionPayType,
                    'wapWechat' => $wapWechatPayType,
                    'wapAlipay' => $wapAlipayPayType,
                    'wapQq'     => $wapQqPayType,
                    'wapJd'     => $wapJdPayType,
                    'wapBaidu'  => $wapBaiduPayType,
                    'wapUnion'  => $wapUnionPayType,
                    'yun'       => $yunPayType,
                    'wapYun'    => $wapYunPayType,
                ];
                $result['payStatus'] = [
                    'wechat'    => $wechat,
                    'alipay'    => $alipay,
                    'netpay'    => $netpay,
                    'qq'        => $qq,
                    'jd'        => $jd,
                    'baidu'     => $baidu,
                    'union'     => $union,
                    'wapWechat' => $wapWechat,
                    'wapAlipay' => $wapAlipay,
                    'wapQq'     => $wapQq,
                    'wapJd'     => $wapJd,
                    'wapBaidu'  => $wapBaidu,
                    'wapUnion'  => $wapUnion,
                    'yun'       => $yun,
                    'wapYun'    => $wapYun,
                ];

                $result['weixin']    = $wechat_op->status;
                $result['onlinepay'] = $onlinepay_op->status;
                $result['yunpay']    = $yunpay_op->status;
                $result['trance']    = $trance_op->status;
                $result['alipay']    = $alipay_op->status;

                $result['data']        = $data;
                $result['multiQrcode'] = $is_5qrcode;
            }
        }
        return $response->withJson($result);
    }

    /**
     * 前端支付接口------
     */
    public function payment(Request $request, Response $response, $args)
    {
        $conf      = [];
        $postDatas = $request->getParsedBody();
        $this->logger->addInfo('doPayment order came in.', $postDatas);
        if (isset($postDatas['order_id'])) {
            //$this->bbCheck($postDatas); //验签
            $number     = $postDatas['number'];
            $notify_url = $postDatas['notify_url'];
            $method_id  = $postDatas['method_id'];
            $bank_id    = $postDatas['bank_id'];
            $device     = "1";
            $bbinkey    = "bbin" . $number;
            if ($bank_id) {
                //网银
                $payType2 = $this->settings[$bbinkey]['bank'][$bank_id];
            } else {
                //扫码，h5
                $payType = $this->settings[$bbinkey][$method_id];
            }

            $payTime    = $postDatas['created_at'];
            $money      = $postDatas['amount'];
            $account    = $postDatas['username'];
            $orderId    = $postDatas['order_id'];
            $ip         = '127.0.0.1';
            $Vendor     = Vendor::where('no', '=', $number)->first();
            $company    = Company::where('no', '=', '00001')->first();
            $vendorid   = $Vendor->id;
            $vendorType = $Vendor->pay_type;
        } else {
            $notify_url = "";
            $vendorType = $postDatas['vendorType'];
            $companyNo  = $postDatas['companyNo'];
            $money      = $postDatas['money'];
            $payTime    = $postDatas['payTime'];
            $account    = $postDatas['username'];
            $payType    = $postDatas['paytype'];
            $payType2   = $postDatas['paytype2'];
            $device     = $postDatas['device'];
            $ip         = $postDatas['ip'] ?? '';
            $orderId    = Utils::getOrderId($payTime);
            $company    = Company::where('no', '=', $companyNo)->first();
        }

        if ($company) {
            $vendor_id = 0;

            $payCode = '';

            $payTypeId = 1;

            // 注：以下支付 code 包含已经作废的 闪亿付 Shanyi
            $jdCode = ['JDPAY', 'JD', 'MSJD', '0801', '912', '004', 'JdPay', '1008', 'JDSCAN', 'jdpay', '0001007', '0601', '01002', '41', 'JD_NATIVE', 'jd', 'jdpay', 'JD_QRCODE_PAY'];

            $baiduCode = ['BAIDUPAY', 'BDPAY', 'BAIDU', '001003'];

            $unionCode = ['UNIONPAY', '0040', '0701', '913', '005', '001007', 'UNIONQRPAY', 'unionPayQR', 'ylsm', 'UNION_WALLET', '001009', '1001', '0002', 'UNIONPAY_NATIVE', 'unionpayqr', '60000103', 'unionpay', 'UNIONPAY_QRCODE_PAY'];

            $qqCode = ['qqrcode', '6011', 'QQWALLET', 'QQ', '993', 'qqpay', 'QQPAY', '0501', '01009', 'tenpay_scan', '31', 'MSTENPAY', '10000170', '1010', '908', '003',
                'QQZF', '46', '1593', '010500', 'tenpay', 'QQSCAN', 'qq', 'qqQR', '0102', '01005', 'QQ_NATIVE', 'qqpay', 'QQ_QRCODE_PAY'];

            $wechatCode = ['wxewm', '0201', 'WECHAT_PC', '0202', '1004', '1007', '8011', 'wx', 'weixin', '12', '100010', '100012', '1003', 'WEIXIN', 'MSWEIXIN', 'WECHATQR', '6001', 'WEIXIN_NATIVE', 'wechat',
                'wxpay', 'weixin_scan', 'WXPAY', '21', '10000168', '902', '102', '001', 'WXZF', '20', 'WxSm', '02', '2', '20001', 'WECHAT', '0101', 'WX', '1000', '10', 'wxpay', '10000103', 'wxcode', '60', 'WXP', 'zxweixin', 'pay.weixin.scan.trade.precreate'];

            $alipayCode = ['VPay_ZFB', 'ALIPAY_PC', 'ali', '0301', '0302', '992', '1006', '8012', 'zfb', 'alipay', '30', '400010', '400012', '1009', 'ZHIFUBAO', 'MSAli', 'ALIPAYQR', '6003', 'ALIPAY_NATIVE',
                'ALIPAY', '01003', 'alipay_scan', '10000169', '7', '101', '903', '002', '22', 'DFYzfb', '1', '10001', '0103', '0132', 'ZFB', '02010', '020', 'alipay', '2', '20000303', 'alipay', 'ALIPAY_QRCODE_PAY', 'sm', '21', 'alpls', 'VPay_ZFB', 'zxalp', '903', '8006', 'shunda001'];

            $wapWechatCode = ['WEIXINWAP', 'WECHAT_MOBILE', '901', '01007', 'WXZFWAP', '48', '1005', 'WECHATWAP', 'wxpayh5', 'wxwap', 'wxh5', 'W1WEIXIN_PAY', 'WX_WAP', '1002', '01030', '1100', '11', 'WEIXIN_H5', '10000203', 'WXWAP', '0121', '62', 'WXH5', 'zxweixin', 'pay.weixin.scan.trade.precreate'];

            $wapAlipayCode = ['ALIPAYWAP', '904', '4', '01006', '38', 'ZfbWap', 'aliwap', 'alipaywap', 'Z2ALIPAY', 'ZFB_WAP', '01004', '0203', '1101', '022', 'ALIPAY_H5', 'alipayh5', '2', '20000203', 'alipaywap', '0131', 'ALIPAY_WAP_PAY', 'sm', '21', 'alpls', 'ALIPAY_MOBILE', 'zxalp', '904', '8007', 'shunda001'];

            $wapQqCode = ['QQ_H5', 'QQPAYWAP', 'QQWAP', '905', '01008', '1594', 'qqpayh5', 'qqwap', 'Q2TEN_PAY', 'QQ_WAP', '001006', '0503', '1102'];

            $wapJdCode = ['JD_H5', 'JDPAYWAP', 'jdwap', 'JDWAP', '910', 'JD_WAP', '001008', '0603', '01012', 'JD_H5', 'JD_WAP_PAY'];

            $wapBaiduCode = [];

            $wapUnionCode = ['UNIONPAY_H5', '0050', 'UNION_WALLET_H5', '1012', 'unionpayqrh5', '60000103', 'UNIONPAY_WAP_PAY'];

            $wapYunCode = ['daniuyun'];

            $yunCode = ['daniuyun'];

            if (in_array($payType, $unionCode, true)) {

                $payCode = $payType;

                $payTypeId = 7;
                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->union_vendor_id;
                }

            } elseif (in_array($payType, $baiduCode, true)) {

                $payCode = $payType;

                $payTypeId = 6;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->baidu_vendor_id;
                }
            } elseif (in_array($payType, $jdCode, true)) {

                $payCode = $payType;

                $payTypeId = 5;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->jd_vendor_id;
                }
            } elseif (in_array($payType, $qqCode, true)) {

                $payCode = $payType;

                $payTypeId = 4;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->qq_vendor_id;
                }
            } elseif (in_array($payType, $alipayCode, true)) {

                $payCode = $payType;

                $payTypeId = 3;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->alipay_vendor_id;
                }
            } elseif (in_array($payType, $wechatCode, true)) {

                $payCode = $payType;

                $payTypeId = 2;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wechat_vendor_id;
                }
            } elseif (in_array($payType, $wapWechatCode, true)) {

                $payCode = $payType;

                $payTypeId = 8;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_wechat_vendor_id;
                }
            } elseif (in_array($payType, $wapAlipayCode, true)) {

                $payCode = $payType;

                $payTypeId = 9;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_alipay_vendor_id;
                }
            } elseif (in_array($payType, $wapQqCode, true)) {

                $payCode = $payType;

                $payTypeId = 10;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_qq_vendor_id;
                }
            } elseif (in_array($payType, $wapJdCode, true)) {

                $payCode = $payType;

                $payTypeId = 11;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_jd_vendor_id;
                }
            } elseif (in_array($payType, $wapBaiduCode, true)) {

                $payCode = $payType;

                $payTypeId = 12;

                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_baidu_vendor_id;
                }
            } elseif (in_array($payType, $wapUnionCode, true)) {

                $payCode = $payType;

                $payTypeId = 13;
                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_union_vendor_id;
                }
            } elseif (in_array($payType, $yunCode, true)) {

                $payCode = $payType;

                $payTypeId = 14;
                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->yun_vendor_id;
                }
            } elseif (in_array($payType, $wapYunCode, true)) {

                $payCode = $payType;

                $payTypeId = 15;
                if (isset($postDatas['order_id'])) {
                    $vendor_id = $vendorid;
                } else {
                    $vendor_id = $company->wap_yun_vendor_id;
                }
            } else {

                $payCode = $payType2;

                $vendor_id = $company->netbank_vendor_id;

            }
            if ($vendor_id > 0) {
                if ($payType == 'VPay_ZFB') //for test
                {
                    $vendor_id = 77;
                }
                $vendor = Vendor::where('id', '=', $vendor_id)->first();
                if ($vendor) {

                    $conf['parterNo']    = $vendor->no;
                    $conf['parterKey']   = $vendor->key;
                    $conf['callbackUrl'] = $vendor->callback_url;
                    $conf['notifyUrl']   = $vendor->notify_url;

                    if ($vendorType == 59) {
                        $orderId = substr($orderId, 0, 20);
                    }

                    // 日志
                    $this->logger->addInfo('Payment order came in.', $postDatas);

                    // 订单入库
                    if (in_array($vendor->pay_type, [25, 47])) {
                        $money    = sprintf("%.2f", $money);
                        $monerArr = explode('.', $money);
                        if ($monerArr[1] == '00') {
                            $rand  = mt_rand(10, 50);
                            $money = intval($money);
                            $money = $money + $rand / 100;
                        }
                    } elseif ($vendor->pay_type == 24) {
                        $money = intval($money);
                    }
                    $newOrder = Pay::create([
                        'pay_type'    => $payTypeId, // 7 银联，6 百度，5 京东，4 QQ，3 支付宝，2 微信，1 网银
                        'pay_code'    => $payCode,
                        'user'        => $account,
                        'device'      => $device, // 1 pc端，2 手机端
                        'order_no'    => $orderId,
                        'money'       => $money,
                        'company_id'  => $company->id,
                        'vendor_id'   => $vendor->id,
                        'vendor_type' => $vendor->pay_type, // 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19 汇达、20 泽圣 21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 61、盖亚 62、ylsl  63、Az 64、A 65、百盛 70、联胜 71、个付
                        'notify_url'  => $notify_url,
                    ]);

                    if ($newOrder) {

                        $res = '';

                        if ($payType === '01') {

                            $payType = $payType2;

                        }

                        // 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19 汇达、20 泽圣 21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 61、盖亚 62、ylsl 63、Az 64、A 65、百盛 70、联胜 71、个付
                        if ($vendorType == 1) {

                            $res = Yafu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 2) {

                            $res = Shanfu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 3) {

                            $res = Xunbao::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 4) {

                            $res = Leying::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 5) {

                            $res = Ziyoufu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 6) {

                            $res = Wallet::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 7) {

                            $res = Jinhaizhe::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendorType == 8) {

                            $res = Huaren::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 9) {

                            $res = Hebao::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 10) {

                            $res = Likefu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 11) {

                            $res = Duoduo::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 12) {

                            $res = JinhaizheNew::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 13) {

                            $res = Renxin::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 14) {

                            $res = Tianfubao::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 15) {

                            $res = Gaotong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 16) {

                            $res = YafuNew::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 17) {

                            $res = Xianxingfu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 18) {

                            $res = Wofu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 19) {

                            $res = Huida::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 20) {

                            $res = Zesheng::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 21) {

                            $res = ZiyoufuNew::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 22) {

                            $res = Qiandai::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 23) {

                            $res = Jinyang::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 24) {

                            $res = SingleQrPay::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 25) {

                            $res = Wangfutong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 26) {

                            $res = Qianying::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 27) {

                            $res = Youfu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 28) {

                            $res = Shangma::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 29) {

                            $res = Hengchen::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 30) {

                            $res = Chengwo::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 31) {

                            $res = Kailiantong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 32) {

                            $res = Dianfuyun::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 33) {

                            $res = Xinfu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 34) {

                            $res = Tengkun::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 35) {

                            $res = Tianji::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 36) {

                            $res = Zhongdian::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 37) {

                            $res = Yunzhifu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 38) {

                            $res = Yunzhifu2::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 39) {

                            $res = Xifu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 40) {

                            $res = Aifu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 41) {

                            $res = Nongfu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 42) {

                            $res = Shunxin::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 43) {

                            $res = Mida::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 44) {

                            $res = Wpay::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 45) {

                            $res = Hengxing::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 46) {

                            $res = Zhongxin::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 47) {

                            $res = Xingjie::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 48) {

                            $res = Xunjie::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 49) {

                            $orderId = $orderId . '_' . $ip;

                            $res = Yuntong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 50) {

                            $res = Shanyi::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 51) {

                            $res = Hengtong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 52) {

                            $res = Bingo::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 53) {

                            $res = Lexiang::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 55) {

                            $res = Suiyifu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 56) {

                            $res = Changzhifu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 57) {

                            $res = Yinxin::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 58) {

                            $res = Wufu::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 59) {

                            $res = Pppay::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 60) {

                            $res = Shundatong::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 61) {

                            $res = Gaiya::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 62) {

                            $res = Ylsl::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 63) {

                            $res = Af::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 64) {

                            $res = Az::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 65) {

                            $res = Baisheng::getInstance($conf)->signature($payType, $money, $orderId)->payment();

                        } elseif ($vendor->pay_type == 66) {
                            //豪富 自接支付
                            $res   = FhPay::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                            $fharr = json_decode($res, true);
                            if ($fharr['is_success'] == "T") {
                                $response->getBody()->write($res);
                                header('Location: ' . $fharr['result']);
                            } else {
                                echo $fharr['result'];
                            }
                            exit();
                        } elseif ($vendor->pay_type == 67) {
                            //酷呗 金融世家 自接支付
                            $data67['payType'] = $payType;
                            $data67['money']   = $money;
                            $data67['money']   = $money;
                            $res               = JrsjPay::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                            $Jrsj              = json_decode($res, true);
                            if ($Jrsj['state'] == "Successful") {
                                $response->getBody()->write($res);
                                header('Location: ' . $Jrsj['payUrl']);
                            } else {
                                echo "请求失败";
                            }
                            exit();
                        } elseif ($vendor->pay_type == 68) {
                            $res = VPay::getInstance($conf)->signature($payType, $money, $newOrder)->payment();
                        } elseif ($vendor->pay_type == 69) {
                            //广付通
                            $conf['banknumber'] = $postDatas['banknumber'];
                            $res                = Gft::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 70) {
                            //联胜
                            if (!isset($postDatas['order_id'])) {
                                $lsarr = Ls::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                                $res   = $lsarr['pageUrl'];
                            } else {
                                $payType = 'alpls';
                                $res     = Ls::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                                if ($res['code'] == '1000') {
                                    header('Location: ' . $res['pageUrl']);
                                    exit;
                                } else {
                                    echo "请求失败";
                                }
                            }
                        } elseif ($vendor->pay_type == 71) {
                            $res = Gefu::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 72) {
                            //百汇
                            $res = Bh::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 73) {
                            //创优
                            $res = Cy::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 74) {
                            //创优
                            $res = Nhh::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 75) {
                            //善融
                            $res = Shanrong::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 76) {
                            //众鑫
                            $res = Zhongxinnew::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 77) {
                            //众鑫
                            $res = Daniu::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 78) {
                            //盈付
                            $res = Yf::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 79) {
                            // 万德
                            $res = Wande::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 80) {
                            // 海图
                            $res = Haitu::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 81) {
                            // 凌云
                            $res = Ly::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 82) {
                            // 艾必德
                            $res = Aibide::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 83) {
                            // 稳付
                            $res = WenPay::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        } elseif ($vendor->pay_type == 84) {
                            // 沃支付
                            $res = WoPay::getInstance($conf)->signature($payType, $money, $orderId)->payment();
                        }
                        $response->getBody()->write($res);
                    }
                }
            }
        }

        return $response;
    }

    /**
     * 前端支付请求接口------新版
     */
    public function doPayment(Request $request, Response $response, $args)
    {
        $conf      = [];
        $postDatas = $request->getParsedBody();
        // 日志
        $this->logger->addInfo('doPayment order came in.', $postDatas);

        $id      = $postDatas['id'];
        $money   = $postDatas['money'];
        $payTime = $postDatas['payTime'];
        $account = $postDatas['username'];
        $device  = $postDatas['device'];
        $ip      = $postDatas['ip'] ?? '';

        $orderId         = Utils::getOrderId($payTime);
        $oPaymentChannel = PaymentChannel::find($id);
        if ($oPaymentChannel) {
            $conf['parterNo']    = $oPaymentChannel->merchant_no;
            $conf['parterKey']   = $oPaymentChannel->key;
            $conf['callbackUrl'] = $oPaymentChannel->callback_url;
            $conf['notifyUrl']   = $oPaymentChannel->notify_url;

            if ($postDatas['netbankPaycode']) //如果是网银充值
            {
                $payCode = $postDatas['netbankPaycode'];
            } else {
                $payCode = $oPaymentChannel->paycode;
            }

            $oChannel = Channel::where('tag', $oPaymentChannel->channel)->first();

            $vendor   = Vendor::where('no', $oPaymentChannel->merchant_no)->first(); //向旧版兼容
            $newOrder = Pay::create([
                'pay_type'    => $oChannel->id,
                'pay_code'    => $payCode,
                'user'        => $account,
                'device'      => $device, // 1 pc端，2 手机端
                'order_no'    => $orderId,
                'money'       => $money,
                'company_id'  => 1,
                'vendor_id'   => is_object($vendor) ? $vendor->id : 888,
                'vendor_type' => $oPaymentChannel->id, // 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19 汇达、20 泽圣 21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 61、盖亚 62、ylsl  63、Az 64、A 65、百盛 70、联胜 71、个付
                //'notify_url'  => $oPaymentChannel->notify_url,
                'version'     => '2.0',
            ]);

            if ($newOrder) {
                $sPlatform = $oPaymentChannel->platform_identifer;
                if ($sPlatform == 'FhPay') {
                    //豪富 自接支付
                    $res   = FhPay::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                    $fharr = json_decode($res, true);
                    if ($fharr['is_success'] == "T") {
                        $response->getBody()->write($res);
                        header('Location: ' . $fharr['result']);
                    } else {
                        echo $fharr['result'];
                    }
                    exit();
                } elseif ($sPlatform == 'JrsjPay') {
                    //酷呗 金融世家 自接支付
                    $data67['payType'] = $payCode;
                    $data67['money']   = $money;
                    $data67['money']   = $money;
                    $res               = JrsjPay::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                    $Jrsj              = json_decode($res, true);
                    if ($Jrsj['state'] == "Successful") {
                        $response->getBody()->write($res);
                        header('Location: ' . $Jrsj['payUrl']);
                    } else {
                        echo "请求失败";
                    }
                    exit();
                } elseif ($sPlatform == 'Gft') {
                    //广付通
                    $conf['banknumber'] = $postDatas['banknumber'];
                    $res                = Gft::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                } elseif ($sPlatform == 'Ls') {
                    //联胜
                    if (!isset($postDatas['order_id'])) {
                        $lsarr = Ls::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                        $res   = $lsarr['pageUrl'];
                    } else {
                        $payCode = 'alpls';
                        $res     = Ls::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                        if ($res['code'] == '1000') {
                            header('Location: ' . $res['pageUrl']);
                            exit;
                        } else {
                            echo "请求失败";
                        }
                    }
                } else {
                    $sPlatform = "\Weiming\Libs\Payments\\" . $sPlatform;
                    try
                    {
                        $res = $sPlatform::getInstance($conf)->signature($payCode, $money, $orderId)->payment();
                    } catch (Exception $e) {
                        return $response->withJson(['status' => 1, 'msg' => '支付渠道信息错误']);
                    }

                }

                $response->getBody()->write($res);
            } else {
                return $response->withJson(['status' => 1, 'msg' => '充值订单写入失败！']);
            }
        } else {
            return $response->withJson(['status' => 1, 'msg' => '充值渠道不存在！']);
        }

        return $response;
    }

    /**
     * 支付后端回调
     */
    public function callbackUrl(Request $request, Response $response, $args)
    {

        $getDatas = $request->getQueryParams();

        // 乐盈支付使用Post方式提交
        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        // 日志
        $this->logger->addInfo('Payment orders have results.', $getDatas);

        $order_no = '';

        // 雅付老接口停用
        // if (isset($getDatas['merOrderNum']) && $getDatas['merOrderNum']) {

        //     $order_no = $getDatas['merOrderNum'];

        // } else

        if (isset($getDatas['orderid']) && $getDatas['orderid']) {

            $order_no = $getDatas['orderid'];

        } elseif (isset($getDatas['orderID']) && $getDatas['orderID']) {

            $order_no = $getDatas['orderID'];
        }

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $pay_type = $vendor->pay_type;

                            // 雅付老接口停用
                            // if ($pay_type == 1) {

                            //     $verify = Yafu::getInstance($conf)->verifySign($getDatas);

                            // } else

                            if ($pay_type == 2) {

                                $verify = Shanfu::getInstance($conf)->verifySign($getDatas);

                            } elseif ($pay_type == 3) {

                                $verify = Xunbao::getInstance($conf)->verifySign($getDatas);

                            } elseif ($pay_type == 4) {

                                $verify = Leying::getInstance($conf)->verifySign($getDatas);

                            }

                            if ($verify) {

                                $vendor_order_no = '';

                                // 雅付老接口停用
                                // if (isset($getDatas['orderId']) && $getDatas['orderId']) {

                                //     $vendor_order_no = $getDatas['orderId'];

                                // } else

                                if (isset($getDatas['sysorderid']) && $getDatas['sysorderid']) {

                                    $vendor_order_no = $getDatas['sysorderid'];

                                } elseif (isset($getDatas['orderNo']) && $getDatas['orderNo']) {

                                    $vendor_order_no = $getDatas['orderNo'];

                                }

                                $status = 1;

                                // 雅付老接口停用
                                // if (isset($getDatas['respCode']) && $getDatas['respCode'] && $getDatas['respCode'] == 'ok') {

                                //     $status = 0;

                                // } else

                                if (isset($getDatas['opstate']) && $getDatas['opstate'] == 0) {

                                    $status = 0;

                                } elseif (isset($getDatas['stateCode']) && $getDatas['stateCode'] && $getDatas['stateCode'] == 2) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $vendor_order_no;
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s', time());
                                $pay->remark          = json_encode($getDatas);

                                $res = $pay->save();

                                if ($res) {

                                    $returnMsg = '';

                                    // if ($pay_type == 1) {

                                    //     // $returnMsg = "success";
                                    //     $returnMsg = $company->url . '/success/index.html';

                                    // } else

                                    if ($pay_type == 2 || $pay_type == 3) {

                                        $returnMsg = "opstate=0";

                                    }

                                    // 自动入款
                                    if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                        $requestParams = [
                                            'account'      => $pay->user,
                                            'fee'          => $pay->money,
                                            'orderNo'      => $order_no,
                                            'rechargeTime' => date('Y-m-d H:i:s'),
                                        ];
                                        $requestParams['sign'] = Utils::generateSignature($requestParams);
                                        $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                        if ($token) {
                                            // 1 已加入队列
                                            $pay->recharge_status = 1;
                                            $pay->recharge_msg    = '正在自动入款';
                                            $pay->queue_job_id    = $token;
                                            $pay->save();
                                            // 日志
                                            $this->logger->addInfo('Payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                        }
                                    }

                                    // Redis 解锁
                                    $this->redisLock->unlock($redisLockey);

                                    $response->getBody()->write($returnMsg);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * 支付前端回调
     */
    public function notifyUrl(Request $request, Response $response, $args)
    {

        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        // 日志
        $this->logger->addInfo('Payment order has been notified to user.', $getDatas);

        // 雅付，无通知地址，它是通过callbackUrl echo 一个url通知用户的。

        // 闪付
        // Array
        // (
        //     [orderid] => 201702281102304440248
        //     [opstate] => 0
        //     [ovalue] => 2.00
        //     [systime] => 2017/02/28 11:03:18
        //     [sysorderid] => 1702281102480590082
        //     [completiontime] => 2017/02/28 11:03:18
        //     [attach] => CZ
        //     [msg] =>
        //     [sign] => 1e89fc96c12fb7806c1ba21c8862718b
        // )

        // 讯宝
        // Array
        // (
        //     [orderid] => 201702281104134830417
        //     [opstate] => 0
        //     [ovalue] => 0.02
        //     [sysorderid] => B5374484704151101674
        //     [systime] => 2017/02/28 11:04:35
        //     [attach] => CZ
        //     [msg] =>
        //     [sign] => 354d7643ceca8de7e0104de50684756d
        // )

        // 乐盈
        // Array
        // (
        //     [charset] => 1
        //     [orderNo] => 1051702281105022528
        //     [orderID] => 201702281105368220541
        //     [resultCode] =>
        //     [completeTime] => 20170228110543
        //     [acquiringTime] => 20170228110543
        //     [remark] => CZ
        //     [orderAmount] => 1
        //     [payAmount] => 1
        //     [signType] => 2
        //     [stateCode] => 2
        //     [partnerID] => 10054202322
        //     [signMsg] => 1c9932ecbab765c5f16eee4a29dd2fb9
        // )

        $status = 1;

        if (isset($getDatas['opstate']) && $getDatas['opstate'] == 0) {

            $status = 0;

        } elseif (isset($getDatas['stateCode']) && $getDatas['stateCode'] && $getDatas['stateCode'] == 2) {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($getDatas['orderid']) && $getDatas['orderid']) {

                $order_no = $getDatas['orderid'];

            } elseif (isset($getDatas['orderID']) && $getDatas['orderID']) {

                $order_no = $getDatas['orderID'];
            }

            if ($order_no) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->company_id) {

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($company) {

                            if ($company->url) {

                                // header("Location: " . $company->url . '/success/index.html');
                                // exit();
                                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

                            } else {

                                $response->getBody()->write('Url is blank, Please contact webmaster.');
                            }
                        }
                    }
                }
            }

        } else {

            $response->getBody()->write('failure');
        }

        return $response;
    }

    /**
     * 自由付微信、支付宝扫描二维码支付页面
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function scancode(Request $request, Response $response, $args)
    {
        $tmp      = '参数错误';
        $getDatas = $request->getQueryParams();

        $tradeNo = $getDatas['trade_no'];
        $fee     = $getDatas['fee'];
        $qrcode  = $getDatas['qrcode'];
        $codeurl = isset($getDatas['codeurl']) ? strtolower($getDatas['codeurl']) : '';
        print_r($getDatas);die;
        if (isset($getDatas['trade_no']) && isset($getDatas['fee']) && isset($getDatas['qrcode']) && $tradeNo && $fee && $qrcode) {

            $payType = '手机';
            if (preg_match("/weixin/i", $qrcode) || preg_match("/weixin/i", $codeurl)) {
                $payType = '微信';
            } elseif (preg_match("/alipay/i", $qrcode) || preg_match("/alipay/i", $codeurl)) {
                $payType = '支付宝';
            } elseif (preg_match("/qq/i", $qrcode) || preg_match("/qq/i", $codeurl)) {
                $payType = 'QQ';
            } elseif (preg_match("/union/i", $qrcode) || preg_match("/union/i", $codeurl)) {
                $payType = '银联';
            }

            //$payType = preg_match("/weixin/i", $qrcode) || preg_match("/weixin/i", $codeurl) ? '微信' : (preg_match("/alipay/i", $qrcode) || preg_match("/alipay/i", $codeurl) ? '支付宝' : (preg_match("/qq/i", $qrcode) || preg_match("/qq/i", $codeurl) ? 'QQ' : ''));

            $tmp = '<!DOCTYPE html>';
            $tmp .= '<html>';
            $tmp .= '<head lang="en">';
            $tmp .= '<meta charset="utf-8">';
            $tmp .= '<meta content="width=device-width,initial-scale=1.0, user-scalable=no" name="viewport" />';
            $tmp .= '<meta content="yes" name="apple-mobile-web-app-capable" />';
            $tmp .= '<meta content="no" name="apple-touch-fullscreen" />';
            $tmp .= '<meta content="black" name="apple-mobile-web-app-status-bar-style" />';
            $tmp .= '<meta content="telephone=no" name="format-detection"/>';
            $tmp .= '<title>微信、支付宝扫码支付</title>';
            $tmp .= '<style>';
            $tmp .= 'body{text-align:center;margin-top:60px;}';
            $tmp .= '.fee{color:red;font-size:20px;}';
            $tmp .= '</style>';
            $tmp .= '</head>';
            $tmp .= '<body>';
            $tmp .= '<div class="info">';
            $tmp .= '<div class="order">单号：' . $tradeNo . '</div>';
            $tmp .= '<div class="order">请支付金额：<span class="fee">' . $fee . ' &yen;</span></div>';
            $tmp .= '</div>';
            $tmp .= '<div class="qrcode">';
            $tmp .= '<img src=' . $qrcode . ' />';
            $tmp .= '</div>';
            $tmp .= '<div>提示：请使用' . $payType . '扫描二维码以完成支付</div>';
            $tmp .= '<div class="fee">温馨提示：请勿重复支付 、重复支付无效</div>';
            $tmp .= '<script type="text/javascript">';
            $tmp .= '</script>';
            $tmp .= '</body>';
            $tmp .= '</html>';

        }

        $response->getBody()->write($tmp);

        return $response;
    }

    /**
     * 自由付扫码支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function scancodeCallback(Request $request, Response $response, $args)
    {
        $xml = file_get_contents('php://input');

        $xmlArr = Utils::toArray($xml);

        $order_no = $xmlArr['out_trade_no'];

        $money = sprintf("%.2f", $xmlArr['total_fee'] / 100);

        // 微信
        // {
        //     "attach": "201703181250343185023",
        //     "bank_type": "CFT",
        //     "charset": "UTF-8",
        //     "fee_type": "CNY",
        //     "is_subscribe": "N",
        //     "mch_id": "7551000001",
        //     "nonce_str": "1489812951242",
        //     "openid": "oywgtuJDmWsVt8NZLQIASwkeajBc",
        //     "out_trade_no": "201703181250343185023",
        //     "out_transaction_id": "4007602001201703183774133656",
        //     "pay_result": "0",
        //     "result_code": "0",
        //     "sign": "552941B79676108532CDC68DF0EB3614",
        //     "sign_type": "MD5",
        //     "status": "0",
        //     "sub_appid": "wxce38685bc050ef82",
        //     "sub_is_subscribe": "N",
        //     "sub_openid": "oHmbkt_tL6gR-DqbwfXWfLvWzngo",
        //     "time_end": "20170318125056",
        //     "total_fee": "1",
        //     "trade_type": "pay.weixin.native",
        //     "transaction_id": "7551000001201703181109628327",
        //     "version": "2.0"
        // }

        // 支付宝
        // {
        //     "attach": "201703181258504135840",
        //     "charset": "UTF-8",
        //     "fee_type": "CNY",
        //     "mch_id": "7551000001",
        //     "nonce_str": "1489813157594",
        //     "openid": "2088502736637858",
        //     "out_trade_no": "201703181258504135840",
        //     "out_transaction_id": "2017031821001004850241132626",
        //     "pay_result": "0",
        //     "result_code": "0",
        //     "sign": "FB933A255A2D3A8EB2272702A3702092",
        //     "sign_type": "MD5",
        //     "status": "0",
        //     "time_end": "20170318125917",
        //     "total_fee": "1",
        //     "trade_type": "pay.alipay.native",
        //     "transaction_id": "7551000001201703183008059321",
        //     "version": "2.0"
        // }

        // 日志
        $this->logger->addInfo('QRcode payment callback data:', $xmlArr);

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'fail';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 5) {

                                $verify = Ziyoufu::getInstance($conf)->verifySign($xmlArr);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($xmlArr['status'] == 0 && $xmlArr['result_code'] == 0) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $xmlArr['transaction_id'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s', time());
                                $pay->remark          = json_encode($xmlArr);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 5) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('QRcode payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 沃雷特支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function walletCallback(Request $request, Response $response, $args)
    {
        $json = file_get_contents('php://input');

        $jsonArr = json_decode($json, true);

        $order_no = $jsonArr['mchTradeNo'];
        $money    = $jsonArr['tradeAmt'];

        // 微信
        // {
        //     "endTime": "2017-03-20 18:15:26",
        //     "mchTradeNo": "201703201815097401453",
        //     "orderNo": "20170320049440672122684177059712",
        //     "payType": "12",
        //     "sign": "Oiy4rdF3zF6gw08rggD3EKl2DCe4aW1FWSO8Zp8hv193g4nTSADp+3iXi/OnvkaBcpG5fcM0c1f/uPeu9BlO1Vxekw1FatRtr0zxE9z1NhH7YUeP8nmk0uz6SbnsLC2KHV2j9pcwQsPh3i3gB90pWvquuz2BaFrWxHwjMgL058g=",
        //     "subject": "CZ",
        //     "tradeAmt": "1.00",
        //     "tradeStatus": "02"
        // }

        // 支付宝
        // {
        //     "endTime": "2017-03-20 18:40:32",
        //     "mchTradeNo": "201703201839285543912",
        //     "orderNo": "20170320610503749706292774657483",
        //     "payType": "30",
        //     "sign": "T/khppl+L/mprESpBOU4BCkOuR6iwVrIo12T2sYFeKnn02F0yEPoEUv2Qr4SwcYGHWTbKXI0puxFmTb3fp3zXd/ZL+g5zET8aPlsFaxacJdrWx/Iqf6RjWucGe0wJ/EGx1DEzJK1j1NV2qQzXoKnSuSnB6a14bGA99YWt/f01fY=",
        //     "subject": "CZ",
        //     "tradeAmt": "1.00",
        //     "tradeStatus": "02"
        // }

        $this->logger->addInfo('Wallet payment callback data:', $jsonArr);

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'fail';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 6) {

                                $verify = Wallet::getInstance($conf)->verifySign($jsonArr);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($jsonArr['tradeStatus'] == '02') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $jsonArr['orderNo'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $jsonArr['endTime'];
                                $pay->remark          = json_encode($jsonArr);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 6) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $jsonArr['endTime'],
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Wallet payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 沃雷特支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function walletNotify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        $this->logger->addInfo('Wallet payment notify data:', $getDatas);

        return $response;
    }

    /**
     * 金海哲支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function jinhaizheCallback(Request $request, Response $response, $args)
    {
        $jsonArr = $request->getQueryParams();

        if ($request->isPost()) {

            $jsonArr = $request->getParsedBody();

        }

        $retArr = json_decode($jsonArr['ret'], true);
        $msgArr = json_decode($jsonArr['msg'], true);

        $order_no = $msgArr['no'];
        $money    = sprintf("%.2f", $msgArr['money'] / 100);

        // 微信
        // {
        //     "sign": "Z/KNDcz+KX7HJ5RpCpJJRtoEzjkzeTvY1RsqMa/oV+sO4/Nwjr84Ndo16rmntdrtBf5RpIlQQipuJPZijDJ6l7cgJqnOMJMHfaBofxhoFAjRfFJ7/Yo98lioUqaCjN5Vs0Yz3K+QuP//jRmc5d/1ABNHjRfQVSnDZDV7ZV01BAM=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":1,\"orderDate\":\"2017-03-24 09:57:37\",\"no\":\"201703240956555425639\",\"payNo\":\"a6719c2b10a0484090eaa3b7d5caeb08\",\"remarks\":\"201703240956555425639\"}"
        // }

        // 支付宝
        // {
        //     "sign": "bXpbqRStmV3yVaev9/wEBfE+rMunBVRUWrcimR4ezzJCgvGYF/5fpUOmm0+FNaM52q+L9XBWefWoSGzH6CuS8DbrAzz4AWQvJamvgSROMnnrbMdv4zN5YIXSiinRXX3bRKvRrwjW36wmDbi15CoiPoqJOAUt3/a5OgIaI1fMQ5Y=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":1,\"orderDate\":\"2017-03-24 10:02:26\",\"no\":\"201703241001356260119\",\"payNo\":\"bbaedf3105414a55a266464a8d4fd126\",\"remarks\":\"201703241001356260119\"}"
        // }

        // 网银
        // {
        //     "sign": "IozqmouEFStlpBLhDxQGSSWdU8zkGctCBXlHXfBT/6p7p16lQ2YVVpIykzcSWN6R6FGirfj5FE//1WHWAtfsLlHDzXOXMIQrKiLG7wsRbWfMUw5V+Hi6QgzXvouhqsZMKTXd9XgJjCl2eKj2Pcz9m9qcqM1fwFQIc1s/4ScVjS0=",
        //     "ret": "{\"code\":\"1000\",\"msg\":\"SUCCESS\"}",
        //     "msg": "{\"money\":30,\"orderDate\":\"2017-03-24 10:14:28\",\"no\":\"201703241012472351231\",\"payNo\":\"99af71426f404db89e5a36f0c8265df2\",\"remarks\":\"201703241012472351231\"}"
        // }

        $this->logger->addInfo('Jinhaizhe payment callback data:', $jsonArr);

        if ($order_no) {

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = 'FAIL';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 7) {

                                $verify = Jinhaizhe::getInstance($conf)->verifySign($jsonArr);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($retArr['code'] == '1000' && $retArr['msg'] == 'SUCCESS') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $msgArr['payNo'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $msgArr['orderDate'];
                                $pay->remark          = json_encode($jsonArr);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 7) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $msgArr['orderDate'],
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Jinhaizhe payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'SUCCESS';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 金海哲支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function jinhaizheNotify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        if ($request->isPost()) {

            $getDatas = $request->getParsedBody();

        }

        $this->logger->addInfo('Jinhaizhe payment notify data:', $getDatas);

        return $response;
    }

    /**
     * 华仁支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function huarenCallback(Request $request, Response $response, $args)
    {
        $postDatas = file_get_contents('php://input');

        $getDatas = $request->getQueryParams();

        if (isset($getDatas['oid']) && $getDatas['oid']) {

            $this->logger->addInfo('Huaren payment callback data:', ['postData' => $postDatas, 'oid' => $getDatas['oid']]);

            $order_no = $getDatas['oid'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 8) {

                                $verify = Huaren::getInstance($conf)->verifySign(['data' => $postDatas]);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($verify['v_result'] == '2000') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $verify['v_orderid'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s');
                                $pay->remark          = json_encode($verify);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 8) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $pay->money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Huaren payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = '[{result:"ok"}]';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 华仁支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function huarenNotify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        /**
        {"oid":"201705032110112621012?EOlkmuKHH9CQopgdv8Q/Ha68DPWMf/GPFwTOnI5tTjzu4a/fqrOm8LfYN7MAYQTNxaoohzV/efMoncA8fsX4mDCKYEhI6eeSR9f3P2tEZf/V8lIY34FOU4ciNPP+7p7M7K7i2FVoUJBgbTxgPXhCzTDJXBrFU91Db3zKXvRehLw%3d@LpZFXRILLucksct2zyBt/tRYegsHrSPBw9a+ccs8SvpgUMQcCbjs2sIa7qbf5f7YVpjEP/ZQHtyh06I/XkNumx3fIIwy/gjVl0rrctm4dz7bdHAsMmm2wSfdpq97oIiNzqUSx8tFQoRzwi466KtGkgbYjvh7pNuFhkMWcllndSU%3d@f2xomilX1OOjRGENl0p+cNNmGCpa/8Nw7JFKu3TgS5hp2DsIUqwJVXoyPNAMifgWrDEAyevDJjY1QfGRGmBRI65ZvUBqgoD9hjjPwSG9CCHZV6dtJ1aDH7uHWXAySNavG9PL4iDFVvAKgW5JNf22Sh6Xa3AmRkCAXgGrzyq84BA%3d@KF/CiVfA1YxqYQ0b7eQ5j/JKMgWfkSvL8iTRyIM/8x/+0lBAxnhCinCE7ZMKgd6142p2kSG0K2dR9sdSKqN+fPpbVERYhFSlz/IY3sg/+dFkZTawAXSLBqRZpfANZ0DfPOuCq9HvEIRCRgIWOoOvktDUY2TVRk1jON84hh9WJYY%3d@W2t0zHLqzstQAxAlvGBedUFg6hrCpjn7ijG7RGhQqgzoY/S7aaCCPXNHuCH1SsNCPj0ukYXdEawpvHHMtaNJCekp1WhmGtJxYIPu2kKpbYFbkmTFjwBXMGPytIndpxzttGlaQfMIlBA/t3pSn6hikC6eiJa6WHjahXbH4/qKXmA%3d@rQQp4QVGkVFVb/Axyjk1G22FTSudHztknWptyv2cHbNC9PTlenQ3G1S10ZpmNzWNYVfNqsJJdLDjbAu0zmsshORpt0iould9d3FPOyrSG6GcgfQyndqyx1COnSOco1xAOeumEamyw+VZ3J5EQ8lcySAUZdjtlRcRqo/8d767Das%3d@RkxhCMTiidnCg07XOTpc1wVUtNlS4zkXjC/cUfqzslBgonyYktercVYw9ZObARW3sw1+ADJzJh5e4UIGcjShTI6GDsbtQ7NkGPj75nhs2vVa2X3x18apdtvUG1AZOV6wp31SREygHBhzZywXnsbtkFrUw/vQebYKfHpYqPoDzq8%3d@UOFhE2HenDS/dPX2ZhQ9679LFeD5A0z9dtt0i3kVkIcCrQ/ysiPUKvrPU7IddUM+FwS027zOBuOnKbsjVEUwawJ2f8OAriIdZmyvE3DHmnsLXClLZWSxYvCE5OP+muSjJk+WS5bW4mIu2EptSvuTBvujMigDkBwq/3smUiBTzq8%3d"}
         */

        $this->logger->addInfo('Huaren payment notify data:', $getDatas);

        $getdata = $getDatas['oid'];

        if ($getdata) {

            $order_no = substr($getdata, 0, 21);
            $signData = substr($getdata, 22);

            $pay = Pay::where('order_no', '=', $order_no)->first();

            if ($pay) {

                if ($pay->vendor_id && $pay->company_id) {

                    $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                    $company = Company::where('id', '=', $pay->company_id)->first();

                    if ($vendor && $company) {

                        $conf['parterNo']    = $vendor->no;
                        $conf['parterKey']   = $vendor->key;
                        $conf['callbackUrl'] = $vendor->callback_url;
                        $conf['notifyUrl']   = $vendor->notify_url;

                        $verify    = false;
                        $returnMsg = '';
                        $pay_type  = $vendor->pay_type;
                        if ($pay_type == 8) {
                            $verify = Huaren::getInstance($conf)->notifyDeCrypt(str_replace('%3d', '=', $signData));
                        }
                        if ($verify) {
                            if ($verify['v_result'] == '2000') {
                                if ($company->url) {
                                    return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');
                                } else {
                                    $response->getBody()->write('Url is blank, Please contact webmaster.');
                                }
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 荷包支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function hebaoCallback(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        /*
        {
        "merchant_code": "19556613",
        "notify_type": "back_notify",
        "order_no": "201705181627241562724",
        "order_amount": "0.01",
        "order_time": "2017-05-18 16:27:24",
        "return_params": "201705181627241562724",
        "trade_no": "3063662344010468",
        "trade_time": "2017-05-18 16:26:51",
        "trade_status": "success",
        "sign": "94c1adeaaf839b436fd2bf1a253daa13"
        }
         */

        $this->logger->addInfo('Hebao payment callback data:', $getDatas);

        if (isset($getDatas['order_no']) && $getDatas['order_no']) {

            $order_no = $getDatas['order_no'];
            $money    = $getDatas['order_amount'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor  = Vendor::where('id', '=', $pay->vendor_id)->first();
                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 9) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Hebao::getInstance($conf)->verifySign(['payType' => $pay->pay_type, 'data' => $getDatas]);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($getDatas['trade_status'] == 'success') {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $getDatas['trade_no'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = $getDatas['trade_time'];
                                $pay->remark          = json_encode($getDatas);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 9) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => $getDatas['trade_time'],
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Hebao payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        $returnMsg = 'success';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 荷包支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function hebaoNotify(Request $request, Response $response, $args)
    {
        $getDatas = $request->getQueryParams();

        $this->logger->addInfo('Hebao payment notify data:', $getDatas);

        return $response;
    }

    /**
     * 雅付支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function yafuCallback(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        // {
        //     "consumerNo": "20133",
        //     "merOrderNo": "201705240948199194821",
        //     "orderNo": "20170524094821497577",
        //     "orderStatus": "1",
        //     "payType": "0201",
        //     "sign": "D96884369BA444A12D62122AF8F222EE",
        //     "transAmt": "0.10",
        //     "version": "3.0"
        // }

        $this->logger->addInfo('Yafu payment callback data:', $returnData);

        if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {

            $order_no = $returnData['merOrderNo'];
            $money    = $returnData['transAmt'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 1) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Yafu::getInstance($conf)->verifySign($returnData);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($returnData['orderStatus'] == 1) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $returnData['orderNo'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s');
                                $pay->remark          = json_encode($returnData);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 1) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Yafu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        // $returnMsg = $company->url . '/success/index.html';
                                        $returnMsg = 'SUCCESS';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 雅付支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function yafuNotify(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        // {
        //     "merOrderNo": "201705241335086743510",
        //     "transAmt": "0.1",
        //     "orderStatus": "1",
        //     "orderNo": "20170524133510618905",
        //     "code": "",
        //     "sign": "DD596A752593771C74400B348B315A7C",
        //     "consumerNo": "20133"
        // }

        $this->logger->addInfo('Yafu payment notify data:', $returnData);

        $status = 1;

        if (isset($returnData['orderStatus']) && $returnData['orderStatus'] && $returnData['orderStatus'] == 1) {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($returnData['merOrderNo']) && $returnData['merOrderNo']) {

                $order_no = $returnData['merOrderNo'];

            }

            if ($order_no) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->company_id) {

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($company) {

                            if ($company->url) {

                                // header("Location: " . $company->url . '/success/index.html');
                                // exit();
                                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

                            } else {

                                $response->getBody()->write('Url is blank, Please contact webmaster.');
                            }
                        }
                    }
                }
            }

        } else {

            $response->getBody()->write('failure');
        }

        return $response;
    }

    /**
     * 立刻付支付回调
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function likefuCallback(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        // {
        //     "consumerNo": "20133",
        //     "merOrderNo": "201705240948199194821",
        //     "orderNo": "20170524094821497577",
        //     "orderStatus": "1",
        //     "payType": "0201",
        //     "sign": "D96884369BA444A12D62122AF8F222EE",
        //     "transAmt": "0.10",
        //     "version": "3.0"
        // }

        $this->logger->addInfo('Likefu payment callback data:', $returnData);

        if (isset($returnData['ordernumber']) && $returnData['ordernumber']) {

            $order_no = $returnData['ordernumber'];
            $money    = $returnData['paymoney'];

            // 加锁防止并发回调
            $redisLockey = 'redisLock:' . $order_no;
            $isLock      = $this->redisLock->lock($redisLockey, 120);

            if ($isLock) {

                $pay = Pay::where('order_no', '=', $order_no)->where('money', '=', $money)->first();

                if ($pay) {

                    if ($pay->vendor_id && $pay->company_id) {

                        $vendor = Vendor::where('id', '=', $pay->vendor_id)->first();

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($vendor && $company) {
                            // if ($vendor) {

                            $conf['parterNo']    = $vendor->no;
                            $conf['parterKey']   = $vendor->key;
                            $conf['callbackUrl'] = $vendor->callback_url;
                            $conf['notifyUrl']   = $vendor->notify_url;

                            $verify = false;

                            $returnMsg = '';

                            $pay_type = $vendor->pay_type;

                            if ($pay_type == 10) {

                                // payType 3 支付宝，2 微信，1 网银
                                $verify = Likefu::getInstance($conf)->verifySign($returnData);

                            }

                            if ($verify) {

                                $status = 1;

                                if ($returnData['orderstatus'] == 1) {

                                    $status = 0;

                                }

                                $pay->vendor_order_no = $returnData['sysnumber'];
                                $pay->status          = $status;
                                $pay->pay_datetime    = date('Y-m-d H:i:s');
                                $pay->remark          = json_encode($returnData);

                                $res = $pay->save();

                                if ($res) {

                                    if ($pay_type == 10) {

                                        // 自动入款
                                        if ($status == 0 && $pay->recharge_status == 0 && $company->is_autorecharge == 1 && !empty($company->autorecharge_url)) {
                                            $requestParams = [
                                                'account'      => $pay->user,
                                                'fee'          => $money,
                                                'orderNo'      => $order_no,
                                                'rechargeTime' => date('Y-m-d H:i:s'),
                                            ];
                                            $requestParams['sign'] = Utils::generateSignature($requestParams);
                                            $token                 = Resque::enqueue('default', AutoRechargeJob::class, ['rechargeUrl' => $company->autorecharge_url, 'requestParams' => $requestParams], true);
                                            if ($token) {
                                                // 1 已加入队列
                                                $pay->recharge_status = 1;
                                                $pay->recharge_msg    = '正在自动入款';
                                                $pay->queue_job_id    = $token;
                                                $pay->save();
                                                // 日志
                                                $this->logger->addInfo('Likefu payment auto recharge [resque job id=' . $token . '] data:', $requestParams);
                                            }
                                        }

                                        // Redis 解锁
                                        $this->redisLock->unlock($redisLockey);

                                        // $returnMsg = $company->url . '/success/index.html';
                                        $returnMsg = 'ok';

                                    }
                                }
                            }
                            $response->getBody()->write($returnMsg);
                        }
                    }
                }
            }
        }
        return $response;
    }

    /**
     * 立刻付支付通知
     * @param  Request  $request
     * @param  Response $response
     * @param  Array   $args
     * @return Response
     */
    public function likefuNotify(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();

        if ($request->isPost()) {

            $returnData = $request->getParsedBody();

        }

        // {
        //     "merOrderNo": "201705241335086743510",
        //     "transAmt": "0.1",
        //     "orderStatus": "1",
        //     "orderNo": "20170524133510618905",
        //     "code": "",
        //     "sign": "DD596A752593771C74400B348B315A7C",
        //     "consumerNo": "20133"
        // }

        $this->logger->addInfo('Likefu payment notify data:', $returnData);

        $status = 1;

        if (isset($returnData['orderstatus']) && $returnData['orderstatus'] && $returnData['orderstatus'] == 1) {

            $status = 0;

        }

        if ($status == 0) {

            $order_no = '';

            if (isset($returnData['ordernumber']) && $returnData['ordernumber']) {

                $order_no = $returnData['ordernumber'];

            }

            if ($order_no) {

                $pay = Pay::where('order_no', '=', $order_no)->first();

                if ($pay) {

                    if ($pay->company_id) {

                        $company = Company::where('id', '=', $pay->company_id)->first();

                        if ($company) {

                            if ($company->url) {

                                // header("Location: " . $company->url . '/success/index.html');
                                // exit();
                                return $response->withStatus(302)->withHeader('Location', $company->url . '/success/index.html');

                            } else {

                                $response->getBody()->write('Url is blank, Please contact webmaster.');
                            }
                        }
                    }
                }
            }

        } else {

            $response->getBody()->write('failure');
        }

        return $response;
    }

    /**
     * 获取支付平台支付方式
     * @param  Integer $vendor_id 支付平台ID
     * @return Array           支付方式数组
     */
    private function getVendorPaymentInfo($vendor_id)
    {

        $res    = [];
        $vendor = Vendor::where('id', '=', $vendor_id)->first();

        if ($vendor) {
            $conf                = [];
            $conf['parterNo']    = $vendor->no;
            $conf['parterKey']   = $vendor->key;
            $conf['callbackUrl'] = $vendor->callback_url;
            $conf['notifyUrl']   = $vendor->notify_url;

            // 1 雅付、2 闪付、3 讯宝、4 乐盈、5 自由付、6 沃雷特、7 金海哲、8 华仁、9 荷包、10 立刻付、 11 多多支付、12 金海哲(新)、13 仁信、14 天付宝、15 高通、16 雅付(新)、17 先行付、18 我付、19汇达、20 泽圣、21、新自由付 22、钱袋子 23、金阳 24、个人支付宝 25、旺富通 26、千应 27、优付 28、商码 29、恒辰 30、成沃 31、开联通 32、点付云 33、芯富 34、滕坤 35、天吉 36、众点 37、智能云支付 38、智能云支付2.0 39、喜付 40、艾付 41、Nong付 42、顺心付 43、米达 44、wpay 45、恒星闪付 46、众信 47、星捷 48、迅捷 49、云通 50、闪亿 51、恒通 52、Bingo 53、乐享 55、随意付 56、畅支付 57、银信 58、五福 59、Pppay 60、顺达通 60、顺达通 61、盖亚 62、ylsl
            if ($vendor->pay_type == 1) {

                $res = Yafu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 2) {

                $res = Shanfu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 3) {

                $res = Xunbao::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 4) {

                $res = Leying::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 5) {

                $res = Ziyoufu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 6) {

                $res = Wallet::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 7) {

                $res = Jinhaizhe::getInstance($conf)->getPayType();

            } elseif ($vendor->pay_type == 8) {

                $res = Huaren::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 9) {

                $res = Hebao::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 10) {

                $res = Likefu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 11) {

                $res = Duoduo::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 12) {

                $res = JinhaizheNew::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 13) {

                $res = Renxin::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 14) {

                $res = Tianfubao::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 15) {

                $res = Gaotong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 16) {

                $res = YafuNew::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 17) {

                $res = Xianxingfu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 18) {

                $res = Wofu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 19) {

                $res = Huida::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 20) {

                $res = Zesheng::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 21) {

                $res = ZiyoufuNew::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 22) {

                $res = Qiandai::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 23) {

                $res = Jinyang::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 24) {

                $res = SingleQrPay::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 25) {

                $res = Wangfutong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 26) {

                $res = Qianying::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 27) {

                $res = Youfu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 28) {

                $res = Shangma::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 29) {

                $res = Hengchen::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 30) {

                $res = Chengwo::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 31) {

                $res = Kailiantong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 32) {

                $res = Dianfuyun::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 33) {

                $res = Xinfu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 34) {

                $res = Tengkun::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 35) {

                $res = Tianji::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 36) {

                $res = Zhongdian::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 37) {

                $res = Yunzhifu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 38) {

                $res = Yunzhifu2::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 39) {

                $res = Xifu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 40) {

                $res = Aifu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 41) {

                $res = Nongfu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 42) {

                $res = Shunxin::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 43) {

                $res = Mida::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 44) {

                $res = Wpay::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 45) {

                $res = Hengxing::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 46) {

                $res = Zhongxin::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 47) {

                $res = Xingjie::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 48) {

                $res = Xunjie::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 49) {

                $res = Yuntong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 50) {

                $res = Shanyi::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 51) {

                $res = Hengtong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 52) {

                $res = Bingo::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 53) {

                $res = Lexiang::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 55) {

                $res = Suiyifu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 56) {

                $res = Changzhifu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 57) {

                $res = Yinxin::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 58) {

                $res = Wufu::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 59) {

                $res = Pppay::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 60) {

                $res = Shundatong::getInstance()->getPayType();

            } elseif ($vendor->pay_type == 61) {

                $res = Gaiya::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 62) {

                $res = Ylsl::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 63) {

                $res = Af::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 64) {

                $res = Az::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 65) {

                $res = Baisheng::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 66) {

                $res = FhPay::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 67) {

                $res = JrsjPay::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 68) {

                $res = VPay::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 69) {

                $res = Gft::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 70) {

                $res = Ls::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 71) {

                $res = Gefu::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 72) {

                $res = Bh::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 73) {

                $res = Cy::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 74) {

                $res = Nhh::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 75) {

                $res = Shanrong::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 76) {

                $res = Zhongxinnew::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 77) {

                $res = Daniu::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 78) {

                $res = Yf::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 79) {

                $res = Wande::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 80) {

                $res = Haitu::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 81) {

                $res = Ly::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 82) {

                $res = Aibide::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 83) {

                $res = WenPay::getInstance()->getPayType();
            } elseif ($vendor->pay_type == 84) {

                $res = WoPay::getInstance()->getPayType();
            }
        }
        return ['payType' => $vendor->pay_type, 'data' => $res];
    }

    public function bbCheck($post)
    {
        $bbKey = '70D5886E6E6CC3CE223340FCC1C9D28B';

        $params = [
            'number'     => $post['number'],
            'order_id'   => $post['order_id'],
            'amount'     => $post['amount'],
            'created_at' => $post['created_at'],
            'username'   => $post['username'],
            'notify_url' => $post['notify_url'],
            'method_id'  => $post['method_id'],
            'bank_id'    => $post['bank_id'],
        ];

        $encodeArr = [];

        foreach ($params as $k => $v) {
            $encodeArr[$k] = $v;
        }

        ksort($encodeArr);
        $encodeArr['key'] = $bbKey;
        $signStr          = strtoupper(md5(urldecode(http_build_query($encodeArr))));

        if ($signStr != $post['sign']) {
            echo '验签失败';
            exit;
        }
    }

}
