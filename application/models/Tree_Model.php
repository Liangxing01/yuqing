<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tree_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
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
        $sql = "SELECT id, name, lft, rgt, type, uid FROM yq_relation WHERE lft BETWEEN ? AND ? ORDER BY lft ASC";
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
                    if ($row["type"] == 0 || $row["type"] == 2) {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",tid:" . $row["id"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";
                    } else {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",tid:" . $row["id"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/admin.png',open:false,children:[";
                    }
                } else {
                    if ($row["type"] == 0 || $row["type"] == 2) {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",tid:" . $row["id"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png'";
                    } else {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",tid:" . $row["id"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/admin.png'";
                    }
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
     * 获取 邮件 通讯录列表
     * 排除 领导 和 工作组
     */
    public function get_email_tree()
    {
        $root = $this->db->query("SELECT lft, rgt FROM yq_relation WHERE name='组织关系'")->first_row('array');

        //查出区委办公室的 节点信息
        $quwei = $this->db->query("SELECT lft, rgt FROM yq_relation WHERE name='区委办公室'")->first_row('array');
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

                //排除 区领导 和 工作组
                if ($row['lft'] >= 2 && $row['rgt'] < $quwei['lft']) {
                    continue;
                }

                //判断是否为父节点
                if ($row["rgt"] - $row["lft"] != 1) {
                    if ($row["type"] == 0 || $row["type"] == 2) {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";
                    } else {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/admin.png',open:false,children:[";
                    }
                } else {
                    if ($row["type"] == 0 || $row["type"] == 2) {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png'";
                    } else {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/admin.png'";
                    }
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
     * 获得部门数据
     * @return Json字符串
     */
    public function get_group_tree()
    {
        $group = $this->db->select("group.id, group.name, relation.id AS tid")
            ->join("relation", "relation.uid = group.id", "left")
            ->where("relation.type = 0 or relation.type = 2")
            ->get("group")->result_array();
        $tree = array(
            "id" => 0,
            "tid" => 1,
            "name" => "单位",
            "open" => true,
            "children" => array()
        );
        foreach ($group AS $g) {
            $tree["children"][] = array(
                "id" => $g["id"],
                "tid" => $g["tid"],
                "name" => $g["name"],
                "icon" => '/assets/ztree/zTreeStyle/img/group.png'
            );
        }
        return json_encode($tree);
    }


    /**
     * 获得处理人(单位)数据
     * @return Json字符串
     */
    public function get_processor_group_tree()
    {
        $processor_group_id = $this->get_processor_group_id();

        $group_tree_nodes = $this->db->select("name, lft, rgt, type, uid")
            ->where_in("uid", $processor_group_id)
            ->where("type", 0)
            ->or_where("name", "组织关系")
            ->or_where("type", 2)
            ->order_by("lft", "asc")
            ->get("relation")->result_array();

        // 以一个空的$right栈开始
        $right = array();

        $tree_json = "";
        // 生成 Ztree Json树
        foreach ($group_tree_nodes AS $node) {
            // 检查栈里面有没有元素
            if (count($right) > 0) {
                // 检查我们是否需要从栈中删除一个节点
                while ($right[count($right) - 1]["rgt"] < $node['rgt']) {
                    if ($right[count($right) - 1]["rgt"] - $right[count($right) - 1]["lft"] != 1) {
                        $tree_json .= "]},";
                    } else {
                        $tree_json .= "},";
                    }
                    array_pop($right);
                }

                //判断分类节点
                if ($node["type"] == 2) {
                    $tree_json .= "{name: '" . $node["name"] . "',id:" . $node["uid"] . ",nocheck:true" . ",isdepartment:" . $node["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";
                } else {
                    $tree_json .= "{name: '" . $node["name"] . "',id:" . $node["uid"] . ",isParent:true" . ",isdepartment:" . $node["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";
                }

            } else {
                //根节点
                $tree_json .= "{name:'处理人(单位)'";
                if ($node["rgt"] - $node["lft"] != 1) {
                    $tree_json .= ",id:0,open:true,children:[";
                }
            }

            // 把这个节点添加到栈中
            $right[] = $node;
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
     * 查询子节点
     * @param $node_id
     * @return Json 字符串
     */
    public function get_processor_nodes($node_id)
    {
        $root = $this->db->select("name, lft, rgt, type, uid")
            ->where(array("uid" => $node_id, "type !=" => 1))
            ->get("relation")->row_array();

        $tree_nodes = $this->db->select("name, lft, rgt, type, relation.uid")
            ->join("user_privilege", "user_privilege.uid = relation.uid", "left")
            ->where(array("type" => 1, "lft>" => $root["lft"], "rgt<" => $root["rgt"], "user_privilege.pid" => 3))
            ->get("relation")->result_array();

        $tree = [];
        foreach ($tree_nodes AS $node) {
            $tree[] = array(
                "id" => $node["uid"],
                "name" => $node["name"],
                "isdepartment" => $node["type"]
            );
        }
        return json_encode($tree);
    }


    /**
     * 查询子节点
     * @param $node_id
     * @return Json 字符串
     */
    public function get_caller_nodes($node_id)
    {
        $root = $this->db->select("name, lft, rgt, type, uid")
            ->where(array("uid" => $node_id, "type !=" => 1))
            ->get("relation")->row_array();

        $tree_nodes = $this->db->select("relation.name, lft, rgt, type, relation.uid")
            ->join("user", "user.id = relation.uid")
            ->where(array("type" => 1, "lft>" => $root["lft"], "rgt<" => $root["rgt"],'user.phone !='=>null))
            ->get("relation")->result_array();

        $tree = [];
        foreach ($tree_nodes AS $node) {
            $tree[] = array(
                "id" => $node["uid"],
                "name" => $node["name"],
                "isdepartment" => $node["type"]
            );
        }
        return json_encode($tree);
    }


    /**
     * 获得 事件已指派 处理人(单位)
     * @param $event_id
     * @return Json字符串
     */
    public function get_event_processor_tree($event_id)
    {
        //处理单位id
        $processor_group_id = $this->get_processor_group_id();

        //事件已指派单位(单位事件)
        $event_group = $this->db->select("group.id")
            ->join("event_designate", "group.id = event_designate.group", "left")
            ->where("event_id", $event_id)
            ->get("group")->result_array();
        //事件已指派人单位(事件处理人的单位)
        $event_processor_group = $this->db->select("user_group.gid")
            ->join("event_designate", "event_designate.processor = user_group.uid", "left")
            ->where("event_designate.event_id", $event_id)
            ->group_by("user_group.gid")
            ->get("user_group")->result_array();

        //不可选 组织
        $unchose_group = array();
        $is_group_event = array();  //单位事件标记
        foreach ($event_group AS $item) {
            if (!in_array($item["id"], $unchose_group) && in_array($item["id"], $processor_group_id)) {
                $unchose_group[] = $item["id"];
            }
            $is_group_event[] = $item["id"];
        }
        foreach ($event_processor_group AS $item) {
            if (!in_array($item["gid"], $unchose_group) && in_array($item["gid"], $processor_group_id)) {
                $unchose_group[] = $item["gid"];
            }
        }

        $group_tree_nodes = $this->db->select("name, lft, rgt, type, uid")
            ->where_in("uid", $processor_group_id)
            ->where("type", 0)
            ->or_where("name", "组织关系")
            ->or_where("type", 2)
            ->order_by("lft", "asc")
            ->get("relation")->result_array();

        // 以一个空的$right栈开始
        $right = array();

        $tree_json = "";
        // 生成 Ztree Json树
        foreach ($group_tree_nodes AS $node) {
            // 检查栈里面有没有元素
            if (count($right) > 0) {
                // 检查我们是否需要从栈中删除一个节点
                while ($right[count($right) - 1]["rgt"] < $node['rgt']) {
                    if ($right[count($right) - 1]["rgt"] - $right[count($right) - 1]["lft"] != 1) {
                        $tree_json .= "]},";
                    } else {
                        $tree_json .= "},";
                    }
                    array_pop($right);
                }

                //判断分类节点
                if ($node["type"] == 2) {
                    $tree_json .= "{name: '" . $node["name"] . "',id:" . $node["uid"] . ",nocheck:true" . ",isdepartment:" . $node["type"];
                } else {
                    $tree_json .= "{name: '" . $node["name"] . "',id:" . $node["uid"] . ",isParent:true" . ",isdepartment:" . $node["type"];
                }
                //判断是否可选
                if (in_array($node["uid"], $unchose_group)) {
                    $tree_json .= ",chkDisabled:true";
                }
                //判断是否单位事件
                if (in_array($node["uid"], $is_group_event)) {
                    $tree_json .= ",is_group_event:1";
                } else {
                    $tree_json .= ",is_group_event:0";
                }
                $tree_json .= ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";

            } else {
                //根节点
                $tree_json .= "{name:'处理人(单位)'";
                if ($node["rgt"] - $node["lft"] != 1) {
                    $tree_json .= ",id:0,open:true,children:[";
                }
            }

            // 把这个节点添加到栈中
            $right[] = $node;
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
     * @param $node_id - 根节点id
     * @param $event_id - 事件id
     * @param $is_group_event - 是否单位事件
     * @return Json字符串
     */
    public function get_event_processor_node($node_id, $event_id, $is_group_event)
    {

        //查询根节点
        $root = $this->db->select("name, lft, rgt, type, uid")
            ->where(array("uid" => $node_id, "type !=" => 1))
            ->get("relation")->row_array();

        //查询子节点
        $tree_nodes = $this->db->select("name, lft, rgt, type, relation.uid")
            ->join("user_privilege", "user_privilege.uid = relation.uid", "left")
            ->where(array("type" => 1, "lft>" => $root["lft"], "rgt<" => $root["rgt"], "user_privilege.pid" => 3))
            ->get("relation")->result_array();

        //查询事件处理人
        $event_processors = $this->db->select("processor")
            ->where(array("event_designate.event_id" => $event_id, "processor !=" => null))
            ->get("event_designate")->result_array();

        //不可选
        $unchoose_processor = array();
        foreach ($event_processors AS $item) {
            $unchoose_processor[] = $item["processor"];
        }

        //生成 Ztree Json树
        $tree = [];
        //单位事件 所有子节点不可选
        if ($is_group_event) {
            foreach ($tree_nodes AS $node) {
                $tree[] = array(
                    "id" => $node["uid"],
                    "name" => $node["name"],
                    "isdepartment" => $node["type"],
                    "chkDisabled" => "true"
                );
            }
        } else {
            foreach ($tree_nodes AS $node) {
                $tree_node = array(
                    "id" => $node["uid"],
                    "name" => $node["name"],
                    "isdepartment" => $node["type"],
                    "chkDisabled" => "false"
                );
                if (in_array($node["uid"], $unchoose_processor)) {
                    $tree_node["chkDisabled"] = "true";
                }
                $tree[] = $tree_node;
            }
        }
        return json_encode($tree);
    }


    /**
     * 获得督办人树
     * @return Json字符串
     */
    public function get_watcher_tree()
    {
        $watcher_group = $this->get_watcher_group();

        $watchers = $this->db->select("user.id, user.name, user_group.gid AS group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("user_group", "user_group.uid = user.id", "left")
            ->where(array("user_privilege.pid" => 4, "user_group.is_exist" => 1))
            ->get("user")->result_array();

        $tree = array(
            "id" => 0,
            "name" => "督办人",
            "open" => true,
            "children" => array()
        );

        foreach ($watcher_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => false,
                "children" => array()
            );
            foreach ($watchers AS $processor) {
                if ($group["id"] == $processor["group_id"]) {
                    $tree_node = array(
                        "id" => $processor["id"],
                        "name" => $processor["name"],
                        "isdepartment" => 1
                    );
                    $group_node["children"][] = $tree_node;
                }
            }
            $tree["children"][] = $group_node;
        }
        return json_encode($tree);
    }


    /**
     * 获得 在线状态
     */
    public function get_online_tree()
    {
        //连接消息服务器
        $this->load->library("Gateway");
        Gateway::$registerAddress = $this->config->item("VM_registerAddress");

        $users = $this->db->select("user.id, user.name, user_group.gid AS group_id")
            ->join("user_group", "user_group.uid = user.id", "left")
            ->get("user")->result_array();

        //生成树
        $tree = array(
            "id" => 0,
            "name" => "在线用户",
            "open" => true,
            "children" => array()
        );
        $online_user = array();
        $online_group_id = array();
        foreach ($users AS $user) {
            //判断用户是否在线
            if (Gateway::isUidOnline($user["id"]) != 0) {
                //获取所有的ws连接id
                $clients = Gateway::getClientIdByUid($user["id"]);
                $user["pc"] = 0;
                $user["app"] = 0;
                foreach ($clients AS $client) {
                    //获取ws连接session
                    $client_session = Gateway::getSession($client);
                    // web端在线
                    if (isset($client_session["p_token"])) {
                        $user["pc"] = 1;
                        continue;
                    }
                    // 移动端在线
                    if (isset($client_session["m_token"])) {
                        $user["app"] = 1;
                        continue;
                    }
                }
                $online_user[] = $user;
                $online_group_id[] = $user["group_id"];
            }
        }

        if (empty($online_group_id) || empty($online_user)) {
            return json_encode($tree);
        }

        $online_group = $this->db->select("id, name")->where_in("id", $online_group_id)->get("group")->result_array();

        foreach ($online_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => true,
                "children" => array()
            );
            foreach ($online_user AS $user) {
                if ($group["id"] == $user["group_id"]) {
                    $tree_node = array(
                        "id" => $user["id"],
                        "name" => $user["name"],
                        "isdepartment" => 1,
                        "pc_online" => $user["pc"], // pc端在线
                        "app_online" => $user["app"], // 移动端在线
                        "icon" => "/assets/ztree/zTreeStyle/img/admin_online.png"
                    );
                    $group_node["children"][] = $tree_node;
                }
            }
            $tree["children"][] = $group_node;
        }
        return json_encode($tree);
    }


    /**
     * 获得督办人数据
     * @param $event_id
     * @return Json字符串
     */
    public function get_event_watcher_tree($event_id)
    {
        $watcher_group = $this->get_watcher_group();

        $watchers = $this->db->select("user.id, user.name, user_group.gid AS group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("user_group", "user_group.uid = user.id", "left")
            ->where(array("user_privilege.pid" => 4, "user_group.is_exist" => 1))
            ->get("user")->result_array();


        //事件已督办人
        $event_watchers = $this->db->select("user.id")
            ->join("event_watch", "user.id = event_watch.watcher", "left")
            ->where("event_id", $event_id)
            ->get("user")->result_array();

        $tree = array(
            "id" => 0,
            "name" => "督办人",
            "open" => true,
            "children" => array()
        );

        foreach ($watcher_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => false,
                "children" => array()
            );
            foreach ($watchers AS $item) {
                if ($group["id"] == $item["group_id"]) {
                    $tree_node = array(
                        "id" => $item["id"],
                        "name" => $item["name"],
                        "isdepartment" => 1,
                        "chkDisabled" => false
                    );
                    //设置事件已督办人
                    foreach ($event_watchers AS $watcher) {
                        if ($watcher["id"] == $item["id"]) {
                            $tree_node["chkDisabled"] = true;
                        }
                    }
                    $group_node["children"][] = $tree_node;
                }
            }
            $tree["children"][] = $group_node;
        }
        return json_encode($tree);
    }


    /**
     * 移动 节点
     * @param int $parent_id 父节点id
     * @param int $node_id 节点id
     * @return bool
     */
    public function move_tree_node($parent_id, $node_id)
    {
        $node = $this->db->select("id, lft, rgt")
            ->where("id", $node_id)
            ->get("relation")->row_array();

        $nodes = $this->db->select("id, lft, rgt")
            ->where(array("lft >= " => $node["lft"], "rgt <= " => $node["rgt"]))
            ->order_by("lft", "ASC")
            ->get("relation")->result_array();

        //待移动节点区间长度
        $length = $node['rgt'] - $node['lft'] + 1;

        $this->db->trans_begin();
        $this->db->where(array("rgt >" => $node["rgt"]))->set("rgt", "rgt - $length", FALSE)->update("relation");
        $this->db->where(array("lft >" => $node["rgt"]))->set("lft", "lft - $length", FALSE)->update("relation");

        $parent_node = $this->db->select("id, lft, rgt")->where("id", $parent_id)->get("relation")->row_array();

        //计算目的节点与待插入节点的距离
        $diff = $node['lft'] - $parent_node['rgt'];

        //节点新的左值
        $node_lft = $parent_node['rgt'];

        //更新插入节点的值
        $count = count($nodes);
        for ($i = 0; $i < $count; $i++) {
            $nodes[$i]["lft"] -= $diff;
            $nodes[$i]["rgt"] -= $diff;
        }
        $new_nodes = $nodes;

        $this->db->where("rgt >=", $node_lft)->set("rgt", "rgt + $length", FALSE)->update("relation"); //更新目的父节点
        $this->db->where("lft >", $node_lft)->set("lft", "lft + $length", FALSE)->update("relation"); //更新目的父节点
        $this->db->update_batch("relation", $new_nodes, "id"); //更新移动的节点

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }


    /**
     * 获得 处理人单位id
     * @return array
     */
    protected function get_processor_group_id()
    {
        $processor_group = $this->db->select("yq_group.id AS id, yq_group.name AS name")
            ->join("user_group", "user_group.gid = group.id")
            ->join("user", "user_group.uid = user.id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where(array("user_privilege.pid" => 3, "user_group.is_exist" => 1))
            ->group_by("group.id")
            ->get("group")->result_array();

        $group_id = array();
        foreach ($processor_group AS $item) {
            $group_id[] = $item["id"];
        }
        return $group_id;
    }


    /**
     * 获得 督办人单位
     * @return array
     */
    protected function get_watcher_group()
    {
        $watcher_group = $this->db->select("yq_group.id AS id, yq_group.name AS name")
            ->join("user_group", "user_group.gid = group.id")
            ->join("user", "user_group.uid = user.id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where(array("user_privilege.pid" => 4, "user_group.is_exist" => 1))
            ->group_by("group.id")
            ->get("group")->result_array();
        return $watcher_group;
    }

}