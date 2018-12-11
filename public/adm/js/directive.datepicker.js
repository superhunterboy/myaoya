app.directive("datepicker",["$filter",function(e){function t(e){if(!e)return new i;var t;if("string"==typeof e){t=new i(0);var a=e.split(" "),n=a[0]&&a[0].match(/\d{1,}/g)||[],s=a[1]&&a[1].match(/\d{1,}/g)||[];t.setDate(n[2]||"1"),t.setMonth(parseInt(n[1]||1)-1),t.setYear(n[0]||"1900"),t.setHours(s[0]||"0"),t.setMinutes(s[1]||"0"),t.setSeconds(s[2]||"0"),t.setMilliseconds(0)}else t=new i(e);return t}var i=window.Date,a=e("date"),n=t,s=new n;return s.setHours(0),s.setMinutes(0),s.setSeconds(0),s.setMilliseconds(0),{restrict:"A",require:"?ngModel",priority:2e3,link:function(i,m,l,d){i.$TODAY=s,i.$toTime=function(e){return e?t(e).getTime():""},m[0].readOnly=!0;var r={dateFmt:"yyyy-MM-dd HH:mm"},u=i.$eval(l.maxEq)||!1,$=i.$eval(l.minEq)||!1;l.datepicker&&(r.dateFmt=l.datepicker),l.notShowToday&&(r.isShowToday=!1),l.max&&i.$watch(l.max,function(e){if(e){var i=new n(e);"Invalid Date"!=i.toString()&&(r.maxDate=a(e,"yyyy-MM-dd HH:mm:ss"),d.$viewValue&&i<=new t(d.$viewValue)?u&&+i==+t(d.$viewValue)?d.$setValidity("max",!0):d.$setValidity("max",!1):d.$setValidity("max",!0))}else d.$setValidity("max",!0),r.maxDate=""}),l.min&&i.$watch(l.min,function(e){if(e){var i=new t(e);"Invalid Date"!=i.toString()&&(r.minDate=a(e,"yyyy-MM-dd HH:mm:ss"),d.$viewValue&&i>=new t(d.$viewValue)?$&&+i==+t(d.$viewValue)?d.$setValidity("min",!0):d.$setValidity("min",!1):d.$setValidity("min",!0))}else r.minDateTemp?r.minDate=r.minDateTemp:r.minDate="",d.$setValidity("min",!0)}),(l.max||l.min)&&d.$parsers.unshift(function(e){var i=new t(e);return""!=e||l.require||(d.$setValidity("max",!0),d.$setValidity("min",!0),d.$setValidity("$valid",!0)),e?"Invalid Date"!=i.toString()?r.maxDate&&i>=t(r.maxDate)?(d.$setValidity("max",u&&+i==+t(r.maxDate)),e):(d.$setValidity("max",!0),r.minDate&&i<=t(r.minDate)?(d.$setValidity("min",$&&+i==+t(r.minDate)),e):(d.$setValidity("min",!0),e)):(d.$setValidity("min",!0),d.$setValidity("max",!0),e):e}),m.on("click",function(e){WdatePicker(r)}),m.on("blur",function(e){l.timestamp?d.$setViewValue(m[0].value&&t(m[0].value).getTime()):d.$setViewValue(m[0].value),i.$apply()}),l.timestamp&&d.$formatters.push(function(t){var i=e("date");return t&&i(t,r.dateFmt)})}}}]);