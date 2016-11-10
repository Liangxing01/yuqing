<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_Model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }


    /**
     * 写入登陆日志
     * @param $login_info
     */
    public function write_login_log($login_info){
        $this->db->set($login_info)->insert("login_log");
    }
}