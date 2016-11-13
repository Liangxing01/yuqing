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
    $('#file').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url:'/designate/attachment_upload',
        autoUpload: false,
        filesContainer: $('#files'),
        // downloadTemplateId: null,
        // downloadTemplate: function (o) {
        //     alert(1);
        // },
        acceptFileTypes: /(\.|\/)(xls|xlsx|doc|docx|txt)$/i
    }).on('fileuploaddone', function (e, data) {
        var res=data.result;
        if (res.res!=1){
            alert('error');
            return;
        }else{
            window.FILES.push(res.info);
        }
    });
});
