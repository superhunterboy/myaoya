app.controller("payList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "vendorsType", "$stateParams",
    function(e, t, a, n, i, p, o) {
        e.payType = o.payType,
            e.vendorsType = p,
            e.vendorsTypeList = [];
        for (var s in e.vendorsType)"0" == s ? e.vendorsTypeList.push({
            id: "",
            name: "全部"
        }) : e.vendorsTypeList.push({
            id: s,
            name: e.vendorsType[s]
        });
        t.patchName = ["payList", "failList", "wechatList", "alipayList"][e.payType],
            e.companyInfo = {},
            n.get("/admin/companies", {
                    page: 1,
                    perPage: 999
                },
                function(t) {
                    if (t.data) for (var a = 0; a < t.data.length; a++) e.companyInfo[t.data[a].id] = t.data[a]
                }),
            e.pageSet = store.get("pageSet") || ["20", "20", "20", "20"],
            e.table = a.init({
                link: "/admin/pays",
                query: {
                    perPage: e.pageSet[e.payType],
                    status: e.payType
                }
            }),
            e.table.vendorType = "",
            e.table.getList(),
            e.total = e.table.total,
            e.act = function(t, a) {
                i.open({
                    template: '<div class="confirm-dialog">                 <h2>您确定要将订单号为“' + t.order_no + "”的支付记录标记为" + ["已入款", "已忽略", "恢复"][a - 1] + '吗？</h2>                <div align="center">                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                </div></div>',
                    plain: !0
                }).closePromise.then(function(i) {
                    i.value && "CONFIRM" == i.value && n.put("/admin/pay/" + t.id, {
                            rk_status: a
                        },
                        function(t) {
                            0 == t.status && (e.table.getList(), e.$emit("getNew"))
                        })
                })
            },
            e.$on("newPay",
                function() {
                    e.table.getList()
                }),
            e.setPage = function() {
                store.set("pageSet", e.pageSet),
                    e.table.query.perPage = e.pageSet[e.payType],
                    e.table.getList(1)
            },
            e.export = function() {
                var t = $('<iframe id="down-file-iframe" />'),
                    a = $('<form target="down-file-iframe" method="get" />');
                a.attr("action", "/admin/pays");
                for (var n in e.table.query) a.append('<input type="hidden" name="' + n + '" value="' + e.table.query[n] + '" />');
                a.append('<input type="hidden" name="down_excel" value="1" />'),
                    t.append(a),
                    $(document.body).append(t),
                    a[0].submit(),
                    t.remove()
            }
    }]);