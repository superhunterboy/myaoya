(function() {
    app.factory('Tip', ["ngDialog", function (ngDialog) {
        function Tip(){}
        Tip.prototype.open=function (msg,theme) {
            theme=theme || '';
            this.msg=msg;
            ngDialog.open({
                template:'<div class="tip-msg">'+msg+'</div>',
                plain: true,
                closeByDocument :false,
                showClose:false,
                className:"ngdialog-tip "+theme,
                controller:['$scope', function(s) {
                    setTimeout(function () {
                       s.closeThisDialog();
                    },3000)
                }]
            });

        };
        Tip.prototype.error=function (msg) {
            this.open(msg,'tipError')
        };
        Tip.prototype.success=function (msg) {
            this.open(msg,'tipSuccess')
        };
        return new Tip()
    }])
})();