app.controller("wechatMerchantQrList",["$scope","$rootScope","Table","http","ngDialog","Tip",function(t,n,e,i,o,a){t.table=e.init({link:"/admin/merchants"}),t.table.getList(),t.edit=function(n,e){o.open({template:'<div class="confirm-dialog">                 <h2>您确定要'+["解锁","锁定"][e]+'此商户吗？</h2>                <div align="center">                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                </div></div>',plain:!0}).closePromise.then(function(o){o.value&&"CONFIRM"==o.value&&(n.status=e,i.post("/admin/updateMerchant/"+n.id,n,function(n){0==n.status?(a.success("操作成功!"),t.table.getList()):a.error(n.msg)}))})}}]);