<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Handler extends MY_Controller {

    public function index()
    {

        //$this->load->view('welcome_message');
        $this->all_display('index.html');
    }

    //展示待处理事件页面
    public function wait_to_handle(){
        $this->assign("active_title","wait_to_handle");
        $this->assign("active_parent","handle_parent");
        $this->all_display('handler/unhandle.html');
    }


}