(function () {
    app.controller("payCompanyReport", [
        "$scope",
        "$rootScope",
        "$filter",
        "Table",
        "http",
        "Tip",
        function (s, rs, $filter, Table, http,tip) {
            http.get("/admin/getReportItems?type=1", {}, function (res) {
                    s.reportItems = res;
            });
            var time=new Date(new Date(new Date()-12*60*60*1000).toLocaleDateString());
            s.table = Table.init({link: "/admin/getPayCompanyReport",callback:function (res) {
                s.table.current_total_amount=res.current_total_amount;
                s.table.total_amount=res.total_amount;
            }});
            s.table.query.items="";
            s.table.query.startime=$filter("date")(time,"yyyy-MM-dd HH:mm:ss");
            s.table.query.endtime=$filter("date")(new Date(time.getTime()+24*60*60*1000-1),"yyyy-MM-dd HH:mm:ss");
            s.table.getList(1);
            s.export=function () {
                var query="";
                for (var key in s.table.query) {
                    query+='&' + key + '=' + s.table.query[key];
                }
                window.open("/admin/getPayCompanyReport?isExport=1"+query);
            };
        }
    ]);
})()