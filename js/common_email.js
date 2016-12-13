//获取未读邮件
function get_unread_email(){
    $.ajax({
        url:'/common/get_unread_num',
        type:"get",
        dataType:'json',
        success:function(data){
            if(data.unread_num){
                $('.rec_num').html(data.unread_num);
            }
        }
    })
}

//获取附件
function get_attachment(){
    var num = window.location.href.indexOf('?id=');
    var email_id = window.location.href.substr(num+4);   //附件id
    var att_id = '<{$attID}>';
    $.ajax({
        url:'/common/get_att_info',
        type:'POST',
        data:{
            att_ids:att_id,
            eid:email_id
        },
        dataType:'json',
        success:function(data){
            if(data[0] != null){
                $('.attachment_list').html('');//清空原有附件
                var len = data.length;
                var attr_list = '';
                for(var i =0 ;i<len;i++){
                    attr_list += '<li>';
                    attr_list += '<a href="javascript:return false;" class="atch-thumb">';
                    attr_list += get_file_type(data[i].file_name,data[i].loc);
                    attr_list += '</a>';
                    attr_list += '<div class="file_img">';
                    if(data[i].is_exist == 1){
                        attr_list += '<a href="/common/att_download?fid='+data[i].id+'&eid='+email_id+'" class="btn btn-md btn-info attr_down">下载</a>';
                    }else{
                        attr_list += '<span class="attr_down" style="color:#fff;font-size: 18px">已过期</span>';
                    }
                    attr_list += '</div>';
                    attr_list += '<div class="file-name">'+data[i].file_name+'</div>';
                    attr_list += '</li>';
                }
                $('.attachment_list').append(attr_list);
            }
        }
    })

}

//获取文件类型
function get_file_type(name,loc){
    if(name.match(/(\.|\/)(doc|docx)$/ig)){ //文档类型
        return '<img src="/img/email_word.png">';
    }
    if(name.match(/(\.|\/)(xlsx)$/ig)){ //表格类型
        return '<img src="/img/email_excel.png">';
    }
    if(name.match(/(\.|\/)(ppt|pptx)$/ig)){ //ppt类型
        return '<img src="/img/email_ppt.png">';
    }
    if(name.match(/(\.|\/)(pdf)$/ig)){ //文档类型
        return '<img src="/img/email_pdf.png">';
    }
    if(name.match(/(\.|\/)(jpg|png|jpeg)$/ig)){
        return '<img src='+loc+' width="130px">';
    }
    return '<img src="/img/">';
}

//获取阅读状态
function get_has_read(){
    var num = window.location.href.indexOf('?id=');
    var id = window.location.href.substr(num+4);
    $.ajax({
        url:"/common/get_has_read",
        type:"GET",
        data:{
            id  :id
        },
        dataType:'json',
        success:function(data){
            if(data.user_read_state.length){
                var str = '';
                for(var i= 0;i<data.user_read_state.length;i++){
                    if(data.user_read_state[i].state == 1){
                        str += data.user_read_state[i].name + '<span class="fa fa-check"></span>';
                    }else{
                        str +=  data.user_read_state[i].name +'<span class="fa fa-times"></span>';
                    }
                }
                $('.read_type').append(str);
            }
        }
    })
}