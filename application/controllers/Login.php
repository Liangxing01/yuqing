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
        $this->load->view("login/login");
    }


    /**
     * 登陆验证
     */
    public function check()
    {
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);

        $return = array(
            "code" => 1,
            "message" => "用户名或密码错误"
        );

        //登陆验证
        $result = $this->identity->user_auth($username, $password);

        if ($result) {
            $this->load->helper(array("public"));
            // 记录登陆日志信息
            $login_info = array(
                "uid" => $this->session->uid,
                "ip" => get_ip(),
                "time" => time(),
            );
            $this->load->model("User_Model", "user");
            $this->user->write_login_log($login_info);

            //返回登陆信息
            $return["code"] = 0;
            $return["message"] = "登陆成功";
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