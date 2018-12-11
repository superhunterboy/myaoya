(function() {
    app.controller("bankEdit",["$scope","http",function (s, http) {
        s.list={
            count:0,
            level_ids:"",
            type:2
        };
        s.url="/admin/addBankCard";s.levels=[];
        if(s.ngDialogData){
            s.list=angular.copy(s.ngDialogData);
            s.url="/admin/updateBankCard/"+s.list.id;
            s.levels=s.list.level_ids.split(",");
        }
        http.get("/admin/levels",{},function (res) {
            if(res.status==0){
                s.levelList=res.data;
                if(s.levels.length>0){
                    s.levelList.forEach(function (level) {
                        if(s.levels.indexOf(""+level.id)>-1){
                            level.checked=true
                        }
                    })
                }

            }
        });
        s.check=function () {
            if(!s.list.bank_name){
                s.error=true;
                s.errorMsg="请填写银行名称！";
                return false;
            }
            if(!s.list.bank_number){
                s.error=true;
                s.errorMsg="请填写银行卡号！";
                return false;
            }
            if(!s.list.user_name){
                s.error=true;
                s.errorMsg="请填写银行户名！";
                return false;
            }
            var levels=[];
            s.levelList.forEach(function (level) {
                if(level.checked){
                    levels.push(level.id)
                }
            });
            s.list.level_ids=levels.join(",");
            return true
        }
        s.sub=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post(s.url,s.list,function (res) {
                    s.loading=false;
                    if(res.status==0){
                        s.closeThisDialog(res)
                    }else{
                        s.error=true;
                        s.errorMsg=res.msg;
                    }
                })
            }
        };
    }])
    app.controller("bankSetting", ["$scope", "$rootScope", "http","Table","ngDialog", "Tip", function(s, rs, http,Table,ngDialog, tip) {
        s.table =Table.init({link: "/admin/bankCards?type=2"});
        s.table.getList();
        http.get("/admin/levels",{},function (res) {
            if(res.status==0){
                s.levelList=res.data;
                s.levelObj={};
                s.levelList.forEach(function (level) {
                    s.levelObj[""+level.id]=level.name;
                })
            }
        });
        s.edit=function (list) {
            ngDialog.open({
                template:"template/bankEditDialog.html",
                controller:"bankEdit",
                data:list
            }).closePromise.then(function (data) {
                if (data.value && data.value.status==0) {
                    s.table.getList();
                }
            });
        };
        s.del=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要删除银行卡“'+list.bank_number+'”吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.get("/admin/deleteBankCard/"+list.id,{},function (res) {
                        if(res.status==0){
                            s.table.getList(1);
                            tip.success("删除成功")
                        }
                    })
                }
            });
        }
        s.act=function (list,n) {
            http.post("/admin/updateBankCard/"+list.id,{status:n},function (res) {
                if(res.status==0){
                    s.table.getList();
                    tip.success("操作成功")
                }else{
                    tip.error(res.msg)
                }
            })
        }

    }]);
})();
