function countUp(count,$display) {

'use strict';

    var div_by = 100,
        speed = Math.round(count / div_by),
        run_count = 1,
        int_speed = 24;
    var int = setInterval(function () {
        if (run_count < div_by && (count < 50 || count > 100)) {
            $display.text(speed * run_count);
            run_count++;
        } else if (parseInt($display.text()) < count) {
            var curr_count = parseInt($display.text()) + 1;
            $display.text(curr_count);
        } else {
            clearInterval(int);
        }
    }, int_speed);
}
$.getJSON('/welcome/get_all_tasks_num','',function(data){
    if (typeof data.zp!='undefined'){
        countUp(data.zp.unread_info_num,$('.js-zhipai .count'));
        countUp(data.zp.designate_num,$('.js-zhipai .count2'));
        countUp(data.zp.un_confirm_num,$('.js-zhipai .count3'));
        countUp(data.zp.done_num,$('.js-zhipai .count4'));
    }else $('.js-zhipai').hide();
    if (typeof data.handler!='undefined'){
        countUp(data.handler.unread_num,$('.js-chuli .count'));
        countUp(data.handler.doing_num,$('.js-chuli .count2'));
        countUp(data.handler.done_num,$('.js-chuli .count3'));
        countUp(data.handler.all_num,$('.js-chuli .count4'));
    }else $('.js-chuli').hide();
});