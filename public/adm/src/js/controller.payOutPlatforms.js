(function () {
    app.controller("rechargeLinks", ["$scope","http","Table","ngDialog","Tip","payOutType", function (s, http,Table,ngDialog,tip, payOutType) {
        s.location=window.location;
        s.payOutType = payOutType;
        s.platform=s.ngDialogData.platform;
        s.t=Table;
        s.table=angular.copy(s.t);
        s.table.init({link: "/admin/rechargeLinks",query:{prePage:10}});
        s.table.query.platformId="";
        if(s.platform)
            s.table.query.platformId=s.platform.id;
        s.table.getList();
        http.get("/admin/platforms", {page: 1, perPage: 999}, function (res) {
            if (res.data) {
                s.platformsList = res.data;
            }
        });
        s.copy=function (id) {
            $("#"+id)[0].select();
            document.execCommand('copy');
            tip.success("复制链接成功！");
        };
        s.changeStatus=function (list) {
            http.get("/admin/updateLinkStatus/" + list.id, {}, function (res) {
                if (res.status == 0) {
                    s.table.getList();
                    tip.success("切换成功");
                }
            });
        }
        s.delLink = function (list) {
            ngDialog
                .open({
                    template:
                    '<div class="confirm-dialog"> \
              <h2>您确定要删除充值外链“'+list.remark +'”吗？</h2>\
              <div align="center">\
                  <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                  <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
              </div></div>',
                    plain: true
                })
                .closePromise.then(function (data) {
                if (data.value && data.value == "CONFIRM") {
                    http.get("/admin/deleteLink/" + list.id, {}, function (res) {
                        if (res.status == 0) {
                            s.table.getList();
                            tip.success("删除成功");
                        }else{
                            tip.error(res.msg)
                        }
                    });
                }
            });
        };
        s.addLink=function () {
            ngDialog
                .open({
                    template: "template/addRechargeLinksDialog.html",
                    controller: "addRechargeLinks",
                    data: {platformsList: s.platformsList}
                })
                .closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    s.table.getList();
                    tip.success("添加成功");
                }
            });
        }
    }]);
    app.controller("addRechargeLinks", ["$scope","http","Table","payOutType", function (s, http,Table, payOutType) {
        s.payOutType = payOutType;
        s.platformsList=s.ngDialogData.platformsList;
        s.link={};
        s.check=function () {
            if (!s.link.platform_id) {
                s.error = true;
                s.errorMsg = "请选择出款平台！";
                return false;
            }
            if (!s.link.remark) {
                s.error = true;
                s.errorMsg = "请填写备注！";
                return false;
            }
            return true;
        };
        s.sub=function () {
            if (!s.loading && s.check()) {
                s.loading = true;
                http.post("/admin/addLink", s.link, function (res) {
                    s.loading = false;
                    if (res.status == 0) {
                        s.closeThisDialog(res);
                    } else {
                        s.error = true;
                        s.errorMsg = res.msg;
                    }
                });
            }
        }
    }]);
    app.controller("chargeList", ["$scope","http","Table","payOutType", function (s, http,Table, payOutType) {
        s.payOutType = payOutType;
        s.platform=s.ngDialogData;
        s.t=Table;
        s.table=angular.copy(s.t);
        s.table.init({link: "/admin/getRecharges",query:{platformId:s.platform.id,prePage:10}});
        s.table.getList();
    }]);
    app.controller("payOutPlatformEdit", [
        "$scope",
        "http",
        "payOutType",
        function (s, http, payOutType) {
            s.platform = {};
            s.payOutType = payOutType;
            s.companyInfo = s.ngDialogData.companyInfo;
            if (s.ngDialogData.platform) {
                s.id = s.ngDialogData.platform.id;
                s.platform.id = s.ngDialogData.platform.id;
                s.platform.company_id = String(s.ngDialogData.platform.company_id);
                s.platform.pay_out_type = String(s.ngDialogData.platform.pay_out_type);
                s.platform.no = s.ngDialogData.platform.no;
                s.platform.key = s.ngDialogData.platform.key;
                s.platform.callback_url = s.ngDialogData.platform.callback_url;
                s.platform.notify_url = s.ngDialogData.platform.notify_url;
            }
            s.check = function () {
                if (!s.platform.company_id) {
                    s.error = true;
                    s.errorMsg = "请选择所属业务平台！";
                    return false;
                }
                if (!s.platform.pay_out_type) {
                    s.error = true;
                    s.errorMsg = "请选择支付平台！";
                    return false;
                }
                if (!s.platform.no) {
                    s.error = true;
                    s.errorMsg = "请填写商户编号！";
                    return false;
                }
                if (!s.platform.key) {
                    s.error = true;
                    s.errorMsg = "请填写商户key！";
                    return false;
                }
                if (!s.platform.callback_url) {
                    s.error = true;
                    s.errorMsg = "请填写回调地址！";
                    return false;
                }
                if (!s.platform.notify_url) {
                    s.error = true;
                    s.errorMsg = "请填写通知地址！";
                    return false;
                }
                return true;
            };
            s.save = function () {
                if (!s.loading && s.check()) {
                    s.loading = true;
                    http.post("/admin/addPlatform", s.platform, function (res) {
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
            s.update = function () {
                if (!s.loading && s.check()) {
                    s.loading = true;
                    http.post("/admin/editPlatform/" + s.id, s.platform, function (res) {
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
        }
    ]);
    app.controller("changePlatform", ["$scope","http","Tip", function (s, http,tip) {
        s.subDate={
            type:s.ngDialogData.type
        };
        s.listId=s.ngDialogData.platform.id;
        s.time=0;
        s.sendCode=function(){
            if(s.time==0){
                http.get("/admin/sendCode",{},function(res){
                    if(res.status==0) 
                        tip.success("发送成功")
                    else{
                        tip.error(res.msg)
                    }
                });
                s.time=30;
                var timer=setInterval(function(){
                    s.time--;
                    if(s.time==0){
                        clearInterval(timer);
                    }
                    s.$apply();
                },1000)
            }
        }
        s.check=function () {
            if (!s.subDate.code) {
                s.error = true;
                s.errorMsg = "请填写手机验证码！";
                return false;
            }
            return true;
        };
        s.sub=function () {
            if (!s.loading && s.check()) {
                s.loading = true;
                http.post("/admin/changePlatform/" + s.listId, s.subDate, function (res) {
                    s.loading = false;
                    if (res.status == 0) {
                        s.closeThisDialog(res);
                    } else {
                        s.error = true;
                        s.errorMsg = res.msg;
                    }
                });
            }
        }
    }])
    app.controller("payOutPlatforms", [
        "$scope",
        "$rootScope",
        "Table",
        "http",
        "ngDialog",
        "$stateParams",
        "Tip",
        "payOutType",
        function (s, rs, Table, http, ngDialog, $stateParams, tip, payOutType) {

            s.saveOutLimit = function (list) {
                if (list.startOutLimitTemp && list.startOutLimitTemp) {
                    if(parseFloat(list.startOutLimitTemp)<=parseFloat(list.endOutLimitTemp)){
                        http.post(
                            "/admin/updateAmountLimit/" + list.id,
                            {start_amount_limit: list.startOutLimitTemp,end_amount_limit: list.endOutLimitTemp},
                            function (res) {
                                if (res.status == 0) {
                                    list.start_amount_limit = list.startOutLimitTemp;
                                    list.end_amount_limit = list.endOutLimitTemp;
                                    list.outLimitStatus = 0;
                                    tip.success("保存成功");
                                } else {
                                    tip.error(res.msg);
                                }
                            }
                        );
                    }
                    else{
                        tip.error("请输入正确的出款额度范围");
                    }
                } else {
                    tip.error("请输入正确的出款额度范围");
                }
            };
            s.payOutType = payOutType;
            s.companyInfo = {};
            http.get("/admin/companies", {page: 1, perPage: 999}, function (res) {
                if (res.data) {
                    for (var i = 0; i < res.data.length; i++) {
                        s.companyInfo[res.data[i].id] = res.data[i];
                    }
                }
            });

            s.table = Table.init({link: "/admin/platforms"});
            s.table.getList();

            s.editPlatform = function (list) {
                ngDialog
                    .open({
                        template: "template/payOutPlatformEdit.html",
                        controller: "payOutPlatformEdit",
                        data: {platform: list, companyInfo: s.companyInfo}
                    })
                    .closePromise.then(function (data) {
                    if (data.value && data.value.status == 0) {
                        s.table.getList();
                        tip.success("保存成功");
                    }
                });
            };

            s.rechargeLinks=function (list) {
                ngDialog.open({
                    template: "template/rechargeLinksDialog.html",
                    controller: "rechargeLinks",
                    data: {platform: list},
                    className:"ngdialog-theme-default max"
                })
            }

            s.delPlatform = function (list) {
                ngDialog
                    .open({
                        template:
                        '<div class="confirm-dialog"> \
                  <h2>您确定要删除出款平台配置“' +
                        s.companyInfo[list.company_id].name +
                        "-" +
                        s.payOutType[list.pay_out_type] +
                        '”吗？</h2>\
                  <div align="center">\
                      <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                      <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                  </div></div>',
                        plain: true
                    })
                    .closePromise.then(function (data) {
                    if (data.value && data.value == "CONFIRM") {
                        http.get("/admin/deletePlatform/" + list.id, {}, function (res) {
                            if (res.status == 0) {
                                s.table.getList();
                                tip.success("删除成功");
                            }
                        });
                    }
                });
            };

            s.changePlatform = function (list,n) {
                ngDialog.open({
                        template: "template/changePlatformDialog.html",
                        controller: "changePlatform",
                        data: {platform:list,type:n}
                    })
                    .closePromise.then(function (data) {
                    if (data.value && data.value.status == 0) {
                        s.table.getList();
                        tip.success("切换成功");
                    }
                });

            };
            s.charge = function (list) {
                window.open("/admin/platformRecharge?type=" + list.pay_out_type + "&companyId=" + list.company_id + "&platformId=" + list.id, "", "height=800, width=800, top=80, left=600, toolbar=no, menubar=no,location=no, status=no")
            };
            s.chargeList = function (list) {
                ngDialog.open({
                    template: "template/chargeListDialog.html",
                    controller: "chargeList",
                    data: list,
                    className:"ngdialog-theme-default max"
                })
            };
            s.refresh=function (list) {
                http.get("/admin/queryBalance/"+list.id,{},function (res) {
                    if (res.status == 0) {
                        tip.success("更新成功")
                        list.balance=res.data.balance;
                    }else{
                        tip.error(res.msg)
                    }
                })
            }
        }
    ]);
})();
