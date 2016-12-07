/**
 * Created by LX on 2016/12/7.
 */
function get_all_file(){
    $.ajax({
        url:'/comment/get_all_files',
        type:'POST',
        dataType:'json',
        success:function(data){
            console.log(data);
        }
    })
}