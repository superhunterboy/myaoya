app.controller("alipayRecords", ["$scope", "$rootScope", "Table", "http", "ngDialog", "Tip",
    function(t, e, a, n, i, s) {
        t.table = a,
            t.setting = {
                isEdit: !1,
                placeholder: "会员账户、银行账号",
                currentTable: "transfer",
                query: {}
            },
            t.dict = {
                transfer: !1,
                personal: !1,
                merchant: !1
            },
            t.temp = {},
            t.ptable = angular.copy(t.table),
            t.mtable = angular.copy(t.table),
            t.gtable = angular.copy(t.table),
            t.displayTable = function(e) {
                switch (t.setting.currentTable = e, e) {
                    case "personal":
                        t.setting.query = t.ptable.query,
                            t.setting.placeholder = "二维码编号、会员账户";
                        break;
                    case "merchant":
                        t.setting.query = t.mtable.query,
                            t.setting.placeholder = "会员账户，单号，商户编码，商户名称";
                        break;
                    case "transfer":
                        t.setting.query = t.gtable.query,
                            t.setting.placeholder = "会员账户、银行账号"
                }
            },
            t.search = function() {
                switch (t.setting.currentTable) {
                    case "personal":
                        t.ptable.query = $.extend(t.ptable.query, t.setting.query),
                            t.ptable.getList(1);
                        break;
                    case "merchant":
                        t.mtable.query = $.extend(t.mtable.query, t.setting.query),
                            t.mtable.getList(1);
                        break;
                    case "transfer":
                        t.gtable.query = $.extend(t.gtable.query, t.setting.query),
                            t.gtable.getList(1)
                }
            },
            t.act = function(e, a, r) {
                var l, c;
                "personal" == r ? (l = "/admin/updatePaySingleState/" + e.id, c = {
                    id: e.id,
                    status: a
                }) : "merchant" == r ? (l = "/admin/updateOrder/" + e.id, c = {
                    id: e.id,
                    status: a
                }) : "transfer" == r && (l = "/admin/updateOfflinePayStatus/" + e.id, c = {
                    id: e.id,
                    status: a,
                    type: 2
                }),
                    i.open({
                        template: '<div class="confirm-dialog">               <h2>您确定要将此记录标记为' + ["已入款", "已忽略", "已恢复"][a - 1] + '吗？</h2>              <div align="center">                  <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                  <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>              </div></div>',
                        plain: !0
                    }).closePromise.then(function(e) {
                        e.value && "CONFIRM" == e.value && n.post(l, c,
                            function(e) {
                                0 == e.status ? (s.success("操作成功!"), t.search(r)) : s.error(e.msg)
                            })
                    })
            },
            t.beginEdit = function() {
                t.setting.isEdit = !0,
                    t.temp = angular.copy(t.dict)
            },
            t.cancelEdit = function() {
                t.setting.isEdit = !1,
                    t.dict = angular.copy(t.temp)
            },
            t.updateAliSetting = function() {
                var e = [];
                t.dict.transfer && e.push(1),
                t.dict.personal && e.push(2),
                t.dict.merchant && e.push(3),
                    n.post("/admin/updateSetting/7", {
                            id: 7,
                            val: e.join(",")
                        },
                        function(e) {
                            0 == e.status ? (s.success("修改成功!"), t.setting.isEdit = !1) : s.error("网络错误")
                        })
            },
            t.getData = function() {
                n.get("/getAliPay", {},
                    function(e) {
                        0 == e.status && e.data[0] ? ( - 1 != e.data[0].val.indexOf("1") && (t.dict.transfer = !0), -1 != e.data[0].val.indexOf("2") && (t.dict.personal = !0), -1 != e.data[0].val.indexOf("3") && (t.dict.merchant = !0)) : s.error("网络错误")
                    })
            },
            t.getData(),
            t.ptable.init({
                link: "/admin/paySingles",
                query: {
                    type: 2
                }
            }).getList(),
            t.mtable.init({
                link: "/admin/PayMerchants",
                query: {
                    type: 2
                }
            }).getList(),
            t.gtable.init({
                link: "/admin/offlinePays",
                query: {
                    type: 2
                }
            }).getList()
    }]);