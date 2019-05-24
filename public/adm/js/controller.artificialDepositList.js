app.controller("artificialDepositListRemarkEdit", ["$scope", "http", "$rootScope",
    function(t, e, a) {
        t.list = angular.copy(t.ngDialogData),
            t.check = function() {
                return ! (t.list.remark.length > 50) || (t.error = !0, t.errorMsg = "备注过长，请缩减至50字以内", !1)
            },
            t.sub = function() {
                t.check() && e.post("/admin/updateOfflinePayStatus/" + t.list.id, {
                        id: t.list.id,
                        remark: t.list.remark || "",
                        status: t.list.status
                    },
                    function(e) {
                        0 == e.status ? t.closeThisDialog(e) : (t.error = !0, t.errorMsg = "网络错误")
                    })
            }
    }]),
    app.controller("artificialDepositList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "Tip",
        function(t, e, a, i, n, o) {
            t.table = a.init({
                link: "/admin/manuals"
            }),
                t.table.query.status = "",
                t.table.getList(),
                t.search = function(e) {
                    t.table.query.status = e,
                        t.table.getList(1)
                },
                t.act = function(e, a) {
                    var s, l = "1";
                    e.order_no ? 1 == e.type ? s = "/admin/updateOfflinePayStatus/": (s = "/admin/updateOfflinePayStatus/", l = "2") : s = (e.type, "/admin/updatePaySingleState/"),
                        n.open({
                            template: '<div class="confirm-dialog">                 <h2>您确定要将该支付记录标记为' + ["已入款", "已忽略", "已恢复"][a - 1] + '吗？</h2>                <div align="center">                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                </div></div>',
                            plain: !0
                        }).closePromise.then(function(n) {
                            n.value && "CONFIRM" == n.value && i.post(s + e.id, {
                                    id: e.id,
                                    remark: e.remark || "",
                                    type: l,
                                    status: a
                                },
                                function(e) {
                                    0 == e.status && (t.table.getList(), o.success("操作成功！"))
                                })
                        })
                },
                t.editRemark = function(e) {
                    n.open({
                        template: "template/payOutRemarkDialog.html",
                        controller: "artificialDepositListRemarkEdit",
                        data: e
                    }).closePromise.then(function(e) {
                        e && 0 == e.value.status && (t.table.getList(), o.success("修改成功!"))
                    })
                },
                t.$on("newPay",
                    function() {
                        t.table.getList()
                    }),
                t.export = function() {
                    var e = $('<iframe id="down-file-iframe" />'),
                        a = $('<form target="down-file-iframe" method="get" />');
                    a.attr("action", "/admin/manuals");
                    for (var i in t.table.query) a.append('<input type="hidden" name="' + i + '" value="' + t.table.query[i] + '" />');
                    a.append('<input type="hidden" name="down_excel" value="1" />'),
                        e.append(a),
                        $(document.body).append(e),
                        a[0].submit(),
                        e.remove()
                }
        }]);