<?php
//处理人模型
class Handler_Model extends CI_Model{
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    /*
     * 获取主页所有任务数 统计
     */
    public function get_tasks_num($uid){
        $unread_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor',$uid)
            ->where('state','待处理')->get()->num_rows();
        $doing_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor',$uid)
            ->where('state','处理中')->get()->num_rows();
        $done_num = $this->db->select('e.id')->from('event_designate AS ed')
            ->join('event AS e','e.id = ed.event_id','left')
            ->where('ed.processor',$uid)
            ->group_start()
            ->where('e.state','未审核')
            ->or_where('e.state','已完成')
            ->group_end()
            ->get()->num_rows();
        $num_arr = array(
            'unread_num' => $unread_num,
            'doing_num'  => $doing_num,
            'done_num'   => $done_num
        );
        return $num_arr;
    }

    /*
     * 获取主页下方任务列表
     */
    public function get_tasks_list($uid){
        $wait_list = $this->db->select('ed.event_id,e.title')->from('event_designate AS ed')
            ->join('event AS e','e.id = ed.event_id','left')
            ->where('ed.processor',$uid)
            ->where('ed.state','未处理')
            ->order_by('ed.time','DESC')
            ->limit(3)
            ->get()->result_array();

        $doing_list = $this->db->select('ed.event_id,e.title')->from('event_designate AS ed')
            ->join('event AS e','e.id = ed.event_id','left')
            ->where('ed.processor',$uid)
            ->where('ed.state','处理中')
            ->order_by('ed.time','DESC')
            ->limit(3)
            ->get()->result_array();
        $data = array(
            'wait_list'  => $wait_list,
            'doing_list' => $doing_list
        );
        return $data;
    }

    //查询所有待处理事件
    public function get_all_unhandle($pInfo,$processorID){
        //多表联合查询
        $sql  = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a 
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id` 
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON e.id = a.event_id
WHERE i.title LIKE '%".$pInfo['search']."%'
order by zptime DESC  limit ?,? ;";

        $sql2 = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a 
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id` 
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON
 e.id = a.event_id 
 WHERE i.title LIKE '%".$pInfo['search']."%'";


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
WHERE b.title LIKE '%".$pInfo['search']."%' ESCAPE '!'
ORDER BY a.time DESC limit ?,?";

        $sql2 = "SELECT a.id,a.event_id,b.title,u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_designate WHERE processor = ? and state ='处理中' ) as a
LEFT JOIN yq_event as b on a.event_id = b.id  LEFT JOIN yq_user as u on a.manager = u.id
WHERE b.title LIKE '%".$pInfo['search']."%' ESCAPE '!'";

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

    /*
     * 根据eid获取事件标题
     * 参数：eid
     */
    public function get_title_by_eid($eid){
        $this->db->select('title,rank');
        $data = $this->db->get_where('event',array('id'=>$eid))->result_array();
        if(!empty($data)){
            return $data[0];
        }else{
            return false;
        }
    }

    /*
     * 检查用户是否显示"确定事件完结"按钮
     * 参数：gid,eid
     */
    public function check_done_btn($gid,$eid){
        $this->db->select('main_processor');
        $main_id = $this->db->get_where('event',array('id'=>$eid))->row();
        if($main_id->main_processor == $gid){
            return true;
        }else{
            return false;
        }
    }

    //获取时间的所有交互记录，pid为总结性发言
    public function get_all_logs_by_id($eid){
        $this->db->select("description,pid,id,time,name");
        $data = $this->db->from('event_log')->where('event_id',$eid)
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

    //插入评论
    public function insert_comment($eid,$pid,$com){
        $speaker = $this->session->userdata('uid');
        $name = $this->session->userdata('name');
        if(empty($pid)){
            //为总结性评论
            $data = array(
                'event_id' => $eid,
                'pid'      => "",
                'description' => $com,
                'speak'    => $speaker,
                'name'     => $name
            );
            $res = $this->db->insert('event_log',$data);
            return $res;
        }else{
            //子评论
            $data = array(
                'event_id' => $eid,
                'pid'      => $pid,
                'description' => $com,
                'speak'    => $speaker,
                'name'     => $name
            );
            $res = $this->db->insert('event_log',$data);
            return $res;
        }
    }

    //确定事件 已完成 功能
    public function confirm_done($eid,$gid){
        $check = $this->check_done_btn($gid,$eid);
        if($check){
            $data = array(
                'state' => '未审核'
            );
            $this->db->where('id',$eid);
            $res = $this->db->update('event',$data);
            if($res){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


    //获取 已完成 或 未审核 事件列表
    public function get_done_list($pInfo,$processorID){
        $data['aaData'] = $this->db->select("e.id,e.title,e.description,e.rank,e.state,e.end_time")
            ->from('event_designate AS ed')
            ->join('event AS e','ed.event_id = e.id','left')
            ->join('event_info AS einfo','einfo.event_id = e.id','left')
            ->join('info','info.id = einfo.info_id','left')
            ->where('ed.processor',$processorID)
            ->group_start()
            ->like('e.title',$pInfo['search'])
            ->or_like('e.rank',$pInfo['search'])
            ->group_end()
            ->where('e.state','未审核')
            ->or_where('e.state','已完成')
            ->order_by('e.end_time','DESC')
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total = $this->db->select("e.id,e.title,e.description,e.rank,e.state,e.end_time")
            ->from('event_designate AS ed')
            ->join('event AS e','ed.event_id = e.id','left')
            ->join('event_info AS einfo','einfo.event_id = e.id','left')
            ->join('info','info.id = einfo.info_id','left')
            ->where('ed.processor',$processorID)
            ->group_start()
            ->like('e.title',$pInfo['search'])
            ->or_like('e.rank',$pInfo['search'])
            ->group_end()
            ->where('e.state','未审核')
            ->or_where('e.state','已完成')
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
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