(function() {
    app.controller('vendorEdit',['$scope','http','vendorsType',function (s,http,vendorsType) {
        s.vendor={};
        s.vendorsType=vendorsType;
        s.companyInfo=s.ngDialogData.companyInfo;
        if(s.ngDialogData.vendor){
            s.vendorId=s.ngDialogData.vendor.id;
            s.vendor.company_id=String(s.ngDialogData.vendor.company_id);
            s.vendor.pay_type=String(s.ngDialogData.vendor.pay_type);
            s.vendor.no=s.ngDialogData.vendor.no;
            s.vendor.key=s.ngDialogData.vendor.key;
            s.vendor.wechat=''+s.ngDialogData.vendor.wechat;
            s.vendor.qq=''+s.ngDialogData.vendor.qq;
            s.vendor.jd=''+s.ngDialogData.vendor.jd;
            s.vendor.baidu=''+s.ngDialogData.vendor.baidu;
            s.vendor.union=''+s.ngDialogData.vendor.union;
            s.vendor.alipay=''+s.ngDialogData.vendor.alipay;
            s.vendor.wap_wechat=''+s.ngDialogData.vendor.wap_wechat;
            s.vendor.wap_qq=''+s.ngDialogData.vendor.wap_qq;
            s.vendor.wap_jd=''+s.ngDialogData.vendor.wap_jd;
            s.vendor.wap_baidu=''+s.ngDialogData.vendor.wap_baidu;
            s.vendor.wap_union=''+s.ngDialogData.vendor.wap_union;
            s.vendor.wap_alipay=''+s.ngDialogData.vendor.wap_alipay;
            s.vendor.netpay=''+s.ngDialogData.vendor.netpay;
            s.vendor.callback_url=s.ngDialogData.vendor.callback_url;
            s.vendor.notify_url=s.ngDialogData.vendor.notify_url;
        }
        s.check=function(){
            if(!s.vendor.company_id){
                s.error=true;
                s.errorMsg="请选择所属业务平台！";
                return false;
            }
            if(!s.vendor.pay_type){
                s.error=true;
                s.errorMsg="请选择支付平台！";
                return false;
            }
            if(!s.vendor.no){
                s.error=true;
                s.errorMsg="请填写商户编号！";
                return false;
            }
            if(!s.vendor.key){
                s.error=true;
                s.errorMsg="请填写商户key！";
                return false;
            }
            if(!s.vendor.callback_url){
                s.error=true;
                s.errorMsg="请填写回调地址！";
                return false;
            }
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/vendors",s.vendor,function (res) {
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
                http.put("/admin/vendors/"+s.vendorId,s.vendor,function (res) {
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

    app.controller('vendorList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','vendorsType', function (s, rs, Table, http, ngDialog, vendorsType) {

        rs.patchName = "vendorList";
        s.vendorsType=vendorsType;
        s.companyInfo={};
        http.get("/admin/getCompaniesByCurrentUser",{},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
            }
        });

        s.table =Table.init({link: "/admin/vendors"});
        s.table.getList();

        s.editVendor=function (list) {
            ngDialog.open({
                template:"template/vendorEditDialog.html",
                controller:"vendorEdit",
                data:{vendor:list,companyInfo:s.companyInfo}
            }).closePromise.then(function (data) {
                if (data.value && data.value.status==0) {
                    s.table.getList();
                }
            });
        };
        s.delVendor=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要删除支付平台配置“'+s.companyInfo[list.company_id].name+'-'+s.vendorsType[list.pay_type]+'”吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.delete("/admin/vendors/"+list.id,function (res) {
                        if(res.status==0){
                            s.table.getList();
                        }
                    })
                }
            });
        }

    }])
})()

