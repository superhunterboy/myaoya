app.controller("bankPayRemarkEdit",["$scope","http","$rootScope",function(t,e,a){t.list=angular.copy(t.ngDialogData),t.check=function(){return!(t.list.remark.length>50)||(t.error=!0,t.errorMsg="备注过长，请缩减至50字以内",!1)},t.sub=function(){t.check()&&e.post("/admin/updateOfflinePayStatus/"+t.list.id,{id:t.list.id,remark:t.list.remark||"",status:t.list.status},function(e){0==e.status?t.closeThisDialog(e):(t.error=!0,t.errorMsg="网络错误")})}}]),app.controller("bankPayList",["$scope","$rootScope","Table","http","ngDialog","Tip",function(t,e,a,n,i,o){t.table=a.init({link:"/admin/offlinePays",query:{type:1}}),t.table.query.status="",t.table.getList(),t.search=function(e){t.table.query.status=e,t.table.getList(1)},t.act=function(e,a){i.open({template:'<div class="confirm-dialog">                 <h2>您确定要将订单号为“'+e.order_no+"”的支付记录标记为"+["已入款","已忽略"][a-1]+'吗？</h2>                <div align="center">                    <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>                    <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>                </div></div>',plain:!0}).closePromise.then(function(i){i.value&&"CONFIRM"==i.value&&n.post("/admin/updateOfflinePayStatus/"+e.id,{id:e.id,remark:e.remark||"",status:a},function(e){0==e.status&&t.table.getList()})})},t.editRemark=function(e){i.open({template:"template/payOutRemarkDialog.html",controller:"bankPayRemarkEdit",data:e}).closePromise.then(function(e){e&&0==e.value.status&&(t.table.getList(),o.success("修改成功!"))})},t.$on("newPay",function(){t.table.getList()})}]);