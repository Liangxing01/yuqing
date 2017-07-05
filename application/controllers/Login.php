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
        //加载 主题配置文件
        $this->config->load('theme_cfg');
        $theme_cfg = $this->config->item('theme');
        $this->assign("theme_cfg",$theme_cfg);

        $this->load->helper(array("public"));
        $is_mobile = isMobile();
        if ($is_mobile) {
            $this->load->view("login/mobile_login");
        } else {
            //$this->load->view("login/login");
            $this->display("login/login.html");
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
                "type" => $login_type
            );
            $this->load->model("User_Model", "user");
            $this->user->write_login_log($login_info);

            //返回登陆信息
            $return["code"] = 0;
            $return["message"] = "登陆成功";
            if ($login_type == 1) {
                $return['data'] = array(
                    'm_token' => $result["m_token"],
                    'name' => $result["name"],
                    'gname' => $result["gname"],
                    'avatar' => $result["avatar"],
                    'privilege' => $result["privilege"]
                );
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

    function callBack() {
		$row=file_get_contents('php://input');
		log_message('debug',$row);
		$input=json_decode($row);
		if (!$input) show_404();
		if ($input['hangup_cause']='NORMAL_CLEARING'){
			$target=$this->db->find('call_log', $input['calloid'],'id','target');
			if (!$target) show_404();
			$target=json_decode($target['target'],true);
			foreach ($target as $key => $value) {
				if ($value['phone']==$input['telnum']){
					$value['status']=1;
					$value['time']=time();
					$target[$key]=$value;
					break;
				}
			}
			$this->db->where('id',$input['calloid'])
			->update('call_log',['target'=>json_encode($target)]);
		}
	}

}