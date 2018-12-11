(function() {
    app.controller('editUser',['$scope', 'http', function (s, http) {
        s.user={};
        s.userCompanyIds=[];
        s.companyInfo=angular.copy(s.ngDialogData.companyInfo);
        if(s.ngDialogData.user){
            s.userId=s.ngDialogData.user.id;
            s.user.id=s.ngDialogData.user.id;
            s.user.type=''+s.ngDialogData.user.type;
            s.user.realname=s.ngDialogData.user.realname;
            s.user.username=s.ngDialogData.user.username;
            s.user.company_ids=s.ngDialogData.user.company_ids;
            // s.user.permissions=s.ngDialogData.user.permissions;
            s.userCompanyIds=s.user.company_ids.split(",");
            for(var i =0;i<s.userCompanyIds.length;i++){
                s.companyInfo[s.userCompanyIds[i]].checked=true;
            }
        };
        // http.get("/admin/getPermissions",{},function (res) {
        //    if(res.status==0){
        //        s.permissions=res.data;
        //        for(var i=0;i<s.permissions.length;i++){
        //            for(var j=0;j<s.permissions[i].permission.length;j++){
        //                if(s.user.permissions.split(",").indexOf(''+s.permissions[i].permission[j].id)>-1)
        //                    s.permissions[i].permission[j].checked=true;
        //            }
        //        }
        //    }
        // });
        s.check=function(){
            if(!s.user.username){
                s.error=true;
                s.errorMsg="请填写用户名！";
                return false;
            }
            s.userCompanyIds=[];
            for(var i in s.companyInfo){
                if(s.companyInfo[i].checked)
                    s.userCompanyIds.push(s.companyInfo[i].id)
            }
            s.user.company_ids=s.userCompanyIds.join(',');
            // var tempPermission=[];
            // for(var i=0;i<s.permissions.length;i++){
            //     for(var j=0;j<s.permissions[i].permission.length;j++){
            //         if(s.permissions[i].permission[j].checked==true)
            //             tempPermission.push(s.permissions[i].permission[j].id);
            //     }
            // }
            // s.user.permissions=tempPermission.join(',');
            if(!s.userId){
                s.user.password='000000';
            }
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/users",s.user,function (res) {
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
        s.update=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.put("/admin/users/"+s.userId,s.user,function (res) {
                    s.loading=false;
                    if(res.status==0){
                        s.closeThisDialog(res)
                    }else{
                        s.error=true;
                        s.errorMsg=res.msg;
                    }
                })
            }

        }
    }]);

    app.controller('userList', ['$scope', '$rootScope','Table','http','ngDialog','Tip', function (s, rs,Table, http,ngDialog,tip) {
        rs.patchName = "userList";

        s.table =Table.init({link: "/admin/users"});
        s.table.getList();
        s.companyInfo={};
        http.get("/admin/getCompaniesByCurrentUser",{},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
            }
        });
        s.resetPassword=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                <h2>您确定要将用户“'+list.username+'”的密码重置吗？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value == 'CONFIRM') {
                    http.get("/admin/resetPassword/" + list.id, {}, function (res) {
                        if (res.status == 0) {
                            tip.success("重置密码成功")
                        }else{
                            tip.error(res.msg);
                        }
                    })
                }
            })
        }
        s.disableUser=function (list,n) {
            http.put("/admin/endisableUser/"+list.id,{status:n},function (res) {
                if(res.status==0){
                    list.status=n
                }
            })
        };
        s.editUser=function (user) {
            ngDialog.open({
                template:"template/userDialog.html",
                controller:"editUser",
                data:{user:user,companyInfo:s.companyInfo}
            }).closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    s.table.getList();
                }
            });
        };
        http.get("/isEnableOtp",{},function (res) {
            if(res.status==0){
                rs.userInfo.isEnableOtp=res.data.isBind;
            }
        })
        s.bindOTP=function () {
            ngDialog.open({
                template:"template/bindOTPDialog.html",
                controller:"bindOTP"
            }).closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    tip.success("绑定成功！");
                    rs.userInfo.isEnableOtp=1;
                }
            });
        }
        s.unbindOTP=function () {
            ngDialog.open({
                template:"template/unbindOTPDialog.html",
                controller:"unbindOTP"
            }).closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    tip.success("解绑成功！");
                    rs.userInfo.isEnableOtp=0;
                }
            });
        }
    }]);
    app.controller('bindOTP',['$scope', 'http', function (s, http) {
        s.bind={};
        http.get("/admin/bindOneTimePwd",{},function (res) {
            if(res.status==0){
                s.bind.qrcode=res.data.qrcode;
            }
        });
        s.sub=function () {
            if(!s.loading ){
                s.loading=true;
                http.post("/admin/verifyBindOneTimePwd",{code:s.bind.code},function (res) {
                    s.loading=false;
                    if(res.status==0){
                        s.closeThisDialog(res)
                    }else{
                        s.error=true;
                        s.errorMsg=res.msg;
                    }
                })
            }
        }
    }])
    app.controller('unbindOTP',['$scope', 'http', function (s, http) {
        s.unbind={};
        s.sub=function () {
            if(!s.loading ){
                s.loading=true;
                http.post("/admin/verifyUnbindOneTimePwd",{code:s.unbind.code},function (res) {
                    s.loading=false;
                    if(res.status==0){
                        s.closeThisDialog(res)
                    }else{
                        s.error=true;
                        s.errorMsg=res.msg;
                    }
                })
            }
        }
    }])
})();
