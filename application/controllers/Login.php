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
        $is_mobile = $this->isMobile();
        if($is_mobile){
            $this->load->view("login/mobile_login");
        }else{
            $this->load->view("login/login");
        }

    }

    /**
     * 判断是否是通过手机访问
     * @return bool 是否是移动设备
     */
    public function isMobile() {
        //判断手机发送的客户端标志
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-'
            ,'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu',
                'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini',
                'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if(preg_match("/(".implode('|',$clientkeywords).")/i",$userAgent)&&strpos($userAgent,'ipad') === false)
            {
                return true;
            }
        }
        return false;
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