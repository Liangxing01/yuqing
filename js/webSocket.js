/**
 * Created by LX on 2016/11/30.
 */
$(function () {
    //get_all_msg();
    get_webSocket_msg();
    window.onfocus = message.clear;
});

//重新绘制
function reload_num() {
    $('#msg tr').each(function (i) {
        $(this).find('td').eq(0).html(i + 1);
    })
}
function get_webSocket_msg() {
    try {
        var client_socket = new WebSocket('ws://' + 'www.bnv6.com' + ':4000');
        client_socket.onopen = function () {
            console.log("服务器已连接");
        };

        client_socket.onmessage = function (ev) {
            var data = $.parseJSON(ev.data);
            if (data) {
                if (data.type != 233) {
                    message.show();
                }
                switch (data.type) {
                    case 233:
                        bind_client_to_uid(data.client_id);
                        break;
                    case 0:
                    case 1:
                    case 2:
                        add_type_list(data);
                        break;//添加消息信息
                    case 3:
                        message_info($('#notification_bar2'), data);
                        break;//事件首回提醒
                    case 4:
                        message_info($('#notification_bar1'), data);
                        break;//超时提醒
                }
            }
        };

        client_socket.onclose = function () {
            console.log("服务器已关闭");
            //断线重连
            setTimeout(function () {
                get_webSocket_msg();
            }, 30000);
        };
        //发送心跳包
        setInterval(function () {
            heart_beat(client_socket);
        }, 45000);
    } catch (err) {
        console.log(err);
    }
}

var msg_num = 1;    //提醒框的偏移量
//向消息记录添加消息信息
function add_type_list(data) {
    var title = ''; //提示框的title
    var list = '<tr><td></td>';
    switch (data.type) {
        case 0:
            list += '<td><span class="label label-info">信息上报</span></td>';
            title = '信息上报提醒';
            break;
        case 1:
            list += '<td><span class="label label-primary">事件指派</span></td>';
            title = '事件指派提醒';
            break;
        case 2:
            list += '<td><span class="label label-danger">事件督办</span></td>';
            title = '事件督办提醒';
            break;
    }
    list += '<td><a href="' + data.url + '">' + data.title + '</a></td>';
    list += '<td><span class="badge bg-important">' + timeToDate(data.time * 1000) + '</span></td>';
    $('#msg').prepend(list);
    $('#msg tr:eq(5)').remove();
    reload_num();
    var width = $(window).width() - 300;
    var height = $(window).height() - 10;
    layer.open({
        type: 1,
        title: title,
        content: '<a class="msg-title" target="_blank" href=' + data.url + '>' + data.title + '</>',
        style: 'text-align:center',
        area: ['300px', '150px'],
        offset: [height - 160 * (msg_num % 3), width],
        shade: 0,
        closeBtn: 1,
        btn: "查看",
        btnAlign: 'c',
        yes: function () {
            window.open(data.url, "target=_blank");
        },
        anim: 2,
        time: 30000 * (msg_num % 3)
    });

    $('.msg-title').parent().eq(1).css({
        textAlign: "center",
        fontSize: "16px",
        marginTop: "5px"
    });
    msg_num++;
}


//消息提醒
function message_info(target, data) {
    var val = target.find('.bar_num').html();
    target.find('.bar_num').html(parseInt(val) + 1);
    target.find('.bar_num').show();
    var val2 = target.find('.notify_total span').html();
    target.find('.notify_total span').html(parseInt(val2) + 1);
    var str = '';
    str += '<li>';
    str += '<a href="/common/event_detail?eid="' + data.eid + '" title="' + data.title + '&option=cancel_alert">';
    str += '<p class="alarm_title"' + data.title + '></p>';
    str += '<span class="small italic">' + timeToDate(data.time * 1000) + '</span>';
    str += '</a></li>';
    target.find('.notify-all').before(str);

    if (target.attr('id') === 'notification_bar1') {
        var title = ['超时提醒', 'color:#fff;background:red'];
    }
    if (target.attr('id') === 'notification_bar2') {
        var title = ['事件首回提醒', 'color:#fff;background:blue']
    }
    layer.open({
        type: 1,
        title: title,
        content: '<a  href="/common/event_detail?eid=' + data.eid + '&option=cancel_alert">点击查看</a>',
        area: ['200px', '150px'],
        offset: 'rb',
        btnAlign: 'c',
        shade: 0,
        time: 15000
    });
}

//获取cookie值
function getCookie(name) {
    if (document.cookie.length > 0) {
        var begin = document.cookie.indexOf(name + '=');
        if (begin != -1) {
            begin += name.length + 1;
            var end = document.cookie.indexOf(";", begin);
            if (end == -1) {
                end = document.cookie.length;
            }
            return decodeURIComponent(document.cookie.substring(begin, end));
        }
    }
    return null;
}

/**
 * 绑定 VMessage client_id 到用户id
 * @var int client_id
 */
function bind_client_to_uid(client_id) {
    $.ajax({
        url: "/welcome/bind_uid",
        method: "post",
        dataType: 'json',
        data: {
            "client_id": client_id
        },
        success: function (data) {
            console.log(data);
        }
    });
}


/**
 * 标题闪烁
 */
var message = {   //title闪烁
    time: 0,
    title: document.title,
    timer: null,
    // 显示新消息提示
    show: function () {
        if (message.timer != null) {
            return;
        }
        var title = message.title.replace("【　　　】", "").replace("【新消息!!】", "");
        // 定时器，设置消息切换频率闪烁效果就此产生
        message.timer = setInterval(function () {
            message.time++;
            message.show();
            if (message.time % 2 == 0) {
                document.title = "【新消息!!】" + title
            }
            else {
                document.title = "【　　　】" + title
            }
        }, 600);

        //声音提醒
        var au = document.createElement("audio");
        au.preload="auto";
        au.src = "/tips.wav";
        au.play();
        //return [message.timer, message.title];
    },
    // 取消新消息提示
    clear: function () {
        clearInterval(message.timer);
        document.title = message.title;
    }
};


/**
 * 发送客户端心跳包
 */
function heart_beat(client_socket) {
    client_socket.send("1");
}