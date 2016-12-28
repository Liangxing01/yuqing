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

}