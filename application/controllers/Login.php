<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_controller
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 登陆页面视图 载入
     */
    public function index()
    {
        $this->load->helper(array("public"));
        $is_mobile = isMobile();
        if ($is_mobile) {
            $this->load->view("login/mobile_login");
        } else {
            $this->load->view("login/login");
        }

    }

    public function m_app()
    {
        $this->load->view("download_app.html");
    }


    /**
     * 登陆验证
     */
    public function check()
    {
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);
        $login_type = $this->input->post('login_type', true);

        $return = array(
            "code" => 1,
            "message" => "用户名或密码错误"
        );

        //判断是否移动端登陆 1:移动端
        $login_type = $login_type == 1 ? 1 : 0;
        $result = $this->identity->user_auth($username, $password, $login_type);

        if ($result !== false) {
            $this->load->helper(array("public"));
            // 记录登陆日志信息
            $login_info = array(
                "uid" => $result["uid"],
                "ip" => get_ip(),
                "time" => time(),
                "type" => $login_type == 1 ? 1 : 0
            );
            $this->load->model("User_Model", "user");
            $this->user->write_login_log($login_info);

            //返回登陆信息
            $return["code"] = 0;
            $return["message"] = "登陆成功";
            if ($login_type == 1) {
                $return["m_token"] = $result["m_token"];
                $return['name'] = $result["name"];
                $return['gname'] = $result["gname"];
                $return['avatar'] = $result["avatar"];
                $return['privilege'] = $result["privilege"];
            }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($return));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($return));
        }
    }

    /**
     * -------------------------------知云网 单点登录--------------------------------
     */

    /*安全获取参数,根据需要过滤非法内容*/
    public function Safe_Request($key)
    {
        $res =$_REQUEST[$key];
        if (strlen($res) <1)
        {
            return $res;
        }

        $check = preg_match('/select|and|insert|update|delete|\=|\'|\`|\/\*|\*\/|\.\.\/|\.\/|union|into|load_file|outfile/', $res,$match);
        if ($check)
        {
            $res = "";
        }
        return $res;
    }

    /**
     * 知云网 登录
     */
    public function zy_login(){
        //$user = $this->Safe_Request("username");
        //$pass = $this->Safe_Request("password");
        $user = $_REQUEST['username'];
        $pass = $_REQUEST['password'];
        //$key = $this->Safe_Request("Key");
        $key  = $_REQUEST['Key'];

        if (strlen($user) < 1 || strlen($pass) < 1 || strlen($key)<1 )
        {
            $arr = array(
                "statusCode" => "300",
                "message" => urlencode("登录失败:账号密码不能为空!"),
                "tabid" => "table, table-fixed",
                "closeCurrent" => true,
                "forward" => "",
                "forwardConfirm" => "",
                "redirect" => "/",
            );
            echo urldecode(json_encode($arr));
            exit();
        }

        if($key != "A57C4D71B39F0E12")
        {
            $arr = array(
                "statusCode" => "300",
                "message" => urlencode("登录失败:授权码校验失败!"),
                "tabid" => "table, table-fixed",
                "closeCurrent" => true,
                "forward" => "",
                "forwardConfirm" => "",
                "redirect" => "/",
            );
            echo urldecode(json_encode($arr));
            exit();
        }
        var_dump($user);
        var_dump($pass);

        //判断是否移动端登陆 1:移动端
        $login_type =  0;
        $result = $this->identity->user_auth($user, $pass, $login_type);

        if ($result !== false) {
            $this->load->helper(array("public"));
            // 记录登陆日志信息
            $login_info = array(
                "uid" => $result["uid"],
                "ip" => get_ip(),
                "time" => time(),
                "type" => $login_type == 1 ? 1 : 0
            );
            $this->load->model("User_Model", "user");
            $this->user->write_login_log($login_info);

            /*账号密码验证通过直接跳转到登陆后主页*/
            Header("Location: /welcome/index");

        } else {
            $arr = array(
                "statusCode" => "300",
                "message" => urlencode("登录失败:账号或密码错误!"),
                "tabid" => "table, table-fixed",
                "closeCurrent" => true,
                "forward" => "",
                "forwardConfirm" => "",
                "redirect" => "/",
            );
            echo urldecode(json_encode($arr));
            exit();
        }

        if (count($result) < 1 )//登录失败
        {
            $arr = array(
                "statusCode" => "300",
                "message" => urlencode("登录失败:账号或密码错误!"),
                "tabid" => "table, table-fixed",
                "closeCurrent" => true,
                "forward" => "",
                "forwardConfirm" => "",
                "redirect" => "/",
            );
            echo urldecode(json_encode($arr));
            exit();
        }


    }

}