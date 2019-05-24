(function() {
    app.controller('paymentMethodEdit',['$scope','http',function (s,http) {
        s.paymentMethodObject = {}
        if(s.ngDialogData.paymentMethodObject){
            s.paymentMethodId=s.ngDialogData.paymentMethodObject.id;
            s.paymentMethodObject.name = s.ngDialogData.paymentMethodObject.name;
            s.paymentMethodObject.tag = s.ngDialogData.paymentMethodObject.tag;
            s.paymentMethodObject.position = s.ngDialogData.paymentMethodObject.position;
            s.paymentMethodObject.status = s.ngDialogData.paymentMethodObject.status;
            s.paymentMethodObject.sequence = s.ngDialogData.paymentMethodObject.sequence;
            s.paymentMethodObject.created_at = s.ngDialogData.paymentMethodObject.created_at;
            s.paymentMethodObject.updated_at = s.ngDialogData.paymentMethodObject.updated_at;
        }else{
            // 默认状态
            s.paymentMethodObject.position = 1;     // PC端
            s.paymentMethodObject.status = 1;       // 启用
        }
        s.check=function(){
            if(!s.paymentMethodObject.name){
                s.error=true;
                s.errorMsg="请填写支付方式！";
                return false;
            }
            if(!s.paymentMethodObject.tag){
                s.error=true;
                s.errorMsg="请填写英文标识！";
                return false;
            }
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/addChannel",s.paymentMethodObject,function (res) {
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
                http.put("/admin/updateChannel/"+s.paymentMethodId,s.paymentMethodObject,function (res) {
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

    app.controller('paymentMethod',['$scope', '$rootScope', 'Table', 'http', 'ngDialog', function (s, rs, Table, http, ngDialog) {

        s.table =Table.init({link: "/admin/channels", query: {position: "", status: ""}});
        s.table.getList();

        s.editPaymentMethod=function (list) {
            ngDialog.open({
                template:"template/paymentMethodEditDialog.html",
                controller:"paymentMethodEdit",
                data:{paymentMethodObject:list}
            }).closePromise.then(function (data) {
                if (data.value && data.value.status==0) {
                    s.table.getList();
                }
            });
        };
        s.delVendor=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                    <h2>您确定要删除支付方式“'+list.name+'”吗？</h2>\
                    <div align="center">\
                        <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                        <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                    </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.delete("/admin/delChannel/"+list.id,function (res) {
                        if(res.status==0){
                            s.table.getList();
                        }
                    })
                }
            });
        }
    }])
})()