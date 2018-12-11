(function() {
    app.controller("changePassword",['$scope','$rootScope', 'http', function (scope,rs, http) {
        scope.change={
            old_password:'',
            new_password:'',
            new_password_confirm:''
        };
        scope.check=function () {
            if(!scope.change.old_password){
                scope.error=true;
                scope.errorMsg="请输入原密码";
                return false
            }
            if(!scope.change.new_password){
                scope.error=true;
                scope.errorMsg="请输入新密码";
                return false
            }
            if(!scope.change.new_password_confirm){
                scope.error=true;
                scope.errorMsg="请再次输入新密码";
                return false
            }
            if(scope.change.new_password!=scope.change.new_password_confirm){
                scope.error=true;
                scope.errorMsg="两次输入的新密码不一致，请确认后重新输入";
                return false
            }
            return true
        };
        scope.sub=function () {
            if(!scope.loading && scope.check()){
                scope.loading=true;
                http.put("/admin/modifyPassword",{old_password:scope.change.old_password,new_password:scope.change.new_password},function (res) {
                    if(res.status==0){
                        scope.closeThisDialog();
                    }else{
                        scope.error=true;
                        scope.errorMsg=res.msg;
                    }
                })
            }
        }
    }]);
    app.controller('account', ['$scope', '$rootScope', 'ngDialog','http', function (s, rs, ngDialog,http) {
        rs.patchName = "";
        s.companyInfo={};
        http.get("/admin/getCompaniesByCurrentUser",{},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
            }
        });
        s.changePassword=function () {
            ngDialog.open({
                template:"template/changePasswordDialog.html",
                controller:"changePassword",
                closeByDocument:false,
                closeByEscape:false,
                showClose:false
            })
        };
        if(rs.userInfo && !rs.userInfo.lastlogin){
            s.changePassword();
        }
    }])
})();