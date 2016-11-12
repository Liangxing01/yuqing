<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model','my_model');
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
        $uid = $this->session->userdata('uid');
        //$pri = $this->my_model->get_privileges($uid);
        $pri = explode(",",$this->session->userdata('privilege'));
        $all_num_arr = array();
        if(!empty($pri)){
            foreach ($pri as $one){
                switch ($one){
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
    public function get_login_list()
    {
        $uid = $this->session->userdata('uid');
        $num = 5;
        $res = $this->my_model->get_login_list($uid, $num);
        return $res;
    }

    /*
     * 主页接口：获取警告事件
     * 根据权限判断 来返回 警告事件
     */
    public function get_event_alert(){
        $uid = $this->session->userdata('uid');
        $pri = explode(",",$this->session->userdata('privilege'));
        $alarm_arr = array(
            'zp_alarm' => array(),
            'processor_alarm' => array()
        );
        foreach ($pri as $one){
            switch ($one){
                case 2 :
                    $zp_alarm = $this->my_model->get_desi_alert($uid);
                    array_push($alarm_arr['zp_alarm'],$zp_alarm);
                    break;
                case 3 :
                    $processor_alarm = $this->my_model->get_processor_alert($uid);
                    array_push($alarm_arr['processor_alarm'],$processor_alarm);
                    break;
            }
        }

        echo json_encode($alarm_arr);
    }

    /*
     * 个人信息查看接口
     */
    public function get_my_info()
    {
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->get_user_info($uid);
        $res[0]['privilege'] = $this->session->userdata('privilege');
        echo json_encode($res);
    }

    /*
     * 个人信息修改接口
     */
    public function update_info()
    {
        $name = $this->input->post('name');
        $sex  = $this->input->post('sex');
        $update_data = array(
            'name' => $name,
            'sex'  => $sex
        );
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->update_info($update_data, $uid);
        if($res){
            echo json_encode(
                array(
                    'res' => 1
                )
            );
        }else{
            echo json_encode(
                array(
                    'res' => 0
                )
            );
        }


    }

    /*
     * 修改密码接口
     */

    public function change_psw(){
        $old = $this->input->post('old_pass');
        $new = $this->input->post('new_pass');
        $uid = $this->session->userdata('uid');
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

    //修改头像接口
    public function change_avatar(){
        $config['upload_path']      = '/uploads/avatar/';
        $config['allowed_types']    = 'jpg|png|jpeg';
        $config['max_size']     = 2048;
        $config['max_width']        = 1024;
        $config['max_height']       = 768;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('avatar'))
        {
            $error = array('error' => $this->upload->display_errors());

            $this->load->view('upload_form', $error);
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());

            $this->load->view('upload_success', $data);
        }
    }

    //展示修改密码界面
    public function show_change_psw(){
        $this->assign("active_title", "change_psw");
        $this->assign("active_parent", "manage_parent");
        $this->all_display("change_psw.html");
    }




    /**
     * 用户登出 接口
     */
    public function logout(){
        $this->identity->destroy();
    }
}
