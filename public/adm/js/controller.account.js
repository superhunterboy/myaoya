app.controller("changePassword",["$scope","$rootScope","http",function(o,r,e){o.change={old_password:"",new_password:"",new_password_confirm:""},o.check=function(){return o.change.old_password?o.change.new_password?o.change.new_password_confirm?o.change.new_password==o.change.new_password_confirm||(o.error=!0,o.errorMsg="两次输入的新密码不一致，请确认后重新输入",!1):(o.error=!0,o.errorMsg="请再次输入新密码",!1):(o.error=!0,o.errorMsg="请输入新密码",!1):(o.error=!0,o.errorMsg="请输入原密码",!1)},o.sub=function(){!o.loading&&o.check()&&(o.loading=!0,e.put("/admin/modifyPassword",{old_password:o.change.old_password,new_password:o.change.new_password},function(r){0==r.status?o.closeThisDialog():(o.error=!0,o.errorMsg=r.msg)}))}}]),app.controller("account",["$scope","$rootScope","ngDialog","http",function(o,r,e,a){r.patchName="",o.companyInfo={},a.get("/admin/getCompaniesByCurrentUser",{},function(r){if(r.data)for(var e=0;e<r.data.length;e++)o.companyInfo[r.data[e].id]=r.data[e]}),o.changePassword=function(){e.open({template:"template/changePasswordDialog.html",controller:"changePassword",closeByDocument:!1,closeByEscape:!1,showClose:!1})},r.userInfo&&!r.userInfo.lastlogin&&o.changePassword()}]);