<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/13
 * Time: 15:36
 */
//督察组模型

class Watcher_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }


    /**
     * @param $pInfo
     * @return mixed
     * 获取所有督查事件
     */
    public function get_all_watch_list($pInfo){
        $uid = $this->session->userdata('uid');
        //高级条件检索

        //查询条件构造
        $condition = array();
        // 时间条件
        if(!empty($pInfo['start_time'])){
            if ($pInfo["start_time"] != 0 && $pInfo["end_time"] != 0) {
                $condition[] = "e.start_time > " . $pInfo["start_time"] . " AND e.start_time < " . ($pInfo["end_time"] + 86400);
            } else if ($pInfo["start_time"] != 0 && $pInfo["end_time"] == 0) {
                $condition[] = "e.start_time >= " . $pInfo["start_time"];
            } else if ($pInfo["start_time"] == 0 && $pInfo["end_time"] != 0) {
                $condition[] = "e.start_time < " . ($pInfo["end_time"] + 86400);
            }
        }


        //单位事件条件
        if(($pInfo['is_group']) != ''){
            if($pInfo['is_group'] == 1){
                $condition[] = "e.group !=''";
            }else{
                $condition[] = "e.group";
            }
        }


        //事件等级
        if(!empty($pInfo['rank'])){
            $condition[] = "e.rank = '".$pInfo['rank']."'";
        }

        $where = "";
        foreach($condition AS $c){
            if($condition){
                $where .= $c . " AND ";
            }
        }

        $where = substr($where, 0, strlen($where) - 4);

        if($where){
            $data['aaData'] = $this->db->select("e.id as event_id,e.title,e.rank,e.start_time as time,
            e.group,u.name,e.state,e.description")
                ->from("event_watch as ea")
                ->where('ea.watcher',$uid)
                ->join('event as e','e.id = ea.event_id','left')
                ->join('user as u','u.id = e.manager','left')
                ->where($where)
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->limit($pInfo['length'],$pInfo['start'])
                ->order_by('time',$pInfo['sort_type'])
                ->get()->result_array();

            $total = $this->db->select("e.id as event_id,e.title,e.rank,e.start_time as time,
            e.group,u.name,e.state,e.description")
                ->from("event_watch as ea")
                ->where('ea.watcher',$uid)
                ->join('event as e','e.id = ea.event_id','left')
                ->join('user as u','u.id = e.manager','left')
                ->where($where)
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();

        }else{
            $data['aaData'] = $this->db->select("e.id as event_id,e.title,e.rank,e.start_time as time,
            e.group,u.name,e.state,e.description")
                ->from("event_watch as ea")
                ->where('ea.watcher',$uid)
                ->join('event as e','e.id = ea.event_id','left')
                ->join('user as u','u.id = e.manager','left')
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->limit($pInfo['length'],$pInfo['start'])
                ->order_by('time',$pInfo['sort_type'])
                ->get()->result_array();

            $total = $this->db->select("e.id as event_id,e.title,e.rank,e.start_time as time,
            e.group,u.name,e.state,e.description")
                ->from("event_watch as ea")
                ->where('ea.watcher',$uid)
                ->join('event as e','e.id = ea.event_id','left')
                ->join('user as u','u.id = e.manager','left')
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();
        }

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }

}