app.directive("datatype", function() {
    return {
        require: '?ngModel',
        priority: 1000,
        link: function(scope, element, attrs, ctrl) {
            attrs.datatype = attrs.datatype || "INT";
            var datatype = attrs.datatype.replace(/\d/, "X").toUpperCase();
            if(datatype=='INT' || datatype=='DOUBLEX'){
                var bkbtn = [8, 13];
                var supportList = [96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 37, 38, 39, 40, 46, 9];
                var noShiftList = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57];
                var ctrlList = [65];
                var dot = [110, 190];
                element.on('keydown', function (ev) {
                    if (bkbtn.some(function(x) {return x == ev.keyCode;})) {
                        return true;
                    }
                    if (supportList.some(function(x) {return x == ev.keyCode;})) {
                        return true;
                    }

                    if (noShiftList.some(function(x) {return x == ev.keyCode && !ev.shiftKey;})) {
                        return true;
                    }
                    if (ctrlList.some(function(x) {return x == ev.keyCode && ev.ctrlKey;})) {
                        return true;
                    }
                    if (datatype=="DOUBLEX" && ev.target.value.indexOf(".") == -1 && dot.some(function(x) {return x == ev.keyCode;})) {
                        return true;
                    }
                    ev.preventDefault();
                    ev.target.value=ev.target.value.replace(/[^\d.]/g,'');
                    return false;
                });

                ctrl.$parsers.unshift(function(value) {
                    if (!value)
                        return value;
                    value = value.replace(/[^\d.]/g, "");
                    // 转换参数与值设定
                    ctrl.$viewValue = element[0].value = value;
                    return value;
                });
                if(datatype=="DOUBLEX"){
                    var len=parseInt(attrs.datatype.slice(6)) || 1;
                    element.on('blur ',function (ev) {
                        if (!isNaN(parseFloat(element[0].value))) {
                            var value = parseFloat(element[0].value);
                            value = value.toFixed(len);
                            ctrl.$setViewValue(value);
                            scope.$eval(attrs.ngModel+"='"+value+"'");
                            element[0].value = value;
                            scope.$apply();
                        }else{
                            ctrl.$setViewValue("");
                            element[0].value = "";
                            scope.$apply();
                        }
                    });
                }else{
                    element.on('blur',function (ev) {
                        if (!isNaN(parseInt(element[0].value))) {
                            var value = parseInt(element[0].value).toString();
                            ctrl.$setViewValue(value);
                            scope.$eval(attrs.ngModel+"='"+value+"'");
                            element[0].value = value;
                            scope.$apply();
                        } else{
                            ctrl.$setViewValue("");
                            element[0].value = "";
                            scope.$apply();
                        }
                    })
                }
            }else if(datatype=="EN"){
                ctrl.$parsers.unshift(function(value) {
                    if (!value)
                        return value;
                    value = value.replace(/[^a-zA-Z\d]/g,'');
                    ctrl.$viewValue = element[0].value = value;
                    return value;
                });
                element.on('blur keyup afterpaste',function () {
                    var value = element[0].value.replace(/[^a-zA-Z\d]/g,'');
                    ctrl.$setViewValue(value);
                    scope.$eval(attrs.ngModel+"='"+value+"'");
                    element[0].value = value;
                    scope.$apply();
                })
            }

        }
    };
});