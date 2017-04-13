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
            ->where("state !=", -1)//不是无效信息和已确认信息
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
            ->where("state !=", -1)//不是无效信息和已确认信息
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
            ->where("info.duplicate = 0 AND state = 2 AND info.id NOT IN (SELECT info_id FROM yq_event_info)")
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
            ->where("info.duplicate = 0 AND state = 2 AND info.id NOT IN (SELECT info_id FROM yq_event_info)")
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
        $info = $this->db->select("info.id,info.relate_scope, info.title, info.description, type.name AS type, info.url, info.state, info.source, user.name AS publisher, group.name AS group, info.time")
            ->from("info")
            ->join("type", "type.id = info.type", "left")
            ->join("user", "user.id = info.publisher", "left")
            ->join("user_group", "user_group.uid = info.publisher", "left")
            ->join("group", "group.id = user_group.gid", "left")
            ->where(array("info.id" => $info_id, "user_group.is_exist" => 1))
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
        $this->load->model("Verify_Model", "verify");
        $e_can = $this->verify->can_see_event($event_id);
        $i = $this->db->select("id")->where(array("info_id" => $info_id, "event_id" => $event_id))->get("event_info")->num_rows();
        //验证是否可查看事件信息
        if (!($e_can && $i != 0)) {
            return false;
        } else {
            $info = $this->db->select("info.id,info.relate_scope,type.name AS type, info.url, info.source, info.description, user.name AS publisher, info.time")
                ->join("user", "user.id = info.publisher")
                ->join("type", "info.type = type.id", "left")
                ->where("info.id", $info_id)
                ->get("info")->row_array();

            //非指派人不可查看上报人信息
            if (!$this->verify->is_manager()) {
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
        //判断信息是否是无效信息
        if ($data['trash'] == 1) {
            $state = -1;
        } else {
            $state = 2;
        }
        return $this->db->set(array("type" => $data["type"], "duplicate" => $data["duplicate"], "relate_scope" => $data['relate_scope'],
            "state" => $state, "source" => $data["source"]))
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
     * 移动端 提交记录 分页数据
     * @param $page_num 当前页码
     * @return string Json字符串
     */
    public function scroll_event_pagination($page_num)
    {
        $length = 10; //默认查出10条记录
        $start = ($page_num - 1) * 10;
        $result = $this->db->select("event.id, title, rank, user.name AS processor, group.name AS group, state, start_time")
            ->join("user", "user.id = event.main_processor", "left")
            ->join("group", "group.id = event.group", "left")
            ->limit($length, $start)
            ->order_by("start_time", "desc")
            ->get("event")->result_array();

        return json_encode($result);
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
     * 查询 event(事件) 详情
     * @param $event_id
     * @return mixed
     */
    public function get_event($event_id)
    {
        $event = $this->db->select("event.id, event.title, event.description, reply_time, A.name AS manager, E.name AS manager_group, B.name AS main_processor, G.name AS main_processor_group, C.name AS main_group, event.rank, event.state, event.start_time, event.end_time")
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
        $time = time();
        $manager = $this->session->uid; //首派人

        //获得处理人(单位)ID
        $processors = array(
            "user" => array(),
            "group" => array()
        );
        if ($data["processor"]) {
            foreach (explode(",", $data["processor"]) AS $processor) {
                $temp = explode("_", $processor);
                if ($temp[0] == "1") {
                    $processors["user"][] = $temp[1];
                } else {
                    $processors["group"][] = $temp[1];
                }
            }
        }


        //获得督办人ID
        $watchers = array();
        if ($data["watcher"]) {
            foreach (explode(",", $data["watcher"]) AS $w) {
                $temp = explode("_", $w);
                $watchers[] = $temp[1];
            }
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


        //事件表数据
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

        // 开始事件生成和指派事务流程
        $this->db->trans_begin();

        $this->db->insert("event", $event);   //事件生成
        $event_id = $this->db->insert_id();   //获得事件ID

        //事件信息关联表数据
        $event_info = array();
        if ($data["info_id"]) {
            $arr_info_id = explode(",", $data["info_id"]);
            foreach ($arr_info_id AS $id) {
                $event_info[] = array(
                    "info_id" => $id,
                    "event_id" => $event_id
                );
            }
        }


        //事件指派表数据
        $event_designate = array();
        foreach ($processors["user"] AS $user) {
            $event_designate[] = array(
                "event_id" => $event_id,
                "manager" => $manager,
                "processor" => $user,
                "group" => null,
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
                "processor" => null,
                "time" => $time,
                "description" => $data["description"],
                "state" => "未处理"
            );
        }


        //关联事件表数据
        $relate_event = array();
        if ($data["relate_event"]) {
            foreach (explode(",", $data["relate_event"]) AS $id) {
                $relate_event[] = array(
                    "relate_id" => $id,
                    "event_id" => $event_id
                );
            }
        }


        //事件督办表数据
        $event_watch = array();
        if (!empty($watchers)) {
            foreach ($watchers AS $watcher) {
                $event_watch[] = array(
                    "watcher" => $watcher,
                    "event_id" => $event_id
                );
            }
        }


        //事件参考文件表数据
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
                    @copy($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $attachment['new_name'], $_SERVER['DOCUMENT_ROOT'] . "/uploads/document/" . $attachment['new_name']);
                    @unlink($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $attachment['new_name']);
                }
            }
        }


        //事件报警表数据
        $event_alert = array();
        if ($data["reply_time"]) {
            foreach ($processors["user"] AS $u) {
                $event_alert[] = array(
                    "event_id" => $event_id,
                    "uid" => $u,
                    "title" => $data["title"],
                    "state" => 2,
                    "type" => 3,   //报警类型
                    "time" => $time + $data["reply_time"] * 60
                );
            }
            //指派人报警
            $event_alert[] = array(
                "event_id" => $event_id,
                "uid" => $manager,
                "title" => $data["title"],
                "state" => 2,
                "type" => 4,    //报警类型
                "time" => $time + $data["reply_time"] * 60
            );
        }


        // TODO 事件消息推送数据
        $event_msg = array();
        foreach ($processors["user"] AS $user) {
            $event_msg[] = array(
                "title" => $data["title"],
                "type" => 1,    //指派消息类型
                "send_uid" => $user,
                "send_gid" => null,
                "time" => $time,
                "url" => "/common/event_detail?eid=" . $event_id,
                "m_id" => $event_id,
                "state" => 0    //消息未读
            );
        }
        foreach ($processors["group"] AS $group) {
            $event_msg[] = array(
                "title" => $data["title"],
                "type" => 1,    //指派消息类型
                "send_uid" => null,
                "send_gid" => $group,
                "time" => $time,
                "url" => "/common/event_detail?eid=" . $event_id,
                "m_id" => $event_id,
                "state" => 0    //消息未读
            );
        }
        foreach ($watchers AS $watcher) {
            $event_msg[] = array(
                "title" => $data["title"],
                "type" => 2,    //督办消息类型
                "send_uid" => $watcher,
                "send_gid" => null,
                "time" => $time,
                "url" => "/common/event_detail?eid=" . $event_id,
                "m_id" => $event_id,
                "state" => 0    //消息未读
            );
        }


        //数据分表操作
        //事件信息关联
        if (!empty($event_info)) {
            $this->db->insert_batch("event_info", $event_info);
        } else {
            $this->db->trans_rollback();
            return false;
        }
        //事件指派
        if (!empty($event_designate)) {
            $this->db->insert_batch("event_designate", $event_designate);
        } else {
            $this->db->trans_rollback();
            return false;
        }
        //事件关联
        if (!empty($relate_event)) {
            $this->db->insert_batch("event_relate", $relate_event);
        }
        //事件督办
        if (!empty($event_watch)) {
            $this->db->insert_batch("event_watch", $event_watch);
        }
        //事件参考文件
        if (!empty($event_attachment)) {
            $this->db->insert_batch("event_attachment", $event_attachment);
        }
        //事件报警
        if (!empty($event_alert)) {
            $this->db->insert_batch("event_alert", $event_alert);
        }
        // TODO 事件消息
        if (!empty($event_msg)) {
            $this->db->insert_batch("business_msg", $event_msg);
        }


        // 事件指派事务提交
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            //TODO 业务信息推送
            try {
                //连接消息服务器
                $this->load->library("Gateway");
                Gateway::$registerAddress = $this->config->item("VM_registerAddress");

                //业务消息推送
                //用户 事件指派消息推送
                foreach ($event_msg AS $msg) {
                    if ($msg["send_uid"] !== null) {
                        Gateway::sendToUid($msg["send_uid"], json_encode(array(
                            "title" => $msg["title"],
                            "type" => $msg["type"],
                            "time" => $msg["time"],
                            "url" => $msg["url"]
                        )));
                    } else {
                        Gateway::sendToGroup($msg["send_gid"], json_encode(array(
                            "title" => $msg["title"],
                            "type" => $msg["type"],
                            "time" => $msg["time"],
                            "url" => $msg["url"]
                        )));
                    }
                }
            } catch (Exception $e) {
                log_message("error", $e->getMessage());
            }
            return true;
        }
    }

    /**
     * 事件改派 获取已指派信息
     * @param $eid
     * @return $info array
     */
    public function designate_info($eid)
    {
        $einfo = $this->db->select('event.title,event.description,event.rank,event.reply_time,event.main_processor')
            ->from('event')
            ->where('event.id', $eid)
            ->get()->row_array();


        $info_arr = $this->db->select('event.id as eid,info.id,info.title')
            ->from('event')
            ->join('event_info', 'event_info.event_id = event.id')
            ->join('info', 'info.id = event_info.info_id')
            ->where('event.id', $eid)
            ->get()->result_array();

        //合并 info_arr 形成info_list
        $einfo['info_list'] = $info_arr;

        //查询相关事件 形成 relate_list
        $relate_arr = $this->db->select('event_relate.relate_id,event.title')
            ->from('event_relate')
            ->join('event', 'event.id = event_relate.relate_id')
            ->where('event_relate.event_id', $eid)
            ->get()->result_array();
        $einfo['relate_list'] = $relate_arr;

        //查询事件 督办人
        $watcher_arr = $this->db->select('event_watch.watcher')
            ->from('event_watch')
            ->where('event_watch.event_id', $eid)
            ->get()->result_array();
        $einfo['watcher_list'] = $watcher_arr;

        return $einfo;
    }

    /**
     * 事件改派 删除原有 改派的一系列记录
     * @param eid
     * @return bool 删除成功 or 失败
     */
    public function del_designate($eid)
    {
        //一次删除 event_designate,event_att,event_info,event_log,event_watch表的数据
        $this->db->trans_begin();

        $this->db->where('m_id', $eid);
        $this->db->delete('yq_business_msg');

        $this->db->where('id', $eid);
        $this->db->delete('event');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_alert');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_designate');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_info');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_log');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_relate');

        $this->db->where('event_id', $eid);
        $this->db->delete('event_watch');

        //删除 事件绑定的文档
        $att_arr = $this->db->select('url')
            ->from('event_attachment')
            ->where('event_id', $eid)
            ->get()->result_array();

        //循环删除文件
        if (!empty($att_arr)) {
            foreach ($att_arr as $att) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $att['url']);
            }
        }

        //删除att 表里面的数据
        $this->db->where('event_id', $eid);
        $this->db->delete('event_attachment');

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }


    /**
     * 事件增派 插入数据
     * @param $data
     * @return bool
     */
    public function event_alter($data)
    {

        $event_id = $data["event_id"];
        $manager = $this->session->uid;
        $time = time();

        //获得处理人(单位)ID
        $processors = array(
            "user" => array(),
            "group" => array()
        );
        if ($data["processor"]) {
            $arr_processor = explode(",", $data["processor"]);
            foreach ($arr_processor AS $processor) {
                $temp = explode("_", $processor);
                if ($temp[0] == "1") {
                    $processors["user"][] = $temp[1];
                } else {
                    $processors["group"][] = $temp[1];
                }
            }
        }

        //获得督办人ID
        $watchers = array();
        if ($data["watcher"]) {
            $arr_watcher = explode(",", $data["watcher"]);
            foreach ($arr_watcher AS $w) {
                $temp = explode("_", $w);
                $watchers[] = $temp[1];
            }
        }

        //事件指派表数据
        $event_designate = array();
        if (!empty($processors["user"])) {
            foreach ($processors["user"] AS $user) {
                $event_designate[] = array(
                    "event_id" => $event_id,
                    "manager" => $manager,
                    "processor" => $user,
                    "group" => null,
                    "time" => $time,
                    "description" => null,
                    "state" => "未处理"
                );
            }
        }
        //单位事件
        if (!empty($processors["group"])) {
            foreach ($processors["group"] AS $group) {
                $event_designate[] = array(
                    "event_id" => $event_id,
                    "manager" => $manager,
                    "group" => $group,
                    "processor" => null,
                    "time" => $time,
                    "description" => null,
                    "state" => "未处理"
                );
            }
        }


        //事件督办表数据
        $event_watch = array();
        if (!empty($watchers)) {
            foreach ($watchers AS $watcher) {
                $event_watch[] = array(
                    "watcher" => $watcher,
                    "event_id" => $event_id
                );
            }
        }


        //事件信息表数据
        $event_info = array();
        if ($data["info_id"]) {
            $arr_info_id = explode(",", $data["info_id"]);
            foreach ($arr_info_id AS $item) {
                $event_info[] = array("event_id" => $event_id, "info_id" => $item);
            }
        }


        //事件关联表数据
        $event_relate = array();
        if ($data["relate_event"]) {
            $arr_relate_event = explode(",", $data["relate_event"]);
            foreach ($arr_relate_event AS $item) {
                $event_relate[] = array("event_id" => $event_id, "relate_id" => $item);
            }

        }


        // 事件增派事务开始
        $this->db->trans_begin();

        //数据分表操作
        //事件信息关联
        if (!empty($event_info)) {
            $this->db->insert_batch("event_info", $event_info);
        }
        //事件关联
        if (!empty($event_relate)) {
            $this->db->insert_batch("event_relate", $event_relate);
        }
        //事件指派
        if (!empty($event_designate)) {
            $this->db->insert_batch("event_designate", $event_designate);
        }
        //事件督办
        if (!empty($event_watch)) {
            $this->db->insert_batch("event_watch", $event_watch);
        }

        // 事件指派事务提交
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }

    }


    /**
     * 获取事件牵头人
     * @param $event_id
     * @return mixed
     */
    public function get_event_main($event_id)
    {
        return $this->db->select("group.name AS group, user.name AS processor")
            ->join("user", "user.id = event.main_processor", "left")
            ->join("group", "group.id = event.group", "left")
            ->where("event.id", $event_id)
            ->get("event")->row_array();
    }


    /**
     * 首回时间设置
     * @param $event_id
     * @return mixed
     * false         - 不显示
     * (array)result - 事件首回参数
     */
    public function show_reply_time_setting($event_id)
    {
        $result = $this->db->select("start_time, first_reply")
            ->where(array("id" => $event_id, "reply_time !=" => null))
            ->get("event")->row_array();
        if (empty($result)) {
            return false;
        } else {
            $result['start_time'] -= 30 * 24 * 3600;// 最小时间往前1个月
            return $result;
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
                //审核通过
                return $this->db->set(array("state" => "已完成", "end_time" => time()))->where("id", $eid)->update("event");
            } elseif ($flag == "not") {
                //审核不通过
                $this->db->trans_begin();
                $this->db->set(array("state" => "处理中"))->where("event_id", $eid)->update("event_designate");
                $this->db->set(array("state" => "已指派"))->where("id", $eid)->update("event");
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    return false;
                } else {
                    $this->db->trans_commit();
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * 提交 事件 首回时间
     * @param $event_id
     * @param $reply_time
     * @return bool
     */
    public function commit_event_reply_time($event_id, $reply_time)
    {
        $this->db->trans_begin();
        $this->db->where("id", $event_id)->update("event", array("first_reply" => $reply_time));
        $this->db->where(array("event_id" => $event_id, "state" => 1))->update("event_alert", array("state" => 0));
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
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
            //事件重启事务开始
            $this->db->trans_begin();
            $this->db->set(array("state" => "已指派", "end_time" => NULL))->where("id", $eid)->update("event");
            $this->db->set(array("state" => "处理中"))->where("event_id", $eid)->update("event_designate");
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return true;
            }
        } else {
            return false;
        }
    }


    /**
     * 修改事件等级
     * @param int $event_id 事件id
     * @param int $rank 事件等级
     */
    public function edit_event_rank($event_id, $rank)
    {
        return $this->db->set(array("rank" => $rank))->where("id", $event_id)->update("event");
    }


    /**
     * 检查url是否重复
     * @param $url
     * @return bool true = 重复; false = 不重复;
     */
    public function check_url($url, $id)
    {
        $res = $this->db->select('id')->from('info')
            ->where('url', $url)
            ->where('id !=' . $id)
            ->get()->num_rows();

        $rep_info = $this->db->select('id')->from('info')
            ->where('url', $url)
            ->order_by('time', 'ASC')
            ->get()->result_array();
        if ($res >= 1) {
            if ($rep_info[0]['id'] == $id) {
                //第一个上报人 不提示重复
                return array(
                    'res' => false
                );
            } else {
                //重复的id返回给前端
                $dup_id = '';
                foreach ($rep_info as $item) {
                    if ($item['id'] != $id) {
                        $dup_id .= $item['id'] . ',';
                    }
                }
                $dup_id = substr($dup_id, 0, -1);
                return array(
                    'res' => true,
                    'dup_id' => $dup_id
                );
            }

        } else {
            return array(
                'res' => false
            );
        }
    }


    /**
     * ---------------------------点名系统---------------------------
     */
    /**
     * 发起点名
     * @param $gids 被点名的组 id
     * @param $msg
     * @param $start_time 开始点名的时间偏移量
     */
    public function make_call($gids, $msg, $start_time)
    {
        //插入数据库操作，涉及到 call call_list call_response 三张表

        //开始事务
        $this->db->trans_begin();
        //插入 call表
        $insert_call = array(
            'uid' => $this->session->userdata('uid'),
            'message' => $msg,
            'start_time' => time() + $start_time,
            'time' => time()
        );
        $this->db->insert('call', $insert_call);
        $call_id = $this->db->insert_id();

        //插入 call_list表
        $insert_list = array();
        $g_arr = explode(',', $gids);
        foreach ($g_arr as $gid) {
            array_push($insert_list, array(
                'call_id' => $call_id,
                'gid' => $gid
            ));
        }
        $this->db->insert_batch('call_list', $insert_list);

        //插入 call_response表，默认设置state = 0
        $rep_info = array();
        foreach ($g_arr as $gid) {
            $uid_arr = $this->db->select('uid')->from('user_group')
                ->where('gid', $gid)
                ->get()->result_array();
            foreach ($uid_arr as $uid) {
                array_push($rep_info, array(
                    'call_id' => $call_id,
                    'gid' => $gid,
                    'uid' => $uid,
                    'state' => 0
                ));
            }
        }
        $this->db->insert_batch('call_response', $rep_info);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            //TODO 点名信息推送
            try {
                //连接消息服务器
                $this->load->library("Gateway");
                Gateway::$registerAddress = $this->config->item("VM_registerAddress");

                //业务消息推送
                //用户 事件指派消息推送
                foreach ($g_arr AS $gid) {
                    Gateway::sendToGroup($gid, json_encode(array(
                        "title" => '点名考勤',
                        "type" => 'call_name',
                        "time" => time(),
                        "msg" => $msg,
                        "gid" => $gid,
                        "call_id" => $call_id
                    )));
                }
            } catch (Exception $e) {
                log_message("error", $e->getMessage());
            }
            return true;
        }

    }

    /**
     *
     * 获得当前总在线人数
     * @return int
     */
    public function count_online_user()
    {
        $users = $this->db->select("id")->get("user")->result_array();
        $num = 0;
        try {
            //连接消息服务器
            $this->load->library("Gateway");
            Gateway::$registerAddress = $this->config->item("VM_registerAddress");

            //获取在线人数
            foreach ($users AS $user) {
                if (Gateway::isUidOnline($user["id"]) != 0) {
                    $num++;
                }
            }
            return $num;

        } catch (Exception $e) {
            log_message("error", $e->getMessage());
            return 0;
        }
    }


    /**
     * 获取点名组
     * @param $type
     * @param $keyword
     */
    public function get_call_list($type, $keyword)
    {
        if ($type == "area") {
            $root = $this->db->select("id, uid, name, lft, rgt")->where("name", "组织关系")->get("relation")->row_array();
            $leader = $this->db->select("id, uid, name, lft, rgt")->where("name", "区领导")->get("relation")->row_array();
            $work_group = $this->db->select("id, uid, name, lft, rgt")->where("name", "涉区楼盘专项工作组")->get("relation")->row_array();
            $street = $this->db->select("id, uid, name, lft, rgt")->where("name", "镇街")->get("relation")->row_array();
            $not_in = $this->db->select("id")
                ->where(array("lft >=" => $leader["lft"], "rgt <=" => $leader["rgt"]))
                ->or_group_start()
                ->where(array("lft >" => $work_group["lft"], "rgt <" => $work_group["rgt"]))
                ->group_end()
                ->or_group_start()
                ->where(array("lft >" => $street["lft"], "rgt <" => $street["rgt"]))
                ->group_end()
                ->get("relation")->result_array();
            $not_in_area = array();
            foreach ($not_in AS $node) {
                $not_in_area[] = $node["id"];
            }
            if ($keyword == null) {
                $result = $this->db->select("id, uid, name, type")
                    ->where("type", 0)
                    ->where(array("lft >" => $root["lft"], "rgt <" => $root["rgt"]))
                    ->where_not_in("id", $not_in_area)
                    ->get("relation")->result_array();
            } else {
                $result = $this->db->select("id, uid, name, type")
                    ->where("type", 0)
                    ->where(array("lft >" => $root["lft"], "rgt <" => $root["rgt"]))
                    ->where_not_in("id", $not_in_area)
                    ->like("name", $keyword)
                    ->get("relation")->result_array();
            }
        } else {
            $street = $this->db->select("id, uid, name, lft, rgt")->where("name", "镇街")->get("relation")->row_array();
            if ($keyword == null) {
                $result = $this->db->select("id, uid, name, type")
                    ->where("type", 0)
                    ->where(array("lft >" => $street["lft"], "rgt <" => $street["rgt"]))
                    ->get("relation")->result_array();
            } else {
                var_dump($keyword);
                $result = $this->db->select("id, uid, name, type")
                    ->where("type", 0)
                    ->where(array("lft >" => $street["lft"], "rgt <" => $street["rgt"]))
                    ->like("name", $keyword)
                    ->get("relation")->result_array();
            }
        }
        return $result;
    }


    /**
     * 获取通知列表
     * @param $pInfo
     * @return array
     */
    public function get_notice_list_data($pInfo)
    {
        $data['aaData'] = $this->db->select("announce.id, title, user.name AS sender, time")
            ->join("user", "user.id = announce.sender")
            ->where("announce.type", "all")
            ->order_by('announce.time', $pInfo['sort_type'])
            ->limit($pInfo['length'], $pInfo['start'])
            ->get("announce")->result_array();

        $total = $this->db->select("id")
            ->where("type", "all")
            ->get("announce")->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * 删除通知
     * @param int $notice_id
     * @return bool
     */
    public function delete_notice($notice_id)
    {
        return $this->db->where("id", $notice_id)->delete("announce");
    }


    /**
     * 查询 通知详情
     * @param int $notice_id
     * @return array
     */
    public function get_notice_detail($notice_id)
    {
        return $this->db->select("id, title, time, content")->where("id", $notice_id)->get("announce")->row_array();
    }


    /**
     * 发布新通知
     * @param string $title
     * @param string $content
     * @return bool
     */
    public function post_notice($title, $content)
    {
        $notice_data = array(
            "title" => $title,
            "content" => $content,
            "sender" => $this->session->uid,
            "type" => "all",
            "time" => time()
        );
        return $this->db->insert("announce", $notice_data);
    }
}