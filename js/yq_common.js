/**
 * Created by LX on 2016/12/22.
 */
$('.search-input').keypress(function(event){
    if(event.keyCode == 13){
        if($(this).val() == ''){
            layer.msg("搜索词为空，请重新输入");
            return;
        }
        arr_all[4] = $(this).val();
        load_who();
    }
});