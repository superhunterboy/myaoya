/**
 * Created by asus on 2017/3/15.
 */
$.fn.placeholder = function(options) {
    var opts = $.extend({},
        $.fn.placeholder.defaults, options);
    var isIE = document.all ? true: false;
    return this.each(function() {
        var _this = this,
            placeholderValue = _this.getAttribute("placeholder"); //缓存默认的placeholder值
        if (isIE) {
            _this.setAttribute("value", placeholderValue);
            _this.onfocus = function() {
                $.trim(_this.value) == placeholderValue ? _this.value = "": '';
            };
            _this.onblur = function() {
                $.trim(_this.value) == "" ? _this.value = placeholderValue: '';
            };
        }
    });
};
Date.prototype.format = function (fmt) {
    var o = {
        "M+": this.getMonth() + 1, //月份
        "d+": this.getDate(), //日
        "h+": this.getHours(), //小时
        "m+": this.getMinutes(), //分
        "s+": this.getSeconds(), //秒
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度
        "S": this.getMilliseconds() //毫秒
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
};
$(document).ready(function () {
    $("input").placeholder();
    setInterval(function () {
        var time=new Date();
        $('#show').val(time.format("yyyy-MM-dd hh:mm:ss"));
        $('#show1').html(time.format("yyyy-MM-dd hh:mm:ss"));
    },1000);
});