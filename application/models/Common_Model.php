<?php

/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/11
 * Time: 15:28
 */
class Common_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 用户是否有权限查看这件事
     * @param $eid
     * @return bool
     */
    public function check_can_see($eid)
    {
        $uid = $this->session->userdata('uid');
        $pri = explode(",", $this->session->userdata('privilege'));
        $flag = false;
        foreach ($pri as $one) {
            switch ($one) {
                case 2 :
                    $flag = true;
                    break;
                case 3 :
                    $res = $this->check_processor_see($eid, $uid);
                    if ($res) {
                        $flag = true;
                    }
                    break;
                case 4 :
                    $res = $this->check_watcher_see($eid, $uid);
                    if ($res) {
                        $flag = true;
                    }
                    break;
                case 5 :
                    $flag = true;
                    break;
                default :
                    break;
            }
        }
        return $flag;
    }

    //权限:判断处理人是否能查看
    public function check_processor_see($eid, $uid)
    {
        $res = $this->db->select('id')->from('event_designate')
            ->where('event_id', $eid)
            ->where('processor', $uid)
            ->get()->result_array();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    //权限：判断 督查者是否能看
    public function check_watcher_see($eid, $uid)
    {
        $res = $this->db->select('id')->from('event_watch')
            ->where('event_id', $eid)
            ->where('watcher', $uid)
            ->get()->result_array();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    //获取事件的所有交互记录，pid为总结性发言
    public function get_all_logs_by_id($eid)
    {
        //检查权限
        $check = $this->check_can_see($eid);
        if (!$check) {
            return false;
        }
        $data = $this->db->select("l.description,l.pid,l.id,l.time,l.name,l.speaker")
            ->from('event_log AS l')
            ->where('l.event_id', $eid)
            ->order_by('time', 'DESC')->get()
            ->result_array();
        if (empty($data)) {
            return false;
        } else {
            //以总结性的话为开头，子数组为评论组成数组
            $summary_array = array();
            $comment_array = array();
            foreach ($data as $words) {
                //var_dump($words);
                if ($words['pid'] == "") {
                    //为总结性发言,记录id值
                    $info = array(
                        'id' => $words['id'],
                        'time' => $words['time'],
                        'desc' => $words['description'],
                        'name' => $words['name'],
                        'usertype' => $this->check_is_watcher($eid, $words['speaker']) ? 1 : 0,
                        'comment' => array()
                    );
                    array_push($summary_array, $info);
                } else {
                    //评论加入评论数组
                    $com = array(
                        'id' => $words['id'],
                        'time' => $words['time'],
                        'desc' => $words['description'],
                        'name' => $words['name'],
                        'pid' => $words['pid']
                    );
                    array_push($comment_array, $com);
                }
            }

            foreach ($comment_array as $words) {
                foreach ($summary_array as $k => $sum) {
                    if ($words['pid'] == $sum['id']) {
                        //var_dump($sum);
                        $info = array(
                            'id' => $words['id'],
                            'time' => $words['time'],
                            'name' => $words['name'],
                            'desc' => $words['desc'],
                            'pid' => $words['pid']
                        );
                        array_push($summary_array[$k]['comment'], $info);

                    }
                }


            }
            return $summary_array;
        }
    }

    //判断用户是否为督察组
    public function check_is_watcher($eid, $uid)
    {
        $res = $this->db->select('watcher')->from('event_watch')
            ->where('event_id', $eid)
            ->get()->result_array();
        if (!empty($res)) {
            foreach ($res as $one) {
                if ($one['watcher'] == $uid) {
                    return 1;
                }
            }
            return 0;
        } else {
            return false;
        }

    }


    /**
     * 事件附件下载
     * @param $attachment_id
     * @return mixed
     */
    public function event_attachment_download($attachment_id)
    {
        $attachment_info = $this->db->select("id, event_id, name, url, type")
            ->where("id", $attachment_id)
            ->get("event_attachment")->row_array();
        if (empty($attachment_info)) {
            return false;
        }
        //用户是否可以下载该文件
        $this->load->model("Verify_Model", "verify");
        $e_can = $this->verify->can_see_event($attachment_info["event_id"]);
        if (!$e_can) {
            return false;
        }
        return $attachment_info;
    }


}