(function () {
    app.controller("payOutRemarkEdit", [
        "$scope",
        "http",
        "$rootScope",
        function (scope, http, rootScope) {
            scope.list = angular.copy(scope.ngDialogData);
            scope.check = function () {
                if (scope.list.remark.length > 50) {
                    scope.error = true;
                    scope.errorMsg = "备注过长，请缩减至50字以内";
                    return false;
                }
                return true;
            };

            scope.sub = function () {
                if (scope.check()) {
                    http.post(
                        "/admin/updateRemark/" + scope.list.wid,
                        {
                            content: scope.list.remark
                        },
                        function (res) {
                            if (res.status == 0) {
                                scope.closeThisDialog(res);
                            } else {
                                scope.error = true;
                                scope.errorMsg = "网络错误";
                            }
                        }
                    );
                }
            };
        }
    ]);
    app.controller("payOutStatusDialog", [
        "$scope",
        function (scope) {
            scope.origData = angular.copy(scope.ngDialogData.origData);
            scope.keyNameList={
                1:{
                    retcode:"结果",
                    retmsg:"结果描述",
                    spid:"商户号",
                    sp_serialno:"商户代付单号",
                    tfb_serialno:"天付宝代付单号",
                    serialno_state:"代付状态",
                    statusList:{
                        1:"处理成功",
                        2:"处理中",
                        3:"处理失败",
                        4:"已退汇"
                    },
                    serialno_desc:"代付状态描述",
                    tran_amt:"总金额（分）",
                    //cur_type:"金额类型",
                    acct_name:"收款人姓名",
                    acct_id:"收款人账号",
                    mobile:"收款人手机",
                    bank_name:"开户行",
                    bank_settle_no:"银联机构号",
                    bank_branch_name:"支行名称",
                    business_type:"业务类型",
                    business_no:"业务号码",
                    memo:"摘要",
                    sp_batch_no:"商户批次号",
                    tfb_rsptime:"响应时间",
                    sign:"签名"
                },
                2:{
                    code:"返回编码",
                    msg:"返回文本提示",
                    consumerNo:"商户号",
                    orderStatus:"代付结果   ",
                    statusList:{
                        0:"未处理",
                        1:"成功",
                        2:"已取消",
                        3:"提现失败",
                        4:"提现处理中",
                        5:"部分成功"
                    },
                    casOrdNo:"平台对应代付订单号",
                    merOrderNo:"商户对应代付批次号",
                    transAmt:"代付提交金额",
                    transTime:"代付提交时间",
                    feeAmt:"手续费",
                    settleAmt:"结算金额",
                    sign:"签名"
                },
                3:{
                    retCode:"结果",
                    retMsg:"结果描述",
                    merchant_no:"商户号",
                    request_no:"商户代付单号",
                    sy_request_no:"金海哲代付单号",
                    status:"订单状态",
                    amount:"总金额(分)",
                    statusList:{
                        1:"处理中",
                        2:"处理成功",
                        3:"处理失败",
                        4:"已退汇"
                    },
                    cur_type:"金额类型",
                    acct_name:"收款人姓名",
                    acct_id:"收款人账号",
                    mobile:"收款人手机号码",
                    bank_name:"开户行名称",
                    bank_settle_no:"开户行银联机构号",
                    bank_branch_name:"支行名称",
                    business_no:"业务号码",
                    memo:"摘要",
                    tfb_rsptime:"响应时间",
                    sign:"关键参数签名"
                },
                4:{
                    merchantCode:"商户号",
                    outOrderId:"商户订单号",
                    orderId:"付款订单号",
                    state:"付款状态",
                    statusList:{
                        "00":"付款成功",
                        "01":"付款处理中",
                        "90":"付款处理中",
                        "02":"付款失败"
                    },
                    transTime:"交易时间",
                    totalAmount:"金额(分)",
                    fee:"手续费(分)",
                    errorMsg:"错误信息",
                    sign:"签名"
                },
                5:{
                    result:"返回结果",
                    code:"返回代码",
                    msg:"返回信息",
                    count:"返回数据数量",
                    status:"状态",
                    statusList:{
                        "失败":"失败",
                        "处理中":"处理中",
                        "成功":"成功",
                        "已退票":"已退票"
                    },
                    //失败/处理中/成功/已退票
                    businessrecordnumber:"支付流水号"
                },
                6:{
                    amount:"金额(分)",
                    charset:"字符编码",
                    mchtId:"商户号",
                    mchtOrderNo:"商户单号",
                    orderDesc:"描述",
                    responseCode:"返回码",
                    responseMsg:"返回消息",
                    status:"状态",
                    statusList:{
                        "INVALID":"非法交易",
                        "CREATED":"已受理",
                        "TX_BEGIN":"交易中",
                        "TX_SUCCESS":"交易成功",
                        "TX_FAIL":"交易失败",
                        "CLOSED":"关闭",
                    },
                    version:"版本号"
                },
                7:{
                    attach:"附加信息",
                    err_code:"错误代码",
                    err_msg:"错误描述",
                    fee_type:"币种",
                    mch_id:"商户号",
                    nonce_str:"随机字符",
                    out_trade_no:"商户单号",
                    result_code:"业务结果",
                    sign:"签名",
                    total_fee:"金额(分)",
                    trade_state:"交易状态",
                    trade_type:"交易类型",
                    transaction_id:"交易单号",
                    statusList:{
                        0:"成功",
                        1:"失败"
                    }
                },
                8:{
                    resultCode:"返回码",
                    resultMsg:"返回消息",
                    outTradeNo:"单号",
                    amount:"金额(分)",
                    returnCode:"状态",
                    statusList:{
                        "0":"已申请",
                        "1":"处理中",
                        "2":"成功",
                        "-1":"失败",
                        "-2":"异常"
                    },
                    remark:"备注",
                    sign:"签名"
                },
                9:{
                    respCode:"返回码",
                    respMessage:"返回消息",
                    charset:"字符编码",
                    merchantId:"商户号",
                    batchVersion:"版本号",
                    batchDate:"日期",
                    batchNo:"单号",
                    batchContent:"详情",
                    sign:"签名",
                    signType:"签名类型"
                },
                10:{
                    result_code:"返回码",
                    result_msg:"返回消息",
                    merchant_no:"商户号",
                    order_no:"单号",
                    result:"状态",
                    statusList:{
                        "H":"处理中",
                        "S":"成功",
                        "F":"失败"
                    },
                    amount:"金额(元)",
                    withdraw_fee:"手续费(元)",
                    sign:"签名"
                },
                11:{
                    success:"请求状态(1为成功)",
                    code:"状态码",
                    msg:"返回消息",
                    timestamp:"时间戳",
                    merchantId:"商户号",
                    merOrderId:"单号",
                    txnAmt:"金额(分)",
                    status:"状态",
                    statusList:{
                        "1000":"待处理",
                        "1001":"交易成功",
                        "1111":"交易进行中",
                        "1002":"交易失败",
                    },
                    statusDesc:"状态描述",
                    signature:"签名"
                },
                12:{
                    respCode:"返回码(00为成功)",
                    respDesc:"返回消息",
                    Status:"状态",
                    statusList:{
                        0:"已受理",
                        1:"成功",
                        2:"失败",
                        5:"划拨处理中"
                    },
                    batchNo:"批次号",
                    accDate:"财务日期",
                    signMsg:"签名"
                },
                14:{
                    version:"版本号",
                    transType:"业务类型",
                    productId:"产品类型",
                    merNo:"商户号",
                    orderDate:"订单日期",
                    orderNo:"订单号",
                    orderState:"订单状态",
                    transAmt:"金额(分)",
                    serialId:"流水号",
                    oRespCode:"原交易码",
                    oRespDesc:"原交易信息",
                    respCode:"交易码",
                    respDesc:"交易信息",
                    signature:"签名字段",
                    commodityName:"产品名称"
                },
                15:{
                    parter:"商户号",
                    settleid:"流水号",
                    orderid:"商户单号",
                    status:"请求状态",
                    statusList:{
                        "error":"失败",
                        "fail":"失败",
                        "success":"成功"
                    },
                    settlestatus:"结算状态",
                    amount:"金额",
                    msg:"消息",
                    sign:"签名"
                },
                16:{
                    key:"业务编码",
                    msg:"业务消息",
                    requestId:"请求单号",
                    respCode:"响应编码",
                    respMsg:"响应消息",
                    amount:"金额(分)",
                    merno:"商户号",
                    method:"业务编码",
                    order_id:"单号",
                    plat_order_sn:"流水号",
                    skipUpmer:"未知",
                    status:"状态",
                    statusList:{
                        1:"处理中",
                        2:"处理失败",
                        3:"代付成功"
                    }
                },
                17:{
                    cmd:"命令",
                    ret_Code:"返回码",
                    error_Msg:"错误描述",
                    batch_No:"打款批次号",
                    total_Num:"记录数",
                    end_Flag:"结束标志",
                    order_Id:"单号",
                    payee_Bank_Account:"收款账号",
                    real_pay_amount:"实付金额",
                    payee_BankName:"开户行",
                    complete_Date:"处理时间",
                    request_Date:"发起时间",
                    amount:"金额",
                    fee:"手续费",
                    payee_Name:"收款人",
                    bank_Status:"银行状态",
                    r1_Code:"状态码",
                    statusList:{
                        "0025":"已接收",
                        "0026":"已汇出",
                        "0027":"已退款",
                        "0028":"已拒绝",
                        "0029":"待复核",
                        "0030":"未知"
                    },
                    fail_Desc:"失败原因"
                },
                18:{
                    field039:"返回码",
                    field124:"错误描述",
                    field128:"签名",
                    txcode:"出款类别",
                    txdate:"日期",
                    txtime:"时间",
                    version:"版本号"
                },
                19:{
                    code:"处理码",
                    msg:"返回码描述",
                    orderNo:"订单号",
                    businessNo:"流水号",
                    statusList:{
                        0:"未支付",
                        1:"交易成功",
                        2:"交易失败",
                        3:"交易关闭",
                        4:"交易受理中",
                        5:"已退款"
                    },
                    describe:"状态描述",
                    tradeTime:"交易时间",
                    fee:"交易费用"
                }
            };
            scope.keyName=scope.keyNameList[scope.origData.ckType]
        }
    ])


    app.controller("payOutList", [
        "$scope",
        "$rootScope",
        "Table",
        "http",
        "ngDialog",
        "$stateParams",
        "Tip",
        "payOutType",
        function (s, rs, Table, http, ngDialog, $stateParams, tip, payOutType) {
            s.companyInfo = {};
            s.payOutType = payOutType;
            http.get("/admin/companies", {page: 1, perPage: 999}, function (res) {
                if (res.data) {
                    for (var i = 0; i < res.data.length; i++) {
                        s.companyInfo[res.data[i].id] = res.data[i];
                    }
                }
            });

            s.table = Table.init({link: "/admin/queryPayOut"});
            s.table.query.status = "";
            s.table.getList();

            s.search = function (param) {
                s.table.query.status = param;
                s.table.getList(1);
            };

            s.queryPayOutStatus = function (list) {
                http.get(
                    "/admin/queryPayOutStatus",
                    {orderNo: list.order_no},
                    function (res) {
                        ngDialog
                            .open({
                                template: "template/payOutStatusDialog.html",
                                controller: "payOutStatusDialog",
                                data: res
                            })
                    }
                );
            };

            s.updateStatus = function (list, status) {
                http.post(
                    "/admin/updateStatus/" + list.wid,
                    {
                        account: list.account,
                        status: status
                    },
                    function (res) {
                        if (res.status == 0) {
                            s.table.getList();
                            tip.success("操作成功!");
                        } else {
                            s.error = true;
                            s.errorMsg = "网络错误";
                        }
                    }
                );
            };

            s.editRemark = function (list) {
                ngDialog
                    .open({
                        template: "template/payOutRemarkDialog.html",
                        controller: "payOutRemarkEdit",
                        data: list
                    })
                    .closePromise.then(function (data) {
                    if (data && data.value.status == 0) {
                        s.table.getList();
                        tip.success("修改成功!");
                    }
                });
            };
        }
    ]);
})();
