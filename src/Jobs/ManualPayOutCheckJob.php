<?php

namespace Weiming\Jobs;

use Weiming\Jobs\BaseJob;
use Weiming\Libs\AgencyPayments\Aifu;
use Weiming\Libs\AgencyPayments\Bingo;
use Weiming\Libs\AgencyPayments\Chuanhua;
use Weiming\Libs\AgencyPayments\Duobao;
use Weiming\Libs\AgencyPayments\Gaiya;
use Weiming\Libs\AgencyPayments\Gft;
use Weiming\Libs\AgencyPayments\GPpay;
use Weiming\Libs\AgencyPayments\Jiayoutong;
use Weiming\Libs\AgencyPayments\Jinhaizhe;
use Weiming\Libs\AgencyPayments\Jiyun;
use Weiming\Libs\AgencyPayments\KaiLianTong;
use Weiming\Libs\AgencyPayments\Nongfu;
use Weiming\Libs\AgencyPayments\Qingying;
use Weiming\Libs\AgencyPayments\RHPay;
use Weiming\Libs\AgencyPayments\Shangma;
use Weiming\Libs\AgencyPayments\Shunxin;
use Weiming\Libs\AgencyPayments\Tianfubao;
use Weiming\Libs\AgencyPayments\Xianfeng;
use Weiming\Libs\AgencyPayments\Xifu;
use Weiming\Libs\AgencyPayments\Xinxinju;
use Weiming\Libs\AgencyPayments\Xunjie;
use Weiming\Libs\AgencyPayments\Yafu;
use Weiming\Libs\AgencyPayments\Yibao;
use Weiming\Libs\AgencyPayments\Zesheng;
use Weiming\Libs\AgencyPayments\Zhongdian;
use Weiming\Libs\AgencyPayments\Zhongxin;
use Weiming\Models\Platform;
use Weiming\Models\Withdrawal;

/**
 * 自动出款自动审核处理 Job
 */
class ManualPayOutCheckJob extends BaseJob
{
    /**
     * 当前出款平台
     * @var null
     */
    private $payOutObj = null;

    /**
     * 当前出款单
     * @var null
     */
    private $payOut = null;

    /**
     * 当前出款平台信息
     * @var null
     */
    private $platform = null;

    /**
     * 爬虫http接口地址
     * @var String
     */
    private $crawlerUrl;

    /**
     * 全局配置
     * @var Array
     */
    private $config;

    public function perform()
    {
        /**
         * 提交数据格式
         * Array
         * (
         *     [orderNo] => 201703032107434712126
         * )
         */
        $orderNo          = $this->args['orderNo'];
        $this->crawlerUrl = 'http://' . $this->settings['crawler']['host'] . ':' . $this->settings['crawler']['port'];
        $this->payOut     = Withdrawal::where('order_no', '=', $orderNo)->first();
        $this->platform   = Platform::whereRaw("`id` = {$this->payOut->platform_id}")->first();
        $payOutType       = $this->platform->pay_out_type;
        $this->config     = ['parterNo' => $this->platform->no, 'parterKey' => $this->platform->key];
        if ($payOutType == 1) {
            $this->doTianfubao();
        } elseif ($payOutType == 2) {
            $this->doYafu();
        } elseif ($payOutType == 3) {
            $this->doJinhaizhe();
        } elseif ($payOutType == 4) {
            $this->doZesheng();
        } elseif ($payOutType == 5) {
            $this->doChuanhua();
        } elseif ($payOutType == 6) {
            $this->doKaiLianTong();
        } elseif ($payOutType == 7) {
            $this->doZhongdian();
        } elseif ($payOutType == 8) {
            $this->doShangma();
        } elseif ($payOutType == 9) {
            $this->doXifu();
        } elseif ($payOutType == 10) {
            $this->doAifu();
        } elseif ($payOutType == 11) {
            $this->doNongfu();
        } elseif ($payOutType == 12) {
            $this->doShunxin();
        } elseif ($payOutType == 14) {
            $this->doXunjie();
        } elseif ($payOutType == 15) {
            $this->doDuobao();
        } elseif ($payOutType == 16) {
            $this->doBingo();
        } elseif ($payOutType == 17) {
            $this->doYibao();
        } elseif ($payOutType == 18) {
            $this->doXinxinju();
        } elseif ($payOutType == 19) {
            $this->doJiayoutong();
        } elseif ($payOutType == 20) {
            $this->doGaiya();
        } elseif ($payOutType == 21) {
            $this->doQingying();
        } elseif ($payOutType == 22) {
            $this->doJiyun();
        } elseif ($payOutType == 23) {
            $this->doRHPay();
        } elseif ($payOutType == 24) {
            $this->doGft();
        } elseif ($payOutType == 25) {
            $this->doZhongxin();
        } elseif ($payOutType == 26) {
            $this->doGPpay();
        } elseif ($payOutType == 27) {
            $this->doXianfeng();
        }
    }

    /**
     * 先锋出款
     */
    private function doXianfeng()
    {
        // 处理中，反查一次代付结果

        $result = Xianfeng::getInstance($this->config)->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();
        $config['orderNo'] = $this->args['orderNo'];
        $this->logger->addInfo('Xianfeng payment data:', $config);

        $queryStatus  = $result['RSPCOD'];
        $resultFlag   = $result['STATE'] ?? ''; //结果代码
        $platform_msg = $result['RSPMSG'] ?? ''; //结果消息

        // 统一状态
        if ($queryStatus == '000000') {
            $platform_status = 2;
            if ($resultFlag == '1') {
                $platform_status = 1;
            } elseif ($resultFlag == '2') {
                $platform_status = 2;
            } elseif ($resultFlag == '3') {
                $platform_status = 3;
            } else {
                $platform_status = 5;
            }
        }

        $this->payOut->status = $platform_status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note   = $platform_msg;
        $this->payOut->save();
    }

    /**
     * GPpay出款
     */
    private function doGPpay()
    {
        // 处理中，反查一次代付结果
        $payOutObj    = GPpay::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $resultFlag   = $result['result'] ?? ''; //结果代码
        $platform_msg = $result['result_msg'] ?? ''; //结果消息

        // 统一状态
        $platform_status = 2;
        if ($resultFlag == 'S') {
            $platform_status = 1;
        } elseif ($resultFlag == 'F') {
            $platform_status = 3;
        } else {
            $platform_status = 5;
        }

        $this->payOut->status = $platform_status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note   = $platform_msg;
        $this->payOut->save();
    }

    /**
     * 天付宝出款
     */
    private function doTianfubao()
    {
        // 处理中，反查一次代付结果
        $payOutObj    = Tianfubao::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $tfb_serialno   = $payOutStatus['tfb_serialno'] ?? '';
        $serialno_state = $payOutStatus['serialno_state'] ?? 2;
        $serialno_desc  = $payOutStatus['serialno_desc'] ?? '';

        // 统一状态
        if ($serialno_state == 0) {
            $serialno_state = 0;
        } elseif ($serialno_state == 1) {
            $serialno_state = 1;
        } elseif ($serialno_state == 2) {
            $serialno_state = 2;
        } elseif ($serialno_state == 3) {
            $serialno_state = 3;
        } elseif ($serialno_state == 4) {
            $serialno_state = 4;
        } else {
            $serialno_state = 5;
        }

        $this->payOut->platform_order_no = $tfb_serialno;
        $this->payOut->status            = $serialno_state; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $serialno_desc;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 雅付出款
     */
    private function doYafu()
    {
        $payOutObj    = Yafu::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'merOrderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $tmpStatus = [
            0 => '未处理',
            1 => '成功',
            2 => '已取消',
            3 => '提现失败',
            4 => '提现处理中',
            5 => '部分成功',
        ];

        $orderNo = $payOutStatus['casOrdNo'] ?? '';
        $code    = $payOutStatus['orderStatus'] ?? 4;
        $msg     = $tmpStatus[$code] ?? '未知';

        // 统一状态
        if ($code == 0) {
            $code = 0;
        } elseif ($code == 1) {
            $code = 1;
        } elseif ($code == 2) {
            $code = 4;
        } elseif ($code == 3) {
            $code = 3;
        } elseif ($code == 4) {
            $code = 2;
        } else {
            $code = 5;
        }

        $this->payOut->platform_order_no = $orderNo;
        $this->payOut->status            = $code; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 金海哲出款
     */
    private function doJinhaizhe()
    {
        $payOutObj    = Jinhaizhe::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $sy_request_no = $payOutStatus['sy_request_no'] ?? '';
        $status        = $payOutStatus['status'] ?? 1;
        $msg           = $payOutStatus['origData']['retMsg'] ?? '未知';

        // 统一状态
        if ($status == 0) {
            $status = 0;
        } elseif ($status == 1) {
            $status = 2;
        } elseif ($status == 2) {
            $status = 1;
        } elseif ($status == 3) {
            $status = 3;
        } elseif ($status == 4) {
            $status = 4;
        } else {
            $status = 5;
        }

        $this->payOut->platform_order_no = $sy_request_no;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 泽圣出款
     */
    private function doZesheng()
    {
        // 处理中，反查一次代付结果
        $payOutObj    = Zesheng::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $orderId = $payOutStatus['orderId'] ?? '';
        $status  = $payOutStatus['state'] ?? '01';
        $msg     = $payOutStatus['errorMsg'] ?? ($payOutStatus['origData']['msg'] ?? '未知');

        // 统一状态
        if ($status == '00') {
            $status = 1;
        } elseif ($status == '01' || $status == '90') {
            $status = 2;
        } elseif ($status == '02') {
            $status = 3;
        } else {
            $status = 5;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 传化出款
     */
    private function doChuanhua()
    {
        // 处理中，10秒后反查一次代付结果
        $payOutObj    = Chuanhua::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $status  = $payOutStatus['result'] ?? '';
        $msg     = $payOutStatus['msg'] ?? '';
        $state   = $payOutStatus['data']['status'] ?? '';
        $orderId = $payOutStatus['data']['businessrecordnumber'] ?? '';

        // 统一状态
        if (strpos($state, '成功') !== false) {
            $status = 1;
        } elseif (strpos($state, '处理中') !== false) {
            $status = 2;
        } elseif (strpos($state, '失败') !== false) {
            $status = 3;
        } elseif (strpos($state, '已退票') !== false) {
            $status = 4;
        } else {
            $status = 5;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $state;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 开联通出款
     */
    private function doKaiLianTong()
    {
        $payOutObj    = KaiLianTong::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $code   = $payOutStatus['response']['envelope']['body']['responseCode'] ?? '';
        $status = $payOutStatus['response']['envelope']['body']['status'] ?? '';
        $msg    = $payOutStatus['response']['envelope']['body']['responseMsg'] ?? '';

        // 统一状态，非法交易 INVALID、已受理 CREATED、交易中 TX_BEGIN、交易成功 TX_SUCCESS、交易失败 TX_FAIL、关闭 CLOSED
        if ($status == 'TX_SUCCESS') {
            $status = 1;
        } elseif ($status == 'CREATED' || $status == 'TX_BEGIN') {
            $status = 2;
        } elseif ($status == 'TX_FAIL') {
            $status = 3;
        } elseif ($status == 'INVALID') {
            $status = 5;
        }
        // PS: 第三方说关闭CLOSED状态不用管

        $this->payOut->platform_order_no = 'R' . date('YmdHis') . rand(1000, 9999);
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 众点出款
     */
    private function doZhongdian()
    {
        $payOutObj    = Zhongdian::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $payOutStatus = $result;
        $code         = $result['result_code'] ?? '';
        $status       = $result['result_code'] ?? '';
        $msg          = $result['result_msg'] ?? '';
        $orderId      = $result['transaction_id'] ?? '';

        // 统一状态，0 成功、非0失败
        if ($status == 0) {
            $status = 1;
        } else {
            $status = 5;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 成功、非0失败
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 商码付出款
     */
    private function doShangma()
    {
        $payOutObj = Shangma::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $resultCode = $result['resultCode'] ?? '01';
        $resultMsg  = $result['resultMsg'] ?? '';
        $orderNo    = $result['outTradeNo'] ?? '';
        $returnCode = $result['returnCode'] ?? -1;

        // 统一状态
        if ($returnCode == 0) {
            $returnCode = 0;
        } elseif ($returnCode == 1) {
            $returnCode = 2;
        } elseif ($returnCode == 2) {
            $returnCode = 1;
        } elseif ($returnCode == -1) {
            $returnCode = 3;
        } elseif ($returnCode == -2) {
            $returnCode = 5;
        }

        $this->payOut->platform_order_no = $orderNo;
        $this->payOut->status            = $returnCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $resultMsg;
        $this->payOut->remark            = json_encode($result);
        $this->payOut->save();
    }

    /**
     * 喜付出款
     */
    private function doXifu()
    {
        $orderNo   = $this->args['orderNo'];
        $payOutObj = Xifu::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $orderNo,
        ], 'query')->sendRequest();

        $resultCode = $result['respCode'] ?? 'F9999';
        $resultMsg  = $result['respMessage'] ?? '';
        $returnCode = [];

        if (isset($result['batchNo']) && isset($result['batchContent']) && $resultCode == 'S0001') {
            $orderNo    = $result['batchNo'] ?? '';
            $returnCode = $result['batchContent'] ?? '';
            $returnCode = explode(',', $returnCode);
            $resultMsg  = empty($returnCode[13]) ? $returnCode[12] : $returnCode[13];
        }

        // 统一状态
        if ($resultCode == 'S0001' && strpos($returnCode[12], 'null') !== false) {
            $resultCode = 2;
        } elseif ($resultCode == 'S0001' && strpos($returnCode[12], '成功') !== false) {
            $resultCode = 1;
        } elseif ($resultCode == 'S0001' && strpos($returnCode[12], '失败') !== false) {
            $resultCode = 3;
        } else {
            $resultCode = 5;
        }

        $this->payOut->platform_order_no = 'WM' . $orderNo;
        $this->payOut->status            = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $resultMsg;
        $this->payOut->remark            = json_encode($result);
        $this->payOut->save();
    }

    /**
     * 艾付出款
     */
    private function doAifu()
    {
        $payOutObj = Aifu::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $resultCode = $result['result_code'] ?? '';
        $resultMsg  = $result['result_msg'] ?? '';
        $resultFlag = $result['result'] ?? 'H';
        $orderNo    = $result['order_no'] ?? '';

        // 统一状态
        if ($resultCode == '000000') {
            if ($resultFlag == 'H') {
                $resultCode = 2;
            } elseif ($resultFlag == 'F') {
                $resultCode = 3;
            } elseif ($resultFlag == 'S') {
                $resultCode = 1;
            } else {
                $resultCode = 5;
            }
        } else {
            $resultCode = 3;
        }

        $this->payOut->platform_order_no = $orderNo;
        $this->payOut->status            = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $resultMsg;
        $this->payOut->remark            = json_encode($result);
        $this->payOut->save();
    }

    /**
     * Nong付出款
     */
    private function doNongfu()
    {
        $payOutObj = Nongfu::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $resultCode = $result['status'] ?? '1002';
        $resultMsg  = $result['msg'] ?? '';
        $resultFlag = $result['success'] ?? 0;
        $orderNo    = $result['merOrderId'] ?? '';

        // 统一状态
        if ($resultFlag == 1) {
            if ($resultCode == '1000') {
                $resultCode = 0;
            } elseif ($resultCode == '1002') {
                $resultCode = 3;
            } elseif ($resultCode == '1001') {
                $resultCode = 1;
            } elseif ($resultCode == '1111') {
                $resultCode = 2;
            } else {
                $resultCode = 5;
            }
        } else {
            $resultCode = 3;
        }

        if (isset($result['statusDesc']) && $result['statusDesc']) {
            $resultMsg = $result['statusDesc'];
        }

        $this->payOut->platform_order_no = 'WM' . $orderNo;
        $this->payOut->status            = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $resultMsg;
        $this->payOut->remark            = json_encode($result);
        $this->payOut->save();
    }

    /**
     * 顺心付出款
     */
    private function doShunxin()
    {
        $payOutObj = Shunxin::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $resultCode = $result['Status'] ?? 2;
        $resultMsg  = $result['respDesc'] ?? '';
        $resultFlag = $result['respCode'] ?? '05';
        $orderNo    = $result['batchNo'] ?? '';

        // 统一状态
        if ($resultFlag == '00') {
            if ($resultCode == 0) {
                $resultCode = 2;
            } elseif ($resultCode == 1) {
                $resultCode = 1;
            } elseif ($resultCode == 2) {
                $resultCode = 3;
            } elseif ($resultCode == 5) {
                $resultCode = 2;
            } else {
                $resultCode = 5;
            }
        } else {
            $resultCode = 3;
        }

        $this->payOut->platform_order_no = $orderNo;
        $this->payOut->status            = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $resultMsg;
        $this->payOut->remark            = json_encode($result);
        $this->payOut->save();
    }

    /**
     * 迅捷付出款
     */
    private function doXunjie()
    {
        $payOutObj = Xunjie::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode   = $result['respCode'] ?? '';
        $resultMsg  = $result['oRespDesc'] ?? '';
        $resultFlag = $result['oRespCode'] ?? '';
        $orderNo    = $result['serialId'] ?? '';

        // 统一状态
        $resultCode = 3;
        if ($resultFlag == '0000' && $respCode == '0000') {
            $resultCode = 1;
        } elseif ($resultFlag == 'P000' || $resultFlag == 'P999') {
            $resultCode = 2;
        } elseif ($resultFlag == '9996') {
            $resultCode = 4;
        } elseif ($resultFlag == '9998') {
            $resultCode = 3;
        } elseif ($resultFlag == '9999') {
            $resultCode = 5;
        }

        // $this->payOut->platform_order_no = $orderNo;
        $this->payOut->status = $resultCode; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note   = $resultMsg;
        $this->payOut->remark = json_encode($result);
        $this->payOut->save();
    }

    /**
     * 多宝出款
     */
    private function doDuobao()
    {
        $payOutObj    = Duobao::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->payOut->platform_order_no, // 这里要用流水号查询
        ], 'query')->sendRequest();

        $status  = $payOutStatus['status'] ?? 'fail';
        $msg     = $payOutStatus['msg'] ?? '';
        $state   = $payOutStatus['settlestatus'] ?? '';
        $orderId = $payOutStatus['settleid'] ?? '';

        // 统一状态
        $status = 3;
        if (strpos($state, '已支付') !== false) {
            $status = 1;
        } elseif (strpos($state, '支付中') !== false) {
            $status = 2;
        } elseif (strpos($state, '已拒绝') !== false) {
            $status = 4;
        } elseif (strpos($state, '代付失败') !== false) {
            $status = 3;
        } elseif (strpos($state, '审核中') !== false) {
            $status = 0;
        } else {
            $status = 5;
        }

        // $this->payOut->platform_order_no = $orderId;
        $this->payOut->status = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note   = $msg;
        $this->payOut->remark = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * Bingo出款
     */
    private function doBingo()
    {
        $payOutObj    = Bingo::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['respCode'] ?? '';
        $msg      = $payOutStatus['msg'] ?? '';
        $state    = $payOutStatus['status'] ?? '';
        $orderId  = $payOutStatus['plat_order_sn'] ?? '';

        // 统一状态
        $status = 5;
        $msg    = '请求失败';
        if ($respCode == '00') {
            $status = 3;
            $msg    = '处理失败';
            if ($state == 3) {
                $status = 1;
                $msg    = '处理成功';
            } elseif ($state == 1) {
                $status = 2;
                $msg    = '处理中';
            }
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * Yibao出款
     */
    private function doYibao()
    {
        $payOutObj    = Yibao::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['ret_Code'] ?? '';
        $msg      = $payOutStatus['fail_Desc'] ?? ($payOutStatus['error_Msg'] ?? '');
        $state    = $payOutStatus['r1_Code'] ?? '';
        $orderId  = '';

        // 统一状态
        $status = 3;
        if ($respCode == 1) {
            $status = 2;
            if ($state == '0026') {
                $status = 1;
            } elseif ($state == '0027') {
                $status = 4;
            } elseif ($state == '0028') {
                $status = 3;
            }
        }

        // $this->payOut->platform_order_no = $orderId;
        $this->payOut->status = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note   = $msg;
        $this->payOut->remark = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 新欣聚出款
     */
    private function doXinxinju()
    {
        $payOutObj    = Xinxinju::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['field039'] ?? '';
        $msg      = $payOutStatus['field124'] ?? '';
        $orderId  = $payOutStatus['field062'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == '00') {
            $status = 1;
        } elseif ($respCode == '01') {
            $status = 0;
        } elseif ($respCode == '02') {
            $status = 2;
        } elseif ($respCode == '03') {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 佳友通出款
     */
    private function doJiayoutong()
    {
        // 代付下发
        $payOutObj = Jiayoutong::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();
        if ($result) {
            $ret_Code          = $result['code'] ?? '';
            $resStatus         = $result['status'] ?? '';
            $platform_order_no = $result['businessNo'] ?? '';
            $msg               = $result['describe'] ?? '';

            // 统一状态
            $status = 3;
            if ($ret_Code == '00') {
                if ($resStatus == 1) {
                    $status = 1;
                } elseif ($resStatus == 2) {
                    $status = 3;
                } elseif ($resStatus == 4) {
                    $status = 2;
                } elseif ($resStatus == 5) {
                    $status = 4;
                }
            }

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($result);
            $this->payOut->save();
        }
    }

    /**
     * 盖亚出款
     */
    private function doGaiya()
    {
        // 代付下发
        $payOutObj = Gaiya::getInstance($this->config);
        $result    = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();
        if ($result) {
            $ret_Code          = $result['code'] ?? '';
            $resStatus         = $result['transStatus'] ?? '';
            $platform_order_no = $result['merOrderId'] ?? '';
            $msg               = $result['respMsg'] ?? '';

            // 统一状态
            $status = 3;
            if ($state == 1) {
                $status = 1;
            } elseif ($state == 2) {
                $status = 3;
            } elseif ($state == 3) {
                $status = 2;
            }

            $this->payOut->platform_order_no = $platform_order_no;
            $this->payOut->platform_status   = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
            $this->payOut->remark            = $msg;
            $this->payOut->platform_attach   = json_encode($result);
            $this->payOut->save();
        }
    }

    /**
     * 青英
     */
    private function doQingying()
    {
        $payOutObj    = Qingying::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['field039'] ?? '';
        $msg      = $payOutStatus['field124'] ?? '';
        $orderId  = $payOutStatus['field062'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == '00') {
            $status = 1;
        } elseif ($respCode == '01') {
            $status = 0;
        } elseif ($respCode == '02') {
            $status = 2;
        } elseif ($respCode == '03') {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * 极云
     */
    private function doJiyun()
    {
        $payOutObj    = Jiyun::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo' => $this->args['orderNo'],
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['field039'] ?? '';
        $msg      = $payOutStatus['field124'] ?? '';
        $orderId  = $payOutStatus['field062'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == '00') {
            $status = 1;
        } elseif ($respCode == '01') {
            $status = 0;
        } elseif ($respCode == '02') {
            $status = 2;
        } elseif ($respCode == '03') {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /**
     * RHPay
     */
    private function doRHPay()
    {
        $payOutObj    = RHPay::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo'           => $this->args['orderNo'],
            'platform_order_no' => $this->payOut->platform_order_no,
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['status'] ?? '';
        $msg      = $payOutStatus['msg'] ?? '';
        $orderId  = $payOutStatus['orderCode'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == 'M1000') {
            $status = 1;
        } elseif ($respCode == 'M1001') {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    //广付通
    private function doGft()
    {
        $payOutObj    = Gft::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo'           => $this->args['orderNo'],
            'platform_order_no' => $this->payOut->platform_order_no,
        ], 'query')->sendRequest();

        $respCode = $payOutStatus['data']['status'] ?? '';
        $msg      = $payOutStatus['data']['statusStr'] ?? '';
        $orderId  = $payOutStatus['data']['mchOrderNo'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == 2) {
            $status = 1;
        } elseif ($respCode == 'M1001') {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

    /*
     * 众鑫出款
     */
    private function doZhongxin()
    {
        $payOutObj    = Zhongxin::getInstance($this->config);
        $payOutStatus = $payOutObj->generateSignature([
            'orderNo'           => $this->args['orderNo'],
            'platform_order_no' => $this->payOut->platform_order_no,
        ], 'query')->sendRequest();

        $respCode   = $payOutStatus['data']['code'] ?? '';
        $respStatus = $payOutStatus['data']['status'] ?? '';
        $msg        = $payOutStatus['data']['statusStr'] ?? '';
        $orderId    = $payOutStatus['data']['mchOrderNo'] ?? '';

        // 统一状态
        $status = 2;
        if ($respCode == 00) {
            $status = 2;
            if ($respStatus == 0) {
                $status = 0;
            } elseif ($respStatus == 1) {
                $status = 5;
            } elseif ($respStatus == 2) {
                $status = 2;
            } elseif ($respStatus == 3) {
                $status = 3;
            } elseif ($respStatus == 4) {
                $status = 1;
            }
        } else {
            $status = 3;
        }

        $this->payOut->platform_order_no = $orderId;
        $this->payOut->status            = $status; // 0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他
        $this->payOut->note              = $msg;
        $this->payOut->remark            = json_encode($payOutStatus);
        $this->payOut->save();
    }

}
