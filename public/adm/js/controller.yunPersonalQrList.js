app.controller("edityunPersonalQr", ["$scope", "http",
    function(e, t) {
        e.id,
            e.qrcode = {
                content: "",
                type: "4"
            },
        e.ngDialogData.qrcode && (e.id = e.qrcode.id = e.ngDialogData.qrcode.id, e.qrcode.account = e.ngDialogData.qrcode.qrcode_name, e.qrcode.status = e.ngDialogData.qrcode.status, e.qrcode.money = e.ngDialogData.qrcode.money, e.qrcode.msg = e.ngDialogData.qrcode.msg, e.qrcode.url = e.ngDialogData.qrcode.url),
            e.check = function() {
                return e.qrcode.account ? !(!e.id && !e.qrcode.content) || (e.error = !0, e.errorMsg = "请上传二维码！", !1) : (e.error = !0, e.errorMsg = "请填写QQ账号！", !1)
            },
            e.save = function() {
                var o; ! e.loading && e.check() && (e.loading = !0, delete(o = angular.copy(e.qrcode)).url, t.post("/admin/addSingleQrcode", o,
                    function(t) {
                        e.loading = !1,
                            0 == t.status ? e.closeThisDialog(t) : (e.error = !0, e.errorMsg = t.msg)
                    }))
            },
            e.update = function() {
                var o; ! e.loading && e.check() && (e.loading = !0, (o = angular.copy(e.qrcode)).content || delete o.content, delete o.url, delete o.status, t.post("/admin/updateSingleQrcode/" + e.id, o,
                    function(t) {
                        //e.loading = !1,
                            //0 == t.status ? e.closeThisDialog(t) : (e.error = !0, e.errorMsg = t.msg)
                    }))
            },
            e.$watch("qrcode.content",
                function() {
                    e.qrcode.content && (e.qrcode.url = e.qrcode.content)
                })
    }]),
    app.controller("yunPersonalQrList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "$stateParams", "Tip",
        function(e, t, o, n, a, c, l) {
            e.checkValue = [],
                e.table = o.init({
                    link: "/admin/singleQrcodes/4",
                    query: {}
                }),
                e.table.getList(),
                e.total = e.table.total,
                e.editQR = function(t) {
                    a.open({
                        template: "template/wechatPersonalQrDialog.html",
                        controller: "edityunPersonalQr",
                        data: {
                            qrcode: t
                        }
                    }).closePromise.then(function(t) {
                        t.value && 0 == t.value.status && (l.success("保存成功!"), setTimeout(function() {
                                e.table.getList()
                            },
                            1e3))
                    })
                },
                e.deleteQR = function(t) {
                    var o, c = "确定要删除此条信息？";
                    t && (o = t.id),
                    e.checkValue.length > 0 && (o = e.checkValue.join(","), c = "确定要删除选中信息？"),
                    o && a.open({
                        template: '<div class="confirm-dialog">                             <h2>' + c + '</h2>                            <div align="center">                                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                            </div></div>',
                        plain: !0
                    }).closePromise.then(function(t) {
                        t.value && "CONFIRM" == t.value && n.get("/admin/deleteSingleQrcode/" + o, {},
                            function(t) {
                                0 == t.status ? (e.checkValue = [], l.success("删除成功!"), e.table.getList()) : l.error(t.msg)
                            })
                    })
                },
                e.updateStatus = function(t, o) {
                    var c;
                    switch (o) {
                        case 0:
                            c = "确定要解锁此二维码？";
                            break;
                        case 1:
                            c = "确定要锁定此二维码";
                            break;
                        case 2:
                            c = "确定要屏蔽此二维码？";
                            break;
                        case 3:
                            c = "确定要启用此二维码？",
                                o = 0
                    }
                    a.open({
                        template: '<div class="confirm-dialog">                         <h2>' + c + '</h2>                        <div align="center">                            <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                            <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                        </div></div>',
                        plain: !0
                    }).closePromise.then(function(a) {
                        a.value && "CONFIRM" == a.value && (t.status = o, n.post("/admin/updateSingleQrcode/" + t.id, t,
                            function(t) {
                                0 == t.status && (l.success("操作成功!"), e.table.getList())
                            }))
                    })
                }
        }]);