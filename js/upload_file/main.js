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
    // $('#upload_type').on('change',function(){
    //     var val=this.value;
    //     window.FILES=[];
    //     if (val==0){
    //         $('#fileupload').fileupload({
    //             url:'/reporter/upload_pic',
    //             acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
    //         });
    //     }else{
    //         $('#fileupload').fileupload({
    //             url:'/reporter/upload_video',
    //             acceptFileTypes: /(\.|\/)(rmvb|rm|avi|flv|mp4|mpeg)$/i
    //         });
    //     }
    // });
    // Initialize the jQuery File Upload widget:
    $('#pic').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url:'/reporter/upload_pic',
        autoUpload: false,
        filesContainer: $('#pic_files'),
        // downloadTemplateId: null,
        // downloadTemplate: function (o) {
        //     alert(1);
        // },
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
        // downloadTemplateId: null,
        // downloadTemplate: function (o) {
        //     alert(1);
        // },
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
});