<?php
class MY_Controller extends CI_Controller {
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

    /*
     * 主页接口：获取用户最近登录的N条记录
     * 参数：uid,num
     */
    public function get_login_list($uid,$num){
        $this->load->model('MY_Model',"my_model");
        $res = $this->my_model->get_login_list($uid,$num);
        return $res;
    }

    /*
     * 个人信息查看接口
     */
    public function get_my_info(){
        $this->load->model('MY_Model',"my_model");
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->get_user_info($uid);
        echo json_encode($res);
    }

    /*
     * 个人信息修改接口
     */
    public function update_info($data,$uid){
        $this->load->model('MY_Model',"my_model");
        $res = $this->my_model->update_info($data,$uid);
        return $res;
    }

    /*
     * 修改密码接口
     */
    public function change_psw($old,$new,$uid){
        

    }
}