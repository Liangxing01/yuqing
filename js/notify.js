$(function(){
    function showNotify(data,target){
        var notice=target.find('#bar_num');
        if (data.length==0) notice.hide();
        else{
            notice.html(data.length);
            notice.show();
        }
        target.find('#notify_total').html('你有'+data.length+'条新提醒');
        var str='';
        $.each(data,function(index,v){
            var i='<li><a href="/common/event_detail?eid='+v.event_id+'" title="'+v.title+'">' +
                '<p id="alarm_title">'+
                v.title+
                '</p><span class="small italic">'+
                v.time+
                '</span></a></li>';
                str+=i;
        });
        target.find('.notify-all').before(str);
    }
    function get_notify(){
        $.getJSON('/welcome/get_event_alert','',function(data){
            if (typeof data.zp_alarm!='undefined'){
                $('#notification_bar2').show();
                showNotify(data.zp_alarm,$('#notification_bar2'));
            }
            showNotify(data.processor_alarm,$('#notification_bar1'));
        });
    }
    setInterval("get_notify()",600000);
    get_notify();
})
