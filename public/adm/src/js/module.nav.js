var nav = angular.module("nav", ["ui.router", "oc.lazyLoad"]);
var version = +new Date();
nav.constant("myModulesConfig", [
    {
        name: "ngDialog",
        files: [
            "js/ngDialog.min.js",
            "css/ngDialog.min.css",
            "css/ngDialog-theme-default.min.css",
            "css/dialog.css",
            "js/factory.tip.js"
        ]
    },
    {
        name: "datePicker",
        files: [
            "js/My97DatePicker/WdatePicker.js",
            "js/directive.datepicker.js"
        ]
    },
    {
        name: "datatype",
        files: ["js/directive.datatype.js"]
    },
    {
        name: "store",
        files: ["js/store.legacy.min.js"]
    },
    {
        name: "simditor",
        files: [
            "js/simditor/styles/font-awesome.css",
            "js/simditor/styles/simditor.css",
            "js/simditor/scripts/simditor-all.min.js"
        ]
    },
    {
        name: "imgUpload",
        files: ["js/directive.imgUpload.js"]
    },
    {
        name: "table",
        files: ["js/factory.table.js", "css/table.css?v=" + version]
    },
    {
        name: "userList",
        files: [
            "js/controller.userList.js?v=" + version,
            //"js/controller.userList.js",
            "css/userList.css"
        ]
    },
    {
        name: "noticeList",
        files: [
            "js/controller.noticeList.js?v=" + version,
            //"js/controller.userList.js",
            "css/userList.css"
        ]
    },
    {
        name: "welcome",
        files: [
            "css/welcome.css",
            "js/controller.welcome.js?v=" + version
        ]
    },
    {
        name: "account",
        files: ["js/controller.account.js?v=" + version, "css/account.css"]
    },
    {
        name: "payList",
        files: ["js/controller.payList.js?v=" + version, "css/payList.css"]
    },
    {
        name: "alipayList",
        files: ["js/controller.payList.js?v=" + version, "css/payList.css"]
    },
    {
        name: "wechatList",
        files: ["js/controller.payList.js?v=" + version, "css/payList.css"]
    },
    {
        name: "failList",
        files: ["js/controller.payList.js?v=" + version, "css/payList.css"]
    },
    {
        name: "companyList",
        files: [
            "js/controller.companyList.js?v=" + version,
            //"src/js/controller.companyList.js",
            "css/companyList.css"
        ]
    },
    {
        name: "vendorList",
        files: [
            "js/controller.vendorList.js?v=" + version,
            //"src/js/controller.vendorList.js",
            "css/vendorList.css"
        ]
    },
    {
        name: "uploadImg",
        files: [
            "js/controller.uploadImg.js?v="+version,
            //"src/js/controller.uploadImg.js",
            "css/uploadImg.css"
        ]
    },
    {
        name: "payOutList",
        files: ["js/controller.payOutList.js?v=" + version, "css/payOutList.css"]
    },
    {
        name: "personalPayOutList",
        files: ["js/controller.personalPayOutList.js?v=" + version, "css/payOutList.css"]
    },
    {
        name: "payOutPlatforms",
        files: ["js/controller.payOutPlatforms.js"]
    },
    {
        name: "payCompanyReport",
        files: [
            "js/controller.payCompanyReport.js?v=" + version,
            "css/reportList.css"
        ]
    },
    {
        name: "artificialDepositReport",
        files: [
            "js/controller.artificialDepositReport.js?v=" + version,
            "css/reportList.css"
        ]
    },
    {
        name: "payOnlineReport",
        files: [
            "js/controller.payOnlineReport.js?v=" + version,
            "css/reportList.css"
        ]
    },
    {
        name: "bankSetting",
        files: [
            "js/controller.bankSetting.js?v=" + version,
            "css/bankSetting.css"
        ]
    },
    {
        name: "bankPayList",
        files: [
            "js/controller.bankPayList.js?v=" + version,
            "css/payList.css"
        ]
    },{
        name:"wechatQRList",
        files:[
            "js/controller.wechatQRList.js?v=" + version,
            "css/wechatQRList.css"
        ]
    },{
        name:"wechatPayList",
        files:[
            "js/controller.wechatPayList.js?v=" + version,
            "css/wechatPayList.css"
        ]
    },{
        name:"qqPayList",
        files:[
            "js/controller.qqPayList.js?v=" + version,
            "css/wechatPayList.css"
        ]
    },{
        name:"qqPersonalQrList",
        files:[
            "js/controller.qqPersonalQrList.js?v=" + version,
            "css/personalQrcode.css"
        ]
    },{
        name:"alipayMerchantQrList",
        files:[
            "js/controller.alipayMerchantQrList.js?v=" + version,
        ]
    },{
        name:"alipayPersonalQrList",
        files:[
            "js/controller.alipayPersonalQrList.js?v=" + version,
            "css/personalQrcode.css"
        ]
    },{
        name:"wechatMerchantQrList",
        files:[
            "js/controller.wechatMerchantQrList.js?v=" + version,
        ]
    }
    ,{
        name:"wechatPersonalQrList",
        files:[
            "js/controller.wechatPersonalQrList.js?v=" + version,
            "css/personalQrcode.css"
        ]
    },{
        name:"alipayBankList",
        files:[
            "js/controller.alipayBankList.js?v=" + version,
            "css/alipayBankList.css"
        ]
    },{
        name:"alipayRecords",
        files:[
            "js/controller.alipayRecords.js?v=" + version,
            "css/alipayRecords.css"
        ]
    },
    {
        name: "artificialDepositList",
        files: [
            "js/controller.artificialDepositList.js?v=" + version,
            "css/payList.css"
        ]
    },
    {
        name: "payOutLimit",
        files: [
            "js/controller.payOutLimit.js?v=" + version,
            "css/userList.css"
        ]
    }

]);
nav.config([
    "$stateProvider",
    "$urlRouterProvider",
    "$ocLazyLoadProvider",
    "myModulesConfig",
    function ($stateProvider,
              $urlRouterProvider,
              $ocLazyLoadProvider,
              myModulesConfig) {
        $ocLazyLoadProvider.config({
            debug: false,
            events: false,
            modules: myModulesConfig
        });
        $urlRouterProvider.when("", "/welcome");
        $stateProvider
            .state("welcome", {
                url: "/welcome",
                templateUrl: "template/welcome.html",
                resolve: loadSequence("ngDialog", "welcome"),
                controller: "welcome"
            })
            .state("payList", {
                url: "/payList/:payType",
                templateUrl: "template/payList.html",
                resolve: loadSequence("ngDialog", "table", "store", "datePicker", "payList"),
                controller: "payList"
            })
            .state("alipayList", {
                url: "/alipayList/:payType",
                templateUrl: "template/alipayList.html",
                resolve: loadSequence("ngDialog", "table", "store", "alipayList"),
                controller: "payList"
            })
            .state("wechatList", {
                url: "/wechatList/:payType",
                templateUrl: "template/wechatList.html",
                resolve: loadSequence("ngDialog", "table", "store", "wechatList"),
                controller: "payList"
            })
            .state("failList", {
                url: "/failList/:payType",
                templateUrl: "template/failList.html",
                resolve: loadSequence("ngDialog", "table", "store", "datePicker", "failList"),
                controller: "payList"
            })
            .state("companyList", {
                url: "/companyList",
                templateUrl: "template/companyList.html",
                resolve: loadSequence("ngDialog", "table", "companyList"),
                controller: "companyList"
            })
            .state("vendorList", {
                url: "/vendorList",
                templateUrl: "template/vendorList.html",
                resolve: loadSequence("ngDialog", "table", "vendorList"),
                controller: "vendorList"
            })
            .state("userList", {
                url: "/userList",
                templateUrl: "template/userList.html",
                resolve: loadSequence("ngDialog", "table", "userList"),
                controller: "userList"
            })
            .state("account", {
                url: "/account",
                templateUrl: "template/account.html",
                resolve: loadSequence("ngDialog", "table", "account"),
                controller: "account"
            })
            .state("uploadImg", {
                url: "/uploadImg",
                templateUrl: "template/uploadImg.html",
                resolve: loadSequence("ngDialog", "table", "imgUpload", "uploadImg"),
                controller: "uploadImg"
            })
            .state("payOutList", {
                url: "/payOutList",
                templateUrl: "template/payOutList.html",
                resolve: loadSequence(
                    "ngDialog",
                    "table",
                    "datePicker",
                    "payOutList"
                ),
                controller: "payOutList"
            })
            .state("personalPayOutList", {
                url: "/personalPayOutList",
                templateUrl: "template/personalPayOutList.html",
                resolve: loadSequence(
                    "ngDialog",
                    "table",
                    "datePicker",
                    "personalPayOutList"
                ),
                controller: "personalPayOutList"
            })
            .state("payOutPlatforms", {
                url: "/payOutPlatforms",
                templateUrl: "template/payOutPlatforms.html",
                resolve: loadSequence("ngDialog", "table", "datatype", "payOutPlatforms"),
                controller: "payOutPlatforms"
            })
            .state("payCompanyReport", {
                url: "/payCompanyReport",
                templateUrl: "template/payCompanyReport.html",
                resolve: loadSequence("ngDialog","table","datePicker", "datatype", "payCompanyReport"),
                controller: "payCompanyReport"
            })
            .state("artificialDepositReport", {
                url: "/artificialDepositReport",
                templateUrl: "template/artificialDepositReport.html",
                resolve: loadSequence("ngDialog","table","datePicker", "datatype", "artificialDepositReport"),
                controller: "artificialDepositReport"
            })
            .state("payOnlineReport", {
                url: "/payOnlineReport",
                templateUrl: "template/payOnlineReport.html",
                resolve: loadSequence("ngDialog","table","datePicker", "datatype", "payOnlineReport"),
                controller: "payOnlineReport"
            })
            .state("bankSetting", {
                url: "/bankSetting",
                templateUrl: "template/bankSetting.html",
                resolve: loadSequence("ngDialog", "table","bankSetting"),
                controller: "bankSetting"
            })
            .state("bankPayList", {
                url: "/bankPayList",
                templateUrl: "template/bankPayList.html",
                resolve: loadSequence("ngDialog","table", "bankPayList"),
                controller: "bankPayList"
            })
            .state("wechatQRList", {
                url: "/wechatQRList",
                templateUrl: "template/wechatQRList.html",
                resolve: loadSequence("ngDialog","table","imgUpload", "wechatQRList"),
                controller: "wechatQRList"
            })
            .state("wechatPayList", {
                url: "/wechatPayList",
                templateUrl: "template/wechatPayList.html",
                resolve: loadSequence("ngDialog","table","datePicker", "wechatPayList"),
                controller: "wechatPayList"
            })
            .state("alipayMerchantQrList", {
                url: "/alipayMerchantQrList",
                templateUrl: "template/alipayMerchantQrList.html",
                resolve: loadSequence("ngDialog","table","alipayMerchantQrList"),
                controller: "alipayMerchantQrList"
            })
            .state("alipayPersonalQrList", {
                url: "/alipayPersonalQrList",
                templateUrl: "template/alipayPersonalQrList.html",
                resolve: loadSequence("ngDialog","table","imgUpload","alipayPersonalQrList"),
                controller: "alipayPersonalQrList"
            })
            .state("wechatMerchantQrList", {
                url: "/wechatMerchantQrList",
                templateUrl: "template/wechatMerchantQrList.html",
                resolve: loadSequence("ngDialog","table","wechatMerchantQrList"),
                controller: "wechatMerchantQrList"
            })
            .state("wechatPersonalQrList", {
                url: "/wechatPersonalQrList",
                templateUrl: "template/wechatPersonalQrList.html",
                resolve: loadSequence("ngDialog","table","imgUpload","wechatPersonalQrList"),
                controller: "wechatPersonalQrList"
            })
            .state("alipayBankList", {
                url: "/alipayBankList",
                templateUrl: "template/alipayBankList.html",
                resolve: loadSequence("ngDialog","table","alipayBankList"),
                controller: "alipayBankList"
            })
            .state("alipayRecords", {
                url: "/alipayRecords",
                templateUrl: "template/alipayRecords.html",
                resolve: loadSequence("ngDialog","table","datePicker", "alipayRecords"),
                controller: "alipayRecords"
            })
            .state("artificialDepositList", {
                url: "/artificialDepositList",
                templateUrl: "template/artificialDepositList.html",
                resolve: loadSequence("ngDialog","table","datePicker", "artificialDepositList"),
                controller: "artificialDepositList"
            })
            .state("payOutLimit", {
                url: "/payOutLimit",
                templateUrl: "template/payOutLimit.html",
                resolve: loadSequence("ngDialog","datatype", "table", "payOutLimit"),
                controller: "payOutLimit"
            })
            .state("qqPayList", {
                url: "/qqPayList",
                templateUrl: "template/qqPayList.html",
                resolve: loadSequence("ngDialog","table","datePicker", "qqPayList"),
                controller: "qqPayList"
            })
            .state("qqPersonalQrList", {
                url: "/qqPersonalQrList",
                templateUrl: "template/qqPersonalQrList.html",
                resolve: loadSequence("ngDialog","table","imgUpload", "qqPersonalQrList"),
                controller: "qqPersonalQrList"
            })
            .state("noticeList", {
                url: "/noticeList",
                templateUrl: "template/noticeList.html",
                resolve: loadSequence("ngDialog", "table", "noticeList"),
                controller: "noticeList"
            })
        ;

        function loadSequence() {
            var _args = arguments;
            return {
                deps: [
                    "$ocLazyLoad",
                    "$q",
                    "$rootScope",
                    function ($ocLL, $q, rs) {
                        var promise = $q.when(1);
                        for (var i = 0, len = _args.length; i < len; i++) {
                            promise = promiseThen(_args[i]);
                        }
                        promise.then(function () {
                            rs.nowMenu = _args[_args.length - 1];
                        });
                        return promise;

                        function promiseThen(name) {
                            return promise.then(function () {
                                return $ocLL.load(name);
                            });
                        }
                    }
                ]
            };
        }
    }
])
    .directive("nav", [
        "$http",
        "$interval",
        function ($http, $interval) {
            return {
                restrict: "AE",
                template:
                    '<div class="nav">\
                      <ul>\
                          <li class="nav-first" ng-class="{true:\'nav-active\'}[nowMenu==\'welcome\']"  ><a class="waves-effect waves-dark" ui-sref="welcome">首页</a></li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==0 || userInfo.type==3" class="nav-first" \
                          ng-class="{true:\'nav-open\'}[nowMenu==\'payList\' || nowMenu==\'wechatList\' || nowMenu==\'alipayList\'|| nowMenu==\'failList\' || nowMenu==\'vendorList\' || nowMenu==\'companyList\' || nowMenu==\'uploadImg\']"><i class="icon-xiangxia1"></i><a>在线支付</a>\
                          <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'payList\' || nowMenu==\'wechatList\' || nowMenu==\'alipayList\'|| nowMenu==\'failList\' || nowMenu==\'vendorList\' || nowMenu==\'companyList\' || nowMenu==\'uploadImg\']">\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'payList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="payList({payType:0})" >支付成功记录<span ng-if="newPay>0" ng-bind="newPay>99?\'99+\':newPay"></span></a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'wechatList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="wechatList({payType:2})" >微信加好友记录<span ng-if="newWechat>0" ng-bind="newWechat>99?\'99+\':newWechat"></span></a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'alipayList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="alipayList({payType:3})" >支付宝加好友记录<span ng-if="newAlipay>0" ng-bind="newAlipay>99?\'99+\':newAlipay"></span></a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'failList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="failList({payType:1})" >支付失败记录</a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'vendorList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="vendorList" >支付平台设置</a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'companyList\']" >\
                                <a class="waves-effect waves-dark" ui-sref="companyList" >业务平台设置</a>\
                            </li>\
                            <li ng-class="{true:\'nav-active\'}[nowMenu==\'uploadImg\']" >\
                                <a class="waves-effect waves-dark" ui-sref="uploadImg" >在线支付二维码</a>\
                            </li>\
                            </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==0 || userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'bankSetting\'|| nowMenu==\'bankPayList\']"><i class="icon-xiangxia1"></i><a>转账汇款</a>\
                            <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'bankSetting\'|| nowMenu==\'bankPayList\']">\
                                <li ng-class="{true:\'nav-active\'}[nowMenu==\'bankPayList\']"  >\
                                    <a class="waves-effect waves-dark" ui-sref="bankPayList" >转账汇款记录<span ng-if="newBank>0" ng-bind="newBank>99?\'99+\':newBank"></span></a> \
                                </li>\
                                <li ng-class="{true:\'nav-active\'}[nowMenu==\'bankSetting\']" >\
                                    <a class="waves-effect waves-dark" ui-sref="bankSetting">转账银行卡设置</a>\
                                </li>\
                            </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==2 ||userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'alipayRecords\' || nowMenu==\'alipayBankList\' || nowMenu==\'alipayPersonalQrList\'|| nowMenu==\'alipayMerchantQrList\']" ><i class="icon-xiangxia1"></i><a>支付宝支付</a>\
                                <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'alipayRecords\' || nowMenu==\'alipayBankList\' || nowMenu==\'alipayPersonalQrList\' || nowMenu==\'alipayMerchantQrList\']">\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'alipayRecords\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="alipayRecords" >支付宝支付记录</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'alipayBankList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="alipayBankList" >转到银行卡</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'alipayPersonalQrList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="alipayPersonalQrList" >个人支付宝扫码</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'alipayMerchantQrList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="alipayMerchantQrList" >商家扫码</a>\
                                    </li>\
                                </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==2 ||userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'wechatPayList\' || nowMenu==\'wechatPersonalQrList\'|| nowMenu==\'wechatMerchantQrList\'|| nowMenu==\'wechatQRList\']" ><i class="icon-xiangxia1"></i><a>微信支付</a>\
                                <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[ nowMenu==\'wechatPayList\' || nowMenu==\'wechatPersonalQrList\' || nowMenu==\'wechatMerchantQrList\'|| nowMenu==\'wechatQRList\']">\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'wechatPayList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="wechatPayList" >微信支付记录</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'wechatPersonalQrList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="wechatPersonalQrList" >个人微信扫码</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'wechatMerchantQrList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="wechatMerchantQrList" >商家微信扫码</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'wechatQRList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="wechatQRList" >智能微信扫码</a>\
                                    </li>\
                                </ul>\
                            </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==2 ||userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'qqPayList\' || nowMenu==\'qqPersonalQrList\']" ><i class="icon-xiangxia1"></i><a>QQ支付</a>\
                                <ul class="nav-second"  ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'qqPayList\' || nowMenu==\'qqPersonalQrList\']">\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'qqPayList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="qqPayList" >QQ支付记录</a>\
                                    </li>\
                                     <li ng-class="{true:\'nav-active\'}[nowMenu==\'qqPersonalQrList\']" >\
                                        <a class="waves-effect waves-dark" ui-sref="qqPersonalQrList" >个人QQ扫码</a>\
                                     </li>\
                                </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==2 ||userInfo.type==3" class="nav-first" ng-class="{true:\'nav-active\'}[nowMenu==\'artificialDepositList\']"><a class="waves-effect waves-dark" ui-sref="artificialDepositList">人工存入支付列表</a></li></li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==2 ||userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'payOutList\'|| nowMenu==\'payOutPlatforms\' || nowMenu==\'payOutLimit\' || nowMenu==\'personalPayOutList\']" ><i class="icon-xiangxia1"></i><a>出款管理</a>\
                              <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'payOutList\' || nowMenu==\'payOutPlatforms\' || nowMenu==\'payOutLimit\' || nowMenu==\'personalPayOutList\']">\
                                  <li ng-class="{true:\'nav-active\'}[nowMenu==\'payOutList\']" >\
                                      <a class="waves-effect waves-dark" ui-sref="payOutList" >自动出款记录</a>\
                                  </li>\
                                  <li ng-class="{true:\'nav-active\'}[nowMenu==\'personalPayOutList\']" >\
                                      <a class="waves-effect waves-dark" ui-sref="personalPayOutList" >手动出款记录</a>\
                                  </li>\
                                  <li ng-class="{true:\'nav-active\'}[nowMenu==\'payOutPlatforms\']" >\
                                      <a class="waves-effect waves-dark" ui-sref="payOutPlatforms" >出款平台设置</a>\
                                  </li>\
                                  <li ng-class="{true:\'nav-active\'}[nowMenu==\'payOutLimit\']" >\
                                      <a class="waves-effect waves-dark" ui-sref="payOutLimit" >出款次数设置</a>\
                                  </li>\
                              </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1 || userInfo.type==3" class="nav-first" ng-class="{true:\'nav-open\'}[nowMenu==\'payCompanyReport\'|| nowMenu==\'artificialDepositReport\'|| nowMenu==\'payOnlineReport\']"><i class="icon-xiangxia1"></i><a>入款数据统计</a>\
                               <ul class="nav-second" ng-style="{true:{\'display\':\'block\'}}[nowMenu==\'payCompanyReport\'|| nowMenu==\'artificialDepositReport\'|| nowMenu==\'payOnlineReport\']">\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'payCompanyReport\']">\
                                        <a class="waves-effect waves-dark" ui-sref="payCompanyReport" >公司入款统计</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'artificialDepositReport\']">\
                                        <a class="waves-effect waves-dark" ui-sref="artificialDepositReport" >人工存入统计</a>\
                                    </li>\
                                    <li ng-class="{true:\'nav-active\'}[nowMenu==\'payOnlineReport\']">\
                                        <a class="waves-effect waves-dark" ui-sref="payOnlineReport" >线上支付统计</a>\
                                    </li>\
                               </ul>\
                          </li>\
                          <li ng-if="userInfo.type==1" class="nav-first" ng-class="{true:\'nav-active\'}[nowMenu==\'noticeList\']"><a class="waves-effect waves-dark" ui-sref="noticeList">公告管理</a></li></li>\
                          <li ng-if="userInfo.type==1" class="nav-first" ng-class="{true:\'nav-active\'}[nowMenu==\'userList\']"><a class="waves-effect waves-dark" ui-sref="userList">后台账号</a></li></li>\
                      </ul>\
                  </div>',
                link: function (scope, element, attrs, ctrl) {
                    $(element).on("click", "a", function () {
                        if (!$(this).attr("href")) {
                            if (
                                $(this)
                                    .parent("li")
                                    .hasClass("nav-open")
                            ) {
                                $(this)
                                    .parent("li")
                                    .removeClass("nav-open");
                                $(this)
                                    .next(".nav-second")
                                    .slideUp();
                            } else {
                                $(this)
                                    .parent("li")
                                    .addClass("nav-open");
                                $(this)
                                    .next(".nav-second")
                                    .slideDown();
                            }
                        }
                    });
                    var audio = document.createElement("AUDIO");
                    var source1 = document.createElement("SOURCE");
                    source1.src = "mp3/tip.ogg";
                    var source2 = document.createElement("SOURCE");
                    source2.src = "mp3/tip.mp3";
                    audio.appendChild(source1);
                    audio.appendChild(source2);
                    scope.newPay = 0;
                    scope.newWechat = 0;
                    scope.newAlipay = 0;
                    scope.newBank = 0;
                    scope.getNew = function (n) {
                        $http({
                            url: "/admin/notOperatOrders",
                            method: "GET",
                            headers: {"X-Requested-With": "XMLHttpRequest"}
                        })
                            .then(function (bk) {
                                var res = bk.data;
                                if (res.status == 0) {
                                    if (
                                        n &&
                                        (scope.newAlipay != res.data.alipay ||
                                            scope.newPay != res.data.success ||
                                            scope.newWechat != res.data.wechat ||
                                            scope.newBank != res.data.offline
                                        )
                                    ) {
                                        scope.$broadcast("newPay");
                                        audio.play();
                                    }
                                    scope.newAlipay = res.data.alipay;
                                    scope.newPay = res.data.success;
                                    scope.newWechat = res.data.wechat;
                                    scope.newBank = res.data.offline;
                                }
                            })
                            .catch(function (err) {
                                if(err.status==401){
                                    alert("您的上网IP发生变化或账号在其他地方登录，请重新登陆！");
                                    location.href="login.html";
                                }
                            });
                    };
                    scope.getNew(false);
                    scope.$on("getNew", function () {
                        scope.getNew(false);
                    });
                    $interval(function () {
                        scope.getNew(true);
                    }, 5000);
                }
            };
        }
    ])
    .directive("navActive", function () {
        return {
            replace: true,
            restrict: "AE",
            template:
                '<div class="navActive open waves-effect waves-dark"><i class="icon-xiangzuo1"></i><i class="icon-xiangyou1"></i></div>',
            link: function (scope, element, attrs, ctrl) {
                $(element).click(function () {
                    if ($(this).hasClass("open")) {
                        $(".nav").css("left", "-200px");
                        $(".view").css("left", "0");
                        $(this).removeClass("open");
                        $(this).addClass("close");
                        scope.$broadcast("openNav");
                        scope.$emit("openNav");
                    } else {
                        $(".nav").css("left", "0");
                        $(".view").css("left", "200px");
                        $(this).removeClass("close");
                        $(this).addClass("open");
                        scope.$broadcast("closeNav");
                        scope.$emit("closeNav");
                    }
                });
            }
        };
    });
