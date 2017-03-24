<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_Model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->model("Verify_Model", "verify");
    }


    /**
     * 写入登陆日志
     * @param $login_info
     */
    public function write_login_log($login_info){
        $this->db->set($login_info)->insert("login_log");
    }


    /**
     * 获得有指派权限的用户
     */
    public function get_managers()
    {
        return $this->db->select("user.id")->from("user")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where("user_privilege.pid", Verify_Model::MANAGER)
            ->get()->result_array();
    }
}