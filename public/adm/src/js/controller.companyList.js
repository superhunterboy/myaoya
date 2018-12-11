(function() {
    app.controller('companyEdit',['$scope','http','vendorsType',function (s,http,vendorsType) {
        s.company={};
        s.vendorsType=vendorsType;
        if(s.ngDialogData){
            s.companyId=s.ngDialogData.id;
            s.company.name=s.ngDialogData.name;
            s.company.url=s.ngDialogData.url;
            s.company.autorecharge_url=s.ngDialogData.autorecharge_url;
            s.company.wechat_vendor_id=String(s.ngDialogData.wechat_vendor_id);
            s.company.alipay_vendor_id=String(s.ngDialogData.alipay_vendor_id);
            s.company.qq_vendor_id=String(s.ngDialogData.qq_vendor_id);
            s.company.jd_vendor_id=String(s.ngDialogData.jd_vendor_id);
            s.company.baidu_vendor_id=String(s.ngDialogData.baidu_vendor_id);
            s.company.union_vendor_id=String(s.ngDialogData.union_vendor_id);
            s.company.wap_wechat_vendor_id=String(s.ngDialogData.wap_wechat_vendor_id);
            s.company.wap_alipay_vendor_id=String(s.ngDialogData.wap_alipay_vendor_id);
            s.company.wap_qq_vendor_id=String(s.ngDialogData.wap_qq_vendor_id);
            s.company.wap_jd_vendor_id=String(s.ngDialogData.wap_jd_vendor_id);
            s.company.wap_baidu_vendor_id=String(s.ngDialogData.wap_baidu_vendor_id);
            s.company.wap_union_vendor_id=String(s.ngDialogData.wap_union_vendor_id);
            s.company.netbank_vendor_id=String(s.ngDialogData.netbank_vendor_id);
            s.company.is_autorecharge=String(s.ngDialogData.is_autorecharge);
            s.company.is_5qrcode=String(s.ngDialogData.is_5qrcode);
            if(s.ngDialogData.wechatVendorList){
                s.wechatVendorList=s.ngDialogData.wechatVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/1",{},function (res) {
                    if(res.status==0){
                        s.wechatVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.alipayVendorList){
                s.alipayVendorList=s.ngDialogData.alipayVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/2",{},function (res) {
                    if(res.status==0){
                        s.alipayVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.netbankVendorList){
                s.netbankVendorList=s.ngDialogData.netbankVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/3",{},function (res) {
                    if(res.status==0){
                        s.netbankVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.qqVendorList){
                s.qqVendorList=s.ngDialogData.qqVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/4",{},function (res) {
                    if(res.status==0){
                        s.qqVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.jdVendorList){
                s.jdVendorList=s.ngDialogData.jdVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/5",{},function (res) {
                    if(res.status==0){
                        s.jdVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.baiduVendorList){
                s.baiduVendorList=s.ngDialogData.baiduVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/6",{},function (res) {
                    if(res.status==0){
                        s.baiduVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.unionVendorList){
                s.unionVendorList=s.ngDialogData.unionVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/7",{},function (res) {
                    if(res.status==0){
                        s.unionVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapAlipayVendorList){
                s.wapAlipayVendorList=s.ngDialogData.wapAlipayVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/9",{},function (res) {
                    if(res.status==0){
                        s.wapAlipayVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapBaiduVendorList){
                s.wapBaiduVendorList=s.ngDialogData.wapBaiduVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/12",{},function (res) {
                    if(res.status==0){
                        s.wapBaiduVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapJdVendorList){
                s.wapJdVendorList=s.ngDialogData.wapJdVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/11",{},function (res) {
                    if(res.status==0){
                        s.wapJdVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapQqVendorList){
                s.wapQqVendorList=s.ngDialogData.wapQqVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/10",{},function (res) {
                    if(res.status==0){
                        s.wapQqVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapUnionVendorList){
                s.wapUnionVendorList=s.ngDialogData.wapUnionVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/13",{},function (res) {
                    if(res.status==0){
                        s.wapUnionVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
            if(s.ngDialogData.wapWechatVendorList){
                s.wapWechatVendorList=s.ngDialogData.wapWechatVendorList;
            }else{
                http.get("/admin/getVendorsByCompanyId/"+s.companyId+"/8",{},function (res) {
                    if(res.status==0){
                        s.wapWechatVendorList=res.data;
                    }else{
                        console.log(res.msg);
                    }
                })
            }
        }
        s.check=function(){
            if(!s.company.name){
                s.error=true;
                s.errorMsg="请填写平台名称！";
                return false;
            }
            if(s.company.is_autorecharge==1 && !s.company.autorecharge_url){
                s.error=true;
                s.errorMsg="请填写自动入款接口！";
                return false;
            }
            return true
        };
        s.save=function () {
            if(!s.loading && s.check()){
                s.loading=true;
                http.post("/admin/companies",s.company,function (res) {
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
                http.put("/admin/companies/"+s.companyId,s.company,function (res) {
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
    
    app.controller('companyList',['$scope', '$rootScope', 'Table', 'http', 'ngDialog','vendorsType','Tip', function (s, rs, Table, http, ngDialog,vendorsType,tip) {

        rs.patchName = "companyList";
        s.vendorsType=vendorsType;
        s.vendorInfo={};
        http.get("/admin/vendors",{page:1,perPage:999},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.vendorInfo[res.data[i].id]=res.data[i];
                }
                s.vendorInfo[0]={pay_type:'0'};
            }
        });

        s.dictionary={
            id:["wechat_vendor_id","alipay_vendor_id","netbank_vendor_id","qq_vendor_id","jd_vendor_id","baidu_vendor_id","union_vendor_id",
                "wap_wechat_vendor_id","wap_alipay_vendor_id","wap_qq_vendor_id","wap_jd_vendor_id","wap_baidu_vendor_id","wap_union_vendor_id"],
            list:["wechatVendorList","alipayVendorList","netbankVendorList","qqVendorList","jdVendorList","baiduVendorList","unionVendorList",
                "wapWechatVendorList","wapAlipayVendorList","wapQqVendorList","wapJdVendorList","wapBaiduVendorList","wapUnionVendorList"],
            model:["wechatChangeModel","alipayChangeModel","netbankChangeModel","qqChangeModel","jdChangeModel","baiduChangeModel","unionChangeModel",
                "wapWechatChangeModel","wapAlipayChangeModel","wapQqChangeModel","wapJdChangeModel","wapBaiduChangeModel","wapUnionChangeModel"]
        }

        s.changeVendor=function (list,n) {
            list[s.dictionary.id[n-1]]=String(list[s.dictionary.id[n-1]]);
            http.get("/admin/getVendorsByCompanyId/"+list.id+"/"+n,{},function (res) {
                if(res.status==0){
                    list[s.dictionary.list[n-1]]=res.data;
                }else{
                    list[s.dictionary.list[n-1]]=[];
                    list[s.dictionary.model[n-1]]=true;
                }
                list[s.dictionary.model[n-1]]=true;
            })
        };
        s.changeVendorConfirm=function (list,n) {
            var data={};
            data[s.dictionary.id[n-1]]=list[s.dictionary.id[n-1]];
            http.put("/admin/changeVendorByCompanyId/"+list.id,data,function (res) {
                if(res.status==0){
                    list[s.dictionary.model[n-1]]=false;
                }else{
                    tip.error(res.msg);
                }
            })
        };

        s.table =Table.init({link: "/admin/companies"});
        s.table.getList();

        s.editCompany=function (list) {
            ngDialog.open({
                template:"template/companyEditDialog.html",
                controller:"companyEdit",
                data:list
            }).closePromise.then(function (data) {
                if (data.value && data.value.status==0) {
                    s.table.getList();
                }
            });
        };
        s.delCompany=function (list) {
            ngDialog.open({
                template:'<div class="confirm-dialog"> \
            <h2>您确定要删除业务平台“'+list.name+'”吗？</h2>\
            <div align="center">\
                <button type="button" class="btn btn-red" ng-click="closeThisDialog(\'CONFIRM\')">确定</button>\
                <button type="button" class="btn btn-default" ng-click="closeThisDialog()">取消</button>\
            </div></div>',
                plain: true
            }).closePromise.then(function (data) {
                if (data.value && data.value=='CONFIRM') {
                    http.delete("/admin/companies/"+list.id,function (res) {
                        if(res.status==0){
                            s.table.getList();
                        }
                    })
                }
            });
        }

    }])
})()
