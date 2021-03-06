//显示全部
function sroll_ajax(type){
    if(type == 'all'){
        var  fn = add_content_all;
    }
    if(type == 'title'){
        var fn = add_content_title;
    }
    var layer2 = layer.load(2);
    var arr = {"sort":arr_all[3],"length":page_length,"search":arr_all[4],"media_type":arr_all[1],"tag":arr_all[0]};
    $.ajax({
        type:'POST',
        url:server_url+'/yuqing/get_cfm_data',
        data:{
            query:arr,
            page_num:page_num
        },
        dataType:'json',
        success:fn,
        error:function(){
            layer.msg('服务器无法连接');
            layer.close(layer2);
        }
    });
}



function add_content_all(data){
    page_total = Math.ceil(data.num/page_length);
    if(data.info.length !== 0){
        if(page_num === 1){
            $('#show_all').html('');//清空盒子内容
        }
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条');
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<table class="table table-responsive">';
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a target="_blank" href="/yuqing/filter_rec_detail?'+obj._id.$id+'#record" title="'+obj.title+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';

            str += '<tr><td colspan="5"><span class="color_red">[摘要]</span>'+obj.summary+'</td></tr>';
            str += '<tr><td colspan="5">关键字：';
            if(obj.keyword !== undefined){
                var k=0,k_len = obj.keyword.length; //遍历文章关键字
                for(;k<k_len;++k){
                    str += '<span class="crux">'+obj.keyword[k]+'</span>';
                }
            }
            str += '</td></tr></table>';

        }
        $('#show_all').append(str);
        layer.closeAll()
    }else{
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条');
        $('#show_all').html("<p style='text-align:center;line-height: 36px'>暂无该数据</p>");
        layer.closeAll()
    }
}

//只看标题
function add_content_title(data){
    page_total = Math.ceil(data.num/page_length);
    if(data.info.length !== 0){
        if(page_num === 1){
            $('#show_all').html('<table class="table table-responsive"></table>');//清空盒子内容
        }
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条');
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a target="_blank" href="/yuqing/filter_rec_detail?'+obj._id.$id+'#record">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';
            str += '</tr>'
        }
        $('#show_all table').append(str);
        layer.closeAll()
    }else{
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条');
        $('#show_all').html("<p style='text-align:center;line-height: 36px'>暂无该数据</p>");
        layer.closeAll()
    }
}

function load_who(){
    var arr = window.location.search.slice(1).split('?');
    var len = arr.length;
    var where_from = arr[len-1];
    if(where_from !== ''){
        arr_all[0] = $('#'+where_from).text();
        $('#'+where_from).parent().addClass('active').siblings('.active').removeClass('active');
    }
    if(arr_all[2] == '显示全部'){
        sroll_ajax('all');
    }else{
        sroll_ajax('title');
    }
}


$(function(){
    //加入垃圾信息
    $('#add_garbage').click(function(){
        var len = $('#show_all input[type="checkbox"]:checked').length;
        if(len>10){
            layer.msg("所选数据不能超过10条",{anim:6});
            return false;
        }
        if(len == 0){
            layer.msg("所选信息为空",{anim:6});
            return false;
        }
        var list = '';
        var arr = []; //id数组
        $('#model_garbage ul').html('');
        $('#show_all input[type="checkbox"]:checked').each(function(i){
            var title = $(this).parent().find('a').html();
            arr.push($(this).data("id"));
            list += '<li class="list-group-item">'+(i+1)+'、'+title+'</li>';
        })
        $('#model_garbage ul').append(list);
        $('#garbage_sure').data('id',arr.join(','));
    })
    //忽略消息
    $('#add_ignore').click(function(){
        var len = $('#show_all input[type="checkbox"]:checked').length;
        if(len>10){
            layer.msg("所选数据不能超过10条",{anim:6});
            return false;
        }
        if(len == 0){
            layer.msg("所选信息为空",{anim:6});
            return false;
        }
        var list = '';
        var arr = []; //id数组
        $('#model_ignore ul').html('');
        $('#show_all input[type="checkbox"]:checked').each(function(i){
            var title = $(this).parent().find('a').html();
            arr.push($(this).data("id"));
            list += '<li class="list-group-item">'+(i+1)+'、'+title+'</li>';
        })
        $('#model_ignore ul').append(list);
        $('#ignore_sure').data('id',arr.join(','));
    })

    $('#from_where li a i').click(function(){
        var content = $(this).parent().attr('id');
        window.open(window.location.href+'?'+content);
    })
});

function ignore_this_yq(that,type){
    var fid = $(that).data('id');
    $.ajax({
        type:'POST',
        url:server_url+"/yuqing/ignore_this_yq",
        data:{
            yids:fid,
            type:type
        },
        dataType:'json',
        success:function(data){
            if(data.res == 1){
                layer.msg(data.msg);
                $('#model_garbage').modal('hide');
                $('#model_ignore').modal('hide');
                load_who();
            }
        }
    })
}