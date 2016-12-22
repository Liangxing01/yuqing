/**
 * Created by LX on 2016/12/21.
 */

var page_num = 1;   //页码
var page_total = 0 ; //总页码
var page_length = 10;   //每页显示多少条
var arr_all = ['全国','全部','显示全部','DESC','']; //默认初始查询
//显示全部
function sroll_ajax(type){
    if(type == 'all'){
        var  fn = add_content_all;
    }
    if(type == 'title'){
        var fn = add_content_title;
    }
    var layer2 = layer.load(2);
    var arr = {"sort":arr_all[3],"length":page_length,"search":arr_all[4]};
    $.ajax({
        type:'POST',
        url:server_url+'/yuqing/trash_data',
        data:{
            query:arr,
            page_num:page_num
        },
        dataType:'json',
        success:fn
    });
}


$(document).scroll(function(){
    if($(document).scrollTop()+$(window).height()>$('#main-content').height()){
        if(page_num<page_total){
            page_num++;
            if(arr_all[2] == '显示全部'){
                sroll_ajax('all');
            }
            if(arr_all[2] == '只看标题'){
                sroll_ajax('title');
            }
        }
    }
    // console.log($(document).scrollTop()+":"+$('#main-content').height()+':'+$(window).height()+":"+$('footer').height());
});
function add_content_all(data){
    page_total = Math.ceil(data.num/page_length);
    if(data.info.length !== 0){
        if(page_num === 1){
            $('#show_all').html('');//清空盒子内容
        }
        $('.total_num').html(data.num?data.num : 0);
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<table class="table table-responsive">';
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a href="/yuqing/yq_detail?'+obj._id.$id+'#record" title="'+obj.title+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
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
            str += '<span><a href="/yuqing/yq_detail?yid='+obj._id.$id+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+(obj.source?obj.source:'')+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';
            str += '</tr>'
        }
        $('#show_all table').append(str);
        layer.closeAll()
    }else{
        $('#show_all').html("<p style='text-align:center;line-height: 36px'>暂无该数据</p>");
        layer.closeAll()
    }
}

function load_who(){
    if(arr_all[2] == '显示全部'){
        sroll_ajax('all');
    }else{
        sroll_ajax('title');
    }
}


$(function(){
    load_who();


    $('#main-content').delegate('ul li a','click',function(){
        var parent = $(this).parent().parent().attr('id');
        var child = $(this).text();
        switch(parent){
            case 'from_where':
                arr_all[0] = child;break;
            case 'what_type':
                arr_all[1] = child; break;
            case 'how_show':
                arr_all[2] = child ;break;
        }
        page_num = 1;
        if(arr_all[2] == '显示全部'){
            sroll_ajax('all');
        }
        if(arr_all[2] == '只看标题'){
            sroll_ajax('title');
        }
    });
    //搜索按钮
    $('.btn-search').click(function(){
        arr_all[4] =  $('#search_input').val();
        load_who();//加载页面
    })
    //全选按钮
    $('input.all_choose').change(function(){
        var checked = this.checked;
        $("#show_all input[type='checkbox']").each(function(){
            this.checked = checked;
        })
    })

    //排序
    $('#sort').click(function(){
        if($(this).hasClass('active')){
            $(this).removeClass('active');
            arr_all[3] = 'ASC';
            $(this).find('.sort').removeClass("fa-sort-amount-desc").addClass('fa-sort-amount-asc');
        }else{
            $(this).addClass('active');
            arr_all[3] = 'DESC';
            $(this).find('.sort').removeClass("fa-sort-amount-asc").addClass('fa-sort-amount-desc');
        }
        load_who();
    })
    //取消标记垃圾信息
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
});

function ignore_this_yq(that,type){
    var fid = $(that).data('id');
    $.ajax({
        type:'POST',
        url:server_url+"/yuqing/unset_ignore",
        data:{
            yids:fid,
            type:type
        },
        dataType:'json',
        success:function(data){
            layer.msg(data.msg);
            $('#model_garbage').modal('hide');
            load_who();
        }
    })
}