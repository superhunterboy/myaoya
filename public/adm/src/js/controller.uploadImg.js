(function() {
    app.controller('uploadImg', ['$scope','$rootScope', 'http','ngDialog', 'Tip', function (s,rs, http,ngDialog, tip) {
        rs.patchName = "uploadImg";
        s.img={};
        s.setting=[false,false,false];
        s.companyInfo={};
        http.get("/admin/getCompaniesByCurrentUser",{},function (res) {
            if(res.data){
                for(var i=0;i<res.data.length;i++){
                    s.companyInfo[res.data[i].id]=res.data[i];
                }
                s.companyList=rs.userInfo.company_ids.split(",");
                s.nowCompany=s.companyList[0];
            }
        });

        http.get("/admin/getPictures",{},function (res) {
            for(var i=0;i<res.data.length;i++){
                if(!s.img[res.data[i].company_id]){
                    s.img[res.data[i].company_id]=[];
                }
                if(res.data[i].type==1){
                    s.img[res.data[i].company_id].unshift(res.data[i]);
                }else{
                    s.img[res.data[i].company_id].push(res.data[i]);
                }
            }
            for(k in s.img){
                s.temp=angular.copy(s.img[k]);
                break;
            }
        });
        s.changeCompany=function () {
            s.setting=[false,false,false];
            s.temp=angular.copy(s.img[s.nowCompany]);
        };
        s.save=function (n) {
            if(!s.loading){
                s.loading=true;
                http.put("/admin/updatePicture/"+s.temp[n].id,s.temp[n],function (res) {
                    s.loading=false;
                    if(res.status==0){
                        s.setting[n]=false;
                        s.img[s.nowCompany][n]=angular.copy(s.temp[n]);
                        tip.success("保存成功！")
                    }else{
                        tip.error(res.msg);
                    }
                })
            }
        };
        s.cancel=function (n) {
            s.setting[n]=false;
            s.temp[n]=angular.copy(s.img[s.nowCompany][n]);
        };
        s.edit=function (n) {
            s.setting[n]=true;
        }
    }])
})()