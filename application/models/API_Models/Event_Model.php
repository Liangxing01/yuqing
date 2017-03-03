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
        $gid = explode(',', $this->session->userdata('gid')); // array 用户的组ID
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
        // 查询数据
        $user_data_type = $user_type . "_" . $data_type;
        switch ($user_data_type) {
            case 'manager_all_event':
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
            case "manager_undo_event":
                // 未审核事件列表
                $data = $this->db->select("event.id, event.title, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time")
                    ->from("event")
                    ->join("user A", "A.id = event.manager", "left")
                    ->join("user B", "B.id = event.main_processor", "left")
                    ->join("group C", "C.id = event.group", "left")
                    ->where("event.state", "未审核")
                    ->order_by("start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->from("event")
                    ->where("event.state", "未审核")
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
//            case "processor_all":
//                $data = $this->db->select("event_designate.event_id, event.title, event.rank, event.start_time, event.end_time, user.name, event_designate.state")
//                    ->from("event_designate")
//                    ->join('event', 'event.id = event_designate.event_id', 'left')
//                    ->join('user', 'user.id = event_designate.manager', 'left')
//                    ->group_start()
//                    ->where('event_designate.processor', $uid)
//                    ->or_where_in('event_designate.group', $gid)
//                    ->group_end()
//                    ->limit($size, $start)
//                    ->order_by('event_designate.time', "desc")
//                    ->get()->result_array();
//                $total = $this->db->select("event_designate.event_id")
//                    ->from("event_designate")
//                    ->group_start()
//                    ->where('event_designate.processor', $uid)
//                    ->or_where_in('event_designate.group', $gid)
//                    ->group_end()
//                    ->get()->num_rows();
//                $this->success['data'] = $data;
//                $this->success['total'] = $total;
//                return $this->success;
//                break;
            case "processor_undo":
                // 未处理列表
                $data = $this->db->select("event_designate.event_id, event.title, event.rank, event.start_time, event.end_time, user.name, event_designate.state")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->join('user', 'user.id = event_designate.manager', 'left')
                    ->where(array("event_designate.state" => "未处理"))
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('event_designate.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->where(array("event_designate.state" => "未处理"))
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "processor_doing":
                // 处理中列表
                $data = $this->db->select("event_designate.event_id, event.title, event.rank, event.start_time, event.end_time, user.name, event_designate.state")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->join('user', 'user.id = event_designate.manager', 'left')
                    ->where(array("event_designate.state" => "处理中"))
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('event_designate.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->where(array("event_designate.state" => "处理中"))
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "processor_done":
                // 已完成列表
                $data = $this->db->select("event_designate.event_id, event.title, event.rank, event.start_time, event.end_time, user.name, event.state")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->join('user', 'user.id = event_designate.manager', 'left')
                    ->group_start()
                    ->where("event.state", "未审核")
                    ->or_where("event.state", "已完成")
                    ->group_end()
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('event_designate.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->group_start()
                    ->where("event.state", "未审核")
                    ->or_where("event.state", "已完成")
                    ->group_end()
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "watcher_all":
                $data = $this->db->select("event.id, event.title, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time")
                    ->from("event")
                    ->join("user A", "A.id = event.manager", "left")
                    ->join("user B", "B.id = event.main_processor", "left")
                    ->join("group C", "C.id = event.group", "left")
                    ->join("event_watch D", "D.event_id = event.id", "left")
                    ->where("D.watcher", $uid)
                    ->order_by("event.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->from("event")
                    ->join("event_watch D", "D.event_id = event.id", "left")
                    ->where("D.watcher", $uid)
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
     * 发表回复
     * @param int $event_id 事件ID
     * @param int $pid 父评论ID
     * @param string $content 评论内容
     * @return array
     */
    public function insert_comment($event_id, $pid, $content)
    {
        // 权限判断
        if (!$this->verify->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 插入数据
        $speaker = $this->session->userdata('uid');
        $name = $this->session->userdata('name');
        $time = time();
        $this->db->trans_begin();
        if (is_null($pid)) {
            // 父评论
            $data = array(
                'event_id' => $event_id,
                'description' => $content,
                'speaker' => $speaker,
                'name' => $name,
                'time' => $time
            );
            $this->db->insert('event_log', $data);
            $id = $this->db->insert_id();
        } else {
            // 子评论
            $data = array(
                'event_id' => $event_id,
                'pid' => $pid,
                'description' => $content,
                'speaker' => $speaker,
                'name' => $name,
                'time' => $time
            );
            $this->db->insert('event_log', $data);
            $id = $this->db->insert_id();
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->param_error;
        } else {
            $this->db->trans_commit();
            $this->success["data"]["id"] = $id;
            return $this->success;
        }
    }


    /**
     * @param int $event_id 事件ID
     * @param string $option 操作
     * @return array
     */
    function event_check($event_id, $option)
    {
        // 检查权限
        if (!$this->verify->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 事件审核操作
        $time = time();
        $uid = $this->session->uid; // 用户id
        $gid = explode(',', $this->session->gid); // 用户组 id
        switch ($option) {
            case "verify_success":
                // 指派人确认通过审核
                if ($this->verify->is_manager()) {
                    // 确认事件状态'未审核'才能操作
                    $n = $this->db->select("id")->where(array("id" => $event_id, "state" => "未审核"))->get("event")->num_rows();
                    if ($n != 1) {
                        return $this->param_error;
                    }
                    $this->db->set(array("state" => "已完成", "end_time" => $time))->where(array('id' => $event_id, 'state' => '未审核'))->update("event");
                    return $this->success;
                } else {
                    return $this->privilege_error;
                }
                break;
            case "verify_failed":
                // 指派人不通过审核
                if ($this->verify->is_manager()) {
                    // 确认事件状态'未审核'才能操作
                    $n = $this->db->select("id")->where(array("id" => $event_id, "state" => "未审核"))->get("event")->num_rows();
                    if ($n != 1) {
                        return $this->param_error;
                    }
                    $this->db->trans_begin();
                    $this->db->set(array("state" => "已指派"))->where(array('id' => $event_id, 'state' => '未审核'))->update("event");
                    $this->db->set(array("state" => "处理中"))->where(array('event_id' => $event_id, 'state' => '已完成'))->update("event_designate");
                    if ($this->db->trans_status() === FALSE) {
                        $this->db->trans_rollback();
                        return $this->param_error;
                    } else {
                        $this->db->trans_commit();
                        return $this->success;
                    }
                } else {
                    return $this->privilege_error;
                }
                break;
            case "commit":
                // 处理人提交审核
                if ($this->is_main_processor($uid, $gid, $event_id)) {
                    // 开始事务
                    $this->db->trans_begin();
                    // 修改事件状态为'未审核'
                    $this->db->where(array('id' => $event_id, 'state' => '已指派'))->update('event', array('state' => '未审核', 'end_time' => $time));
                    // 指派表状态为'已完成'
                    $this->db->where(array('event_id' => $event_id, 'state' => '处理中'))->update('event_designate', array('state' => '已完成'));
                    if ($this->db->trans_status() === FALSE) {
                        $this->db->trans_rollback();
                        return $this->param_error;
                    } else {
                        $this->db->trans_commit();
                        return $this->success;
                    }
                } else {
                    return $this->privilege_error;
                }
                break;
            default:
                return $this->param_error;
        }
    }


    /**
     * @param int $uid
     * @param array $gid
     * @param int $event_id
     * @return bool
     */
    protected function is_main_processor($uid, $gid, $event_id)
    {
        $event = $this->db->select('main_processor, group')->get_where('event', array('id' => $event_id))->row();
        if (in_array($event->group, $gid) || $event->main_processor == $uid) {
            return true;
        } else {
            return false;
        }
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