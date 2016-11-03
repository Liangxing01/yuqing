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
     * 待处理信息 分页数据
     * @param $pInfo
     * @return array
     */
    public function info_not_handle_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("info.id, info.title, source, user.name AS publisher, time")
            ->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where("state != 2 AND (info.id LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\" OR info.source LIKE \"%" . $pInfo['search'] . "%\" ESCAPE \"!\" OR user.name LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\" OR info.title LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\")")
            ->where_not_in("select info_id from yq_event_info")
            ->order_by("time", $pInfo["sort_type"])
            ->limit($pInfo["length"], $pInfo["start"])
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where("state != 2 AND (info.id LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\" OR info.source LIKE \"%" . $pInfo['search'] . "%\" ESCAPE \"!\" OR user.name LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\" OR info.title LIKE \"%" . $pInfo["search"] . "%\" ESCAPE \"!\")")
            ->where_not_in("select info_id from yq_event_info")
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * 查询 舆情信息 详情
     * @param $info_id
     * @return array
     */
    public function get_info($info_id)
    {
        return $this->db->select("info.id, info.title, info.description, info.type, info.url, info.picture, info.source, user.name AS publisher, info.time")
            ->from("info")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("info.id" => $info_id))
            ->get()->row_array();
    }


    /**
     * 设置 舆情信息 为已看状态
     * @param $info_id
     * @return bool
     */
    public function set_info_seen($info_id)
    {
        return $this->db->set(array("state" => 1))->where("id", $info_id)->update("info");
    }


    /**
     * 舆情信息 确认
     * @param $data
     * @return bool
     */
    public function info_commit($data)
    {
        return $this->db->set(array("type" => $data["type"], "duplicate" => $data["duplicate"], "state" => 2))
            ->where(array("id" => $data["id"]))
            ->update("info");
    }


    /**
     * 获取 信息类型
     * @return array
     */
    public function get_info_type()
    {
        return $this->db->get("type")->result_array();
    }


    /**
     * 待指派事件 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function event_not_designate_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("event.id, event.title, source, user.name AS publisher, start_time")
            ->from("event")
            ->join("user", "user.id = event.publisher", "left")
            ->where(array("state" => "已上报"))
            ->limit(10, 0)
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("event.id, event.title, event_type.name AS type, source, user.username AS publisher, start_time")
            ->from("event")
            ->join("user", "user.id = event.publisher", "left")
            ->join("event_type", "event_type.id = event.type", "left")
            ->where(array("state" => "已上报"))
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;

    }


    /**
     * 已指派事件 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function event_is_designate_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("event.id, event.title, source, event_type.name AS type, publisher.name AS publisher, start_time")
            ->from("event")
            ->join("event_type", "event_type.id = event.type", "type")
            ->join("user publisher", "publisher.id = event.publisher", "left")
            ->where(array("state" => "已指派", "manager" => 2))
            ->limit(10, 0)
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->from("event")
            ->join("user", "user.id = event.publisher", "left")
            ->join("event_type", "event_type.id = event.type", "left")
            ->where(array("state" => "已指派"))
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;

    }


    /**
     * 事件检索 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function event_search_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("event.id, event.title, source, event_type.name AS type, publisher.name AS publisher, manager.name AS manager, start_time, state")
            ->from("event")
            ->join("event_type", "event_type.id = event.type", "type")
            ->join("user publisher", "publisher.id = event.publisher", "left")
            ->join("user manager", "manager.id = event.manager", "left")
            ->limit(10, 0)
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->from("event")->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;

    }


    /**
     * 查询 event(事件) 信息
     * @param $event_id
     * @return mixed
     */
    public function get_event_info($event_id)
    {
        $event = $this->db->select("event.id, event.title, event.url, event.description, event.picture, event_type.name AS type, source, user.name AS publisher, start_time")
            ->from("event")
            ->join("user", "user.id = event.publisher", "left")
            ->join("event_type", "event_type.id = event.type", "left")
            ->where(array("event.id" => $event_id))
            ->get()->row_array();

        return $event;
    }


    public function get_relation_tree()
    {
        $root = $this->db->query("SELECT lft, rgt FROM yq_relation WHERE name='组织关系'")->first_row('array');

        // 以一个空的$right栈开始
        $right = array();

        // 获得root节点的所有子节点
        $sql = "SELECT name, lft, rgt FROM yq_relation WHERE lft BETWEEN ? AND ? ORDER BY lft ASC";
        $result = $this->db->query($sql, array($root['lft'], $root['rgt']))->result_array();

        // 显示
        foreach ($result AS $row) {
            // 检查栈里面有没有元素
            if (count($right) > 0) {
                // 检查我们是否需要从栈中删除一个节点
                while ($right[count($right) - 1] < $row['rgt']) {
                    array_pop($right);
                }
            }

            // 显示缩进的节点标题
            echo str_repeat('&nbsp;', count($right)) . $row['name'] . "</br>";

            // 把这个节点添加到栈中
            $right[] = $row['rgt'];
        }
    }


    /**
     * 事件指派 插入指派数据
     * @param $data
     * @return mixed
     */
    public function event_designate($data)
    {
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