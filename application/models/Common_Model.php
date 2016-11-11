<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/11
 * Time: 15:28
 */

class Common_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    //权限判断：用户是否有权限查看这件事
    public function check_can_see($eid,$uid){
        
    }

    //获取事件的所有交互记录，pid为总结性发言
    public function get_all_logs_by_id($eid){

        $data = $this->db->select("l.description,l.pid,l.id,l.time,l.name,l.speaker")
            ->from('event_log AS l')
            ->join('event_designate as ed','ed.event_id = '.$eid,'left')
            ->where('l.event_id',$eid)
            ->order_by('time','DESC')->get()
            ->result_array();
        if (empty($data)){
            return false;
        }else{
            //以总结性的话为开头，子数组为评论组成数组
            $summary_array = array();
            $comment_array = array();
            foreach ($data as $words){
                //var_dump($words);
                if($words['pid'] == ""){
                    //为总结性发言,记录id值
                    $info = array(
                        'id'    => $words['id'],
                        'time'  => $words['time'],
                        'desc'  => $words['description'],
                        'name'  => $words['name'],
                        'type'  => $this
                        'comment' => array()
                    );
                    array_push($summary_array,$info);
                }else{
                    //评论加入评论数组
                    $com = array(
                        'id'    => $words['id'],
                        'time'  => $words['time'],
                        'desc'  => $words['description'],
                        'name'  => $words['name'],
                        'pid'   => $words['pid']
                    );
                    array_push($comment_array,$com);
                }
            }

            foreach ($comment_array as $words) {
                foreach ($summary_array as $k=> $sum) {
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
}