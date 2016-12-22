//请求数据
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
        url:server_url+'/yuqing/has_rep_yq',
        data:{
            query:arr,
            page_num:page_num
        },
        dataType:'json',
        success:fn
    });
}
//显示全部
function add_content_all(data){
    page_total = Math.ceil(data.num/page_length);
    if(data.info.length !== 0){
        if(page_num === 1){
            $('#show_all').html('');//清空盒子内容
        }
        $(".all_total").html('当前数据量：<span class="red">'+(data.num?data.num:0)+'</span>条');
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<table class="table table-responsive">';
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a href="/yuqing/rec_detail?'+obj._id.$id+'#record" title="'+obj.title+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            switch (obj.is_cfm){
                case 0 :
                    str += '<td><span class="label label-default">未查看</span></td>';break;
                case 1 :
                    str += '<td><span class="label label-success">已采纳</span></td>';break;
                case -1 :
                    str += '<td><span class="label label-primary">未采纳</span></td>';break;
            }
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';

            str += '<tr><td colspan="6"><span class="color_red">[摘要]</span>'+obj.summary+'</td></tr>';
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
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a href="/yuqing/rec_detail?yid='+obj._id.$id+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            switch (obj.is_cfm){
                case 0 :
                    str += '<td><span class="label label-default">未查看</span></td>';break;
                case 1 :
                    str += '<td><span class="label label-success">已采纳</span></td>';break;
                case -1 :
                    str += '<td><span class="label label-primary">未采纳</span></td>';break;
            }
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

//忽略此消息或者加入垃圾箱
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

$(function(){
    $('#from_where li a i').click(function(){
        var content = $(this).parent().attr('id');
        window.open(window.location.href+'?'+content);
    })
})