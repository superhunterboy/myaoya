app.directive("imgUpload",function() {
    return {
        restrict: "AE",
        link: function (scope, element, attrs, ctrl) {
            element.on("change",function (event) {
                if(event.target.files.length>0){
                    var canvas = document.createElement('CANVAS');
                    var ctx = canvas.getContext('2d');
                    var img = new Image;
                    img.crossOrigin = 'Anonymous';
                    var url=URL.createObjectURL(event.target.files[0]);
                    img.onload = function(){
                        canvas.height = img.height;
                        canvas.width = img.width;
                        ctx.drawImage(img,0,0);
                        var dataURL = canvas.toDataURL('image/png');
                        scope.$eval(attrs.imgUpload+"='"+dataURL+"'");
                        scope.$apply();
                        URL.revokeObjectURL(url);
                        canvas = null;
                        img=null;
                    };
                    img.src = url;
                }
            })
        }
    }
});
