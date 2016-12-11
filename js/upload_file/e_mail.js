/*
 * jQuery File Upload Plugin JS Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */

$(function () {
    window.FILES=[];
    // Initialize the jQuery File Upload widget:
    $('#pic').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url:'/reporter/upload_pic',
        autoUpload: false,
        filesContainer: $('#pic_files'),
        getFilesFromResponse:function(res){
            if (res.result&&res.result.res==1){
                res=res.result.info;
                res.url=res.url.replace('pic/','temp/');
                return [{'url':res.url,'name':res.name,'isPic':true}];
            }
            return [];
        },
        acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
    }).on('fileuploaddone', function (e, data) {
        var res=data.result;
        if (res.res!=1){
            alert('error');
            return;
        }else{
            res.info.isPic=1;
            window.FILES.push(res.info);
        }
    });

    $('#video').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url:'/reporter/upload_video',
        autoUpload: false,
        filesContainer: $('#video_files'),
        getFilesFromResponse:function(res){
            if (res.result&&res.result.res==1){
                res=res.result.info;
                return [{'url':res.url,'name':res.name}];
            }
            return [];
        },
        acceptFileTypes: /(\.|\/)(rmvb|rm|avi|flv|mp4|mpeg)$/i
    }).on('fileuploaddone', function (e, data) {
        var res=data.result;
        if (res.res!=1){
            alert('error');
            return;
        }else{
            res.info.isPic=0;
            window.FILES.push(res.info);
        }
    });
    $('form').on('click','.cancle-file',function(){
        var url=$(this).data('url');
        for (var x in window.FILES){
            if (FILES[x].url==url){
                FILES[x]=FILES[0];
                FILES.shift();
                break;
            }
        }
        $(this).parent().parent().remove();
    });
});
