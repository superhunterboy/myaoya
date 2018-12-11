(function() {
    app.controller('editNotice',['$scope', 'http', function (s, http) {
        s.notice={};
        if(s.ngDialogData && s.ngDialogData.id){
            s.notice={
                id:s.ngDialogData.id,
                title:s.ngDialogData.title,
                content:s.ngDialogData.content,
                status:s.ngDialogData.status
            };
        }
        s.check=function(){
            if(!s.notice.title){
                s.error=true;
                s.errorMsg="请填写标题！";
                return false;
            }
            if(!s.notice.content){
                s.error=true;
                s.errorMsg="请填写内容！";
                return false;
            }
            
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/addAffiche",{title:s.notice.title,content:s.notice.content},function (res) {
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
                http.post("/admin/updateAffiche",s.notice,function (res) {
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

    app.controller('noticeList', ['$scope', '$rootScope','Table','http','ngDialog','Tip', function (s, rs,Table, http,ngDialog,tip) {
        s.table =Table.init({link: "/admin/affiches"});
        s.table.getList();
        
        s.delNotice=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                <h2>您确定要将公告“'+list.title+'”删除吗？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value == 'CONFIRM') {
                    http.get("/admin/deleteAffiche/" + list.id, {}, function (res) {
                        if (res.status == 0) {
                            tip.success("删除成功")
                        }else{
                            tip.error(res.msg);
                        }
                        s.table.getList();
                    })
                }
            })
        }
        s.updateNotice=function (list,n) {
            http.post("/admin/updateAffiche",{id:list.id,title:list.title,content:list.content,status:n},function (res) {
                if(res.status==0){
                    list.status=n
                }
            })
        };
        s.editNotice=function (list) {
            ngDialog.open({
                template:"template/noticeDialog.html",
                controller:"editNotice",
                data:list
            }).closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    s.table.getList();
                }
            });
        };
        
    }]);
})();
