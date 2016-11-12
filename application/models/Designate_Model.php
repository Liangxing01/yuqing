<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate_Model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    /**
     * 未确认信息 分页数据
     * @param $pInfo
     * @return array
     */
    public function info_not_handle_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("info.id, info.title, source, user.name AS publisher, time")
            ->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where("state !=", 2)
            ->group_start()
            ->like("info.id", $pInfo["search"])
            ->or_like("info.source", $pInfo["search"])
            ->or_like("user.name", $pInfo["search"])
            ->or_like("info.title", $pInfo["search"])
            ->group_end()
            ->order_by("time", $pInfo["sort_type"])
            ->limit($pInfo["length"], $pInfo["start"])
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where("state !=", 2)
            ->group_start()
            ->like("info.id", $pInfo["search"])
            ->or_like("info.source", $pInfo["search"])
            ->or_like("user.name", $pInfo["search"])
            ->or_like("info.title", $pInfo["search"])
            ->group_end()
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * 已确认信息 分页数据
     * @param $pInfo
     * @return array
     */
    public function info_is_handle_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("info.id, info.title, source, type.name AS type, user.name AS publisher, time")
            ->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->join("type", "type.id = info.type", "left")
            ->where("state = 2 AND info.id NOT IN (SELECT info_id FROM yq_event_info)")
            ->group_start()
            ->like("info.id", $pInfo["search"])
            ->or_like("info.source", $pInfo["search"])
            ->or_like("type.name", $pInfo["search"])
            ->or_like("user.name", $pInfo["search"])
            ->or_like("info.title", $pInfo["search"])
            ->group_end()
            ->order_by("time", $pInfo["sort_type"])
            ->limit($pInfo["length"], $pInfo["start"])
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->join("type", "type.id = info.type", "left")
            ->where("state = 2 AND info.id NOT IN (SELECT info_id FROM yq_event_info)")
            ->group_start()
            ->like("info.id", $pInfo["search"])
            ->or_like("info.source", $pInfo["search"])
            ->or_like("type.name", $pInfo["search"])
            ->or_like("user.name", $pInfo["search"])
            ->or_like("info.title", $pInfo["search"])
            ->group_end()
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * TODO
     * 查询 上报信息 详情
     * @param $info_id
     * @return array
     */
    public function get_info($info_id)
    {
        return $this->db->select("info.id, info.title, info.description, info.type, info.url, info.state, info.picture, info.source, user.name AS publisher, info.time")
            ->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("info.id" => $info_id))
            ->get()->row_array();
    }


    /**
     * TODO
     * 查询 事件信息 详情
     * @param $event_id
     * @param $info_id
     */
    public function get_event_info($event_id, $info_id){
        $this->load->model("Common_Model", "common");
        $e = $this->common->check_can_see($event_id);
        $i = $this->db->select("id")->where(array("info_id" => $info_id, "event_id" => $event_id))->get("event_info")->num_rows();
        echo $i;
    }


    /**
     * 设置 上报信息 为已看状态
     * @param $info_id
     * @return bool
     */
    public function set_info_seen($info_id)
    {
        return $this->db->set(array("state" => 1))->where(array("info.id" => $info_id, "state" => 0))->update("info");
    }


    /**
     * 上报信息 确认
     * @param $data
     * @return bool
     */
    public function info_commit($data)
    {
        return $this->db->set(array("type" => $data["type"], "duplicate" => $data["duplicate"], "state" => 2, "source" => $data["source"]))
            ->where(array("id" => $data["id"]))
            ->update("info");
    }


    /**
     * 获取 信息类型 列表
     * @return array
     */
    public function get_info_type()
    {
        return $this->db->get("type")->result_array();
    }


    /**
     * 事件检索 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function event_search_pagination($pInfo)
    {
        //查询条件构造
        $condition = array();

        // 开始时间条件
        if ($pInfo["start_start"] != 0 && $pInfo["start_end"] != 0) {
            $condition[] = "start_time > " . $pInfo["start_start"] . " AND start_time < " . ($pInfo["start_end"] + 86400);
        } else if ($pInfo["start_start"] != 0 && $pInfo["start_end"] == 0) {
            $condition[] = "start_time >= " . $pInfo["start_start"];
        } else if ($pInfo["start_start"] == 0 && $pInfo["start_end"] != 0) {
            $condition[] = "start_time < " . ($pInfo["start_end"] + 86400);
        }

        // 完成时间条件
        if ($pInfo["end_start"] != 0 && $pInfo["end_end"] != 0) {
            $condition[] = "end_time > " . $pInfo["end_start"] . " AND end_time < " . ($pInfo["end_end"] + 86400);
        } else if ($pInfo["end_start"] != 0 && $pInfo["end_end"] == 0) {
            $condition[] = "end_time >= " . $pInfo["end_start"];
        } else if ($pInfo["end_start"] == 0 && $pInfo["end_end"] != 0) {
            $condition[] = "end_time < " . ($pInfo["end_end"] + 86400);
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
                ->where($where)
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
                ->where($where)
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


    /**
     * 上报信息检索 分页数据
     * @param $pInfo
     * @return array
     */
    public function info_search_pagination($pInfo)
    {
        //查询条件构造
        $condition = array();

        // 时间条件
        if ($pInfo["start_time"] != 0 && $pInfo["end_time"] != 0) {
            $condition[] = "time > " . $pInfo["start_time"] . " AND time < " . ($pInfo["end_time"] + 86400);
        } else if ($pInfo["start_time"] != 0 && $pInfo["end_time"] == 0) {
            $condition[] = "time >= " . $pInfo["start_time"];
        } else if ($pInfo["start_time"] == 0 && $pInfo["end_time"] != 0) {
            $condition[] = "time < " . ($pInfo["end_time"] + 86400);
        }

        // 状态条件
        if ($pInfo["state"] == "已确认") {
            $condition[] = "info.state = 2 ";
        } else if ($pInfo["state"] == "未确认") {
            $condition[] = "info.state < 2 ";
        }

        // 类型条件
        if ($pInfo["type"]) {
            $condition[] = "info.type = " . $pInfo["type"];
        }

        // 重复条件
        if ($pInfo["duplicate"] == "重复") {
            $condition[] = "info.duplicate = 1";
        } else if ($pInfo["duplicate"] == "不重复") {
            $condition[] = "info.duplicate = 0";
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
            $data['aaData'] = $this->db->select("info.id, info.title, source, type.name AS type, user.name AS publisher, time, duplicate, state")
                ->from("info")
                ->join("user", "user.id = info.publisher", "left")
                ->join("type", "type.id = info.type", "left")
                ->where($where)
                ->group_start()
                ->like("info.id", $pInfo["search"])
                ->or_like("info.source", $pInfo["search"])
                ->or_like("type.name", $pInfo["search"])
                ->or_like("user.name", $pInfo["search"])
                ->or_like("info.title", $pInfo["search"])
                ->group_end()
                ->order_by("time", $pInfo["sort_type"])
                ->limit($pInfo["length"], $pInfo["start"])
                ->get()->result_array();

            //查询总记录条数
            $total = $this->db->select("info.id")->from("info")
                ->join("user", "user.id = info.publisher", "left")
                ->join("type", "type.id = info.type", "left")
                ->where($where)
                ->group_start()
                ->like("info.id", $pInfo["search"])
                ->or_like("info.source", $pInfo["search"])
                ->or_like("type.name", $pInfo["search"])
                ->or_like("user.name", $pInfo["search"])
                ->or_like("info.title", $pInfo["search"])
                ->group_end()
                ->get()->num_rows();
        } else {
            $data['aaData'] = $this->db->select("info.id, info.title, source, type.name AS type, user.name AS publisher, time, duplicate, state")
                ->from("info")
                ->join("user", "user.id = info.publisher", "left")
                ->join("type", "type.id = info.type", "left")
                ->group_start()
                ->like("info.id", $pInfo["search"])
                ->or_like("info.source", $pInfo["search"])
                ->or_like("type.name", $pInfo["search"])
                ->or_like("user.name", $pInfo["search"])
                ->or_like("info.title", $pInfo["search"])
                ->group_end()
                ->order_by("time", $pInfo["sort_type"])
                ->limit($pInfo["length"], $pInfo["start"])
                ->get()->result_array();

            //查询总记录条数
            $total = $this->db->select("info.id")->from("info")
                ->join("user", "user.id = info.publisher", "left")
                ->join("type", "type.id = info.type", "left")
                ->group_start()
                ->like("info.id", $pInfo["search"])
                ->or_like("info.source", $pInfo["search"])
                ->or_like("type.name", $pInfo["search"])
                ->or_like("user.name", $pInfo["search"])
                ->or_like("info.title", $pInfo["search"])
                ->group_end()
                ->get()->num_rows();
        }

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * TODO
     * 查询 event(事件) 详情
     * @param $event_id
     * @return mixed
     */
    public function get_event($event_id)
    {
        $event = $this->db->select("event.id, event.title, event.description, reply_time, A.name AS manager, B.name AS main_processor, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
            ->from("event")
            ->join("user A", "A.id = event.manager", "left")
            ->join("user B", "B.id = event.main_processor", "left")
            ->join("group C", "C.id = event.group", "left")
            ->where("event.id", $event_id)
            ->get()->row_array();

        $event["info"] = $this->db->select("info.id, info.title")
            ->from("info")
            ->where("info.id IN (SELECT `info_id` FROM `yq_event_info` WHERE `event_id` = $event_id)")
            ->get()->result_array();

        $event["relate_event"] = $this->db->select("event.id, event.title")
            ->from("event")
            ->where("event.id IN (SELECT `relate_id` FROM `yq_event_relate` WHERE `event_id` = $event_id)")
            ->get()->result_array();

        $event["attachment"] = $this->db->select("event_id, name, url, type")
            ->from("event_attachment")
            ->where("event_id", $event_id)
            ->get()->result_array();

        return $event;
    }


    /**
     * 生成组织关系树的 Json数据
     * @return Json字符串
     */
    public function get_relation_tree()
    {
        $root = $this->db->query("SELECT lft, rgt FROM yq_relation WHERE name='组织关系'")->first_row('array');

        // 以一个空的$right栈开始
        $right = array();

        // 获得root节点的所有子节点
        $sql = "SELECT name, lft, rgt, type, uid FROM yq_relation WHERE lft BETWEEN ? AND ? ORDER BY lft ASC";
        $result = $this->db->query($sql, array($root['lft'], $root['rgt']))->result_array();

        $tree_json = "";
        // 显示
        foreach ($result AS $row) {
            // 检查栈里面有没有元素
            if (count($right) > 0) {

                // 检查我们是否需要从栈中删除一个节点
                while ($right[count($right) - 1]["rgt"] < $row['rgt']) {
                    if ($right[count($right) - 1]["rgt"] - $right[count($right) - 1]["lft"] != 1) {
                        $tree_json .= "]},";
                    } else {
                        $tree_json .= "},";
                    }
                    array_pop($right);
                }

                //判断是否为父节点
                if ($row["rgt"] - $row["lft"] != 1) {
                    $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",open:false,children:[";
                } else {
                    $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"];
                }

            } else {
                $tree_json .= "{name:'" . $row["name"] . "'";
                if ($row["rgt"] - $row["lft"] != 1) {
                    $tree_json .= ",id:0,open:true,children:[";
                }
            }

            // 把这个节点添加到栈中
            $right[] = $row;
        }

        //闭合括号
        while (!empty($right)) {
            if ($right[count($right) - 1]["rgt"] - $right[count($right) - 1]["lft"] != 1) {
                $tree_json .= "]}";
            } else {
                $tree_json .= "}";
            }
            array_pop($right);
        }

        $tree_json = str_replace(",]", "]", $tree_json);
        return $tree_json;
    }


    /**
     * TODO
     * 事件指派 插入指派数据
     * @param $data
     * @return mixed
     */
    public function event_designate($data)
    {
        //添加事件信息
//        $event_info = array();
//        foreach($data AS $key ){}

        $processors = explode(",", $data["processor"]);
        $insert = array();
        $time = time();
        $manager_id = $this->session->userdata("uid");
        foreach ($processors AS $processor_id) {
            $insert[] = array(
                "event_id" => $data["event_id"],
                "description" => $data["description"],
                "manager" => $manager_id,
                "processor" => $processor_id,
                "time" => $time
            );
        }

        //TODO 检测是否重复指派
        $result = $this->db->where("id", $data["event_id"])->update("event", array("state" => "已指派"));

        return $this->db->insert_batch("event_designate", $insert);
    }

}