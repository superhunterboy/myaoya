(function() {
    app.controller('payList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','vendorsType','$stateParams', function (s, rs, Table, http, ngDialog,vendorsType,$stateParams) {
        s.payType=$stateParams.payType;
        s.vendorsType=vendorsType;
        s.vendorsTypeList=[];
        for(var id in s.vendorsType){
            if(id=='0'){
                s.vendorsTypeList.push({
                    id:"",
                    name:"全部"
                })
            }else{
                s.vendorsTypeList.push({
                    id:id,
                    name:s.vendorsType[id]
                })
            }

        }
        rs.patchName = ["payList","failList","wechatList","alipayList"][s.payType];
        s.companyInfo={};
        http.get("/admin/companies",{page:1,perPage:999},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
            }
        });

        s.pageSet=store.get("pageSet") || ['20','20','20','20'];
        s.table =Table.init({link: "/admin/pays",query:{perPage:s.pageSet[s.payType],status:s.payType}});
        s.table.vendorType="";
        s.table.getList();
        s.total=s.table.total;
        s.act=function (list,n) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
                <h2>您确定要将订单号为“'+list.order_no+'”的支付记录标记为'+['已入款','已忽略'][n-1]+'吗？</h2>\
                <div align="center">\
                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.put("/admin/pay/"+list.id,{rk_status:n},function (res) {
                        if(res.status==0){
                            s.table.getList();
                            s.$emit("getNew")
                        }
                    })
                }
            });
        };
        s.$on('newPay',function () {
            s.table.getList();
        });
        s.setPage=function () {
            store.set("pageSet",s.pageSet);
            s.table.query.perPage=s.pageSet[s.payType];
            s.table.getList(1);
        };
        s.export=function () {
            var $iframe = $('<iframe id="down-file-iframe" />');
            var $form = $('<form target="down-file-iframe" method="get" />');
            $form.attr("action", "/admin/pays");
            for (var key in s.table.query) {
                $form.append('<input type="hidden" name="' + key + '" value="' + s.table.query[key] + '" />');
            }
            $form.append('<input type="hidden" name="down_excel" value="1" />');
            $iframe.append($form);
            $(document.body).append($iframe);
            $form[0].submit();
            $iframe.remove();
        }
    }])
})()
