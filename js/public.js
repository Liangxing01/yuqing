/**
 * Unix时间戳转Y-m-d H:i:s
 * @param time
 * @return string
 */
function timeToDate(time) {
    var now = new Date(time);
    var yy = now.getFullYear();      //年
    var mm = now.getMonth() + 1;     //月
    var dd = now.getDate();          //日
    var hh = now.getHours();         //时
    var ii = now.getMinutes();       //分
    var ss = now.getSeconds();       //秒
    var clock = yy + "-";
    if (mm < 10) clock += "0";
    clock += mm + "-";
    if (dd < 10) clock += "0";
    clock += dd + " ";
    if (hh < 10) clock += "0";
    clock += hh + ":";
    if (ii < 10) clock += '0';
    clock += ii + ":";
    if (ss < 10) clock += '0';
    clock += ss;

    return clock;
}