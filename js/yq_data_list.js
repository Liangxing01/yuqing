/**
 * Created by LX on 2016/12/17.
 */
function sroll_ajax(){
    var str = '';
    for(var i=0; i<5;i++){
        str += '<table class="table">';
        str += '<tr>';
        str += '<td colspan="2">';
        str += '<input type="checkbox">';
        str += '<span>gsdgdsdsgdsgdsgdsgdsggdsgsd</span>';
        str += '</td>';
        str += '<td>【sdgdsgdsgdshgfdh】</td>';
        str += '<td>2015-2-12 10:01:12</td>';
        str += '<td></td>';
        str += '<tr><td colspan="5">sagfsdagdsgjdsgjsd</td></tr>';
        str += '<tr>';
        str += '<td>要素<span class="label label-info">巴南</span><span class="label label-primary">东温泉</span></td>';
        str += '<td>要素<span class="label label-info">巴南</span><span class="label label-primary">东温泉</span></td>';
        str += '<td></td><td></td><td></td></tr>';
        str += '</table>';
    }
    $('#show_all').append(str);
}
sroll_ajax();
var i = 0;
$(document).scroll(function(){
    if($(document).scrollTop()+$(window).height()>$('#main-content').height()){
        i++;
        if(i<5){
            alert(1);
            sroll_ajax();
        }

    }
   console.log($(document).scrollTop()+":"+$('#main-content').height()+':'+$(window).height()+":"+$('footer').height());
});