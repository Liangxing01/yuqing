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
    <!-- END STYLESHEET-->
</head>
<body class="login-screen">
<!-- BEGIN SECTION -->
<div class="container">
    <form class="form-signin" action="/Login/checkLogin" method="post">
        <h2 class="form-signin-heading">
            登录
        </h2>
        <!-- LOGIN WRAPPER  -->
        <div class="login-wrap">
            <input type="text" class="form-control" placeholder="用户名" name="username" autofocus>
            <input type="password" class="form-control" placeholder="密码" name="password">

            <!--<label class="checkbox">-->
            <!--<input type="checkbox" value="remember-me" name="remember-me">-->
            <!--记住我-->
            <!--<span class="pull-right">-->
            <!--<a data-toggle="modal" href="#myModal">-->
            <!--忘记密码-->
            <!--</a>-->
            <!--</span>-->
            <!--</label>-->
        </div>

        <!--<div class="registration">-->
        <!--还没有账号吗？-->
        <!--<a class="" href="registration.html">-->
        <!--创建一个账号-->
        <!--</a>-->
        <!--</div>-->
        <button class="btn btn-lg btn-login btn-block" type="submit">
            登录
        </button>
    </form>




    <!-- END LOGIN WRAPPER -->
    <!-- MODAL -->
    <!--<div  id="myModal" class="modal fade">-->
    <!--<div class="modal-dialog">-->
    <!--<div class="modal-content">-->
    <!--<div class="modal-header">-->
    <!--<button type="button" class="close" data-dismiss="modal" aria-hidden="true">-->
    <!--&times;-->
    <!--</button>-->
    <!--<h4 class="modal-title">-->
    <!--Forgot Password ?-->
    <!--</h4>-->
    <!--</div>-->
    <!--<div class="modal-body">-->
    <!--<p>-->
    <!--Enter your e-mail address below to reset your password.-->
    <!--</p>-->
    <!--<input type="text" name="email" placeholder="Email" autocomplete="off" class="form-control placeholder-no-fix">-->
    <!--</div>-->
    <!--<div class="modal-footer">-->
    <!--<button data-dismiss="modal" class="btn btn-default" type="button">-->
    <!--Cancel-->
    <!--</button>-->
    <!--<button class="btn btn-success" type="button">-->
    <!--Submit-->
    <!--</button>-->
    <!--</div>-->
    <!--</div>-->
    <!--</div>-->
    <!--</div>-->
    <!-- END MODAL -->

    <!--</div>-->
    <!-- END SECTION -->
    <!-- BEGIN JS -->
    <script src="/js/jquery.js" ></script><!-- BASIC JQUERY LIB. JS -->
    <script src="/js/bootstrap.min.js" ></script><!-- BOOTSTRAP JS -->
    <!-- END JS -->
</body>
</html>

