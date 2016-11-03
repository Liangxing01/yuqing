<?php
/**
 * Created by PhpStorm.
 * User: 黎佑民
 * Date: 2016/10/26
 * Time: 14:42
 */

class Login extends MY_controller  {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 登录判断
     */

    public function checkLogin(){

        $this->load->library('form_validation');
        $this->form_validation->set_rules('username', '用户名或密码', 'required');
        $this->form_validation->set_rules('password', '用户名或密码', 'required');

        if ($this->form_validation->run() !== false){
            $this->load->model('user');
            $res = $this->user->verify_users(
                $this->input->post('username'),
                $this->input->post('password')
            );
            if($res['flag'] == true){
//                echo $res['id'];
                $this->session->set_userdata("uid",$res['id']);
                header("location:/Welcome/index");
            }else{
                header("location:/Welcome/login");

            }
        }else{
            $this->load->view('login/login');
        }
    }


    public function checkSession(){
        $this->load->library('session');
        if($this->session->userdata('uid')){
            echo '已经登录';
        }else{
            echo '没有登录';
        }
    }

    public function loginOut(){
        $this->load->helper('url');
        $this->load->library('session');
        $this->session->unset_userdata('uid');
        redirect('/Welcome/login','refresh');
    }

}