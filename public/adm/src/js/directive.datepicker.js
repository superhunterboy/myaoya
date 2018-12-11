app.directive("datepicker", ['$filter', function($filter) {
    var _date = window.Date;
    var dateFilter = $filter('date');

    function uDate(str) {

        if (!str)
            return new _date();
        var date;
        if (typeof str == "string") {
            date = new _date(0);
            var param = str.split(" ");
            var pa = param[0] && param[0].match(/\d{1,}/g) || [];
            var pb = param[1] && param[1].match(/\d{1,}/g) || [];

            date.setDate(pa[2] || "1");
            date.setMonth(parseInt(pa[1] || 1) - 1);
            date.setYear(pa[0] || "1900");

            date.setHours(pb[0] || "0");
            date.setMinutes(pb[1] || "0");
            date.setSeconds(pb[2] || "0");
            date.setMilliseconds(0);
        }else{
            date = new _date(str);
        }
        return date;
    }

    var Date = uDate;

    var isOpen = false;
    var $TODAY = new Date();
    $TODAY.setHours(0);
    $TODAY.setMinutes(0);
    $TODAY.setSeconds(0);
    $TODAY.setMilliseconds(0);
    return {
        restrict: 'A',
        require: '?ngModel',
        priority:2000,
        link: function(scope, element, attrs, ctrl) {
            scope.$TODAY = $TODAY;
            scope.$toTime = function(str){
                return str ? uDate(str).getTime() : "";
            };

            element[0].readOnly = true;
            var format = {
                dateFmt: 'yyyy-MM-dd HH:mm'
            };
            var maxEq = scope.$eval(attrs.maxEq) || false,
                minEq = scope.$eval(attrs.minEq) || false;
            if (attrs.datepicker)
                format.dateFmt = attrs.datepicker;
            if (attrs.notShowToday)
                format.isShowToday=false;
            if (attrs.max) {
                scope.$watch(attrs.max, function(newValue) {
                    if (newValue) {
                        var temp = new Date(newValue);
                        if (temp.toString() != "Invalid Date") {
                            // 执行最大值检查
                            format.maxDate = dateFilter(newValue, "yyyy-MM-dd HH:mm:ss");
                            if (ctrl.$viewValue && temp <= (new uDate(ctrl.$viewValue))) {
                                if (maxEq && (+temp == +uDate(ctrl.$viewValue))) {
                                    ctrl.$setValidity("max", true);
                                } else {
                                    ctrl.$setValidity("max", false);
                                }
                            } else
                                ctrl.$setValidity("max", true);
                        }
                    } else {
                        ctrl.$setValidity("max", true);
                        format.maxDate = '';
                    }
                });
            }

            if (attrs.min) {
                scope.$watch(attrs.min, function(newValue) {
                    if (newValue) {
                        var temp = new uDate(newValue);
                        if (temp.toString() != "Invalid Date") {
                            // 执行最小值检查
                            format.minDate = dateFilter(newValue, "yyyy-MM-dd HH:mm:ss");
                            if (ctrl.$viewValue && temp >= (new uDate(ctrl.$viewValue))) {
                                if (minEq && (+temp == +uDate(ctrl.$viewValue))) {
                                    ctrl.$setValidity("min", true);
                                } else {
                                    ctrl.$setValidity("min", false);
                                }
                            } else
                                ctrl.$setValidity("min", true);
                        }
                    } else {
                        if (format.minDateTemp) {
                            format.minDate = format.minDateTemp;
                        } else
                            format.minDate = '';

                        ctrl.$setValidity("min", true);

                    }
                });
            }

            if (attrs.max || attrs.min)
                ctrl.$parsers.unshift(function(value) {
                    var temp = new uDate(value);
                    if (value == "" && !attrs.require) {
                        ctrl.$setValidity("max", true);
                        ctrl.$setValidity("min", true);
                        ctrl.$setValidity("$valid", true);
                    }
                    if (!value) return value;
                    if (temp.toString() != "Invalid Date") {
                        if (format.maxDate && temp >= uDate(format.maxDate)) {
                            ctrl.$setValidity("max", maxEq && (+temp == +uDate(format.maxDate)));
                            return value;
                        } else
                            ctrl.$setValidity("max", true);
                        if (format.minDate && temp <= uDate(format.minDate)) {
                            ctrl.$setValidity("min", minEq && (+temp == + uDate(format.minDate)));
                            return value;
                        } else {
                            ctrl.$setValidity("min", true);
                        }
                        return value;
                    } else {
                        ctrl.$setValidity("min", true);
                        ctrl.$setValidity("max", true);
                        return value;
                    }
                });

            element.on("click", function(event) {
                WdatePicker(format);
            });
            element.on("blur", function(ev) {
                if (attrs.timestamp)
                    ctrl.$setViewValue(element[0].value && uDate(element[0].value).getTime());
                else
                    ctrl.$setViewValue(element[0].value);
                scope.$apply();
            });
            if (attrs.timestamp)
                ctrl.$formatters.push(function(value) {
                    var dateFilter = $filter('date');
                    return value && dateFilter(value, format.dateFmt); //format
                });
        }
    };
}]);