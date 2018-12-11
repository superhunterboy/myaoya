(function() {
    app.controller("qqPayList", [
        "$scope",
        "$rootScope",
        "Table",
        "http",
        "ngDialog",
        "Tip",
        function(s, rs, Table, http, ngDialog, tip) {
            s.table = Table;
            s.table.init({ link: "/admin/paySingles" ,query: { type: 3 } }).getList();
            s.act = function(list, n) {
                ngDialog
                    .open({
                        template:
                        '<div class="confirm-dialog"> \
                      <h2>您确定要将此记录标记为' +
                        ["已入款", "已忽略"][n - 1] +
                        '吗？</h2>\
                      <div align="center">\
                          <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                          <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                      </div></div>',
                        plain: true
                    })
                    .closePromise.then(function(data) {
                    if (data.value && data.value == "CONFIRM") {
                        http.post("/admin/updatePaySingleState/"+ list.id, { id: list.id, status: n }, function(res) {
                            if (res.status == 0) {
                                tip.success("操作成功!");
                                s.table.getList();
                            } else {
                                tip.error(res.msg);
                            }
                        });
                    }
                });
            };
        }
    ]);
})();
