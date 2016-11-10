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


/**
 * 获取 Unix 时间戳
 * @param dateStr
 * @return string
 */
function dateToTime(dateStr) {
    if(dateStr == ""){
        return "";
    }
    dateStr = dateStr.toString();
    var newstr = dateStr.replace(/[年,月,日]/g, '/');
    var date = new Date(newstr);
    var time_str = date.getTime().toString();
    return time_str.substr(0, 10);
}


/**
 * 刷新当前页面
 */
function refresh() {
    window.location.reload();
}


/**
 * 跳转到url
 * @param url
 */
function forward(url) {
    window.location.href = url;
}