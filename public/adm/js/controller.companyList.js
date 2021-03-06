app.controller("companyEdit", ["$scope", "http", "vendorsType",
    function(a, n, o) {
        a.company = {},
            a.vendorsType = o,
        a.ngDialogData && (a.companyId = a.ngDialogData.id, a.company.name = a.ngDialogData.name, a.company.url = a.ngDialogData.url, a.company.autorecharge_url = a.ngDialogData.autorecharge_url, a.company.wechat_vendor_id = String(a.ngDialogData.wechat_vendor_id), a.company.alipay_vendor_id = String(a.ngDialogData.alipay_vendor_id), a.company.qq_vendor_id = String(a.ngDialogData.qq_vendor_id), a.company.jd_vendor_id = String(a.ngDialogData.jd_vendor_id), a.company.baidu_vendor_id = String(a.ngDialogData.baidu_vendor_id), a.company.union_vendor_id = String(a.ngDialogData.union_vendor_id), a.company.wap_wechat_vendor_id = String(a.ngDialogData.wap_wechat_vendor_id), a.company.wap_alipay_vendor_id = String(a.ngDialogData.wap_alipay_vendor_id), a.company.wap_qq_vendor_id = String(a.ngDialogData.wap_qq_vendor_id), a.company.wap_jd_vendor_id = String(a.ngDialogData.wap_jd_vendor_id), a.company.wap_baidu_vendor_id = String(a.ngDialogData.wap_baidu_vendor_id), a.company.wap_union_vendor_id = String(a.ngDialogData.wap_union_vendor_id), a.company.netbank_vendor_id = String(a.ngDialogData.netbank_vendor_id), a.company.is_autorecharge = String(a.ngDialogData.is_autorecharge), a.company.is_5qrcode = String(a.ngDialogData.is_5qrcode), a.ngDialogData.wechatVendorList ? a.wechatVendorList = a.ngDialogData.wechatVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/1", {},
            function(n) {
                0 == n.status ? a.wechatVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.alipayVendorList ? a.alipayVendorList = a.ngDialogData.alipayVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/2", {},
            function(n) {
                0 == n.status ? a.alipayVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.netbankVendorList ? a.netbankVendorList = a.ngDialogData.netbankVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/3", {},
            function(n) {
                0 == n.status ? a.netbankVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.qqVendorList ? a.qqVendorList = a.ngDialogData.qqVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/4", {},
            function(n) {
                0 == n.status ? a.qqVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.jdVendorList ? a.jdVendorList = a.ngDialogData.jdVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/5", {},
            function(n) {
                0 == n.status ? a.jdVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.baiduVendorList ? a.baiduVendorList = a.ngDialogData.baiduVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/6", {},
            function(n) {
                0 == n.status ? a.baiduVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.unionVendorList ? a.unionVendorList = a.ngDialogData.unionVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/7", {},
            function(n) {
                0 == n.status ? a.unionVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapAlipayVendorList ? a.wapAlipayVendorList = a.ngDialogData.wapAlipayVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/9", {},
            function(n) {
                0 == n.status ? a.wapAlipayVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapBaiduVendorList ? a.wapBaiduVendorList = a.ngDialogData.wapBaiduVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/12", {},
            function(n) {
                0 == n.status ? a.wapBaiduVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapJdVendorList ? a.wapJdVendorList = a.ngDialogData.wapJdVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/11", {},
            function(n) {
                0 == n.status ? a.wapJdVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapQqVendorList ? a.wapQqVendorList = a.ngDialogData.wapQqVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/10", {},
            function(n) {
                0 == n.status ? a.wapQqVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapUnionVendorList ? a.wapUnionVendorList = a.ngDialogData.wapUnionVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/13", {},
            function(n) {
                0 == n.status ? a.wapUnionVendorList = n.data: console.log(n.msg)
            }), a.ngDialogData.wapWechatVendorList ? a.wapWechatVendorList = a.ngDialogData.wapWechatVendorList: n.get("/admin/getVendorsByCompanyId/" + a.companyId + "/8", {},
            function(n) {
                0 == n.status ? a.wapWechatVendorList = n.data: console.log(n.msg)
            })),
            a.check = function() {
                return a.company.name ? !(1 == a.company.is_autorecharge && !a.company.autorecharge_url) || (a.error = !0, a.errorMsg = "请填写自动入款接口！", !1) : (a.error = !0, a.errorMsg = "请填写平台名称！", !1)
            },
            a.save = function() { ! a.loading && a.check() && (a.loading = !0, n.post("/admin/companies", a.company,
                function(n) {
                    a.loading = !1,
                        0 == n.status ? a.closeThisDialog(n) : (a.error = !0, a.errorMsg = n.msg)
                }))
            },
            a.update = function() { ! a.loading && a.check() && (a.loading = !0, n.put("/admin/companies/" + a.companyId, a.company,
                function(n) {
                    a.loading = !1,
                        0 == n.status ? a.closeThisDialog(n) : (a.error = !0, a.errorMsg = n.msg)
                }))
            }
    }]),
    app.controller("companyList", ["$scope", "$rootScope", "Table", "http", "ngDialog", "vendorsType", "Tip",
        function(a, n, o, i, t, d, e) {
            n.patchName = "companyList",
                a.vendorsType = d,
                a.vendorInfo = {},
                i.get("/admin/vendors", {
                        page: 1,
                        perPage: 999
                    },
                    function(n) {
                        if (n.data) {
                            for (var o = 0; o < n.data.length; o++) a.vendorInfo[n.data[o].id] = n.data[o];
                            a.vendorInfo[0] = {
                                pay_type: "0"
                            }
                        }
                    }),
                a.dictionary = {
                    id: ["wechat_vendor_id", "alipay_vendor_id", "netbank_vendor_id", "qq_vendor_id", "jd_vendor_id", "baidu_vendor_id", "union_vendor_id", "wap_wechat_vendor_id", "wap_alipay_vendor_id", "wap_qq_vendor_id", "wap_jd_vendor_id", "wap_baidu_vendor_id", "wap_union_vendor_id","yun_vendor_id","wap_yun_vendor_id"],
                    list: ["wechatVendorList", "alipayVendorList", "netbankVendorList", "qqVendorList", "jdVendorList", "baiduVendorList", "unionVendorList", "wapWechatVendorList", "wapAlipayVendorList", "wapQqVendorList", "wapJdVendorList", "wapBaiduVendorList", "wapUnionVendorList","yunVendorList","wapyunVendorList"],
                    model: ["wechatChangeModel", "alipayChangeModel", "netbankChangeModel", "qqChangeModel", "jdChangeModel", "baiduChangeModel", "unionChangeModel", "wapWechatChangeModel", "wapAlipayChangeModel", "wapQqChangeModel", "wapJdChangeModel", "wapBaiduChangeModel", "wapUnionChangeModel","yunChangeModel","wapyunChangeModel"]
                },
                a.changeVendor = function(n, o) {
                    n[a.dictionary.id[o - 1]] = String(n[a.dictionary.id[o - 1]]),
                        i.get("/admin/getVendorsByCompanyId/" + n.id + "/" + o, {},
                            function(i) {
                                0 == i.status ? n[a.dictionary.list[o - 1]] = i.data: (n[a.dictionary.list[o - 1]] = [], n[a.dictionary.model[o - 1]] = !0),
                                    n[a.dictionary.model[o - 1]] = !0
                            })
                },
                a.changeVendorConfirm = function(n, o) {
                    var t = {};
                    t[a.dictionary.id[o - 1]] = n[a.dictionary.id[o - 1]],
                        i.put("/admin/changeVendorByCompanyId/" + n.id, t,
                            function(i) {
                                0 == i.status ? n[a.dictionary.model[o - 1]] = !1 : e.error(i.msg)
                            })
                },
                a.table = o.init({
                    link: "/admin/companies"
                }),
                a.table.getList(),
                a.editCompany = function(n) {
                    t.open({
                        template: "template/companyEditDialog.html",
                        controller: "companyEdit",
                        data: n
                    }).closePromise.then(function(n) {
                        n.value && 0 == n.value.status && a.table.getList()
                    })
                },
                a.delCompany = function(n) {
                    t.open({
                        template: '<div class="confirm-dialog">             <h2>您确定要删除业务平台“' + n.name + '”吗？</h2>            <div align="center">                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>            </div></div>',
                        plain: !0
                    }).closePromise.then(function(o) {
                        o.value && "CONFIRM" == o.value && i.delete("/admin/companies/" + n.id,
                            function(n) {
                                0 == n.status && a.table.getList()
                            })
                    })
                }
        }]);