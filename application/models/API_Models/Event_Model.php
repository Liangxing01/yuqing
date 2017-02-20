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
     * @param int $page_num
     * @param int $size
     * @param string $type
     * @param int $event_id
     * @param int $parent_id
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


    /**
     * 获取事件列表 分页数据
     * @param int $page_num
     * @param int $size
     * @param string $data_type
     * @param string $user_type
     * @return array
     */
    public function get_event_list_data($page_num, $size, $data_type, $user_type)
    {
        $uid = $this->session->uid;
        // 检查权限
        switch ($user_type) {
            case "manager":
                if (!$this->verify->is_manager($uid)) {
                    return $this->privilege_error;
                }
                break;
            case "processor":
                if (!$this->verify->is_processor($uid)) {
                    return $this->privilege_error;
                }
                break;
            case "watcher":
                if (!$this->verify->is_watcher($uid)) {
                    return $this->privilege_error;
                }
                break;
            default:
                return $this->param_error;
                break;
        }
        // 检查参数
        if (is_int($page_num) && is_int($size)) {
            $start = ($page_num - 1) * $size;
        } else {
            return $this->param_error;
        }
        // 查询数据 TODO 角色不同数据不同
        switch ($data_type) {
            case 'all':
                $data = $this->db->select("event.id, event.title, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time")
                    ->from("event")
                    ->join("user A", "A.id = event.manager", "left")
                    ->join("user B", "B.id = event.main_processor", "left")
                    ->join("group C", "C.id = event.group", "left")
                    ->order_by("start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->from("event")
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            default:
                return $this->param_error;
                break;
        }
    }


    /**
     * 获取事件详情
     * @param int $event_id 事件ID
     * @return array
     */
    public function get_event_detail_data($event_id)
    {
        // 检查权限
        if (!$this->verify->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 检查参数
        if (is_null($event_id)) {
            return $this->param_error;
        }
        // 查询数据
        $data = array();
        $data["detail"] = $this->db->select("event.id, event.title, event.description, reply_time, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
            ->from("event")
            ->join("user A", "A.id = event.manager", "left")
            ->join("user B", "B.id = event.main_processor", "left")
            ->join("group C", "C.id = event.group", "left")
            ->where("event.id", $event_id)
            ->get()->row_array();
        // 信息列表
        $data["info_list"] = $this->db->select("info.id, info.title")
            ->from("info")
            ->where("info.id IN (SELECT `info_id` FROM `yq_event_info` WHERE `event_id` = $event_id)")
            ->get()->result_array();
        // 关联事件
        $data["relate_event"] = $this->db->select("event.id, event.title")
            ->from("event")
            ->where("event.id IN (SELECT `relate_id` FROM `yq_event_relate` WHERE `event_id` = $event_id)")
            ->get()->result_array();
        // 参考文件
        $data["attachment"] = $this->db->select("id, name")
            ->from("event_attachment")
            ->where("event_id", $event_id)
            ->get()->result_array();
        $this->success["data"] = $data;
        return $this->success;
    }


    /**
     * 判断用户是否为事件督察组
     * @param int $eid
     * @param int $uid
     * @return bool
     */
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