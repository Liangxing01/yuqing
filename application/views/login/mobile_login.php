<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>舆情手机登录</title>
    <style>
        *{ margin: 0; padding: 0;}
        body{  font-family:Verdana,Arial,"Microsoft Yahei","宋体"; text-align:center; font-size:12px;}
        a{color: #333; text-decoration: none;}
        a:hover {color: #fd0202;}
        ul,li{ list-style:none;}
        li,input,img,textarea,select{ vertical-align:middle;}
        h1,h2,h3,h4,h5,h6 { font-size:100%; font-weight:bolder;}

        .fl{ float:left;}
        .fr{ float:right;}
        .clears{ clear:both; line-height:0px; overflow:hidden; font-size:0px; height:0px;}
        .section{width: 1520px; margin: auto;}
        .mr5{ margin-right:5px;}
        .ml100{ margin-left:105px;}
        .admin_login{
            width: 100%;
            height: 100%;
            margin: auto;
            position: absolute;
            z-index: -1;
        }
        .admin_login img{
            width: 100%;
            height: 100%;
        }

        .form_login{
            width: 80%;
            margin:auto;
            text-align:center;
        }
        .form_logo{
            padding-top: 20%;
        }
        .form-title{
            margin:0 auto;
            color: white;
            line-height: 2.4em;
            font-size: 1.8rem;
            margin-bottom: 10%;
        }
        .form-group{
            width: 90%;
            margin: auto;
        }
        .form-group .form-control{
            display:block;
            width:100%;
            line-height: 40px;
            float:left;
            border-bottom: 1px solid #FFFFFF;
            border-top: none;
            border-left:none;
            border-right: none;
            background-color: none;
            padding:0;
            margin:15px auto;
            text-indent:3em;
            color: white;
        }
        #username{
            background:url(/img/mobile/admin.png) no-repeat 10px center;
            font-size: 1.4em;
        }
        #password{
            background:url(/img/mobile/password.png)  no-repeat 10px center;
            font-size: 1.4em;
        }



        .form-group .btn{
            width:100%;
            line-height:40px;
            background-color:#00a688;
            border-radius: 4px;
            border:0px;
            color:#fff;
            font-size:1.4em;
            cursor: pointer;
            margin-top: 20px;
        }
		label{ 
			color: #ffe300;
			position: relative;
			top: -3.0em;
			font-size: 1.4em;
		}
		#login_error{
			color: #ffe300;
			font-size: 1.4em;
		}

    </style>
</head>
<body>
<div class="admin_login">
    <img src="/img/mobile/logobg.png" />
</div>
<div class="form_login" id="box">
    <div class="form_logo"><img src="/img/mobile/logo.png" width="80"></div>

    <form  role="form" id="form_login">
        <div class="form-title" >
            <h2>网络舆情工作平台</h2>
        </div>
        <div class="form-group">
            <input type="text" class="form-control" name="username" id="username" placeholder="账号" autocomplete="off">
            <label class="error" for="username" style=" height: 15px;"></label>
        </div>

        <div class="form-group">
            <input type="password" class="form-control" name="password" id="password" placeholder="密码" autocomplete="off">
            <label class="error" for="password" style=" height: 15px;"></label>
        </div>

        <div class="form-group"><span id="login_error" class="error" style="display: block;"></span></div>
        <div class="form-group">
            <button type="submit" class="btn" >登录</button>
        </div>

    </form>

</div>


<!-- BEGIN JS -->
<script src="/js/jquery-2.1.1.min.js"></script><!-- BASIC JQUERY LIB. JS -->
<script src="/js/bootstrap.min.js"></script><!-- BOOTSTRAP JS -->
<script src="/js/jquery.validate.min.js"></script>
<script src="/js/public.js"></script>
<!-- END JS -->
<script>
    // jq validate 插件初始化
    $.validator.setDefaults({
        submitHandler: function () {
            login_commit();
        }
    });
    $().ready(function () {
        $("#form_login").validate({
            rules: {
                username: {required: true},
                password: {required: true}
            },
            messages: {
                username: {required: "请输入用户名"},
                password: {required: "请输入密码"}
            }
        });
    });

    //登陆表单提交
    function login_commit() {
        var username = $("#username").val();
        var password = $("#password").val();

        $.ajax({
            url: "/login/check/",
            method: "post",
            data: {
                "username": username,
                "password": password
            },
            success: function (data) {
                if (data.code == "0") {
                    forward("/welcome/index");
                } else {
                    $("#login_error").html("用户名或密码错误").show();
                }
            }
        });
    }
</script>
</body>
</html>
