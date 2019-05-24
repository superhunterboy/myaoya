(function() {
    app.controller('paymentChannelEdit',['$scope','http',function (s,http) {
        s.paymentChannelObject = {}
        s.paymentMethodList = s.ngDialogData.paymentMethodList
        if(s.ngDialogData.paymentChannelObject){
            s.paymentChannelId=s.ngDialogData.paymentChannelObject.id;
            s.paymentChannelObject.platform = s.ngDialogData.paymentChannelObject.platform;
            s.paymentChannelObject.platform_identifer = s.ngDialogData.paymentChannelObject.platform_identifer;
            s.paymentChannelObject.channel = s.ngDialogData.paymentChannelObject.channel;
            s.paymentChannelObject.paycode = s.ngDialogData.paymentChannelObject.paycode;
            s.paymentChannelObject.display_name = s.ngDialogData.paymentChannelObject.display_name;
            s.paymentChannelObject.merchant_no = s.ngDialogData.paymentChannelObject.merchant_no;
            s.paymentChannelObject.key = s.ngDialogData.paymentChannelObject.key;
            s.paymentChannelObject.callback_url = s.ngDialogData.paymentChannelObject.callback_url;
            s.paymentChannelObject.notify_url = s.ngDialogData.paymentChannelObject.notify_url;
            s.paymentChannelObject.position = s.ngDialogData.paymentChannelObject.position;
            s.paymentChannelObject.offline_category = s.ngDialogData.paymentChannelObject.offline_category;
            s.paymentChannelObject.deposit_range = s.ngDialogData.paymentChannelObject.deposit_range;
            s.paymentChannelObject.status = s.ngDialogData.paymentChannelObject.status;
            s.paymentChannelObject.sequence = s.ngDialogData.paymentChannelObject.sequence;
            
            for(var i = 0; i < s.paymentMethodList.length; i++){
                if(s.paymentChannelObject.channel === s.paymentMethodList[i].name){
                    s.paymentChannelObject.channel = s.paymentMethodList[i].tag;
                }
            }
        }else{
            // 默认参数
            s.paymentChannelObject.status = 0;      // 禁用

            // 线上
            s.paymentChannelObject.position = 1
        }
        s.channelPosition = function(){
            if(s.paymentChannelObject.position === 1){
                s.paymentChannelObject.offLineCategory = undefined;
            }else if(s.paymentChannelObject.position === 2){
                s.paymentChannelObject.offLineCategory = "scanCode";
            }
        }
        s.check=function(){
            if(!s.paymentChannelObject.platform){
                s.error=true;
                s.errorMsg="请填写支付平台！";
                return false;
            }
            if(!s.paymentChannelObject.platform_identifer){
                s.error=true;
                s.errorMsg="请填写平台标识！";
                return false;
            }
            if(!s.paymentChannelObject.channel){
                s.error=true;
                s.errorMsg="请选择支付方式！";
                return false;
            }
            if(!s.paymentChannelObject.paycode){
                s.error=true;
                s.errorMsg="请选择支付代码！";
                return false;
            }
            if(!s.paymentChannelObject.display_name){
                s.error=true;
                s.errorMsg="请填写前台显示名称！";
                return false;
            }
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/addPaymentChannel",s.paymentChannelObject,function (res) {
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
                http.put("/admin/updatePaymentChannel/"+s.paymentChannelId,s.paymentChannelObject,function (res) {
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

    app.controller('paymentChannelList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog', function (s, rs, Table, http, ngDialog) {

        http.get("/admin/channels", {}, function (res) {
            s.paymentMethodList =  res.data

        })
        s.table =Table.init({link: "/admin/getPaymentChannels", query: {channel: "", position: "", offline_category: ""}});
        s.table.getList();

        s.editPaymentChannel=function (list) {
            ngDialog.open({
                template:"template/channelEditorDialog.html",
                controller:"paymentChannelEdit",
                data:{paymentChannelObject:list, paymentMethodList: s.paymentMethodList}
            }).closePromise.then(function (data) {
                if (data.value && data.value.status==0) {
                    s.table.getList();
                }
            });
            
        };
        s.delPaymentChannel=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要删除支付渠道“'+list.display_name+'”吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.delete("/admin/delPaymentChannel/"+list.id,function (res) {
                        if(res.status==0){
                            s.table.getList();
                        }
                    })
                }
            });
        }

        s.changeChannelStatus=function (list, status){
            var statusStr
            status ? statusStr = "启用" : statusStr = "禁用"
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要'+ statusStr +'支付渠道“'+list.display_name+'”吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.put("/admin/switchStatus/"+list.id, {status: status}, function (res) {
                        s.loading=false;
                        if(res.status==0){
                            s.table.getList();
                        }else{
                            s.error=true;
                            s.errorMsg=res.msg;
                        }
                    })
                }
            });
        }

    }])
})()

