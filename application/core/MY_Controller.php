<?php

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function assign($key, $val)
    {
        $this->ci_smarty->assign($key, $val);
    }

    public function display($html)
    {
        $this->ci_smarty->display($html);
    }

    //共用显示header,sidebar,footer
    public function all_display($html)
    {
        $this->load->model('MY_Model','my_model');
        $privilege = explode(",", $this->session->privilege);
        sort($privilege);
        $name = $this->session->userdata('name');
        $this->assign('name',$name);
        $this->assign("avatar", $this->session->avatar);
        $username = $this->session->userdata('username');
        $this->assign('username',$username);
        $job = $this->my_model->get_job();
        $this->assign('job',$job);
        $this->ci_smarty->display('head.html');
        $this->ci_smarty->display('menu/menu_home.html');

        //菜单载入
        foreach ($privilege AS $item) {
            switch ($item) {
                case 1 :
                    $this->ci_smarty->display('menu/menu_publisher.html');
                    break;
                case 2 :
                    $this->ci_smarty->display('menu/menu_manager.html');
                    break;
                case 3 :
                    $this->ci_smarty->display('menu/menu_processor.html');
                    break;
                case 4:
                    $this->ci_smarty->display('menu/menu_watcher.html');
                    break;
                case 5 :
                    $this->ci_smarty->display('menu/menu_admin.html');
                    break;
            }
        }

        // 6视频会议菜单
        if(in_array(2, $privilege) || in_array(3, $privilege) || in_array(4, $privilege)){
            $this->ci_smarty->display('menu/v6_meeting.html');
        }

        $this->ci_smarty->display('menu/menu_common.html');
        $this->ci_smarty->display($html);
        $this->ci_smarty->display('footer.html');
    }
    
    // 手机版 共用显示header,sidebar,footer
    public function m_all_display($html)
    {
        $this->load->model('MY_Model','my_model');
        $privilege = explode(",", $this->session->privilege);
        sort($privilege);
        $name = $this->session->userdata('name');
        $this->assign('name',$name);
        $this->assign("avatar", $this->session->avatar);
        $username = $this->session->userdata('username');
        $this->assign('username',$username);
        $job = $this->my_model->get_job();
        $this->assign('job',$job);
        $this->ci_smarty->display('m_head.html');
        $this->ci_smarty->display('menu/menu_home.html');

        //菜单载入
        foreach ($privilege AS $item) {
            switch ($item) {
                case 1 :
                    $this->ci_smarty->display('menu/menu_publisher.html');
                    break;
                case 2 :
                    $this->ci_smarty->display('menu/menu_manager.html');
                    break;
                case 3 :
                    $this->ci_smarty->display('menu/menu_processor.html');
                    break;
                case 4:
                    $this->ci_smarty->display('menu/menu_watcher.html');
                    break;
                case 5 :
                    $this->ci_smarty->display('menu/menu_admin.html');
                    break;
            }
        }

        // 6视频会议菜单
        if(in_array(2, $privilege) || in_array(3, $privilege) || in_array(4, $privilege)){
            $this->ci_smarty->display('menu/v6_meeting.html');
        }

        $this->ci_smarty->display('menu/menu_common.html');
        $this->ci_smarty->display($html);
        $this->ci_smarty->display('footer.html');
    }

}