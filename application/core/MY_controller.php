<?php
class MY_controller extends CI_Controller {
    public function __construct() {

        parent::__construct();
    }
    public function assign($key,$val)
    {
        $this->ci_smarty->assign($key,$val);
    }

    public function display($html)
    {
        $this->ci_smarty->display($html);
    }

    //共用显示header,sidebar,footer
    public function all_display($html){
        $this->ci_smarty->display('head.html');
        $this->ci_smarty->display('sidebar.html');
        $this->ci_smarty->display($html);
        $this->ci_smarty->display('footer.html');
    }
}