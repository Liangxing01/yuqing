<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

	public function index()
	{
//		$this->load->view('welcome_message');
		$this->all_display('index.html');
//	    $this->load->view("login.html");
//        $this->load->view("index.html");
	}

	public function login(){
        $this->load->view("login/login.html");
    }

	public function wait_to_handle(){
		$this->assign("active_title","wait_to_handle");
		$this->assign("active_parent","handle_parent");
		$this->all_display('wait_to_handle.html');
	}
}
