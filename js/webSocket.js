/**
 * Created by LX on 2016/11/30.
 */
$(function(){
    //get_all_msg();
    get_webSocket_msg();
})

window.onload = function(){

}
//重新绘制
function reload_num(){
    $('#msg tr').each(function(i){
        $(this).find('td').eq(0).html(i+1);
    })
}
function get_webSocket_msg(){
    var client_socket = new WebSocket('ws://192.168.0.127:3000');
    client_socket.onopen = function(){
         console.log("服务器已连接");
        var cookie = getCookie('p_token');
        var json = {'token':cookie};
        client_socket.send(JSON.stringify(json));
    }
    client_socket.onmessage =function(ev){
         console.log(ev.data);
        var data = $.parseJSON(ev.data);
        if(data){
            var list = '';
                list += '<tr>';
                list += '<td></td>';
                switch(data.type){
                    case 0:
                        list += '<td><span class="label label-info">信息上报</span></td>';break;
                    case 1:
                        list += '<td><span class="label label-primary">事件指派</span></td>';break;
                    case 2:
                        list += '<td><span class="label label-danger">事件督办</span></td>';break;
                    case 3:
                        beforetime_info(data);return;//事件首回提醒
                    case 4:
                        overtime_info(data);return;//超时提醒
                };
                list += '<td><a href="'+data.url+'">'+data.title+'</a></td>';
                list += '<td><span class="badge bg-important">'+timeToDate(data.time*1000)+'</span></td>';
            $('#msg').prepend(list);
            $('#msg tr:eq(5)').remove();
            reload_num();

            layer.open({
                title: '你有新的短消息',
                content: '<a class="" href="'+data.url+'">点击查看</a>',
                area:['300px','250px'],
                offset:'rb',
                btn:['确定'],
                btnAlign:'c',
                shade:0,
                time:15000,
                skin:'demo-lx',
                tipsMore:true
            });
        }
    }
    client_socket.onclose = function(){
        console.log("服务器已关闭");
    }

}
//超时提醒
function overtime_info(data){
    var val = $('#notification_bar2 .bar_num').html();
    $('#notification_bar2 .bar_num').html(val+1);
    $('#notification_bar2 .bar_num').show();
    var val2 = $('#notification_bar2 .notify_total span').html();
    $('#notification_bar2 .notify_total span').html(val2+1);
    var str = '';
    str += '<li>';
    str += '<a href="/common/event_detail?eid="'+data.event_id+'" title="'+data.title+'">';
    str += '<p class="alarm_title"'+data.title+'></p>';
    str += '<span class="small italic">'+data.time+'</span>';
    str += '</a></li>';
    $('#notification_bar2').find('.notify-all').before(str);
    layer.open({
        title: ['超时提醒','color:#fff;background:red'],
        content: '<p>'+data.title+'</p><a  href="javascript:void(0)">点击查看</a>',
        area:['200px','150px'],
        offset:'rb',
        btn:['确定'],
        btnAlign:'c',
        shade:0,
        time:15000,
        skin:'demo-lx',
        tipsMore:true
    });
}
/*
*   功能：事件首回提醒
*   参数：content  数据
*
* */
function beforetime_info(data){
    var val = $('#notification_bar1 .bar_num').html();
    $('#notification_bar1 .bar_num').html(val+1);
    $('#notification_bar1 .bar_num').show();
    var val2 = $('#notification_bar1 .notify_total span').html();
    $('#notification_bar1 .notify_total span').html(val2+1);
    var str = '';
        str += '<li>';
        str += '<a href="/common/event_detail?eid="'+data.event_id+'" title="'+data.title+'">';
        str += '<p class="alarm_title"'+data.title+'></p>';
        str += '<span class="small italic">'+data.time+'</span>';
        str += '</a></li>';
    $('#notification_bar1').find('.notify-all').before(str);
    layer.open({
        title: ['事件首回提醒','color:#fff;background:blue'],
        content: '<p>'+data.title+'</p><a class="" href="">点击查看</a>',
        area:['200px','150px'],
        offset:'rb',
        btn:['确定'],
        btnAlign:'c',
        shade:0,
        time:15000,
        skin:'demo-lx',
        tipsMore:true
    });
}
//获取历史记录消息
function get_all_msg(){
    $.ajax({
        url:'/welcome/get_msg',
        type :'get',
        dataType:'json',
        success:function(data){
            if(data){
                var len = data.length;
                var list = '';
                for(var i = 0;i<len;i++){
                    list += '<tr>';
                    list += '<td></td>';
                    switch(data[i].type){
                        case '0':
                            list += '<td><span class="label label-info">信息上报</span></td>';break;
                        case '1':
                            list += '<td><span class="label label-primary">事件指派</span></td>';break;
                        case '2':
                            list += '<td><span class="label label-danger">事件督办</span></td>';break;
                    };
                    list += '<td><a href="'+data[i].url+'">'+data[i].title+'</a></td>';
                    list += '<td><span class="badge bg-important">'+timeToDate(data[i].time*1000)+'</span></td>';
                }
                $('#msg').html(list);
                reload_num();
            }else{
                $('#msg').html('<tr><td style="text-align: center">暂无历史消息</td></tr>')
            }

        }
    })
}
//获取cookie值
function getCookie(name){
    if(document.cookie.length>0){
        var begin = document.cookie.indexOf(name+'=');
        if(begin != -1){
            begin += name.length+1;
            var end = document.cookie.indexOf(";",begin);
            if(end == -1){
                end = document.cookie.length;
            }
            return  decodeURIComponent(document.cookie.substring(begin,end));
        }
    }
    return null;
}