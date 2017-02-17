<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Event_Model extends CI_Model
{

    /**
     * 数据接口 返回值
     * @var array
     * code 0 请求成功
     */
    private $success;

    /**
     * 数据接口 返回值
     * @var array
     * code 1 参数错误
     */
    private $param_error;

    /**
     * 数据接口 返回值
     * @var array
     * code 2 权限错误
     */
    private $privilege_error;


    /**
     * Info_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 载入 权限模型
        $this->load->model("Verify_Model", "verify");
        // 载入 api 返回值
        $this->load->helper("api");
        $this->success = APIResponseBody::$success;
        $this->param_error = APIResponseBody::$param_error;
        $this->privilege_error = APIResponseBody::$privilege_error;
    }


    /**
     * 获取事件处理 回复时间线 分页数据
     * @param $page_num
     * @param $size
     * @param $type
     * @param $event_id
     * @param $parent_id
     * @return array
     */
    public function get_response_timeline_data($page_num, $size, $type, $event_id, $parent_id)
    {
        // 检查权限
        if (!$this->verify->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 检查参数
        if (is_int($page_num) && is_int($size)) {
            $start = ($page_num - 1) * $size;
        } else {
            return $this->param_error;
        }
        if (is_null($event_id)) {
            return $this->param_error;
        }
        if ($type == "c_record" && is_null($parent_id)) {
            return $this->param_error;
        }
        // 查询数据
        switch ($type) {
            case 'f_record':
                // 返回父评论
                $data = $this->db->select("event_log.id, event_log.speaker, user.name, event_log.description, event_log.time, user.avatar, (select count(id) from yq_event_log AS log where log.event_id = $event_id AND log.pid = yq_event_log.id) AS comments_num")
                    ->from("event_log")
                    ->join("user", "event_log.speaker = user.id")
                    ->where(array("event_log.event_id" => $event_id, "pid" => null))
                    ->limit($size, $start)
                    ->order_by("time", "desc")
                    ->get()->result_array();
                $total = $this->db->where(array("event_log.event_id" => $event_id, "pid" => null))->get("event_log")->num_rows(); // 总条数
                foreach ($data AS $key => $val) {
                    $data[$key]['usertype'] = $this->is_event_watcher($event_id, $val['speaker']) ? 1 : 0;
                }
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case 'c_record':
                // 返回子评论
                $data = $this->db->select("event_log.id, event_log.speaker, user.name, event_log.description, event_log.time, user.avatar")
                    ->from("event_log")
                    ->join("user", "event_log.speaker = user.id")
                    ->where("pid", $parent_id)
                    ->limit($size, $start)
                    ->order_by("time", "desc")
                    ->get()->result_array();
                foreach ($data AS $key => $val) {
                    $data[$key]['usertype'] = $this->is_event_watcher($event_id, $val['speaker']) ? 1 : 0;
                }
                $total = $this->db->where("pid", $parent_id)->get("event_log")->num_rows(); // 总条数
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            default:
                return $this->param_error;
                break;
        }
    }


    //判断用户是否为事件督察组
    protected function is_event_watcher($eid, $uid)
    {
        $res = $this->db->where(array("event_id" => $eid, "watcher" => $uid))->get("event_watch")->num_rows();
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }
}