/**
 * Created by LX on 2016/12/17.
 */
var page_num = 1;   //页码
var page_total = 0 ; //总页码
var page_length = 10;   //每页显示多少条
//显示全部
function sroll_ajax(){
    var layer2 = layer.load(2);
    var arr = {"sort":"ASC","length":page_length,"search":"","media_type":"全部"};
    $.ajax({
        type:'POST',
        url:'http://192.168.0.135:81/yuqing/get_yqData_by_page',
        data:{
            query:arr,
            page_num:page_num
        },
        dataType:'json',
        success:add_content_all
    });
}
sroll_ajax();

$(document).scroll(function(){
    if($(document).scrollTop()+$(window).height()>$('#main-content').height()){
        if(page_num<page_total){
            page_num++;
            sroll_ajax();
        }else{
            layer.msg("已经没有数据了");
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
        var str = '',i=0,len=data.info.length;
        for(; i<len;i++){
            var obj = data.info[i];
            str += '<table class="table table-responsive">';
            str += '<tr class="tr_title">';
            str += '<td colspan="2">';
            str += '<input type="checkbox" data-id="'+obj._id.$id+'">';
            str += '<span><a href="/yuqing/yq_detail?yid='+obj._id.$id+'">'+obj.title+'</a></span>';
            str += '</td>';
            str += '<td>【'+obj.source+'】</td>';
            str += '<td><span class="label label-danger">'+obj.yq_tag+'</span></td>';
            str += '<td>'+timeToDate(obj.yq_pubdate*1000)+'</td>';

            str += '<tr><td colspan="5"><span class="color_red">[摘要]</span>'+obj.summary+'</td></tr>';
            /*str += '<tr><td colspan="2">要素';
            var j=0,j_len = obj.nrtags.length;  //循环遍历文章要素
            for(;j<j_len;++j){
                str += '<span class="crux">'+obj.nrtags[j]+'</span>';
            }
            str += '</td><td colspan="2">关联级别:';
            for(var m = 0;m<obj.yq_relevance*1;++m){
                str += '<i class="fa fa-star"></i>';
            }
            str += '</td><td></td></tr>';*/
            str += '<tr><td colspan="5">关键字：';
            var k=0,k_len = obj.keyword.length; //遍历文章关键字
            for(;k<k_len;++k){
                str += '<span class="crux">'+obj.keyword[k]+'</span>';
            }
            str += '</td></tr></table>';

        }
        $('#show_all').append(str);
        layer.closeAll()
    }
}

//只看标题
function add_content_title(){
    var str = '<table class="table">';
    //循环
    str += '<tr class="tr_title">';
    str += '<td colspan="2"><input type="checkbox">';
    str += '<span>重启啊是个撒了感觉萨嘎杀</span></td>';
    str += '<td>【safsafsa】</td>';
    str += '<td>2014-20-12 20：10：01</td>';
    str += '<td></td></tr>';
    //结束循环
    str += '</table>';
    $("#only_title table").append(str);
}

function find_from_where(){
    var from = $('.from_where li.active a').attr('href');
    var type = $('.what_type li.active a').attr('href');
    var how = $('.how_show li.active a').attr('href');
    console.log(from + ':'+ type + ':' + how);
}
var arr = ['全国','全部','显示全部','按时间'];
$('#main-content').delegate('ul li a','click',function(){
    var parent = $(this).parent().parent().attr('id');
    var child = $(this).text();
    switch(parent){
        case 'from_where':
            arr[0] = child;break;
        case 'what_type':
            arr[1] = child; break;
        case 'how_show':
            arr[2] = child ;break;
    }
    console.log(arr);
});


$(function(){
    //全选按钮
    $('input.all_choose').change(function(){
        var checked = this.checked;
        $("#show_all input[type='checkbox']").each(function(){
            this.checked = checked;
        })
    })

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
    
});

function ignore_this_yq(that,type){
    var fid = $(that).data('id');
    $.ajax({
        type:'POST',
        url:"http://192.168.0.135:81/yuqing/ignore_this_yq",
        data:{
            yids:fid,
            type:type
        },
        dataType:'json',
        success:function(data){
            if(data.res == 1){
                layer.msg(data.msg);
            }
        }
    })
}