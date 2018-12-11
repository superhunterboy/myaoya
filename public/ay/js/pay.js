/**
 * Created by Administrator on 2016/12/3.
 */
$(function(){
    $.ajax({
        url:"/getEnabledPicture/1",
        type:"GET",
        dataType:"json",
        success:function(res){
            if(res.status==0){
                for(var i=0;i<res.data.length;i++){
                    if(res.data[i].type==1){
                        $("#wechat").attr("src",res.data[i].picture);
                    }else if(res.data[i].type==2){
                        $("#alipay").attr("src",res.data[i].picture);
                    }
                }
            }else{
                alert("网络错误！请刷新页面")
            }
        },
        error:function(){
            alert("网络错误！请刷新页面")
        }
    });
    $(".confirm").click(function(){
        popWin("confirmBox");
    })
});