<?php
class MY_Controller extends CI_Controller {
    public function __construct() {

        parent::__construct();
        $this->load->model('MY_Model',"my_model");
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

    /*
     * 主页接口：获取用户最近登录的N条记录
     * 参数：uid,num
     */
    public function get_login_list($uid,$num){
        $res = $this->my_model->get_login_list($uid,$num);
        return $res;
    }
}