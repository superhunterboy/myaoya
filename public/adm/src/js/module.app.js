var app = angular.module('app', ['nav', 'user']).config(["$provide", "$compileProvider", "$controllerProvider", "$filterProvider", '$httpProvider',
    function($provide, $compileProvider, $controllerProvider, $filterProvider, $httpProvider) {
        app.controller = $controllerProvider.register;
        app.directive = $compileProvider.directive;
        app.filter = $filterProvider.register;
        app.factory = $provide.factory;
        app.service = $provide.service;
        app.constant = $provide.constant;
        $httpProvider.defaults.transformRequest.push(function(obj) {
            if (obj && typeof obj == 'string') {
                obj = JSON.parse(obj);
                var str = [];
                for (var p in obj) {
                    str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
                }
                return str.join("&");
            }
        });
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
    }
]);
app.directive("smscode", ["$rootScope", "http", "ngDialog", "Tip", function(rs, http, ngDialog, tip) {
    return {
        replace: true,
        restrict: "AE",
        template: "<div class='smscode'>" + "<div class='page-title'>" + "<span>短信验证</span>" + "</div>" + "<div class='table' style='text-align: center;padding: 180px 0;margin: 100px 600px;'>" + "<input type='text' maxlength='4' class='input codeInput' /> <input type='button' ng-disabled='loading' class='btn btn-default sendCode' value='发送验证码'>" + "</div> ",
        link: function(scope, element, attrs, ctrl) {
            rs.smsCode = false;
            http.get("/admin/isSmsVerify", {}, function(res) {
                if (res.status != 0) {
                    rs.smsCode = true;
                }
            });
            $(".sendCode").on("click", function() {
                if (!scope.loading) {
                    scope.loading = true;
                    http.get("/admin/sendCode", {}, function(res) {
                        if (res.status == 0) {
                            tip.success("发送成功");
                            var time = 30;
                            var t = setInterval(function() {
                                if (time > 0) {
                                    scope.loading = true;
                                    time--;
                                    $(".sendCode").val(time + "s");
                                } else {
                                    clearInterval(t);
                                    $(".sendCode").val("发送验证码");
                                    scope.loading = false;
                                }
                            }, 1000)
                        } else {
                            tip.error(res.data.msg);
                            scope.loading = false;
                        }
                    })
                }
            });
            $(".codeInput").on("keyup", function() {
                if ($(this).val().length == 4) {
                    http.get("/admin/verifyCode?code=" + $(this).val(), {}, function(res) {
                        if (res.status == 0) {
                            tip.success("验证通过");
                            rs.smsCode = true;
                        } else {
                            tip.error(res.data.msg)
                        }
                    })
                }
            });
            rs.$on("$stateChangeStart", function() {
                rs.smsCode = false;
            })
        }
    }
}])
app.directive("checkAll", function() {
    return {
        restrict: "AE",
        link: function(scope, element, attrs, ctrl) {
            $(document).off("change", "." + attrs.checkAll).on("change", "." + attrs.checkAll, function() {
                scope.$apply(function() {
                    scope[attrs.ngModel] = false;
                });
                if ($(this).prop("checked")) {
                    scope[attrs.checkValue].push($(this).val())
                } else {
                    var _index = scope[attrs.checkValue].indexOf($(this).val());
                    if (_index != -1) {
                        scope[attrs.checkValue].splice(_index, 1);
                    }
                }
            });
            element.on("change", function() {
                var checkBoxes = $("." + attrs.checkAll + ":enabled");
                if ($(this).prop("checked")) {
                    checkBoxes.prop("checked", true);
                    scope[attrs.checkValue].length = 0;
                    checkBoxes.each(function(i, checkbox) {
                        scope[attrs.checkValue].push($(checkbox).val());
                    })
                } else {
                    checkBoxes.prop("checked", false);
                    scope[attrs.checkValue].length = 0;
                }
            })
        }
    }
});
app.factory('http', ['$http', function($http) {
    function Http() {}
    Http.prototype.get = function(url, data, callback) {
        $http({
            url: url,
            params: data,
            method: 'GET',
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function(bk) {
            var res = bk.data;
            callback(res);
        }).catch(function(err) {
            if (err.status == 401) {
                alert("您的上网IP发生变化或账号在其他地方登录，请重新登陆！");
                location.href = "login.html";
            }
            callback({
                status: err.status,
                msg: "接口错误"
            })
        })
    };
    Http.prototype.delete = function(url, callback) {
        $http({
            url: url,
            method: 'DELETE',
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function(bk) {
            var res = bk.data;
            callback(res);
        }).catch(function(err) {
            if (err.status == 401) {
                alert("您的上网IP发生变化或账号在其他地方登录，请重新登陆！");
                location.href = "login.html";
            }
            callback({
                status: err.status,
                msg: "接口错误"
            })
        })
    };
    Http.prototype.post = function(url, data, callback) {
        $http({
            url: url,
            data: angular.toJson(data, true),
            method: 'POST',
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/x-www-form-urlencoded"
            }
        }).then(function(bk) {
            var res = bk.data;
            callback(res);
        }).catch(function(err) {
            if (err.status == 401) {
                alert("您的上网IP发生变化或账号在其他地方登录，请重新登陆！");
                location.href = "login.html";
            }
            callback({
                status: err.status,
                msg: "接口错误"
            })
        })
    };
    Http.prototype.put = function(url, data, callback) {
        $http({
            url: url,
            data: angular.toJson(data, true),
            method: 'PUT',
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/x-www-form-urlencoded"
            }
        }).then(function(bk) {
            var res = bk.data;
            callback(res);
        }).catch(function(err) {
            if (err.status == 401) {
                alert("您的上网IP发生变化或账号在其他地方登录，请重新登陆！");
                location.href = "login.html";
            }
            callback({
                status: err.status,
                msg: "接口错误"
            })
        })
    };
    return new Http();
}]);
app.constant('vendorsType', {
    '0': '不可用',
    '1': '雅付',
    '2': '闪付',
    '3': '讯宝',
    '4': '乐盈',
    '5': '自由付',
    '6': '沃雷特',
    '7': '金海哲',
    '8': '华仁',
    '9': '荷包',
    '10': '立刻付',
    '11': '多多',
    '12': '金海哲(新)',
    '13': '仁信',
    '14': '天付宝',
    '15': '高通',
    '16': '新雅付',
    '17': '先行付',
    '18': '我付',
    '19': '汇达',
    '20': '泽圣',
    '21': '新自由付',
    '22': '钱袋支付',
    '23': '金阳',
    '24': '个人支付宝',
    '25': '旺富通',
    '26': '千应',
    '27': '优付',
    '28': '商码付',
    '29': '恒辰',
    '30': '成沃',
    '31': '开联通',
    '32': '点付云',
    '33': '芯富',
    '34': '滕坤',
    '35': '天吉',
    '36': '众点',
    '37': '智能云支付',
    '38': '智能云支付2.0',
    '39': '喜付',
    '40': '艾付',
    '41': 'Npay付',
    '42': '顺心付',
    '43': '米达支付',
    '44': 'wpay',
    '45': '恒星闪付',
    '46': '众信',
    '47': '星捷',
    '48': '迅捷',
    '51': '恒通',
    /*'50':'闪亿',*/
    '52': 'Bingopay',
    '49': '云通',
    '53': '乐享',
    '54': '易宝',
    '55': '随意付',
    '56': '畅支付',
    '57': '银信',
    '58': '五福',
    '59': 'Pppay',
    '60': '顺达通',
});
app.constant('payOutType', {
    '0': '无',
    '1': '天付宝',
    '2': '雅付',
    '3': '金海哲',
    '4': '泽圣',
    '5': '传化',
    '6': '开联通',
    '7': '众点',
    '8': '商码付',
    '9': '喜付',
    '10': '艾付',
    '11': 'Npay付',
    '12': '顺心付',
    '13': '天吉(仅充值)',
    '14': '迅捷',
    '15': '多宝(仅出款)',
    '16': 'Bingopay',
    '17': '易宝',
    '18': '新欣聚',
    '19': '佳友通',
    '20': '盖亚',
    '21': '青英',
    '22': '极云',
    '23': 'RHPay',
    '24': '广付通',
    '25': '众鑫',
    '25': '众鑫',
    '26':'GPpay',
    '27':'先锋'
})