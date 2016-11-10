<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
    }


    /**
     * 首页视图 载入
     */
	public function index()
	{
        $this->assign("active_title", "home_page");
        $this->assign("active_parent", "home_parent");
		$this->all_display('index.html');
	}


	/*
	 * 主页接口：获取指派工作和处理工作任务数接口
	 */
	public function get_all_tasks_num(){
        $this->load->model('MY_Model','my_model');
        $uid = $this->session->userdata('uid');
        $pri = $this->my_model->get_privileges($uid);

        $all_num_arr = array();
        if(!empty($pri)){
            foreach ($pri as $one){
                switch ($one['pid']){
                    case 2 :
                        $zp_tasks_num_arr = $this->my_model->get_zp_tasks($uid);
                        break;
                    case 3:
                        $handler_num_arr  = $this->my_model->get_handler_num($uid);
                        break;
                }

            }
            if(isset($zp_tasks_num_arr)){
                array_push($all_num_arr,$zp_tasks_num_arr);
            }
            if(isset($handler_num_arr)){
                array_push($all_num_arr,$handler_num_arr);
            }
        }
        echo json_encode($all_num_arr);

    }


    /*
     * 主页接口：获取用户最近登录的N条记录
     * 参数：uid,num
     */
    public function get_login_list($uid, $num)
    {
        $this->load->model('MY_Model', "my_model");
        $res = $this->my_model->get_login_list($uid, $num);
        return $res;
    }

    /*
     * 个人信息查看接口
     */
    public function get_my_info()
    {
        $this->load->model('MY_Model', "my_model");
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->get_user_info($uid);
        echo json_encode($res);
    }

    /*
     * 个人信息修改接口
     */
    public function update_info($data, $uid)
    {
        $this->load->model('MY_Model', "my_model");
        $res = $this->my_model->update_info($data, $uid);
        return $res;
    }

    /*
     * 修改密码接口
     */

    public function change_psw($old,$new,$uid){
        $this->load->model('MY_Model',"my_model");
        $check = $this->my_model->check_old_pass($old);
        if($check){
            $res = $this->my_model->update_psw($new,$uid);
            if($res){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }




    /**
     * 用户登出 接口
     */
    public function logout(){
        $this->identity->destroy();
    }
}
