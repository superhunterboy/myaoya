app.controller("yunPayList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "Tip",
    function(t, i, n, e, a, o) {
        t.table = n,
            t.table.init({
                link: "/admin/paySingles",
                query: {
                    type: 4
                }
            }).getList(),
            t.act = function(i, n) {
                a.open({
                    template: '<div class="confirm-dialog">                       <h2>您确定要将此记录标记为' + ["已入款", "已忽略"][n - 1] + '吗？</h2>                      <div align="center">                          <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                          <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                      </div></div>',
                    plain: !0
                }).closePromise.then(function(a) {
                    a.value && "CONFIRM" == a.value && e.post("/admin/updatePaySingleState/" + i.id, {
                            id: i.id,
                            status: n
                        },
                        function(i) {
                            0 == i.status ? (o.success("操作成功!"), t.table.getList()) : o.error(i.msg)
                        })
                })
            }
    }]);