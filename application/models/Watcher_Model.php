<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/13
 * Time: 15:36
 */
//督察组模型

class Watcher_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }


    /**
     * @param $pInfo
     * @return mixed
     * 获取所有督查事件
     */
    public function get_all_watch_list($pInfo){
        $uid = $this->session->userdata('uid');
        //高级条件检索

        //查询条件构造
        $condition = array();

        // 开始时间条件
        if ($pInfo["start_start"] != 0 && $pInfo["start_end"] != 0) {
            $condition[] = "event.start_time > " . $pInfo["start_start"] . " AND event.start_time < " . ($pInfo["start_end"] + 86400);
        } else if ($pInfo["start_start"] != 0 && $pInfo["start_end"] == 0) {
            $condition[] = "event.start_time >= " . $pInfo["start_start"];
        } else if ($pInfo["start_start"] == 0 && $pInfo["start_end"] != 0) {
            $condition[] = "event.start_time < " . ($pInfo["start_end"] + 86400);
        }

        // 状态条件
        if ($pInfo["state"]) {
            $condition[] = "event.state = '" . $pInfo["state"] . "'";
        }

        // 等级条件
        if ($pInfo["rank"]) {
            $condition[] = "event.rank = '" . $pInfo["rank"] . "'";
        }

        $where = "";
        foreach ($condition AS $c) {
            if ($condition) {
                $where .= $c . " AND ";
            }
        }
        $where = substr($where, 0, strlen($where) - 4);

        //执行查询语句

        if ($where) {
            $data['aaData'] = $this->db->select("event.id, event.title, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
                ->from("event")
                ->join("user A", "A.id = event.manager", "left")
                ->join("user B", "B.id = event.main_processor", "left")
                ->join("group C", "C.id = event.group", "left")
                ->join("event_watch D", "D.event_id = event.id", "left")
                ->where($where)
                ->where("D.watcher = 5")
                ->group_start()
                ->like("event.id", $pInfo["search"])
                ->or_like("A.name", $pInfo["search"])
                ->or_like("B.name", $pInfo["search"])
                ->or_like("C.name", $pInfo["search"])
                ->or_like("event.title", $pInfo["search"])
                ->group_end()
                ->order_by("start_time", $pInfo["sort_start"])
                ->order_by("end_time", $pInfo["sort_end"])
                ->limit($pInfo["length"], $pInfo["start"])
                ->get()->result_array();

            //查询总记录条数
            $total = $this->db->select("event.id")
                ->from("event")
                ->join("user A", "A.id = event.manager", "left")
                ->join("user B", "B.id = event.main_processor", "left")
                ->join("group C", "C.id = event.group", "left")
                ->join("event_watch D", "D.event_id = event.id", "left")
                ->where($where)
                ->where("D.watcher = $uid")
                ->group_start()
                ->like("event.id", $pInfo["search"])
                ->or_like("A.name", $pInfo["search"])
                ->or_like("B.name", $pInfo["search"])
                ->or_like("C.name", $pInfo["search"])
                ->or_like("event.title", $pInfo["search"])
                ->group_end()
                ->get()->num_rows();
        } else {
            $data['aaData'] = $this->db->select("event.id, event.title, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
                ->from("event")
                ->join("user A", "A.id = event.manager", "left")
                ->join("user B", "B.id = event.main_processor", "left")
                ->join("group C", "C.id = event.group", "left")
                ->join("event_watch D", "D.event_id = event.id", "left")
                ->where("D.watcher = $uid")
                ->group_start()
                ->like("event.id", $pInfo["search"])
                ->or_like("A.name", $pInfo["search"])
                ->or_like("B.name", $pInfo["search"])
                ->or_like("C.name", $pInfo["search"])
                ->or_like("event.title", $pInfo["search"])
                ->group_end()
                ->order_by("start_time", $pInfo["sort_start"])
                ->order_by("end_time", $pInfo["sort_end"])
                ->limit($pInfo["length"], $pInfo["start"])
                ->get()->result_array();

            //查询总记录条数
            $total = $this->db->select("event.id")
                ->from("event")
                ->join("user A", "A.id = event.manager", "left")
                ->join("user B", "B.id = event.main_processor", "left")
                ->join("group C", "C.id = event.group", "left")
                ->join("event_watch D", "D.event_id = event.id", "left")
                ->where("D.watcher = $uid")
                ->group_start()
                ->like("event.id", $pInfo["search"])
                ->or_like("A.name", $pInfo["search"])
                ->or_like("B.name", $pInfo["search"])
                ->or_like("C.name", $pInfo["search"])
                ->or_like("event.title", $pInfo["search"])
                ->group_end()
                ->get()->num_rows();
        }

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }

}