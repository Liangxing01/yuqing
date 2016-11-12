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
        $privilege = explode(",", $this->session->privilege);
        $name = $this->session->userdata('name');
        $this->assign('name',$name);
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
                    break;
                case 5 :
                    $this->ci_smarty->display('menu/menu_admin.html');
                    break;
            }
        }

        $this->ci_smarty->display('menu/menu_common.html');
        $this->ci_smarty->display($html);
        $this->ci_smarty->display('footer.html');
    }


}