<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/4
 * Time: 11:20
 */

class MY_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    //获取用户登录记录最新的6条
    public function get_login_list($uid,$num){
        $res = $this->db->select('ip,time')->from('login_log')
            ->where('uid',$uid)
            ->order_by('time','DESC')
            ->limit($num)
            ->get()->result_array();
        return $res;

    }


}