/**
 * Created by LX on 2016/12/22.
 */

var page_num = 1;   //页码
var page_total = 0 ; //总页码
var page_length = 10;   //每页显示多少条
var arr_all = ['全区','全部','显示全部','DESC','']; //默认初始查询

$(function(){
    load_who();//默认加载数据

    //滚动请求
    $(document).scroll(function () {
        if ($(document).scrollTop() + $(window).height() > $('#main-content').height()) {
            if (page_num < page_total) {
                page_num++;
                if (arr_all[2] == '显示全部') {
                    sroll_ajax('all');
                }
                if (arr_all[2] == '只看标题') {
                    sroll_ajax('title');
                }
            }
        }
    });

    //搜索按钮
    $('.btn-search').click(function(){
        page_num = 1;
        arr_all[4] =  $('#search_input').val();
        load_who();//加载页面
    });

    //排序图标
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

    //全选按钮
    $('input.all_choose').change(function(){
        var checked = this.checked;
        $("#show_all input[type='checkbox']").each(function(){
            this.checked = checked;
        })
    });

    //搜索按钮回车事件
    $('.search-input').keypress(function(event){
        if(event.keyCode == 13){
            if($(this).val() == ''){
                layer.msg("搜索词为空，请重新输入");
                return;
            }
            page_num = 1;
            arr_all[4] = $(this).val();
            load_who();
        }
    });

    //代理选项卡的点击事件
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
        arr_all[4] = $('.search-input').val();
        if(arr_all[2] == '显示全部'){
            sroll_ajax('all');
        }
        if(arr_all[2] == '只看标题'){
            sroll_ajax('title');
        }
    });
});
