<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verify_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();

        define("reporter", 1);   //上报人权限
        define("manager", 2);    //指派人权限
        define("processor", 3);  //处理人权限
        define("watcher", 4);    //督办人权限
        define("admin", 5);      //管理员权限
    }


    /**
     * 验证指派人权限
     * @return bool
     */
    public function is_manager()
    {
        $privilege = explode(",", $this->session->privilege);
        if (in_array(manager, $privilege)) {
            return true;
        }
        return false;
    }


    /**
     * 验证处理人权限
     * @return bool
     */
    public function is_processor()
    {
        $privilege = explode(",", $this->session->privilege);
        if (in_array(processor, $privilege)) {
            return true;
        }
        return false;
    }


    /**
     * 验证处理人权限
     * @return bool
     */
    public function is_watcher()
    {
        $privilege = explode(",", $this->session->privilege);
        if (in_array(watcher, $privilege)) {
            return true;
        }
        return false;
    }


    /**
     * 事件查看权限
     * @param $event_id
     * @return bool
     */
    public function can_see_event($event_id)
    {
        $uid = $this->session->userdata('uid');
        $_p = explode(",", $this->session->userdata('privilege'));

        foreach ($_p as $one) {
            switch ($one) {
                case manager :
                    return true;
                    break;
                case processor :
                    if ($this->processor_see_event($event_id, $uid)) {
                        return true;
                    }
                    break;
                case watcher :
                    if ($this->watcher_see_event($event_id, $uid)) {
                        return true;
                    }
                    break;
                case admin :
                    return true;
                    break;
                default :
                    break;
            }
        }
        return false;
    }


    /**
     * 处理人查看事件权限
     * @param $event_id
     * @param $uid
     * @return bool
     */
    protected function processor_see_event($event_id, $uid)
    {
        //指派事件可查看
        $r_1 = $this->db->select('event_designate.id')->from('event_designate')
            ->where(array("event_id" => $event_id, "processor" => $uid))
            ->get()->num_rows();

        //关联事件可查看
        $r_2 = $this->db->select('event_designate.id')->from("event_designate")
            ->join("event_relate", "event_designate.event_id = event_relate.event_id", "left")
            ->where(array("event_relate.relate_id" => $event_id, "event_designate.processor" => $uid))
            ->get()->num_rows();

        if ($r_1 > 0 || $r_2 > 0) {
            return true;
        }
        return false;
    }


    /**
     * 督办人查看事件权限
     * @param $event_id
     * @param $uid
     * @return bool
     */
    protected function watcher_see_event($event_id, $uid)
    {
        //督办事件可查看
        $r_1 = $this->db->select('event_watch.id')->from('event_watch')
            ->where(array("event_id" => $event_id, "watcher" => $uid))
            ->get()->num_rows();

        //关联事件可查看
        $r_2 = $this->db->select('event_watch.id')->from("event_watch")
            ->join("event_relate", "event_watch.event_id = event_relate.event_id", "left")
            ->where(array("event_relate.relate_id" => $event_id, "event_watch.watcher" => $uid))
            ->get()->num_rows();

        if ($r_1 > 0 || $r_2 > 0) {
            return true;
        }
        return false;
    }

}