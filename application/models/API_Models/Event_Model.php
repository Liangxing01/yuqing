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
    public function get_response_timeline_data($event_id, $parent_id)
    {
        // 检查权限
        if (!$this->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 检查参数
//        if (is_int($page_num) && is_int($size)) {
//            $start = ($page_num - 1) * $size;
//        } else {
//            return $this->param_error;
//        }
        if (is_null($event_id)) {
            return $this->param_error;
        }
        if (is_null($parent_id)) {
            $type = 'f_record';
//            return $this->param_error;
        } else {
            $type = 'c_record';
        }
        // 查询数据
        switch ($type) {
            case 'f_record':
                // 返回父评论
//                $data = $this->db->select("event_log.id, event_log.speaker, group.name AS group, user.name, event_log.description, event_log.time, user.avatar, (select count(id) from yq_event_log AS log where log.event_id = $event_id AND log.pid = yq_event_log.id) AS comments_num")
//                    ->from("event_log")
//                    ->join("user", "event_log.speaker = user.id")
//                    ->join("user_group", "user_group.uid = event_log.speaker", "left")
//                    ->join("group", "group.id = user_group.gid", "left")
//                    ->where(array("event_log.event_id" => $event_id, "pid" => null))
//                    ->limit($size, $start)
//                    ->order_by("time", "desc")
//                    ->get()->result_array();
//                $total = $this->db->where(array("event_log.event_id" => $event_id, "pid" => null))->get("event_log")->num_rows(); // 总条数
//                foreach ($data AS $key => $val) {
//                    $data[$key]['usertype'] = $this->verify->is_watcher($val['speaker']) ? 1 : 0;
//                }
                $this->load->model('Common_Model', 'common_model');
                $data = $this->common_model->get_all_logs_by_id($event_id);
                $this->success['data'] = $data === false ? array() : $data;
//                $this->success['total'] = $total;
                return $this->success;
                break;
            case 'c_record':
                // 返回子评论
                $data = $this->db->select("event_log.id, group.name AS group, user.name, event_log.description AS desc, event_log.time, user.avatar, event_log.pid, event_log.speaker")
                    ->from("event_log")
                    ->join("user", "event_log.speaker = user.id")
                    ->join("user_group", "user_group.uid = event_log.speaker", "left")
                    ->join("group", "group.id = user_group.gid", "left")
                    ->where("pid", $parent_id)
//                    ->limit($size, $start)
                    ->order_by("time", "desc")
                    ->get()->result_array();
                foreach ($data AS $key => $val) {
                    $data[$key]['usertype'] = $this->verify->is_watcher($val['speaker']) ? 1 : 0;
                }
//                $total = $this->db->where("pid", $parent_id)->get("event_log")->num_rows(); // 总条数
                $this->success['data'] = $data;
//                $this->success['total'] = $total;
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
     * @param string $keyword
     * @return array
     */
    public function get_event_list_data($page_num, $size, $data_type, $user_type, $keyword)
    {
        $uid = $this->session->uid;
        $gid = explode(',', $this->session->userdata('gid')); // array 用户的组ID
        // 检查权限
        switch ($user_type) {
            case "manager":
                if (!($this->verify->is_manager($uid))) {
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
        $keyword = is_null($keyword) ? "" : $keyword;
        // 查询数据
        $user_data_type = $user_type . "_" . $data_type;
        switch ($user_data_type) {
            case 'manager_all_event':
                $data = $this->db->select("e.id AS event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event AS e")
                    ->like("e.title", $keyword)
                    ->join('user AS u', 'u.id = e.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = e.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->order_by("e.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->like("event.title", $keyword)
                    ->from("event")
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case 'manager_doing_event':
                $data = $this->db->select("e.id AS event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event AS e")
                    ->where("e.state", "已指派")
                    ->like("e.title", $keyword)
                    ->join('user AS u', 'u.id = e.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = e.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->order_by("e.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->where("event.state", "已指派")
                    ->like("event.title", $keyword)
                    ->from("event")
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "manager_verify_event":
                // 未审核事件列表
                $data = $this->db->select("e.id AS event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event AS e")
                    ->join('user AS u', 'u.id = e.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = e.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where("e.state", "未审核")
                    ->like("e.title", $keyword)
                    ->order_by("e.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->from("event")
                    ->where("event.state", "未审核")
                    ->like("event.title", $keyword)
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "processor_undo":
                // 未处理列表
                $data = $this->db->select("d.event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, d.state")
                    ->from("event_designate AS d")
                    ->join('event AS e', 'e.id = d.event_id', 'left')
                    ->join('user AS u', 'u.id = d.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = d.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where(array("d.state" => "未处理"))
                    ->like("e.title", $keyword)
                    ->group_start()
                    ->where('d.processor', $uid)
                    ->or_where_in('d.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('d.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->where(array("event_designate.state" => "未处理"))
                    ->like("event.title", $keyword)
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
                $data = $this->db->select("d.event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, d.state")
                    ->from("event_designate AS d")
                    ->join('event AS e', 'e.id = d.event_id', 'left')
                    ->join('user AS u', 'u.id = d.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = d.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where(array("d.state" => "处理中"))
                    ->like("e.title", $keyword)
                    ->group_start()
                    ->where('d.processor', $uid)
                    ->or_where_in('d.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('d.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->where(array("event_designate.state" => "处理中"))
                    ->like("event.title", $keyword)
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "processor_verify":
                // 审核中列表
                $data = $this->db->select("d.event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, '审核中' AS state")
                    ->from("event_designate AS d")
                    ->join('event AS e', 'e.id = d.event_id', 'left')
                    ->join('user AS u', 'u.id = d.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = d.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where(array("e.state" => "未审核"))
                    ->like("e.title", $keyword)
                    ->group_start()
                    ->where('d.processor', $uid)
                    ->or_where_in('d.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('d.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->where(array("event.state" => "未审核"))
                    ->like("event.title", $keyword)
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
                $data = $this->db->select("d.event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event_designate AS d")
                    ->join('event AS e', 'e.id = d.event_id', 'left')
                    ->join('user AS u', 'u.id = d.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = d.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where("e.state", "已完成")
                    ->group_start()
                    ->where('d.processor', $uid)
                    ->or_where_in('d.group', $gid)
                    ->group_end()
                    ->like("e.title", $keyword)
                    ->limit($size, $start)
                    ->order_by('d.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->where("event.state", "已完成")
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->like("event.title", $keyword)
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "processor_all":
                // 处理中事件
                $data = $this->db->select("d.event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, d.state")
                    ->from("event_designate AS d")
                    ->join('event AS e', 'e.id = d.event_id', 'left')
                    ->join('user AS u', 'u.id = d.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = d.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->group_start()
                    ->where(array("d.state" => "未处理"))
                    ->or_where(array("d.state" => "处理中"))
                    ->group_end()
                    ->like("e.title", $keyword)
                    ->group_start()
                    ->where('d.processor', $uid)
                    ->or_where_in('d.group', $gid)
                    ->group_end()
                    ->limit($size, $start)
                    ->order_by('d.time', "desc")
                    ->get()->result_array();
                $total = $this->db->select("event_designate.event_id")
                    ->from("event_designate")
                    ->join('event', 'event.id = event_designate.event_id', 'left')
                    ->group_start()
                    ->where(array("event_designate.state" => "未处理"))
                    ->or_where(array("event_designate.state" => "处理中"))
                    ->group_end()
                    ->like("event.title", $keyword)
                    ->group_start()
                    ->where('event_designate.processor', $uid)
                    ->or_where_in('event_designate.group', $gid)
                    ->group_end()
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "watcher_important":
                $data = $this->db->select("e.id AS event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event AS e")
                    ->like("e.title", $keyword)
                    ->join('user AS u', 'u.id = e.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = e.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->where('e.rank', 'Ⅰ级特大舆情')
                    ->order_by("e.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->like("event.title", $keyword)
                    ->where('event.rank', 'Ⅰ级特大舆情')
                    ->from("event")
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "watcher_all":
                $data = $this->db->select("e.id AS event_id, e.title, e.rank, e.start_time, e.end_time, u.name AS manager, mg.name AS manager_group, m.name AS main_processor, pg.name AS main_processor_group, g.name AS main_group, e.state")
                    ->from("event AS e")
                    ->like("e.title", $keyword)
                    ->join('user AS u', 'u.id = e.manager', 'left')
                    ->join('user AS m', 'm.id = e.main_processor', 'left')
                    ->join('group AS g', 'g.id = e.group', 'left')
                    ->join('user_group AS m_ug', 'm_ug.uid = e.manager', 'left')
                    ->join('group AS mg', 'mg.id = m_ug.gid', 'left')
                    ->join('user_group AS p_ug', 'p_ug.uid = e.main_processor', 'left')
                    ->join('group AS pg', 'pg.id = p_ug.gid', 'left')
                    ->order_by("e.start_time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("event.id")
                    ->like("event.title", $keyword)
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
     * @param string $user_type 用户类型 manager|processor|watcher
     * @return array
     */
    public function get_event_detail_data($event_id, $user_type)
    {
        // 检查权限
        if (!$this->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        // 检查参数
        if (is_null($event_id)) {
            return $this->param_error;
        }
        // 查询数据
        $data = array();
        $data["detail"] = $this->db->select("event.id, event.title, event.description, first_reply AS reply_time, A.name AS manager, E.name AS manager_group, B.name AS main_processor, G.name AS main_processor_group, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
            ->from("event")
            ->join("user A", "A.id = event.manager", "left")
            ->join("user B", "B.id = event.main_processor", "left")
            ->join("group C", "C.id = event.group", "left")
            ->join("user_group D", "D.uid = event.manager", "left")
            ->join("group E", "D.gid = E.id", "left")
            ->join("user_group F", "F.uid = event.main_processor", "left")
            ->join("group G", "F.gid = G.id", "left")
            ->where("event.id", $event_id)
            ->get()->row_array();
        if ($data['detail']['state'] == '未审核') {
            switch ($user_type) {
                case 'manager':
                    break;
                case 'processor':
                    $data['detail']['state'] = '审核中';
                    break;
                case 'watcher':
                    $data['detail']['state'] = '审核中';
                    break;
                default:
                    break;
            }
        }
        // 信息列表
        $data["info_list"] = $this->db->select("info.id, info.title, info.time")
            ->from("info")
            ->where("info.id IN (SELECT `info_id` FROM `yq_event_info` WHERE `event_id` = $event_id)")
            ->get()->result_array();
        // 关联事件
        $data["relate_event"] = $this->db->select("event.id, event.title, event.start_time")
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
        if (!$this->can_see_event($event_id)) {
            return $this->privilege_error;
        }
        if (!$this->is_event_completed($event_id)) {
            $this->param_error['message'] = "事件已完成,不可再回复";
            return $this->param_error;
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
     * 事件审核
     * @param int $event_id 事件ID
     * @param string $option 操作
     * @return array
     */
    public function event_check($event_id, $option)
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
     * 事件状态更新
     * @param int $event_id 事件ID
     * @return array
     */
    public function event_state_update($event_id)
    {
        $this->db->where(array('event_id' => $event_id, 'state' => '未处理'))
            ->update('event_designate', array("state" => "处理中"));
        return $this->success;
    }


    /**
     * 查询事件 回复日志
     * @param $event_id
     * @return array
     */
    public function get_all_logs_by_id($event_id)
    {
        $this->load->model('Common_Model', 'common_model');
        $data = $this->common_model->get_all_logs_by_id($event_id);
        $this->success['data'] = $data === false ? array() : $data;
        return $this->success;
    }


    /**
     * 判断是否是事件负责人
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


    /**
     * 判断事件是否完成
     * @param $eid
     * @return bool
     */
    protected function is_event_completed($eid)
    {
        $result = $this->db->select("id")->from("event")->where(array("id" => $eid, "state" => '已完成'))->get()->num_rows();
        return $result == 0;
    }


    /**
     * 判断是否可以查看事件
     * @param $event_id
     * @return bool
     */
    protected function can_see_event($event_id)
    {
        return $this->verify->can_see_event($event_id) || $this->verify->is_watcher();
    }
}