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


	


    /**
     * 用户登出 接口
     */
    public function logout(){
        $this->identity->destroy();
    }
}
