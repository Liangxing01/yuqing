<!DOCTYPE html>
<html lang="en">
<head>
    <!-- BEGIN META -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Custom Theme">
    <!-- END META -->

    <!-- BEGIN SHORTCUT ICON -->
    <link rel="shortcut icon" href="img/favicon.ico">
    <!-- END SHORTCUT ICON -->
    <title>
        登录
    </title>
    <!-- BEGIN STYLESHEET-->
    <link href="/s_src/bootstrap.min.css" rel="stylesheet"><!-- BOOTSTRAP CSS -->
    <link href="/s_src/bootstrap-reset.css" rel="stylesheet"><!-- BOOTSTRAP CSS -->
    <link href="/s_src/font-awesome.css" rel="stylesheet"><!-- FONT AWESOME ICON CSS -->
    <link href="/s_src/style.css" rel="stylesheet"><!-- THEME BASIC CSS -->
    <link href="/s_src/style-responsive.css" rel="stylesheet"><!-- THEME RESPONSIVE CSS -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.js">
    </script>
    <script src="/js/respond.min.js">
    </script>
    <![endif]-->
    <style>
        .error {
            font-size: 0.9em;
            color: #FF4500;
        }

        #login_error {
            display: none;
        }

        .login_bg {
            width: 100%;
            height: 100%;
            /*position: absolute;
            z-index: -1;*/
            background: url(../../../img/login_bg.png) no-repeat;
            background-size: cover;
            margin-left:auto;
            margin-right:auto;
            position: fixed;
            left: 0;
            top: 0
        }

        #box {
            width: 309px;
            height: 328px;
            background: #000;
            filter: alpha(opacity=60);
            opacity: 0.6;
            position: absolute;
            right: 15%;
        }

        .meih {
            width: 250px;
            color: #ffffff;
            margin: auto;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .meih input {
            border-radius: 4px;
            box-shadow: none;
            width: 200px;
            background: white;
            color: #000;
            border: none;
            line-height: 30px;

        }
		#username, #password{text-indent: 20px !important;}
        .meih a {
            color: white;
            text-align: left !important;
        }

        .an {
            width: 250px !important;
            background: #5cc8f1 !important;
            border: none;
            border-radius: 8px !important;
            line-height: 38px !important;
            color: white !important;
            font-size: 1.8em;
            position: absolute;
            bottom: 38px;
        }
        .wjmm {
            width: 250px;
            color: #ffffff;
            margin: auto;
            line-height: 20px;
            display: none;
        }
        .wjmm a {
            color: white;
            text-align: left !important;
        }
        .error{ text-indent: 40px !important;}
		#login_error{ text-indent: 40px ;}

    </style>
    <!-- END STYLESHEET-->
</head>
<body>


<div class="login_bg">
   <!-- <img src="../../../img/login_bg.png" width="100%" height="100%">-->

<div id="box">
    <form id="login-form">
        <div class="meih" style="text-align: center;"><h2>账号登录</h2></div>
        <div class="meih">账号：<input name="username" id="username" type="text" placeholder="输入账号"/>
        	<label class="error" for="username" style=" height: 15px;"></label>
        </div>
        <div class="meih">密码：<input name="password" id="password" type="password" placeholder="输入密码"/>
        	<label class="error" for="password" style=" height: 15px;"></label>
        </div>
        <div class="meih"><span id="login_error" class="error" style="display: block;"></span></div>
        <div class="wjmm"><a href="">忘记密码？</a></div>
        <div class="meih"><input type="submit" value="登录" class="an"></div>
    </form>
</div>

<!-- BEGIN FOOTER -->
<footer class="site-footer" style="position: absolute; bottom: 0px; width: 100%;">
    <div class="text-center">
        2016 &copy;重庆·巴南
        <a href="" target="_blank">
            网信工作管理平台
        </a>
        <a href="/" class="go-top">
            <i class="fa fa-angle-up">
            </i>
        </a>
    </div>
</footer>
</div>
<!-- END  FOOTER -->
<!-- BEGIN JS -->
<script src="/s_src/jquery-2.1.1.min.js"></script><!-- BASIC JQUERY LIB. JS -->
<script src="/s_src/bootstrap.min.js"></script><!-- BOOTSTRAP JS -->
<script src="/s_src/jquery.validate.min.js"></script>
<script src="/s_src/public.js"></script>
<!-- END JS -->

<!-- Page Script -->
<script>
    window.onload = function () {
        function box() {
            var oBox = document.getElementById('box');
            var L1 = oBox.offsetWidth;
            var H1 = oBox.offsetHeight;
//			    var Left = (document.documentElement.clientWidth-L1)/2;
            var top = (document.documentElement.clientHeight - H1) / 2;
//			    oBox.style.left = Left+'px';
            oBox.style.top = top + 'px';
        }

        box();
        window.onresize = function () {
            box();
        }
    }


    // jq validate 插件初始化
    $.validator.setDefaults({
        submitHandler: function () {
            login_commit();
        }
    });
    $().ready(function () {
        $("#login-form").validate({
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
                    $("#login_error").html(data.message).show();
                }
            }
        });
    }
</script>

</body>
</html>

