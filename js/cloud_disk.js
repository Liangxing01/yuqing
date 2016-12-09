//获取所有文件
function get_all_files(page){
    $.ajax({
        url:'/common/get_all_files',
        type:'POST',
        data:{
            search:'',
            start:page,
            length:5
        },
        dataType:'json',
        success:function(data){
            if(data){
                $('.My_pan_list tbody').html('');
                var str = '';
                $('.my_pan_page .total').html(Math.ceil(data.num/5));
                $('.My_pan_title .all').html(data.num);
                if(Math.ceil(data.num/5)<2){
                    $('.my_pan_page').hide();
                }
                for(var i=0;i<data.files.length;i++){
                    str += '<tr><td>';
                    str += '<input name="" type="checkbox" class="file-checkbox" value="" data-id="'+data.files[i].id+'">';
                    if(data.files[i].file_name.match(/(\.|\/)(doc|docx)$/ig)){
                        str += '<span class="file-file file-word">w</span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(xlxs)$/ig)){
                        str += '<span class="file-file file-ex">e<i>x</i></span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(ppt|pptx)$/ig)){
                        str += '<span class="file-file file-p">p</span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(pdf)$/ig)){
                        str += '<span class="file-file file-pdf">p<i>DF</i></span>';
                    }else{
                        str += '<span class="file-file file-other">?</span>';
                    }
                    str += data.files[i].file_name+'</td>';
                    if(data.files[i].size>1024){
                        var m = data.files[i].size%1024;
                        var d = Math.floor(data.files[i].size/1024);
                        str += '<td>'+m+'.'+d+'kb</td>';
                    }else{
                        str += '<td>'+data.files[i].size+'kb</td>';
                    }
                    str += '<td>'+timeToDate(data.files[i].upload_time*1000)+'</td>';
                    str += '<td><a href="/common/file_download?fid='+data.files[i].id+'" class="btn btn-info btn-sm">下载</a></td>';
                    str += '</tr>';
                }
                $('.My_pan_list tbody').append(str);

            }
        }
    })
}

//上传
function upload_btn(){
    var file = $('input[type="file"]')[0].files[0];
    var form1 = new FormData();
    form1.append('file',file);
    if(file){
        $.ajax({
            url:'/common/upload_file',
            type:"POST",
            data:form1,
            dataType:'json',
            processData: false,  // 告诉jQuery不要去处理发送的数据
            contentType: false,
            success:function(data){
                if(data.res){
                    layer.msg(data.msg);
                    window.location.reload();
                }else{
                    layer.msg(data.msg,{anim:6});
                }
            }
        })
    }
}

//转换时间格式
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

//搜索文件
function search_files(){
    var txt = $('.file-search-text').val();
    $.ajax({
        url:'/common/get_all_files',
        type:'POST',
        data:{
            search:txt,
            start:0,
            length:5
        },
        dataType:'json',
        success:function(data){
            if(data){
                $('.My_pan_list tbody').html('');
                var str = '';
                $('.my_pan_page .total').html(Math.ceil(data.num/5));
                $('.My_pan_title .all').html(data.num);
                if(Math.ceil(data.num/5)<2){
                    $('.my_pan_page').hide();
                }
                for(var i=0;i<data.files.length;i++){
                    str += '<tr><td>';
                    str += '<input name="" type="checkbox" class="file-checkbox" value="" data-id="'+data.files[i].id+'">';
                    if(data.files[i].file_name.match(/(\.|\/)(doc|docx)$/ig)){
                        str += '<span class="file-file file-word">w</span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(xlxs)$/ig)){
                        str += '<span class="file-file file-ex">e<i>x</i></span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(ppt|pptx)$/ig)){
                        str += '<span class="file-file file-p">p</span>';
                    }else if(data.files[i].file_name.match(/(\.|\/)(pdf)$/ig)){
                        str += '<span class="file-file file-pdf">p<i>DF</i></span>';
                    }else{
                        str += '<span class="file-file file-other">?</span>';
                    }
                    str += data.files[i].file_name+'</td>';
                    if(data.files[i].size>1024){
                        var m = data.files[i].size%1024;
                        var d = Math.floor(data.files[i].size/1024);
                        str += '<td>'+m+'.'+d+'kb</td>';
                    }else{
                        str += '<td>'+data.files[i].size+'kb</td>';
                    }
                    str += '<td>'+timeToDate(data.files[i].upload_time*1000)+'</td>';
                    str += '<td><a href="/common/file_download?fid='+data.files[i].id+'" class="btn btn-info btn-sm">下载</a></td>';
                    str += '</tr>';
                }
                $('.My_pan_list tbody').append(str);

            }
        }
    })
}
//跳转
function goto_page(){
    var num = $('.special input').val()*1;
    if(typeof num != 'number'){
        layer.msg('请输入数字');
        return false;
    }
    if(num>$('.special .total').html()){
        layer.msg("你输入的页码有误，请重新输入");
        return false;
    }
    get_all_files((num-1)*5);
}
$(function(){
    get_all_files(0);   //默认第一页数据

    //上传按钮
    $('.upload').click(function(){
        var str = '<div class="row">';
        str += '<form name="files">';
        str += '<div class="upload_files">';
        str += '<input type="file" name="file">'
        str += '<a href="javascript:;" class="btn btn-md btn-info" id="upload_file" onclick="upload_btn()">上传</a>';
        str += '</div></form></div>';
        layer.open({
            title:'上传页面',
            content:str,
            area:['600px','400px'],
        })
    })

    //首页
    $('.my_pan_page .index').click(function(){
        get_all_files(0);
        $('.special input').val(1);
    })

    //尾页
    $('.my_pan_page .last').click(function(){
        get_all_files(($('.special .total').html()-1)*5);
        $('.special input').val($('.special .total').html());
    })

    //上一页
    $('.my_pan_page .prev').click(function(){
        var val = $('.special input').val();
        if(val<2){
            layer.msg('当前为首页');
        }else{
            get_all_files((val-2)*5);
            $('.special input').val(val-1);
        }
    })

    //下一页
    $('.my_pan_page .next').click(function(){
        var val = $('.special input').val();
        var total = $('.special .total').html();
        if(val*1+1>total){
            layer.msg('暂无更多数据');
        }else{
            get_all_files((val*1)*5);
            $('.special input').val(val*1+1);
        }
    })


    //全选
    $('.all_choose').change(function(){
        var isCheck = this.checked;
        $('tbody .file-checkbox').each(function(){
            this.checked = isCheck;
        })
    })

    //删除
    $('.del').click(function(){
        if($('tbody .file-checkbox:checked').length){
            var arr = [];
            $('tbody .file-checkbox:checked').each(function(){
                arr.push($(this).attr('data-id'));
            })
            var str = arr.join(',');
           var isClose = layer.confirm('执行此操作不可复原',{icon: 3, title:'提示'},function(){
               delete_files(str);
               layer.close(isClose);
            });
        }else{
            layer.msg("请至少勾选一项");
        }

    })
})

//删除
function delete_files(str){
    $.ajax({
        url:'/common/del_file',
        type:'POST',
        data:{
            del_id:str
        },
        dataType:'json',
        success:function(data){
            if(data.res*1){
                layer.msg('删除成功');
                window.location.reload();
            }else{
                layer.msg('删除失败');
            }
        }
    })
}
