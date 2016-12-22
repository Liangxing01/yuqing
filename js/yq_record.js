/**
 * Created by LX on 2016/12/20.
 */
/**
 * Created by LX on 2016/12/17.
 */
var page_num = 1;   //页码
var page_total = 0 ; //总页码
var page_length = 10;   //每页显示多少条
var arr_all = ['全区','全部','显示全部','DESC','']; //默认初始查询
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
        url:server_url+'/yuqing/has_rep_yq',
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
        }else{
            $(this).addClass('active');
            arr_all[3] = 'DESC';
        }
        load_who();
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