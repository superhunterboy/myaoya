(function() {
    app.controller("bankPayRemarkEdit", [
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
                        "/admin/updateOfflinePayStatus/" + scope.list.id,
                        {
                            id:scope.list.id,
                            remark: scope.list.remark || "",
                            status:scope.list.status
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
    app.controller('bankPayList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','Tip', function (s, rs, Table, http, ngDialog,tip) {
        s.table =Table.init({link: "/admin/offlinePays",query:{type:1}});
        s.table.query.status = "";
        s.table.getList();

        s.search = function (param) {
            s.table.query.status = param;
            s.table.getList(1);
        };

        s.act=function (list,n) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                <h2>您确定要将订单号为“'+list.order_no+'”的支付记录标记为'+['已入款','已忽略'][n-1]+'吗？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.post("/admin/updateOfflinePayStatus/"+list.id,{
                        id:list.id,
                        remark: list.remark || "",
                        status:n},function (res) {
                        if(res.status==0){
                            s.table.getList();
                        }
                    })
                }
            });
        };
        s.editRemark = function (list) {
            ngDialog
                .open({
                    template: "template/payOutRemarkDialog.html",
                    controller: "bankPayRemarkEdit",
                    data: list
                })
                .closePromise.then(function (data) {
                if (data && data.value.status == 0) {
                    s.table.getList();
                    tip.success("修改成功!");
                }
            });
        };
        s.$on('newPay',function () {
            s.table.getList();
        });
    }])
})()
