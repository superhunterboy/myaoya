(function() {
    app.controller("wechatPayList", [
        "$scope",
        "$rootScope",
        "Table",
        "http",
        "ngDialog",
        "Tip",
        function(s, rs, Table, http, ngDialog, tip) {
            s.table = Table;
            s.setting = {
                isEdit: false,
                placeholder: "二维码编号，会员账户",
                currentTable: "personal",
                query: {}
            };

            s.temp;
            s.dict = { "0": "加微信好友支付", "1": "个人微信扫码", "2": "商家微信扫码", "3": "智能微信扫码" };

            s.ptable = angular.copy(s.table);
            s.mtable = angular.copy(s.table);
            s.gtable = angular.copy(s.table);

            s.displayTable = function(name) {
                s.setting.currentTable = name;
                switch (name) {
                    case "personal":
                        s.setting.query = s.ptable.query;
                        s.setting.placeholder = "二维码编号，会员账户";
                        break;
                    case "merchant":
                        s.setting.query = s.mtable.query;
                        s.setting.placeholder = "会员账户，单号，商户编码，商户名称";
                        break;
                    case "group":
                        s.setting.query = s.gtable.query;
                        s.setting.placeholder = "支付微信号，会员账号，收款微信号";
                        break;
                }
            };

            s.search = function() {
                switch (s.setting.currentTable) {
                    case "personal":
                        s.ptable.query = $.extend(s.ptable.query, s.setting.query);
                        s.ptable.getList(1);
                        break;
                    case "merchant":
                        s.mtable.query = $.extend(s.mtable.query, s.setting.query);
                        s.mtable.getList(1);
                        break;
                    case "group":
                        s.gtable.query = $.extend(s.gtable.query, s.setting.query);
                        s.gtable.getList(1);
                        break;
                }
            };

            s.act = function(list, n, type) {
                var apiUrl,temp;
                if (type == "personal"){
                    apiUrl = "/admin/updatePaySingleState/" + list.id;
                    temp=n;
                }

                else if (type == "merchant") {
                    apiUrl = "/admin/updateOrder/" + list.id;
                    temp=n;
                }
                else if (type == "group") {
                    apiUrl = "/admin/updatePayQrcodeStatus/" + list.id;
                    temp=n/2;
                }
                ngDialog
                    .open({
                        template:
                        '<div class="confirm-dialog"> \
                      <h2>您确定要将此记录标记为' +
                        ["已入款", "已忽略"][temp - 1] +
                        '吗？</h2>\
                      <div align="center">\
                          <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                          <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                      </div></div>',
                        plain: true
                    })
                    .closePromise.then(function(data) {
                    if (data.value && data.value == "CONFIRM") {
                        http.post(apiUrl, { id: list.id, status: n }, function(res) {
                            if (res.status == 0) {
                                tip.success("操作成功!");
                                s.search(type);
                            } else {
                                tip.error(res.msg);
                            }
                        });
                    }
                });
            };

            s.beginEdit = function() {
                s.setting.isEdit = true;
                s.temp = s.setting.wechat_single_qrcode;
            };

            s.cancelEdit = function() {
                s.setting.isEdit = false;
                s.setting.wechat_single_qrcode = s.temp;
            };

            s.updateWechatSetting = function() {
                http.post(
                    "/admin/updateSetting/" + 6,
                    { id: 6, val: s.setting.wechat_single_qrcode },
                    function(res) {
                        if (res.status == 0) {
                            tip.success("修改成功!");
                            s.setting.isEdit = false;
                        } else {
                            tip.error("网络错误");
                        }
                    }
                );
            };

            s.getData = function() {
                http.get("/admin/getWechat", {}, function(res) {
                    if (res.status == 0 && res.data[0]) {
                        s.setting.wechat_single_qrcode = res.data[0].val + "";
                    } else {
                        tip.error("网络错误");
                    }
                });
            };
            s.getData();

            s.ptable.init({ link: "/admin/paySingles" }).getList();
            s.mtable
                .init({ link: "/admin/PayMerchants", query: { type: 1 } })
                .getList();
            s.gtable.init({ link: "/admin/getPayQrcodes" }).getList();
            s.editRemark = function (list) {
                ngDialog
                    .open({
                        template: "template/wechatDialog.html",
                        controller: "wechatRemarkEdit",
                        data: list
                    })
                    .closePromise.then(function (data) {
                    if (data && data.value.status == 0) {
                        s.gtable.getList();
                        tip.success("修改成功!");
                    }
                });
            };
        }
    ]);
    app.controller("wechatRemarkEdit", [
        "$scope",
        "http",
        "$rootScope",
        function (scope, http, rootScope) {
            scope.list = angular.copy(scope.ngDialogData);
            scope.check = function () {
                if (scope.list.result.length > 50) {
                    scope.error = true;
                    scope.errorMsg = "备注过长，请缩减至50字以内";
                    return false;
                }
                return true;
            };

            scope.sub = function () {
                if (scope.check()) {
                    http.post(
                        "/admin/updatePayQrcodeRemark/" + scope.list.id,
                        {
                            remark: scope.list.result
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
})();
