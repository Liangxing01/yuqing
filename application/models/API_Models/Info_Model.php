<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Info_Model extends CI_Model
{

    /**
     * 数据接口 返回值
     * @var array
     * code 0 请求成功
     */
    private $success = array(
        "code" => 0,
        "message" => "Request Success",
        "data" => array()
    );

    /**
     * 数据接口 返回值
     * @var array
     * code 1 参数错误
     */
    private $param_error = array(
        "code" => 1,
        "message" => "Bad Request Params",
    );

    /**
     * 数据接口 返回值
     * @var array
     * code 2 权限错误
     */
    private $privilege_error = array(
        "code" => 2,
        "message" => "No Privilege",
    );


    /**
     * Info_Model constructor.
     */
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
        // 检测参数
        if (!(is_int($page_num) && is_int($size))) {
            return $this->param_error;
        }
        // 查询数据
        $start = ($page_num - 1) * $size;
        $uid = $this->session->uid;
        $this->success["data"] = $this->db->select("info.id, title, url, time")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->limit($size, $start)
            ->order_by("time", "desc")
            ->get("info")->result_array();
        return $this->success;
    }


    /**
     * 获取上报信息详情
     * @param int $info_id 信息id
     * @return array
     */
    public function get_info_detail($info_id)
    {
        // 检测参数
        if (!$info_id) {
            return $this->param_error;
        }
        // 检测权限
        // 查询数据
        $info = $this->db->select("info.id, info.relate_scope, info.title, info.description, type.name AS type, info.url, info.state, info.source, user.name AS publisher, info.time")
            ->from("info")
            ->join("type", "type.id = info.type", "left")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("info.id" => $info_id))
            ->get()->row_array();
        // 附件信息
        $attachments = $this->db->select("id, name, url, type")
            ->where("info_id", $info_id)
            ->get("info_attachment")->result_array();
        $info['video'] = array();
        $info['picture'] = array();
        foreach ($attachments AS $attachment) {
            if ($attachment["type"] == "pic") {
                $info['video'][] = $attachment;
            } else {
                $info['picture'][] = $attachment;
            }
        }

        $this->success['data'] = $info;
        return $this->success;
    }

}