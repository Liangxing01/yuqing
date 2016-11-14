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
     * 查询 上报信息 详情
     * @param $info_id
     * @return array
     */
    public function get_info($info_id)
    {
        $info = $this->db->select("info.id, info.title, info.description, type.name AS type, info.url, info.state, info.source, user.name AS publisher, info.time")
            ->from("info")
            ->join("type", "type.id = info.type", "left")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("info.id" => $info_id))
            ->get()->row_array();
        //附件信息
        $info["attachment"] = $this->db->select("id, name, url, type")
            ->where("info_id", $info_id)
            ->get("info_attachment")->result_array();

        return $info;
    }


    /**
     * 查询 事件信息 详情
     * @param $event_id
     * @param $info_id
     * @return bool
     */
    public function get_event_info($event_id, $info_id)
    {
        $this->load->model("Common_Model", "common");
        $e_can = $this->common->check_can_see($event_id);
        $i = $this->db->select("id")->where(array("info_id" => $info_id, "event_id" => $event_id))->get("event_info")->num_rows();
        if (!($e_can && $i != 0)) {
            return false;
        } else {
            $info = $this->db->select("info.id, type.name AS type, info.url, info.source, info.description, user.name AS publisher, info.time")
                ->join("user", "user.id = info.publisher")
                ->join("type", "info.type = type.id", "left")
                ->where("info.id", $info_id)
                ->get("info")->row_array();

            //验证是否有指派人权限
            $privilege = explode(",", $this->session->privilege);
            if (!in_array(2, $privilege)) {
                unset($info["publisher"]);
            }

            $info["attachment"] = $this->db->select("id, name, url, type")
                ->where("info_id", $info_id)
                ->get("info_attachment")->result_array();

            return $info;
        }
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

        $event["attachment"] = $this->db->select("id, event_id, name, url, type")
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
     * 事件指派 插入指派数据
     * @param $data
     * @return bool
     */
    public function event_designate($data)
    {
        //TODO 添加事务流程
        //TODO 检测是否重复指派

        $time = time();
        $manager = $this->session->uid; //首派人

        //获得处理人(单位)ID
        $processors = array(
            "user" => array(),
            "group" => array()
        );
        foreach (explode(",", $data["processor"]) AS $processor) {
            $temp = explode("_", $processor);
            if ($temp[0] == "1") {
                $processors["user"][] = $temp[1];
            } else {
                $processors["group"][] = $temp[1];
            }
        }

        //获得督办人ID
        $watchers = array();
        foreach (explode(",", $data["watcher"]) AS $w) {
            $temp = explode("_", $w);
            $watchers[] = $temp[1];
        }

        //获得牵头人(单位)
        $main = explode("_", $data["main_processor"]);
        if ($main[0] == "1") {
            $main_processor = $main[1];
            $main_group = null;
        } else {
            $main_processor = null;
            $main_group = $main[1];
        }


        //添加事件表
        $event = array(
            "title" => $data["title"],             // 标题
            "manager" => $manager,                 // 首派人
            "rank" => $data["rank"],               // 事件等级
            "reply_time" => $data["reply_time"] ? $data["reply_time"] : NULL,   // 首回时间
            "description" => $data["description"], // 事件描述
            "main_processor" => $main_processor,   // 牵头人
            "group" => $main_group,                // 牵头单位
            "state" => "已指派",                    // 事件状态
            "start_time" => $time                  // 开始时间
        );
        $this->db->insert("event", $event);   //事件生成
        //获得事件ID
        $event_id = $this->db->insert_id();


        //添加事件信息关联表
        $info_id = explode(",", $data["info_id"]);
        $event_info = array();
        if(!empty($info_id)){
            foreach ($info_id AS $id) {
                $event_info[] = array(
                    "info_id" => $id,
                    "event_id" => $event_id
                );
            }
            $this->db->insert_batch("event_info", $event_info);   //事件信息关联
        }


        //添加事件指派表
        $event_designate = array();
        foreach ($processors["user"] AS $user) {
            $event_designate[] = array(
                "event_id" => $event_id,
                "manager" => $manager,
                "processor" => $user,
                "time" => $time,
                "description" => $data["description"],
                "state" => "未处理"
            );
        }
        foreach ($processors["group"] AS $group) {
            $event_designate[] = array(
                "event_id" => $event_id,
                "manager" => $manager,
                "group" => $group,
                "time" => $time,
                "description" => $data["description"],
                "state" => "未处理"
            );
        }
        $this->db->insert_batch("event_designate", $event_designate);  //事件指派


        //添加事件关联表
        $relate_event = array();
        foreach (explode(",", $data["relate_event"]) AS $id) {
            $relate_event[] = array(
                "relate_id" => $id,
                "event_id" => $event_id
            );
        }
        $this->db->insert_batch("event_relate", $relate_event);  //事件关联


        //添加事件督办表
        $event_watch = array();
        if (!empty($watchers)) {
            foreach ($watchers AS $watcher) {
                $event_watch[] = array(
                    "watcher" => $watcher,
                    "event_id" => $event_id
                );
            }
            $this->db->insert_batch("event_watch", $event_watch);  //事件督办
        }


        //添加事件参考文件表
        $event_attachment = array();
        if (!empty($data["attachment"])) {
            foreach ($data["attachment"] AS $attachment) {
                $event_attachment[] = array(
                    "event_id" => $event_id,
                    "name" => $attachment["name"],
                    "url" => $attachment["url"],
                    "type" => "document"
                );
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $attachment['new_name'])) {
                    copy($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $attachment['new_name'], $_SERVER['DOCUMENT_ROOT'] . "/uploads/document/" . $attachment['new_name']);
                    unlink($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $attachment['new_name']);
                }
            }
            $this->db->insert_batch("event_attachment", $event_attachment);  //事件参考文件
        }


        //添加事件报警表
        if ($data["reply_time"]) {
            $event_alert = array();
            foreach ($processors["user"] AS $u) {
                $event_alert[] = array(
                    "event_id" => $event_id,
                    "uid" => $u,
                    "title" => $data["title"],
                    "state" => 1,
                    "time" => $time + $data["reply_time"] * 60
                );
            }
            //指派人报警
            $event_alert[] = array(
                "event_id" => $event_id,
                "uid" => $manager,
                "title" => $data["title"],
                "state" => 1,
                "time" => $time + $data["reply_time"] * 60
            );
            $this->db->insert_batch("event_alert", $event_alert);  //事件报警
        }

    }


    /**
     * 确认事件审核按钮显示
     * @param $eid
     * @return bool
     */
    public function check_done_btn($eid)
    {
        $ok = $this->db->select("id")->from("event")->where(array("id" => $eid, "state" => "未审核"))->get()->num_rows();
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 事件审核
     * @param $eid
     * @param $flag
     * @return bool
     */
    public function event_confirm_done($eid, $flag)
    {
        $n = $this->db->select("id")->where(array("id" => $eid, "state" => "未审核"))->get("event")->num_rows();
        if ($n == 1) {
            if ($flag == "ok") {
                return $this->db->set(array("state" => "已完成", "end_time" => time()))->where("id", $eid)->update("event");
            } elseif ($flag == "not") {
//                TODO 添加事务
                $r_1 = $this->db->set(array("state" => "处理中"))->where("event_id", $eid)->update("event_designate");
                $r_2 = $this->db->set(array("state" => "已指派"))->where("id", $eid)->update("event");
                return $r_1 && $r_2;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * 事件重启
     * @param $eid
     * @return bool
     */
    public function event_restart($eid)
    {
        $n = $this->db->select("id")->where(array("id" => $eid, "state" => "已完成"))->get("event")->num_rows();
        if ($n == 1) {
            //TODO 添加事务
            $r_1 = $this->db->set(array("state" => "已指派", "end_time" => NULL))->where("id", $eid)->update("event");
            $r_2 = $this->db->set(array("state" => "处理中"))->where("event_id", $eid)->update("event_designate");
            return $r_1 && $r_2;
        } else {
            return false;
        }
    }
}