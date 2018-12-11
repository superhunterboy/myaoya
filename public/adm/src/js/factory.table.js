(function() {
    app.factory('Table', ['$http', function ($http) {
        function Table(){
            this.list=[];
            this.query={
                page: 1,
                perPage: '20'
               }
        }
        Table.prototype.init=function (option) {
            this.error=false;
            this.ready=false;
            this.list=[];
            this.query={
                page: 1,
                perPage: '20'
            };
            this.link = option.link;
            this.callback=option.callback || function () {};
            if (option.query)
                this.query = $.extend(this.query, option.query);
            return this;
        };
        Table.prototype.getList=function (page) {
            var me=this;
            page = page || me.query.page;
            me.query.page = page;
            $http({
                url: me.link,
                params: me.query,
                method: 'GET',
                headers: {"X-Requested-With": "XMLHttpRequest"}
            }).then(function (bk) {
                var res = bk.data;
                if (res.data) {
                    me.error = false;
                    me.list = res.data;
                    // me.pageCount = res.last_page;
                    me.pageCount = parseInt(res.total % me.query.perPage) > 0 ? parseInt(res.total / me.query.perPage) + 1 : parseInt(res.total / me.query.perPage);
                    me.total=res.total;
                }
                else {
                    me.error = true;
                    me.errorMsg = res.msg;
                }
                me.callback(res);
            }).catch(function (err) {
                me.error = true;
                me.errorMsg = err.status;
            }).finally(function () {
                me.ready = true;
            });
        };
        return new Table();
    }]);

    app.directive("tableFloatHeader",["$compile",function ($compile) {
        return {
            restrict: "AE",
            template: '<div class="table-float-header"></div>',
            link: function (scope, element, attrs, ctrl) {
                var ele=$(element).find(".table-float-header");
                if(attrs.floatElement)
                    ele.html($compile($("."+attrs.floatElement).clone())(scope));
                var thead=$("<table></table>");
                thead.html($compile($("."+attrs.floatThead).clone())(scope));
                $(element).find(".table-float-header").append(thead);
                function resize() {
                    var thd=thead.find("td");
                    var tbd=$("."+attrs.floatThead).next("tbody").find("tr").eq(0).find("td");
                    for(var i=0;i<thd.length;i++){
                        thd.eq(i).width(tbd.eq(i).width());
                    }
                }
                $('.view').on('scroll',function(){
                    resize();
                    ele.css("top",$(this).scrollTop()+"px");
                    if ($(this).scrollTop() >70) {
                        ele.show();
                    }else{
                        ele.hide();
                    }
                });
            }
        }
    }]);

    app.directive("tableFixedTr",function () {
        return {
            restrict: "AE",
            link: function (scope, element, attrs, ctrl) {
                scope.$on("repeatFinish",function () {
                    setTimeout(function () {
                        var trLen=$(element).find("tbody tr").length;
                        if(trLen>attrs.tableFixedTr){
                            for(var i=0;i<trLen-attrs.tableFixedTr;i++){
                                $(element).find("tbody tr").eq($(element).find("tbody tr").length-1).remove();
                            }
                        }else if(trLen<attrs.tableFixedTr){
                            for(var j=0;j<attrs.tableFixedTr-trLen;j++){
                                var tr=$("<tr></tr>");
                                tr.append("<td colspan='"+$(element).find("thead tr td").length+"'>&#12288;</td>");
                                $(element).find("tbody").append(tr)
                            }
                        }
                    })
                })
            }
        }
    });
    
    app.directive("tablePage", function () {
        return {
            restrict: "AE",
            template: '<div class="table-page"></div>',
            link: function (scope, element, attrs, ctrl) {
                scope.$watch(attrs.tablePage, function (table) {
                    if (table && table.pageCount && table.pageCount > 1) {
                        $(element).find(".table-page").html('<span class="pre">上一页</span>\
                        <span class="next">下一页</span>');
                        if (table.query.page == 1) {
                            $(element).find(".pre").addClass("disabled")
                        } else if (table.query.page == table.pageCount) {
                            $(element).find(".next").addClass("disabled")
                        }
                        var html = "";
                        if (table.pageCount > 10) {
                            if (table.query.page < 6) {
                                for (var i = 1; i <= table.query.page + 2; i++) {
                                    if (i == table.query.page)
                                        html += '<span class="page-to now">' + i + '</span>';
                                    else
                                        html += '<span class="page-to">' + i + '</span>';
                                }

                                html += '...<span class="page-to">' + table.pageCount + '</span>'
                            }
                            else if (table.query.page >= 6 && table.query.page < table.pageCount - 2) {
                                html += '<span class="page-to">1</span>...';
                                for (var i = table.query.page - 2; i <= table.query.page + 2; i++) {
                                    if (i == table.query.page)
                                        html += '<span class="page-to now">' + i + '</span>';
                                    else
                                        html += '<span class="page-to">' + i + '</span>';
                                }
                                html += '...<span class="page-to">' + table.pageCount + '</span>'
                            } else {
                                html += '<span class="page-to">1</span>...';
                                for (var i = table.query.page - 2; i <= table.pageCount; i++) {
                                    if (i == table.query.page)
                                        html += '<span class="page-to now">' + i + '</span>';
                                    else
                                        html += '<span class="page-to">' + i + '</span>';
                                }
                            }
                        } else {
                            for (var i = 1; i <= table.pageCount; i++) {
                                if (i == table.query.page)
                                    html += '<span class="page-to now">' + i + '</span>';
                                else
                                    html += '<span class="page-to">' + i + '</span>';
                            }
                        }
                        $(element).find(".pre").after(html);
                    }else{
                        $(element).find(".table-page").html("");
                    }
                }, true);
                var tablep = scope.$eval(attrs.tablePage);
                $(element).on('click', '.page-to', function () {
                    if (!$(this).hasClass("now")) {
                        tablep.getList(parseInt($(this).text()));
                    }
                });
                $(element).on('click', '.pre', function () {
                    if (!$(this).hasClass("disabled")) {
                        tablep.getList(tablep.query.page - 1);
                    }
                });
                $(element).on('click', '.next', function () {
                    if (!$(this).hasClass("disabled")) {
                        tablep.getList(tablep.query.page + 1);
                    }
                });
            }
        }
    })
})()