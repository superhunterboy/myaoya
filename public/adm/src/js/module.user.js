var user=angular.module('user',['ngDialog']).directive("user",["$rootScope","$http","$state",'ngDialog',function(rs,$http,$state,ngDialog){
    return {
        restrict: "AE",
        template :'<div class="user">\
                    <i class="icon-user"></i>\
                    <a ui-sref="account" class="username"></a>\
                    <a href="#" ng-click="logout()">退出登录</a>\
                </div>',
        link:function(scope, element, attrs, ctrl){
            scope.logout=function () {
                $http({url:"/admin/auth/logout",method:'GET'}).then(function () {
                    location.href="login.html"
                })
            };
            var t=+new Date;
            $http({url:"/admin/getCurrentUser?t="+t,cache:false,method:'GET'}).then(function (bk) {
                var res=bk.data;
                if(res.status==0){
                    rs.userInfo=res.data;
                    $(element).find(".username").html(res.data.realname);
                    if(!rs.userInfo.lastlogin){
                        $state.go("account");
                    }
                }else{
                    location.href="login.html"
                }
            }).catch(function(err){
                location.href="login.html"
            });
            rs.warn=document.createElement("AUDIO");
            rs.warn.loop="loop";
            rs.warnSource=document.createElement("SOURCE");
            rs.warnSource.src="mp3/warn.mp3";
            rs.warn.appendChild(rs.warnSource);
            scope.otpStatus=0;
            rs.connect=function () {
                rs.socket = null;
                rs.socket = new WebSocket('wss://yjv5.com/');
                rs.socket.onopen = function () {
                    rs.heartCheck.start();
                    rs.socket.onmessage = function (event) {
                        rs.heartCheck.reset();
                        var res=JSON.parse(event.data);
                        //res.ret=1006;
                        if(res.ret==1001){
                            console.log(res.msg);
                            if(scope.otpStatus==0){
                                scope.otpStatus=1;
                                rs.warn.play();
                                ngDialog.open({
                                    template:"template/otpConfigDialog.html",
                                    controller:"otpConfig",
                                    closeByDocument :false
                                }).closePromise.then(function () {
                                    scope.otpStatus=0;
                                    rs.warn.pause();
                                })
                            }
                        }else if(res.ret==1003){
                            console.error(res.msg);
                        }else if(res.ret==1004){
                            console.log(res.msg);
                        }else if(res.ret==1005){
                            console.log(res.msg);
                            if(scope.otpStatus==0){
                                scope.otpStatus=1;
                                rs.warn.play();
                                ngDialog.open({
                                    template:"template/otpConfigDialog.html",
                                    controller:"otpConfig",
                                    closeByDocument :false
                                }).closePromise.then(function () {
                                    scope.otpStatus=0;
                                    rs.warn.pause();
                                })
                            }
                        }else if(res.ret==1006){
                            console.log(res.msg);
                        }else if(res.ret==1007){
                            console.error(res.msg);
                        }else{
                            console.log(res.msg);
                        }
                    };
                    rs.socket.onclose = function (event) {
                        console.log('Client notified socket has closed', event);
                        rs.connect();
                    };
                    rs.socket.onerror = function (event) {
                        console.error(event);
                        rs.connect();
                    };
                };
            };
            rs.connect();
            rs.heartCheck = {
                timeout: 30000,
                timeoutObj: null,
                serverTimeoutObj: null,
                reset: function(){
                    clearTimeout(this.timeoutObj);
                    clearTimeout(this.serverTimeoutObj);
                    this.start();
                },
                start: function(){
                    var self = this;
                    this.timeoutObj = setTimeout(function(){
                        rs.socket.send('{"type":"ping"}');
                        self.serverTimeoutObj = setTimeout(function(){
                            rs.socket.close();
                        }, self.timeout)
                    }, this.timeout)
                }
            };
        }
    }
}]);
user.controller('otpConfig',['$scope','$rootScope',function (s,rs) {
    s.change={
        name:'otp',
        content:''
    };
    s.sub=function () {
        if(s.change.content && /^([0-9]*)$/.test(s.change.content)){
            rs.socket.send('{"otp":"'+s.change.content+'"}');
            s.closeThisDialog();
        }else{
            s.error=true;
            s.errorMsg='请输入正确的OTP密码';
        }

    }
}]);
