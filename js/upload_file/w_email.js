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
        url:'/common/email_att_upload',
        autoUpload: false,
        filesContainer: $('#pic_files'),
        getFilesFromResponse:function(res){
            if (res.result&&res.result.res==1){
                res=res.result;
                return [{'id':res.fid,'name':res.file_name}];
            }
            return [];
        },
        acceptFileTypes: /(\.|\/)(doc|docx|ppt|pdf|pptx|xlsx|word|jpg|jpeg|png|zip|rar)$/i,
        maxFileSize:50000000
    }).on('fileuploaddone', function (e, data) {
        var res=data.result;
        console.log(res);
        if (res.res!=1){
            alert('error');
            return;
        }else{
            /*res.info.isPic=1;
            window.FILES.push(res.info);*/
        }
    });
    
    $('form').on('click','.cancle-file',function(){
        var id =$(this).data('id');
        $.ajax({
            url:'del_att',
            data:{
                fid:id,
            },
        })
        $(this).parent().parent().remove();
    });
});
