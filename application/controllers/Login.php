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

        $this->load->model('user');
        $user = $this->user->user_select($_POST['username']);
        if ($user){
            if ($user->password == md5($_POST['password'])){
                $this->load->library('session');
                $this->session->set_userdata("uid",$user->id);
                echo $this->session->userdata('uid');
                header("location:/welcome/index");
            }
            else{
                echo '用户名或密码不正确';
            }
        }else{
            echo '用户名不存在';
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
        redirect('/welcome/login','refresh');
    }

}