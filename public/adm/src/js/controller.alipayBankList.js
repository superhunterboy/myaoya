(function() {
    app.controller('alipayBankChange',['$scope', 'http', 'ngDialog','Tip',function (s,http, ngDialog,tip) {
        s.list=angular.copy(s.ngDialogData);
        s.list.actId=""+s.list.actBank.id;
        s.sub=function () {
            var actBank=s.list.banks.filter(function (t) {
                return t.id==s.list.actId;
            })[0];
            actBank.status=1;
            http.post("/admin/updateBankCard/"+s.list.actId,actBank,function (res) {
                if(res.status==0){
                    s.closeThisDialog(res);
                }else{
                    tip.error(res.msg);
                }
            })
        }
    }]);
    app.controller('alipayBankEdit',['$scope', 'http', 'ngDialog','Tip',function (s,http, ngDialog,tip) {
        s.temp=s.ngDialogData;
        s.bank=angular.copy(s.ngDialogData);
        if(!s.bank.name){
            s.bank={
                banks:[],
                actBank:{},
                name:""
            }
        }else{
            s.bankName=s.bank.name;
        }
        s.add=function () {
            s.bank.banks.push({
                bank_name:s.bank.name,
                user_name:"",
                bank_number:"",
                address:""
            })
        };
        s.del=function (list,index) {
            if(list.id){
                ngDialog.open({
                    template:'<div class="confirm-dialog"> \
                    <h2>您确定要删除银行卡“'+list.bank_number+'”吗？</h2>\
                    <p>删除后将无法恢复！</p>\
                    <div align="center">\
                        <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                        <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
                    </div></div>',
                    plain: true
                }).closePromise.then(function (data) {
                    if (data.value && data.value=='CONFIRM') {
                        http.get("/admin/deleteBankCard/"+list.id,{},function (res) {
                            if(res.status==0){
                                tip.success("删除成功！");
                                s.bank.banks=s.bank.banks.filter(function (t) {
                                    return t.id!=list.id;
                                })
                            }else{
                                tip.error(res.msg)
                            }
                        })
                    }
                });
            }else{
                s.bank.banks.splice(index,1);
            }
        };
        s.check=function () {
            s.banks=[];
            var n=0;
            if(!s.bank.name){
                s.error=true;
                s.errorMsg="请填写银行名称";
                return false;
            }
            if(s.bank.banks.length==0){
                s.error=true;
                s.errorMsg="请添加银行卡";
                return false;
            }
            for(var i=0;i<s.bank.banks.length;i++){
                var bank={
                    bank_name:s.bank.name,
                    user_name:s.bank.banks[i].user_name,
                    bank_number:s.bank.banks[i].bank_number,
                    address:s.bank.banks[i].address
                };
                if(!s.bank.banks[i].user_name||!s.bank.banks[i].bank_number){
                    s.error=true;
                    s.errorMsg="请填写第"+(i+1)+"张银行卡的完整信息";
                    return false;
                }
                if(s.bank.banks[i].id  && (bank.user_name!=s.temp.banks[i].user_name || bank.bank_number!=s.temp.banks[i].bank_number || bank.address!=s.temp.banks[i].address)){
                    bank.id=s.bank.banks[i].id;
                    s.banks.push(bank);
                }else if(!s.bank.banks[i].id){
                    s.banks.push(bank);
                }
                n++
            }
            if(n!=s.bank.banks.length){
                return false;
            }
            return true;
        };

        s.sub=function () {
            if(s.check()){
                http.post("/admin/updateBankCards",{banks:angular.toJson(s.banks,true)},function (res) {
                    if(res.status==0){
                        tip.success("操作成功！");
                        s.closeThisDialog(res);
                    }else{
                        tip.error(res.msg)
                    }
                })
            }

        }
    }]);
    app.controller('alipayBankList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','Tip', function (s, rs, Table, http, ngDialog,tip) {
        s.getList=function () {
            s.bankList={};
            http.get("/admin/bankCards",{},function (res) {
                s.banks=res;
                s.banks.forEach(function (list) {
                     if(!s.bankList[list.bank_name]){
                         s.bankList[list.bank_name]={
                             banks:[],
                             actBank:{},
                             name:list.bank_name
                         }
                     }
                     s.bankList[list.bank_name].banks.push(list);
                     if(list.status==1){
                         s.bankList[list.bank_name].actBank=list;
                     }
                })
            })
        };
        s.getList();
        s.editBank=function (list) {
            list=list || {};
            ngDialog
                .open({
                    template: "template/alipayBankEditDialog.html",
                    controller: "alipayBankEdit",
                    data: list,
                    closeByDocument :false,
                    className:"ngdialog-theme-default large"
                })
                .closePromise.then(function (data) {
                if (data && data.value.status == 0) {
                    s.getList();
                }
            });
        };
        s.changeBank=function (list) {
            ngDialog
                .open({
                    template: "template/alipayBankChangeDialog.html",
                    controller: "alipayBankChange",
                    data: list
                })
                .closePromise.then(function (data) {
                if (data && data.value.status == 0) {
                    s.getList();
                    tip.success("切换成功!");
                }
            });
        }
    }])
})()