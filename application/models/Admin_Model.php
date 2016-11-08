<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/8
 * Time: 11:20
 */

class Admin_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    /**
     * 获取 信息类型 列表
     * @return array
     */
    public function get_info_type()
    {
        return $this->db->get("type")->result_array();
    }




}