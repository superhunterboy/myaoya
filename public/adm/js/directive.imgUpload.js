app.directive("imgUpload",function(){return{restrict:"AE",link:function(e,t,n,a){t.on("change",function(t){if(t.target.files.length>0){var a=document.createElement("CANVAS"),i=a.getContext("2d"),r=new Image;r.crossOrigin="Anonymous";var o=URL.createObjectURL(t.target.files[0]);r.onload=function(){a.height=r.height,a.width=r.width,i.drawImage(r,0,0);var t=a.toDataURL("image/png");e.$eval(n.imgUpload+"='"+t+"'"),e.$apply(),URL.revokeObjectURL(o),a=null,r=null},r.src=o}})}}});