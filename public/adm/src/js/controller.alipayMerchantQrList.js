(function() {
    app.controller('alipayMerchantQrList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','Tip', function (s, rs, Table, http, ngDialog,tip) {
        s.table =Table.init({link: "/admin/merchants",query:{type:2}});
        s.table.getList();

        s.edit = function(list,n){
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                <h2>您确定要'+['解锁','锁定'][n]+'此商户吗？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    list.status = n;
                    http.post("/admin/updateMerchant/"+list.id,list,function (res) {
                        if(res.status==0){
                            tip.success("操作成功!");
                            s.table.getList();
                        }else{
                          tip.error(res.msg);
                        }
                    })
                }
            });
        }
    }])
})()
