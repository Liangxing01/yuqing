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
                    if ($row["type"] == 0) {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/group.png',open:false,children:[";
                    } else {
                        $tree_json .= "{name: '" . $row["name"] . "',id:" . $row["uid"] . ",isdepartment:" . $row["type"] . ",icon:'/assets/ztree/zTreeStyle/img/admin.png',open:false,children:[";
                    }
                } else {
                    if ($row["type"] == 0) {
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
        $group = $this->db->select("group.id, group.name")->get("group")->result_array();
        $tree = array(
            "id" => 0,
            "name" => "单位",
            "open" => true,
            "children" => array()
        );
        foreach ($group AS $g) {
            $tree["children"][] = array(
                "id" => $g["id"],
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
    public function get_processor_tree()
    {
        $processor_group = $this->db->select("user.group_id AS id, group.name AS name")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("group", "group.id = user.group_id", "left")
            ->where("user_privilege.pid", 3)
            ->group_by("user.group_id")
            ->get("user")->result_array();

        $processors = $this->db->select("user.id, user.name, user.group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where("user_privilege.pid", 3)
            ->get("user")->result_array();

        $tree = array(
            "id" => 0,
            "name" => "处理人(单位)",
            "open" => true,
            "children" => array()
        );

        foreach ($processor_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => true,
                "children" => array()
            );
            foreach ($processors AS $processor) {
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
     * 获得 事件已指派 处理人(单位)
     * @param $event_id
     * @return Json字符串
     */
    public function get_event_processor_tree($event_id)
    {
        //处理单位
        $processor_group = $this->db->select("user.group_id AS id, group.name AS name")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("group", "group.id = user.group_id", "left")
            ->where("user_privilege.pid", 3)
            ->group_by("user.group_id")
            ->get("user")->result_array();
        //处理人
        $processors = $this->db->select("user.id, user.name, user.group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where("user_privilege.pid", 3)
            ->get("user")->result_array();


        //事件已指派单位
        $event_group = $this->db->select("group.name, group.id")->join("event_designate", "group.id = event_designate.group", "left")
            ->where("event_id", $event_id)
            ->get("group")->result_array();
        //事件已指派人
        $event_processors = $this->db->select("user.name, user.id, user.group_id")->join("event_designate", "user.id = event_designate.processor", "left")
            ->where("event_id", $event_id)
            ->get("user")->result_array();


        //生成 Json 树
        $tree = array(
            "id" => 0,
            "name" => "处理人(单位)",
            "open" => true,
            "children" => array()
        );

        foreach ($processor_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => true,
                "children" => array(),
                "chkDisabled" => false
            );
            //设置已指派单位
            foreach ($event_group AS $event_g) {
                if ($event_g["id"] == $group["id"]) {
                    $group_node["chkDisabled"] = true;
                }
            }
            foreach ($processors AS $processor) {
                if ($group["id"] == $processor["group_id"]) {
                    $tree_node = array(
                        "id" => $processor["id"],
                        "name" => $processor["name"],
                        "isdepartment" => 1,
                        "chkDisabled" => false
                    );
                    foreach ($event_processors AS $event_p) {
                        if ($event_p["id"] == $processor["id"]) {
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
     * 获得督办人数据
     * @return Json字符串
     */
    public function get_watcher_tree()
    {
        $processor_group = $this->db->select("user.group_id AS id, group.name AS name")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("group", "group.id = user.group_id", "left")
            ->where("user_privilege.pid", 4)
            ->group_by("user.group_id")
            ->get("user")->result_array();

        $processors = $this->db->select("user.id, user.name, user.group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where("user_privilege.pid", 4)
            ->get("user")->result_array();

        $tree = array(
            "id" => 0,
            "name" => "督办人",
            "open" => true,
            "children" => array()
        );

        foreach ($processor_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => true,
                "children" => array()
            );
            foreach ($processors AS $processor) {
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
     * 获得督办人数据
     * @param $event_id
     * @return Json字符串
     */
    public function get_event_watcher_tree($event_id)
    {
        $processor_group = $this->db->select("user.group_id AS id, group.name AS name")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->join("group", "group.id = user.group_id", "left")
            ->where("user_privilege.pid", 4)
            ->group_by("user.group_id")
            ->get("user")->result_array();

        $processors = $this->db->select("user.id, user.name, user.group_id")
            ->join("user_privilege", "user.id = user_privilege.uid", "left")
            ->where("user_privilege.pid", 4)
            ->get("user")->result_array();

        //事件已督办人
        $event_watchers = $this->db->select("user.name, user.id, user.group_id")->join("event_watch", "user.id = event_watch.watcher", "left")
            ->where("event_id", $event_id)
            ->get("user")->result_array();

        $tree = array(
            "id" => 0,
            "name" => "督办人",
            "open" => true,
            "children" => array()
        );

        foreach ($processor_group AS $group) {
            $group_node = array(
                "id" => $group["id"],
                "name" => $group["name"],
                "isdepartment" => 0,
                "open" => true,
                "children" => array()
            );
            foreach ($processors AS $processor) {
                if ($group["id"] == $processor["group_id"]) {
                    $tree_node = array(
                        "id" => $processor["id"],
                        "name" => $processor["name"],
                        "isdepartment" => 1,
                        "chkDisabled" => false
                    );
                    //设置事件已督办人
                    foreach ($event_watchers AS $watcher) {
                        if ($watcher["id"] == $processor["id"]) {
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

}