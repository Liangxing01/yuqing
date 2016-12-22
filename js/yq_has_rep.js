var timer = null ; //定时刷新
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
        url:server_url+'/yuqing/get_rep_data',
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
            str += '<span><a href="/yuqing/filter_detail?'+obj._id.$id+','+obj.rep_id.$id+'#reporter" title="'+obj.title+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>首报人:<span class="label label-primary">'+obj.first_name+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';

            str += '<tr><td colspan="6"><span class="color_red">[摘要]</span>'+obj.summary+'</td></tr>';
            str += '<tr><td colspan="6">舆情等级：'+obj.tag+'</td></tr>';
            str += '<tr><td colspan="6">关键字：';
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
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条')
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
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a href="/yuqing/filter_detail?yid='+obj._id.$id+'#reporter">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>首报人:<span class="label label-primary">'+obj.first_name+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';
            str += '</tr>'
        }
        $('#show_all table').append(str);
        layer.closeAll()
    }else{
        $(".all_total").html('总数据量：<span class="red">'+(data.num?data.num:0)+'</span>条')
        $('#show_all').html("<p style='text-align:center;line-height: 36px'>暂无该数据</p>");
        layer.closeAll()
    }
}

function load_who(){
    var where_from = window.location.hash.slice(1);
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
    //地区切换箭头点击事件
    $('#from_where li a i').click(function(){
        var content = $(this).parent().attr('id');
        window.open(window.location.href+'#'+content);
    })

    //定时刷新按钮
    $('.time_to_updata').click(function(){
        if($(this).hasClass('label-success')){
            $(this).removeClass('label-success').addClass('label-danger');
            $(this).text('关闭定时刷新');
            timer = setInterval(function(){
                var key = $('.search-input').val();
                arr_all[4] = key;
                load_who();
                $('.search-input').val(key);
            },60000);
        }else{
            $(this).removeClass('label-danger').addClass('label-success');
            $(this).text('开启定时刷新');
            clearInterval(timer);
            timer = null ;
        }
    })
});
