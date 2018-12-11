(function() {
    app.controller('editQrcode',['$scope', 'http', function (s, http) {
        s.id;
        s.count=0;
        s.qrcode={content:'',limit:'0',type:'1'};
        if(s.ngDialogData.qrcode){
            s.id = s.qrcode.id=s.ngDialogData.qrcode.id;
            s.count=s.ngDialogData.qrcode.count;
            s.qrcode.wechat=s.ngDialogData.qrcode.wechat_id;
            s.qrcode.type=s.ngDialogData.qrcode.type + '';
            s.qrcode.limit=s.ngDialogData.qrcode.limit;
            s.qrcode.url=s.ngDialogData.qrcode.url;
        }
        s.check=function(){
            if(!s.qrcode.wechat){
                s.error=true;
                s.errorMsg="请填写微信号！";
                return false;
            }
            if(s.qrcode.limit==''){
                s.error=true;
                s.errorMsg="请填写限额！";
                return false;
            }
            if(!s.id && !s.qrcode.content){
                s.error=true;
                s.errorMsg="请上传二维码！";
                return false;
            }
            return true
        };
        s.save=function () {
            var data;
            if(!s.loading && s.check()){
                s.loading=true;
                data = angular.copy(s.qrcode);
                delete data.url;

                http.post("/admin/addQrcode",data,function (res) {
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
            var data;
            if(!s.loading && s.check()){
                s.loading=true;

                data = angular.copy(s.qrcode);
                if(!data.content) delete data.content;
                delete data.url;

                http.post("/admin/updateQrcode/"+s.id,data,function (res) {
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
        s.$watch('qrcode.content',function(){
            if(s.qrcode.content)s.qrcode.url = s.qrcode.content;
        });
    }]);

    app.controller('wechatQRList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','$stateParams',"Tip", function (s, rs, Table, http, ngDialog,$stateParams,tip) {
        s.companyInfo={};
        http.get("/admin/companies",{page:1,perPage:999},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
            }
        });

        s.table =Table.init({link: "/admin/qrcodes",query:{}});
        s.table.getList();
        s.total=s.table.total;


            
        s.editQR=function (qrcode) {
            ngDialog.open({
                template:"template/wechatQRDialog.html",
                controller:"editQrcode",
                data:{qrcode:qrcode}
            }).closePromise.then(function (data) {
                if (data.value && data.value.status == 0) {
                    tip.success("保存成功!");
                    setTimeout(function(){
                        s.table.getList();
                    },1000);
                }
            });
        };

        s.deleteQR = function(qrcode){
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要删除微信号为“'+qrcode.wechat_id+'”的二维码吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.get("/admin/deleteQrcode/"+qrcode.id,{},function (res) {
                        if(res.status==0){
                            tip.success("删除成功!");
                            s.table.getList(1);
                        }else{
                            tip.error(res.msg);
                        }
                    });
                }
            });
        }

        s.$on("fileUploaded",function(){
            s.table.getList();
        });

        s.disable=function (list,n) {
            http.post("/admin/disableQrcode/"+list.id,{disable:n},function (res) {
                if(res.status==0){
                    tip.success("操作成功!");
                    s.table.getList();
                }
            });
        }
    }]);

    app.directive("pkgUpload",["Tip",function(tip) {
        return {
            restrict: "AE",
            link: function (scope, element, attrs, ctrl) {
                element.on("change",function (event) {
                    if(event.target.files.length>0){
                        var file = event.target.files[0];

                        if(file.name.indexOf(".zip")==-1){
                            tip.error("请上传ZIP格式的压缩包");
                            return;
                        }

                        var fd = new FormData();
                        fd.append("file", file);

                        $.ajax({
                            url: "/admin/batchUploadQrcode",
                            type: "POST",
                            data: fd,
                            processData: false,
                            contentType: false,
                            success: function(res) {
                              if(res.status==0){
                                element[0].value = '';
                                tip.success(res.msg);
                                scope.$emit('fileUploaded',file);
                              }else{
                                  tip.error(res.msg);
                              }
                            },
                            error: function(responseStr) {
                              tip.error("服务器内部错误，上传失败!");
                            }
                        });
                    }
                });
            }
        }
    }]);
})()
