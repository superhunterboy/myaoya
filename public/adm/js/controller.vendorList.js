app.controller("vendorEdit", ["$scope", "http", "vendorsType",
function(n, o, a) {
    n.vendor = {},
    n.vendorsType = a,
    n.companyInfo = n.ngDialogData.companyInfo,
    n.ngDialogData.vendor && (n.vendorId = n.ngDialogData.vendor.id, n.vendor.company_id = String(n.ngDialogData.vendor.company_id), n.vendor.pay_type = String(n.ngDialogData.vendor.pay_type), n.vendor.no = n.ngDialogData.vendor.no, n.vendor.key = n.ngDialogData.vendor.key, n.vendor.wechat = "" + n.ngDialogData.vendor.wechat, n.vendor.qq = "" + n.ngDialogData.vendor.qq, n.vendor.jd = "" + n.ngDialogData.vendor.jd, n.vendor.baidu = "" + n.ngDialogData.vendor.baidu, n.vendor.union = "" + n.ngDialogData.vendor.union, n.vendor.alipay = "" + n.ngDialogData.vendor.alipay, n.vendor.wap_wechat = "" + n.ngDialogData.vendor.wap_wechat, n.vendor.wap_qq = "" + n.ngDialogData.vendor.wap_qq, n.vendor.wap_jd = "" + n.ngDialogData.vendor.wap_jd, n.vendor.wap_baidu = "" + n.ngDialogData.vendor.wap_baidu, n.vendor.wap_union = "" + n.ngDialogData.vendor.wap_union, n.vendor.wap_alipay = "" + n.ngDialogData.vendor.wap_alipay,n.vendor.wap_yun = "" + n.ngDialogData.vendor.wap_yun,n.vendor.yun = "" + n.ngDialogData.vendor.yun, n.vendor.netpay = "" + n.ngDialogData.vendor.netpay, n.vendor.callback_url = n.ngDialogData.vendor.callback_url, n.vendor.notify_url = n.ngDialogData.vendor.notify_url),
    n.check = function() {
        return n.vendor.company_id ? n.vendor.pay_type ? n.vendor.no ? n.vendor.key ? !!n.vendor.callback_url || (n.error = !0, n.errorMsg = "请填写回调地址！", !1) : (n.error = !0, n.errorMsg = "请填写商户key！", !1) : (n.error = !0, n.errorMsg = "请填写商户编号！", !1) : (n.error = !0, n.errorMsg = "请选择支付平台！", !1) : (n.error = !0, n.errorMsg = "请选择所属业务平台！", !1)
    },
    n.save = function() { ! n.loading && n.check() && (n.loading = !0, o.post("/admin/vendors", n.vendor,
        function(o) {
            n.loading = !1,
            0 == o.status ? n.closeThisDialog(o) : (n.error = !0, n.errorMsg = o.msg)
        }))
    },
    n.update = function() { ! n.loading && n.check() && (n.loading = !0, o.put("/admin/vendors/" + n.vendorId, n.vendor,
        function(o) {
            n.loading = !1,
            0 == o.status ? n.closeThisDialog(o) : (n.error = !0, n.errorMsg = o.msg)
        }))
    }
}]),
app.controller("vendorList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "vendorsType",
function(n, o, a, e, r, t) {
    o.patchName = "vendorList",
    n.vendorsType = t,
    n.companyInfo = {},
    e.get("/admin/getCompaniesByCurrentUser", {},
    function(o) {
        if (o.data) for (var a = 0; a < o.data.length; a++) n.companyInfo[o.data[a].id] = o.data[a]
    }),
    n.table = a.init({
        link: "/admin/vendors"
    }),
    n.table.getList(),
    n.editVendor = function(o) {
        r.open({
            template: "template/vendorEditDialog.html",
            controller: "vendorEdit",
            data: {
                vendor: o,
                companyInfo: n.companyInfo
            }
        }).closePromise.then(function(o) {
            o.value && 0 == o.value.status && n.table.getList()
        })
    },
    n.delVendor = function(o) {
        r.open({
            template: '<div class="confirm-dialog">             <h2>您确定要删除支付平台配置“' + n.companyInfo[o.company_id].name + "-" + n.vendorsType[o.pay_type] + '”吗？</h2>            <div align="center">                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>            </div></div>',
            plain: !0
        }).closePromise.then(function(a) {
            a.value && "CONFIRM" == a.value && e.delete("/admin/vendors/" + o.id,
            function(o) {
                0 == o.status && n.table.getList()
            })
        })
    }
}]);