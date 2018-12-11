(function() {
    app.controller("editQQPersonalQr", [
        "$scope",
        "http",
        function(s, http) {
            s.id;
            s.qrcode = { content: "", type: "3" };
            if (s.ngDialogData.qrcode) {
                s.id = s.qrcode.id = s.ngDialogData.qrcode.id;
                s.qrcode.account = s.ngDialogData.qrcode.qrcode_name;
                s.qrcode.status = s.ngDialogData.qrcode.status;
                s.qrcode.money = s.ngDialogData.qrcode.money;
                s.qrcode.msg = s.ngDialogData.qrcode.msg;
                s.qrcode.url = s.ngDialogData.qrcode.url;
            }
            s.check = function() {
                if (!s.qrcode.account) {
                    s.error = true;
                    s.errorMsg = "请填写QQ账号！";
                    return false;
                }
                if (!s.id && !s.qrcode.content) {
                    s.error = true;
                    s.errorMsg = "请上传二维码！";
                    return false;
                }
                return true;
            };
            s.save = function() {
                var data;
                if (!s.loading && s.check()) {
                    s.loading = true;
                    data = angular.copy(s.qrcode);
                    delete data.url;

                    http.post("/admin/addSingleQrcode", data, function(res) {
                        s.loading = false;
                        if (res.status == 0) {
                            s.closeThisDialog(res);
                        } else {
                            s.error = true;
                            s.errorMsg = res.msg;
                        }
                    });
                }
            };
            s.update = function() {
                var data;
                if (!s.loading && s.check()) {
                    s.loading = true;

                    data = angular.copy(s.qrcode);
                    if (!data.content) delete data.content;
                    delete data.url;
                    delete data.status;

                    http.post("/admin/updateSingleQrcode/" + s.id, data, function(res) {
                        s.loading = false;
                        if (res.status == 0) {
                            s.closeThisDialog(res);
                        } else {
                            s.error = true;
                            s.errorMsg = res.msg;
                        }
                    });
                }
            };
            s.$watch("qrcode.content", function() {
                if (s.qrcode.content) s.qrcode.url = s.qrcode.content;
            });
        }
    ]);

    app.controller("qqPersonalQrList", [
        "$scope",
        "$rootScope",
        "Table",
        "http",
        "ngDialog",
        "$stateParams",
        "Tip",
        function(s, rs, Table, http, ngDialog, $stateParams, tip) {
            s.checkValue = [];
            s.table = Table.init({ link: "/admin/singleQrcodes/" + 3, query: {} });
            s.table.getList();
            s.total = s.table.total;

            s.editQR = function(qrcode) {
                ngDialog
                    .open({
                        template: "template/wechatPersonalQrDialog.html",
                        controller: "editQQPersonalQr",
                        data: { qrcode: qrcode }
                    })
                    .closePromise.then(function(data) {
                    if (data.value && data.value.status == 0) {
                        tip.success("保存成功!");
                        setTimeout(function() {
                            s.table.getList();
                        }, 1000);
                    }
                });
            };

            s.deleteQR = function(qrcode) {
                var ids,
                    msg = "确定要删除此条信息？";

                if (qrcode) ids = qrcode.id;

                if (s.checkValue.length > 0) {
                    ids = s.checkValue.join(",");
                    msg = "确定要删除选中信息？";
                }

                if (ids) {
                    ngDialog
                        .open({
                            template:
                            '<div class="confirm-dialog"> \
                            <h2>' +
                            msg +
                            '</h2>\
                            <div align="center">\
                                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                            </div></div>',
                            plain: true
                        })
                        .closePromise.then(function(data) {
                        if (data.value && data.value == "CONFIRM") {
                            http.get("/admin/deleteSingleQrcode/" + ids, {}, function(res) {
                                if (res.status == 0) {
                                    s.checkValue = [];
                                    tip.success("删除成功!");
                                    s.table.getList();
                                }else{
                                    tip.error(res.msg);
                                }
                            });
                        }
                    });
                }
            };

            s.updateStatus = function(list, status) {
                var msg;
                switch (status) {
                    case 0:
                        msg = "确定要解锁此二维码？";
                        break;
                    case 1:
                        msg = "确定要锁定此二维码";
                        break;
                    case 2:
                        msg = "确定要屏蔽此二维码？";
                        break;
                    case 3:
                        msg = "确定要启用此二维码？";
                        status = 0;
                        break;
                }

                ngDialog
                    .open({
                        template:
                        '<div class="confirm-dialog"> \
                        <h2>' +
                        msg +
                        '</h2>\
                        <div align="center">\
                            <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                            <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                        </div></div>',
                        plain: true
                    })
                    .closePromise.then(function(data) {
                    if (data.value && data.value == "CONFIRM") {
                        list.status = status;
                        http.post("/admin/updateSingleQrcode/" + list.id, list, function(res) {
                            if (res.status == 0) {
                                tip.success("操作成功!");
                                s.table.getList();
                            }
                        });
                    }
                });
            };
        }
    ]);
})();
