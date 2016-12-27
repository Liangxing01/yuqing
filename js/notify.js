$(function(){
    get_notify();
})


function showNotify(data,target)
{
    var notice=target.find('.bar_num');
    if (data === undefined || data.length===0 ) {
        notice.hide();
        target.find('.notify_total').html("你有<span>0</span>条新信息");
        return;
    }
    else{
        notice.html(data.length);
        notice.show();
    }
    target.find('.notify_total').html("你有<span>"+data.length+"</span>新信息");
    
    var str='';
    $.each(data,function(index,v){
        var i='<li><a href="/common/event_detail?eid='+v.event_id+'&option=cancel_alert" title="'+v.title+'">' +
            '<p class="alarm_title">'+
            v.title+
            '</p><span class="small italic">'+
            v.time+
            '</span></a></li>';
            str+=i;
    });
    target.find('.notify-all').before(str);
}

  

function get_notify(){
    $.ajax({
        type:'get',
        dataType:'json',
        url:'/welcome/get_event_alert',
        success:function(data){
            if(data.processor_alarm){//提前首回提醒
                $('#notification_bar2').show();
                showNotify(data.processor_alarm,$('#notification_bar2'));
            }
            if(data.zp_alarm){//超时提醒
                $('#notification_bar1').show();
                showNotify(data.zp_alarm,$('#notification_bar1'));
            }
        }
    })
}

