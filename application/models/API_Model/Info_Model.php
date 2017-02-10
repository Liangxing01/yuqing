<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Info_Model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        // 载入 权限模型
        $this->load->model("Verify_Model", "verify");
    }


    /**
     * 获取上报记录
     * @param $page_num int 页码
     * @param $size int 每页大小
     * @return array
     */
    public function get_info_record_data($page_num, $size)
    {
        // 返回值
        $return = array(
            "code" => 0,
            "message" => "Request Success",
            "data" => array()
        );
        // 检测参数
        if (!(is_int($page_num) && is_int($size))) {
            $return["code"] = 1;
            $return["message"] = "Bad Request Params";
            return $return;
        }
        // 查询数据
        $start = ($page_num - 1) * $size;
        $uid = $this->session->uid;
        $return["data"] = $this->db->select("info.id, title, url, time")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->limit($size, $start)
            ->order_by("time", "desc")
            ->get("info")->result_array();
        return $return;
    }

}