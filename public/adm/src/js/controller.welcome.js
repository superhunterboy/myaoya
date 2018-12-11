(function() {
    app.controller('welcome', ['$scope', 'http', '$rootScope', function (s, http, rs) {
        s.onlinePay = [{
                memo:"payList/0",
                icon:"icon-list",
                css:{"background-color":"#d9534f"},
                name:"支付成功记录"
            },{
                memo:"wechatList/2",
                icon:"icon-dataaplatform",
                css:{"background-color":"#5cb85c"},
                name:"微信加好友记录"
            },{
                memo:"alipayList/3",
                icon:"icon-danger",
                css:{"background-color":"#337ab7"},
                name:"支付宝加好友记录"
            },{
                memo:"failList/1",
                icon:"icon-record",
                css:{"background-color":"#ec971f"},
                name:"支付失败记录"
            },{
                memo:"vendorList",
                icon:"icon-money",
                css:{"background-color":"#92cc30"},
                name:"支付平台设置"
            },{
                memo:"companyList",
                icon:"icon-contact",
                css:{"background-color":"#b85ca8"},
                name:"业务平台设置"
            },{
                memo:"uploadImg",
                icon:"icon-member",
                css:{"background-color":"#ffdd2f"},
                name:"在线支付二维码"
            },
        ]

        s.transferPay = [{
                memo:"bankPayList",
                icon:"icon-vip",
                css:{"background-color":"#d9534f"},
                name:"转账汇款记录"
            },{
                memo:"bankSetting",
                icon:"icon-set",
                css:{"background-color":"#46bf1e"},
                name:"转账汇款设置"
            },
        ]

        s.aliPay = [{
                memo:"alipayRecords",
                icon:"icon-detail",
                css:{"background-color":"#73C791"},
                name:"支付宝支付记录"
            },{
                memo:"alipayBankList",
                icon:"icon-money",
                css:{"background-color":"#FFC90E"},
                name:"转到银行卡"
            },{
                memo:"alipayPersonalQrList",
                icon:"icon-user",
                css:{"background-color":"#B97A57"},
                name:"个人支付宝扫码"
            },{
                memo:"alipayMerchantQrList",
                icon:"icon-star",
                css:{"background-color":"#7868EB"},
                name:"商家扫码"
            },
        ]

        s.wechatPay = [{
                memo:"wechatPayList",
                icon:"icon-detail",
                css:{"background-color":"#B97A57"},
                name:"微信支付记录"
            },{
                memo:"wechatPersonalQrList",
                icon:"icon-user",
                css:{"background-color":"#F218F4"},
                name:"个人微信扫码"
            },{
                memo:"wechatMerchantQrList",
                icon:"icon-star",
                css:{"background-color":"#0CAFF6"},
                name:"商家微信扫码"
            },{
                memo:"wechatQRList",
                icon:"icon-member",
                css:{"background-color":"#FC4E69"},
                name:"群控微信扫码"
            },

        ]

        s.artificialDeposit =[{
            memo:"artificialDepositList",
            icon:"icon-record",
            css:{"background-color":"#73C791"},
            name:"人工存入支付列表"
        }]

        s.payOutMenu=[{
            memo:"payOutList",
            icon:"icon-list",
            css:{"background-color":"#C8BFE7"},
            name:"出款申请记录"
        },{
            memo:"payOutPlatforms",
            icon:"icon-set",
            css:{"background-color":"#cc3030"},
            name:"出款平台设置"
        },{
            memo:"payOutLimit",
            icon:"icon-record",
            css:{"background-color":"#ffdd2f"},
            name:"出款次数设置"
        }];
        s.reportMenu=[{
            memo:"payCompanyReport",
            icon:"icon-list",
            css:{"background-color":"#46bf1e"},
            name:"公司入款统计"
        },{
            memo:"artificialDepositReport",
            icon:"icon-record",
            css:{"background-color":"#ff5d5d"},
            name:"人工存入统计"
        },{
            memo:"payOnlineReport",
            icon:"icon-edit",
            css:{"background-color":"#2292ff"},
            name:"人工存入统计"
        }];
        s.userMenu=[{
            memo:"userList",
            icon:"icon-member",
            css:{"background-color":"#ffdd2f"},
            name:"后台账号"
        }]
        // s.classList={
        //     icon:{
        //         memberManager:"icon-contact",
        //         memberSearchRecord:"icon-list",
        //         telRecord:"icon-record",
        //         userManager:"icon-member",
        //         vipManager:"icon-vip",
        //         siteManager:"icon-dataaplatform",
        //         roleManager:"icon-set",
        //         accountChangeManager:"icon-money",
        //         riskControlManager:"icon-danger",
        //         loginManager:"icon-login",
        //         operatingRecord:"icon-sousuo",
        //         userCheck:"icon-check",
        //         queryMonitor:"icon-monitor",
        //         tierManager:"icon-memberlevel",
        //         ownerManager:"icon-ownerlevel",
        //         grabOrder:"icon-rob",
        //         grabOrderReport:"icon-robreport",
        //         attentionDataReport:"icon-mainreport",
        //         cashReport:"icon-moneyreport",
        //         registerUserReport:"icon-newreport",
        //         whiltManage:"icon-user",
        //         userPromote:"icon-member",
        //         promoteRecord:"icon-record",
        //         permissionSet:"icon-set",
        //         personnelManage:"icon-member",
        //     },
        //     color:{
        //         memberManager:{"background":"#5cb85c"},
        //         memberSearchRecord:{"background":"#337ab7"},
        //         telRecord:{"background":"#d9534f"},
        //         userManager:{"background":"#ec971f"},
        //         vipManager:{"background":"#5c9ab8"},
        //         siteManager:{"background":"#b85ca8"},
        //         roleManager:{"background":"#ffdd2f"},
        //         accountChangeManager:{"background":"##cddc39"},
        //         riskControlManager:{"background":"#02a6f2"},
        //         loginManager:{"background":"#cddc39"},
        //         operatingRecord:{"background":"#ff4400"},
        //         userCheck:{"background":"#33bbca"},
        //         queryMonitor:{"background":"#4e5484"},
        //         tierManager:{"background":"#ffdd2f"},
        //         ownerManager:{"background":"#f7a5bf"},
        //         grabOrder:{"background":"#607D8B"},
        //         grabOrderReport:{"background":"#4CAF50"},
        //         attentionDataReport:{"background":"#c84243"},
        //         cashReport:{"background":"#fac005"},
        //         registerUserReport:{"background":"#58b0d8"},
        //         whiltManage:{"background":"#ddd"},
        //         userPromote:{"background":"#C8BFE7"},
        //         promoteRecord:{"background":"#00A2E8"},
        //         permissionSet:{"background":"#ffdd2f"},
        //         personnelManage:{"background":"#ec971f"},
        //     }
        // }
        //
    }])
})();