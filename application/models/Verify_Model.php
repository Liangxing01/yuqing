<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verify_Model extends CI_Model
{
    /**
     * @const int 上报人权限
     */
    const REPORTER = 1;

    /**
     * @const int 指派人权限
     */
    const MANAGER = 2;

    /**
     * @const int 处理人权限
     */
    const PROCESSOR = 3;

    /**
     * @const int 督办人权限
     */
    const WATCHER = 4;

    /**
     * @const int 管理员权限
     */
    const ADMIN = 5;


    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    /**
     * 验证指派人权限
     * @return bool
     */
    public function is_manager()
    {
        $privilege = explode(",", $this->session->privilege);
        if (in_array(self::MANAGER, $privilege)) {
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
        if (in_array(self::PROCESSOR, $privilege)) {
            return true;
        }
        return false;
    }


    /**
     * 验证督办人权限
     * @param int $uid
     * @return bool
     */
    public function is_watcher($uid = null)
    {
        if (!is_null($uid)) {
            $privilege = $this->get_privilege_list($uid);
        } else {
            $privilege = explode(",", $this->session->privilege);
        }
        if (in_array(self::WATCHER, $privilege)) {
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
                case self::MANAGER :
                    return true;
                    break;
                case self::PROCESSOR :
                    if ($this->processor_see_event($event_id, $uid)) {
                        return true;
                    }
                    break;
                case self::WATCHER :
                    if ($this->watcher_see_event($event_id, $uid)) {
                        return true;
                    }
                    break;
                case self::ADMIN :
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
        $gid = $this->session->gid;
        //指派事件可查看
        $r_1 = $this->db->select('event_designate.id')->from('event_designate')
            ->where("event_id", $event_id)
            ->group_start()
            ->where("processor = $uid OR group in ($gid)")
            ->group_end()
            ->get()->num_rows();

        //关联事件可查看
        $r_2 = $this->db->select('event_designate.id')->from("event_designate")
            ->join("event_relate", "event_designate.event_id = event_relate.event_id", "left")
            ->where("event_relate.relate_id", $event_id)
            ->group_start()
            ->where("event_designate.processor = $uid OR event_designate.group in ($gid)")
            ->group_end()
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


    /**
     * 获得权限数组
     * @param int $uid
     * @return array
     */
    protected function get_privilege_list($uid)
    {
        $privileges = $this->db->select("pid")->where("uid", $uid)->get("user_privilege")->result_array();
        $ret = array();
        if (!empty($privileges)) {
            foreach ($privileges AS $p) {
                $ret[] = $p["pid"];
            }
        }
        return $ret;
    }

}