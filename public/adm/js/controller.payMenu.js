app.controller("editNotice", ["$scope", "http",
    function(t, e) {
        t.notice = {},
        t.ngDialogData && t.ngDialogData.id && (t.notice = {
            id: t.ngDialogData.id,
            title: t.ngDialogData.title,
            content: t.ngDialogData.content,
            status: t.ngDialogData.status
        }),
            t.check = function() {
                return t.notice.title ? !!t.notice.content || (t.error = !0, t.errorMsg = "请填写内容！", !1) : (t.error = !0, t.errorMsg = "请填写标题！", !1)
            },
            t.save = function() { ! t.loading && t.check() && (t.loading = !0, e.post("/admin/addAffiche", {
                    title: t.notice.title,
                    content: t.notice.content
                },
                function(e) {
                    t.loading = !1,
                        0 == e.status ? t.closeThisDialog(e) : (t.error = !0, t.errorMsg = e.msg)
                }))
            },
            t.update = function() { ! t.loading && t.check() && (t.loading = !0, e.post("/admin/updateAffiche", t.notice,
                function(e) {
                    t.loading = !1,
                        0 == e.status ? t.closeThisDialog(e) : (t.error = !0, t.errorMsg = e.msg)
                }))
            }
    }]),
    app.controller("paymenuList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "Tip",
        function(t, e, i, n, o, a) {
            t.table = i.init({
                link: "/admin/paymenu"
            }),
                t.table.getList(),
                t.delNotice = function(e) {
                    o.open({
                        template: '<div class="confirm-dialog">                 <h2>您确定要将公告“' + e.title + '”删除吗？</h2>                <div align="center">                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                </div></div>',
                        plain: !0
                    }).closePromise.then(function(i) {
                        i.value && "CONFIRM" == i.value && n.get("/admin/deleteAffiche/" + e.id, {},
                            function(e) {
                                0 == e.status ? a.success("删除成功") : a.error(e.msg),
                                    t.table.getList()
                            })
                    })
                },
                t.updateNotice = function(t, e) {
                    n.post("/admin/updateAffiche", {
                            id: t.id,
                            title: t.title,
                            content: t.content,
                            status: e
                        },
                        function(i) {
                            0 == i.status && (t.status = e)
                        })
                },
                t.editNotice = function(e) {
                    o.open({
                        template: "template/noticeDialog.html",
                        controller: "editNotice",
                        data: e
                    }).closePromise.then(function(e) {
                        e.value && 0 == e.value.status && t.table.getList()
                    })
                },
                t.act = function(e, i) {
                    n.post("/admin/paymenustatus/" + e.id, {
                            status: i
                        },
                        function(e) {
                            0 == e.status ? (t.table.getList(), a.success("操作成功")) : a.error(e.msg)
                        })
                }
        }]);