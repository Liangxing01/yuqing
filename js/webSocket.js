/**
 * Created by LX on 2016/11/30.
 */
$(function(){
    get_all_msg();
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
    var client_socket = new WebSocket('ws://192.168.0.130:3000');
    client_socket.onopen = function(){
        console.log("服务器已连接");
        var json = {'token':'d5f6ced3528dca7c56a1d0a30987e55c'};
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
                };
                list += '<td><a href="'+data.url+'">'+data.title+'</a></td>';
                list += '<td><span class="badge bg-important">'+timeToDate(data.time*1000)+'</span></td>';
            $('#msg').prepend(list);
            $('#msg tr:eq(5)').remove();
            reload_num();
            layer.open({
                title: '你有新的短消息',
                content: '<a class="'+data.url+'" href="">点击查看</a>',
                area:['200px','150px'],
                offset:'rb',
                btn:['确定'],
                btnAlign:'c',
                shade:0,
                time:3000,
                skin:'demo-lx',
                tipsMore:true
            });
        }
    }
    client_socket.onclose = function(){
        console.log("服务器已关闭");
    }

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