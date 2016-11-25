$(function(){
   
    setInterval("get_notify()",600000);
    get_notify();
})


function updatotal(){
    var num=document.getElementById("total");
    console.log(num.value);
    if(num.value<=0){	    	
    	return;
    }else{
    	num.value=num.value-1;
    }
}
function showNotify(data,target)
{
    var notice=target.find('#bar_num');
    if (data.length==0) notice.hide();
    else{
        notice.html(data.length);
        notice.show();
    }
    target.find('#notify_total').html("你有<label id='total'>"+data.length+"</label>新信息");
    
    var str='';
    $.each(data,function(index,v){
        var i='<li onclick="updatotal()"><a href="/common/event_detail?eid='+v.event_id+'" title="'+v.title+'">' +
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