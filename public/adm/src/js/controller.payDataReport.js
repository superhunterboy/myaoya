(function () {
    app.controller("payDataReport", [
        "$scope",
        "$rootScope",
        "$filter",
        "Table",
        "http",
        "Tip",
        function (s, rs, $filter, Table, http,tip) {

            // 刷新频率
            s.frequency = "10min"
            // 对比天数
            s.compared = "5day"
            // 统计数据接口地址
            s.urlString = "/data/getDatas/FiveDayTenMinPayData"
            // echart实例
            s.chart_instance

            /**
             * 根据刷新频率及对比天数获取接口请求地址
             * @param {String} frequency 刷新频率 
             * @param {String} compared 对比天数
             * @return {String} url 
             */
            function getUrl(frequency, compared){
                if(compared === "1day"){
                    if(frequency === "5min"){
                        return "/data/getDatas/FiveMinPayData"
                    }else if(frequency === "10min"){
                        return "/data/getDatas/TenMinPayData"
                    }else if(frequency === "15min"){
                        return "/data/getDatas/FifteenMinPayData"
                    }else if(frequency === "30min"){
                        return "/data/getDatas/HalfHourPayData"
                    }else if(frequency === "1hour"){
                        return "/data/getDatas/OneHourPayData"
                    }
                }else if(compared === "2day"){
                    if(frequency === "5min"){
                        return "/data/getDatas/TwoDayFiveMinPayData"
                    }else if(frequency === "10min"){
                        return "/data/getDatas/TwoDayTenMinPayData"
                    }else if(frequency === "15min"){
                        return "/data/getDatas/TwoDayFifteenMinPayData"
                    }else if(frequency === "30min"){
                        return "/data/getDatas/TwoDayHalfHourPayData"
                    }else if(frequency === "1hour"){
                        return "/data/getDatas/TwoDayOneHourPayData"
                    }
                }else if(compared === "3day"){
                    if(frequency === "5min"){
                        return "/data/getDatas/ThreeDayFiveMinPayData"
                    }else if(frequency === "10min"){
                        return "/data/getDatas/ThreeDayTenMinPayData"
                    }else if(frequency === "15min"){
                        return "/data/getDatas/ThreeDayFifteenMinPayData"
                    }else if(frequency === "30min"){
                        return "/data/getDatas/ThreeDayHalfHourPayData"
                    }else if(frequency === "1hour"){
                        return "/data/getDatas/ThreeDayOneHourPayData"
                    }
                }else if(compared === "5day"){
                    if(frequency === "5min"){
                        return "/data/getDatas/FiveDayFiveMinPayData"
                    }else if(frequency === "10min"){
                        return "/data/getDatas/FiveDayTenMinPayData"
                    }else if(frequency === "15min"){
                        return "/data/getDatas/FiveDayFifteenMinPayData"
                    }else if(frequency === "30min"){
                        return "/data/getDatas/FiveDayHalfHourPayData"
                    }else if(frequency === "1hour"){
                        return "/data/getDatas/FiveDayOneHourPayData"
                    }
                }else if(compared === "7day"){
                    if(frequency === "5min"){
                        return "/data/getDatas/OneWeekFiveMinPayData"
                    }else if(frequency === "10min"){
                        return "/data/getDatas/OneWeekTenMinPayData"
                    }else if(frequency === "15min"){
                        return "/data/getDatas/OneWeekFifteenMinPayData"
                    }else if(frequency === "30min"){
                        return "/data/getDatas/OneWeekHalfHourPayData"
                    }else if(frequency === "1hour"){
                        return "/data/getDatas/OneWeekOneHourPayData"
                    }
                }
            }

            /**
             * 获取图表渲染数据
             * @param {String} url 接口请求地址 
             * @param {String} frequency 刷新频率
             * @param {String} compared 对比天数
             */
            function getReportData(url, frequency, compared){
                s.chart_instance.showLoading();
                http.get(url, {}, function (res) {
                    if(!res.status){
                        drawChart(res.data, frequency, compared)
                    }else{
                        alert("查询数据失败：" + res.msg)
                    }
                });
            }
            
            // 绘制图形
            var drawChart = function (dataList, frequency, compared) {
                echarts_all_option = {
                    title: {
                        text: '成功入款笔数监控数据'
                    },
                    tooltip : {
                        trigger: 'item'
                    },
                    grid: {
                        top: '12%',
                        left: '1%',
                        right: '10%',
                        containLabel: true
                    },
                    yAxis: [
                        {
                            type : 'value',
                            name : '(笔)'
                        }
                    ]
                };

                if(frequency === "5min" || frequency === "15min"){
                    echarts_all_option.dataZoom = [
                        {
                            type: 'inside',
                            show: true,
                            start: 94,
                            end: 100,
                            handleSize: 8
                        },
                        {
                            type: 'inside',
                            show: true,
                            yAxisIndex: 0,
                            filterMode: 'filter',
                            width: 12,
                            height: '40%',
                            handleSize: 8,
                            showDataShadow: false,
                            left: '93%'
                        }
                    ]
                }

                if(compared === "1day"){
                    echarts_all_option.legend = {
                        data: [dataList.time[0].split(' ')[0]]
                    }
                    echarts_all_option.xAxis = [
                        {
                            type : 'category',
                            data : dataList.time
                        }
                    ]
                    echarts_all_option.series = [
                        {
                            name: dataList.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.num
                        }
                    ]
                }else if(compared === "2day"){
                    echarts_all_option.legend = {
                        data: [dataList.day1.time[0].split(' ')[0], dataList.day2.time[0].split(' ')[0]]
                    }
                    echarts_all_option.xAxis = [
                        {
                            type : 'category',
                            data : dataList.day1.time
                        }
                    ]
                    echarts_all_option.series = [
                        {
                            name: dataList.day1.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day1.num
                        },
                        {
                            name: dataList.day2.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day2.num
                        }
                    ]
                }else if(compared === "3day"){
                    echarts_all_option.legend = {
                        data: [dataList.day1.time[0].split(' ')[0], dataList.day2.time[0].split(' ')[0], dataList.day3.time[0].split(' ')[0]]
                    }
                    echarts_all_option.xAxis = [
                        {
                            type : 'category',
                            data : dataList.day1.time
                        }
                    ]
                    echarts_all_option.series = [
                        {
                            name: dataList.day1.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day1.num
                        },
                        {
                            name: dataList.day2.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day2.num
                        },
                        {
                            name: dataList.day3.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day3.num
                        }
                    ]
                }else if(compared === "5day"){
                    echarts_all_option.legend = {
                        data: [dataList.day1.time[0].split(' ')[0], dataList.day2.time[0].split(' ')[0], dataList.day3.time[0].split(' ')[0], dataList.day4.time[0].split(' ')[0], dataList.day5.time[0].split(' ')[0]]
                    }
                    echarts_all_option.xAxis = [
                        {
                            type : 'category',
                            data : dataList.day1.time
                        }
                    ]
                    echarts_all_option.series = [
                        {
                            name: dataList.day1.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day1.num
                        },
                        {
                            name: dataList.day2.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day2.num
                        },
                        {
                            name: dataList.day3.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day3.num
                        },
                        {
                            name: dataList.day4.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day4.num
                        },
                        {
                            name: dataList.day5.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day5.num
                        }
                    ]
                }else if(compared === "7day"){
                    echarts_all_option.legend = {
                        data: [dataList.day1.time[0].split(' ')[0], dataList.day2.time[0].split(' ')[0], dataList.day3.time[0].split(' ')[0], dataList.day4.time[0].split(' ')[0]
                        ,dataList.day5.time[0].split(' ')[0], dataList.day6.time[0].split(' ')[0], dataList.day7.time[0].split(' ')[0]]
                    }
                    echarts_all_option.xAxis = [
                        {
                            type : 'category',
                            data : dataList.day1.time
                        }
                    ]
                    echarts_all_option.series = [
                        {
                            name: dataList.day1.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day1.num
                        },
                        {
                            name: dataList.day2.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day2.num
                        },
                        {
                            name: dataList.day3.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day3.num
                        },
                        {
                            name: dataList.day4.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day4.num
                        },
                        {
                            name: dataList.day5.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day5.num
                        },
                        {
                            name: dataList.day6.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day6.num
                        },
                        {
                            name: dataList.day7.time[0].split(' ')[0],
                            type: 'line',
                            data: dataList.day7.num
                        }
                    ]
                }
    
                s.chart_instance.hideLoading();
                s.chart_instance.setOption(echarts_all_option);

                // 动态更新数据
                dynUpdateData(frequency)
    
            };

            // 动态数据更新
            function dynUpdateData(frequency){
                var timeTicket;
                var lastData;
                var axisData;
                var refreshTime
                clearInterval(timeTicket);
                timeTicket = setInterval(function () {
                    http.get(s.urlString, {}, function (res) {
                        if(!res.status){
                            echarts_all_option.axisData = dataList.time
                            echarts_all_option.series.data = dataList.num

                            echarts_all_option.setOption(echarts_all_option);
                        }else{
                            alert("查询数据失败：" + res.msg)
                        }
                    });
        
                }, 1000 * 60 * 5);
            }
        
            // 默认加载
            var timer;
            timer = setInterval(function(){
                if(document.getElementById('chart-waper')){
                    s.chart_instance = echarts.init(document.getElementById('chart-waper'));
                    s.urlString = getUrl(s.frequency, s.compared)
                    getReportData(s.urlString, s.frequency, s.compared)
                    clearInterval(timer)
                }
            }, 100)

            s.handleChangeFrequency = function(f){
                s.frequency = f;
                s.urlString = getUrl(f, s.compared)
                getReportData(s.urlString, f, s.compared)
            }

            s.handleChangeCompared = function(c){
                s.compared = c;
                s.urlString = getUrl(s.frequency, c)
                getReportData(s.urlString, s.frequency, c)
            }
        }
    ]);
})()