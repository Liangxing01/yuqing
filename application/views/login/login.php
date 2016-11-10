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
    <link href="/css/bootstrap.min.css" rel="stylesheet"><!-- BOOTSTRAP CSS -->
    <link href="/css/bootstrap-reset.css" rel="stylesheet"><!-- BOOTSTRAP CSS -->
    <link href="/assets/font-awesome/css/font-awesome.css" rel="stylesheet"><!-- FONT AWESOME ICON CSS -->
    <link href="/css/style.css" rel="stylesheet"><!-- THEME BASIC CSS -->
    <link href="/css/style-responsive.css" rel="stylesheet"><!-- THEME RESPONSIVE CSS -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.js">
    </script>
    <script src="/js/respond.min.js">
    </script>
    <![endif]-->
    <style>
        .error{
            font-size: 0.9em;
            color: #FF4500;
        }
        #login_error{
            display: none;
        }
    </style>
    <!-- END STYLESHEET-->
</head>
<body class="login-screen">

<!-- BEGIN SECTION -->
<div class="container">
    <form class="form-signin" id="login-form">
        <h2 class="form-signin-heading">
            舆情工作平台
        </h2>
        <!-- LOGIN WRAPPER  -->
        <div class="login-wrap">
            <input type="text" class="form-control" placeholder="用户名" name="username" id="username" autofocus>
            <input type="password" class="form-control" placeholder="密码" name="password" id="password">
            <span class="error" id="login_error" style="display: none"></span>
            <input class="btn btn-lg btn-login btn-block" type="submit" value="登&nbsp;&nbsp;录"/>
        </div>
        <!-- END LOGIN WRAPPER -->
    </form>
</div>
<!-- END SECTION -->

<!-- BEGIN JS -->
<script src="/js/jquery-2.1.1.min.js"></script><!-- BASIC JQUERY LIB. JS -->
<script src="/js/bootstrap.min.js"></script><!-- BOOTSTRAP JS -->
<script src="/js/jquery.validate.min.js"></script>
<script src="/js/public.js"></script>
<!-- END JS -->

<!-- Page Script -->
<script>

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
    function login_commit(){
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
                if(data.code == "0"){
                    forward("/welcome/index");
                }else {
                    $("#login_error").html(data.message).show();
                }
            }
        });
    }
</script>

</body>
</html>

