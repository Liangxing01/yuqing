<?php
//处理人模型
class Handler_Model extends CI_Model{
    public function __construct(){
        $this->load->database();
    }

    //查询所有待处理事件
    public function get_all_unhandle($pInfo,$processorID){
        //多表联合查询
        $sql  = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a 
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id` 
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON
 e.id = a.event_id order by zptime DESC  limit ?,? ;";

        $sql2 = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a 
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id` 
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON
 e.id = a.event_id";


        $data['aaData'] = $this->db->query($sql,array($processorID,(int)$pInfo['start'],(int)$pInfo['length']))->result_array();
        $total = $this->db->query($sql2,array($processorID))->num_rows();
        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }

    //根据event_id查询事件内容
    public function get_detail_by_id($id){
        $data = $this->db->select("i.id,einfo.event_id as event_id,i.title,t.name as type_name,i.url,i.source,i.picture,
        e.description,yq_user.name,i.time")
            ->from("info AS i")
            ->join("type as t","t.id = i.type","left")
            ->join("event_info AS einfo","einfo.event_id = ".(int)$id,"left")
            ->join("event as e","e.id = ".(int)$id,"left")
            ->join("user","e.manager = yq_user.id","left")
            ->where("i.id = einfo.info_id")
            ->where("e.state","已指派")
            ->get()->result_array();

        if(!empty($data)){
            return $data[0];
        }else{
            return false;
        }
    }


    //查询正在处理事件
    public function get_doing_handle($pInfo,$processorID){
        $sql = "SELECT a.id,a.event_id,b.title,u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_designate WHERE processor = ? and state ='处理中' ) as a
LEFT JOIN yq_event as b on a.event_id = b.id  LEFT JOIN yq_user as u on a.manager = u.id 
limit ?,?";

        $sql2 = "SELECT a.id,a.event_id,b.title,u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_designate WHERE processor = ? and state ='处理中' ) as a
LEFT JOIN yq_event as b on a.event_id = b.id  LEFT JOIN yq_user as u on a.manager = u.id";

        $data['aaData'] = $this->db->query($sql,array($processorID,(int)$pInfo['start'],(int)$pInfo['length']))->result_array();
        $total = $this->db->query($sql2,array($processorID))->num_rows();
        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }

    //获取时间的所有交互记录，pid为总结性发言
    public function get_all_logs_by_id($eid){
        $this->db->select("description,pid,id,time");
        $data = $this->db->get_where('event_log',array('event_id' => $eid))->result_array();
        if (empty($data)){
            return false;
        }else{
            $format_words = array();
            //以总结性的话为开头，子数组为评论组成数组
            $summary_array = array();
            $comment_array = array();
            foreach ($data as $words){
                //var_dump($words);
                if($words['pid'] == ""){
                    //为总结性发言,记录id值
                    $id =   $words['id'];
                    $time = $words['time'];
                    $desc = $words['description'];
                    $info = array(
                        'id'    => $id,
                        'time'  => $time,
                        'desc'  => $desc,
                        'comment' => array()
                    );
                    array_push($summary_array,$info);
                }else{
                    //评论加入评论数组
                    $com = array(
                        'id'    => $words['id'],
                        'time'  => $words['time'],
                        'desc'  => $words['description'],
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
                                'desc' => $words['desc'],
                                'pid' => $words['pid']
                            );
                            array_push($summary_array[$k]['comment'], $info);

                        }
                    }


            }

            /*foreach ($data as $words){
                if($words['pid'] != ""){
                    foreach ($summary_array as $sum){
                        var_dump($summary_array);
                        if($words['pid'] == $sum['id']){
                            $info = array(
                                'id'    => $words['id'],
                                'time'  => $words['time'],
                                'desc'  => $words['description'],
                                'pid'   => $words['pid']
                            );
                            array_push($sum['comment'],$info);
                            //var_dump($sum);
                        }
                    }
                }
            }*/

            //array_push($format_words,$summary_array);
            var_dump($summary_array);
        }
    }

    //获取事件所有交互记录，pid为总结性发言的id
    public function get_all_logs($eid,$uid){
        //判断是否为第一次处理，则插入一条父类记录
        $this->db->select('id,pid');
        $num = $this->db->get_where('event_log',array('event_id'=>$eid,'processor'=>$uid))->result_array();
        if(empty($num)){
            $this->insert_parent_log($eid,$uid);
        }else{
            //找出pid为空的那条记录的id，作为pid去查询子日志
            for($i = 0; $i < count($num) ; $i++){
                if($num[$i]['pid'] == ''){
                    $pid = $num[$i]['id'];
                }
            }

            //查询所有该pid的日志记录
            $res = $this->db->get_where('event_log',array('pid'=> $pid))->result_array();
            return $res;
        }
    }

    //开始执行处理任务，向数据库插入一条记录
    public function insert_parent_log($event_id,$uid){
        //根据事件id 查询指派记录id
        $this->db->select('id,manager');
        $zpID = $this->db->get_where('event_designate',array('event_id' => $event_id,'processor' => $uid))->result_array();
        $zpinfo = $zpID[0];
        $arr = array(
            'event_id' => $event_id,
            'designate_id' => $zpinfo['id'],
            'manager' => $zpinfo['manager'],
            'processor' => $uid,
            'description' =>'',
            'time' => time(),
            'state' => '处理中'
        );
        $this->db->insert('event_log',$arr);
    }

    //根据用户关键字查询待处理事件
    public function get_unhandle_by_keyword($key){
        $this->db->where('');
    }




}