<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate extends MY_controller{

    public function __construct()
    {
        parent::__construct();
        //TODO 登陆验证权限控制
    }


    /**
     * 待指派事件列表
     */
    public function event_not_designate(){
        $this->all_display("designate/event_not_designate.html");
    }


}