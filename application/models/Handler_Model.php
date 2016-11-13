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



    //取消 事件报警 状态
    public function cancel_alarm_state($eid,$uid){
        $belong = $this->db->select('ed.id')->from('event_designate as ed')
            ->join('event_alert as ea','ed.id = ea.designate_id')
            ->where('event_id',$eid)
            ->where('processor',$uid)
            ->get()->result_array();
        if(!empty($belong)){
            $data = array(
                'state' => 0
            );
            $this->db->where('id',$belong[0]['id']);
            $this->db->update('event_alert',$data);
        }
    }

    //查询所有待处理事件
    public function get_all_unhandle($pInfo,$processorID){
        //高级条件检索

        //查询条件构造
        $condition = array();
        // 时间条件
        if(!empty($pInfo['start_time'])){
            if ($pInfo["start_time"] != 0 && $pInfo["end_time"] != 0) {
                $condition[] = "a.time > " . $pInfo["start_time"] . " AND a.time < " . ($pInfo["end_time"] + 86400);
            } else if ($pInfo["start_time"] != 0 && $pInfo["end_time"] == 0) {
                $condition[] = "a.time >= " . $pInfo["start_time"];
            } else if ($pInfo["start_time"] == 0 && $pInfo["end_time"] != 0) {
                $condition[] = "a.time < " . ($pInfo["end_time"] + 86400);
            }
        }


        //单位事件条件
        if(($pInfo['is_group']) != ''){
            if($pInfo['is_group'] == 1){
                $condition[] = "a.group !=''";
            }else{
                $condition[] = "a.group";
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


        //多表联合查询
    /*    $sql  = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id`
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON e.id = a.event_id
WHERE i.title LIKE '%".$pInfo['search']."%'".$where."
order by zptime DESC  limit ?,? ;";

        $sql2 = "SELECT a.id,a.time AS zptime,a.is_group,t.name as type_name,a.description,i.title,i.id as info_id,u.username as zpname,
einfo.event_id as eid,e.rank  FROM (SELECT * FROM `yq_event_designate` WHERE state = '未处理' AND processor = ?) AS a
LEFT JOIN yq_event_info as einfo ON einfo.event_id = a.event_id LEFT JOIN yq_info AS i ON i.id = einfo.`info_id`
LEFT JOIN yq_user as u on u.id = a.manager LEFT JOIN yq_type as t on t.id = i.type LEFT JOIN yq_event as e ON
 e.id = a.event_id
WHERE i.title LIKE '%".$pInfo['search']."%'".$where;*/
        $gid = $this->session->userdata('gid');
        if($where){
            $data['aaData'] = $this->db->select('a.id,a.time AS zptime,a.group,t.name as type_name,a.description,
        i.title,i.id as info_id,u.username as zpname,einfo.event_id as eid,e.rank')->from('event_designate AS a')
                ->where('a.state','未处理')
                ->group_start()
                ->where('a.processor',$processorID)
                ->or_where('a.group',$gid)
                ->group_end()
                ->where($where)
                ->join('event_info as einfo','einfo.event_id = a.event_id','left')
                ->join('info as i','i.id = einfo.`info_id`','left')
                ->join('user as u','u.id = a.manager','left')
                ->join('type as t','t.id = i.type','left')
                ->join('event as e','e.id = a.event_id','left')
                ->group_start()
                ->like('i.title',$pInfo['search'])
                ->group_end()
                ->order_by('zptime',$pInfo['sort_type'])
                ->limit($pInfo['length'],$pInfo['start'])
                ->get()->result_array();

            //$data['aaData'] = $this->db->query($sql,array($processorID,(int)$pInfo['start'],(int)$pInfo['length']))->result_array();
            //$total = $this->db->query($sql2,array($processorID))->num_rows();

            $total = $this->db->select('a.id,a.time AS zptime,a.group,t.name as type_name,a.description,
        i.title,i.id as info_id,u.username as zpname,einfo.event_id as eid,e.rank')->from('event_designate AS a')
                ->where('a.state','未处理')
                ->group_start()
                ->where('a.processor',$processorID)
                ->or_where('a.group',$gid)
                ->group_end()
                ->where($where)
                ->join('event_info as einfo','einfo.event_id = a.event_id','left')
                ->join('info as i','i.id = einfo.`info_id`','left')
                ->join('user as u','u.id = a.manager','left')
                ->join('type as t','t.id = i.type','left')
                ->join('event as e','e.id = a.event_id','left')
                ->group_start()
                ->like('i.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();
            $data['sEcho']                = $pInfo['sEcho'];

            $data['iTotalDisplayRecords'] = $total;

            $data['iTotalRecords']        = $total;

            if(!empty($data)){
                return $data;
            }else{
                return false;
            }
        }else{
            $data['aaData'] = $this->db->select('a.id,a.time AS zptime,a.group,t.name as type_name,a.description,
        i.title,i.id as info_id,u.username as zpname,einfo.event_id as eid,e.rank')->from('event_designate AS a')
                ->where('a.state','未处理')
                ->group_start()
                ->where('a.processor',$processorID)
                ->or_where('a.group',$gid)
                ->group_end()
                ->join('event_info as einfo','einfo.event_id = a.event_id','left')
                ->join('info as i','i.id = einfo.`info_id`','left')
                ->join('user as u','u.id = a.manager','left')
                ->join('type as t','t.id = i.type','left')
                ->join('event as e','e.id = a.event_id','left')
                ->group_start()
                ->like('i.title',$pInfo['search'])
                ->group_end()
                ->order_by('zptime',$pInfo['sort_type'])
                ->limit($pInfo['length'],$pInfo['start'])
                ->get()->result_array();

            $total = $this->db->select('a.id,a.time AS zptime,a.group,t.name as type_name,a.description,
        i.title,i.id as info_id,u.username as zpname,einfo.event_id as eid,e.rank')->from('event_designate AS a')
                ->where('a.state','未处理')
                ->group_start()
                ->where('a.processor',$processorID)
                ->or_where('a.group',$gid)
                ->group_end()
                ->join('event_info as einfo','einfo.event_id = a.event_id','left')
                ->join('info as i','i.id = einfo.`info_id`','left')
                ->join('user as u','u.id = a.manager','left')
                ->join('type as t','t.id = i.type','left')
                ->join('event as e','e.id = a.event_id','left')
                ->group_start()
                ->like('i.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();
            $data['sEcho']                = $pInfo['sEcho'];

            $data['iTotalDisplayRecords'] = $total;

            $data['iTotalRecords']        = $total;

            if(!empty($data)){
                return $data;
            }else{
                return false;
            }
        }

    }

    //根据event_id查询事件内容
    public function get_detail_by_id($id,$uid){
        $data = $this->db->select("einfo.info_id,info.title,t.name,info.url,info.source,info.picture,info.description,info.time")
            ->from("event_info AS einfo")
            ->join("event_designate AS ed","einfo.event_id = ed.event_id","left")
            ->join("info","info.id = einfo.info_id","left")
            ->join("type as t","t.id=info.type","left")
            ->where("ed.processor",$uid)
            ->where("einfo.event_id",$id)
            ->get()->result_array();

        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }


    //查询正在处理事件
    public function get_doing_handle($pInfo,$processorID){
        //查询条件构造
        $condition = array();
        //事件等级
        if(!empty($pInfo['rank'])){
            $condition[] = "b.rank = '".$pInfo['rank']."'";
        }

        $where = "";
        foreach($condition AS $c){
            if($condition){
                $where .= $c . " AND ";
            }
        }
        $where = substr($where, 0, strlen($where) - 4);
        if($where){
            $data['aaData'] = $this->db->select('a.id,a.event_id,b.title,u.username as zpname,a.time,a.state,b.rank')
                ->from('event_designate as a')
                ->where('a.processor',$processorID)
                ->where('a.state','处理中')
                ->where($where)
                ->join('event as b','a.event_id = b.id','left')
                ->join('user as u','a.manager = u.id','left')
                ->group_start()
                ->like('b.title',$pInfo['search'])
                ->group_end()
                ->order_by('a.time',$pInfo['sort_type'])
                ->limit($pInfo['length'],$pInfo['start'])
                ->get()->result_array();

            $total = $this->db->select('a.id,a.event_id,b.title,u.username as zpname,a.time,a.state,b.rank')
                ->from('event_designate as a')
                ->where('a.processor',$processorID)
                ->where('a.state','处理中')
                ->where($where)
                ->join('event as b','a.event_id = b.id','left')
                ->join('user as u','a.manager = u.id','left')
                ->group_start()
                ->like('b.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();
        }else{
            $data['aaData'] = $this->db->select('a.id,a.event_id,b.title,u.username as zpname,a.time,a.state,b.rank')
                ->from('event_designate as a')
                ->where('a.processor',$processorID)
                ->where('a.state','处理中')
                ->join('event as b','a.event_id = b.id','left')
                ->join('user as u','a.manager = u.id','left')
                ->group_start()
                ->like('b.title',$pInfo['search'])
                ->group_end()
                ->order_by('a.time',$pInfo['sort_type'])
                ->limit($pInfo['length'],$pInfo['start'])
                ->get()->result_array();

            $total = $this->db->select('a.id,a.event_id,b.title,u.username as zpname,a.time,a.state,b.rank')
                ->from('event_designate as a')
                ->where('a.processor',$processorID)
                ->where('a.state','处理中')
                ->join('event as b','a.event_id = b.id','left')
                ->join('user as u','a.manager = u.id','left')
                ->group_start()
                ->like('b.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();
        }


        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }

    public function update_doing_state($eid){
        $data = array(
          'state' => '处理中'
        );
        $this->db->where('event_id',$eid);
        $this->db->update('event_designate',$data);
    }

    /*
     * 根据eid获取事件标题
     * 参数：eid
     */
    public function get_title_by_eid($eid){
        $this->db->select('title,rank,state,end_time');
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
        $this->db->select('main_processor,group');
        $main_id = $this->db->get_where('event',array('id'=>$eid))->row();
        if($main_id->group == $gid || $main_id->main_processor == $this->session->userdata('uid')){
            return true;
        }else{
            return false;
        }
    }



    //获取事件 参考文档
    public function get_attachment_by_id($eid,$uid){
        $data = $this->db->select('e.attachment')->from('event as e')
            ->join('event_designate as ed','ed.processor = '.$uid.' and ed.event_id = '.$eid,'left')
            ->where('e.id',$eid)
            ->get()->result_array();
        if(!empty($data)){
            return $data[0];
        }else{
            return false;
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
                'description' => $com,
                'speaker'    => $speaker,
                'name'     => $name,
                'time'     => time()
            );
            $res = $this->db->insert('event_log',$data);
            return $res;
        }else{
            //子评论
            $data = array(
                'event_id' => $eid,
                'pid'      => $pid,
                'description' => $com,
                'speaker'    => $speaker,
                'name'     => $name,
                'time'     => time()
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

            //更改 指派表 状态为已完成
            $data1 = array(
                'state' => '已完成'
            );
            $this->db->where('event_id',$eid);
            $res1 = $this->db->update('event_designate',$data1);

            if($res && $res1){
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
            ->order_by('e.end_time',$pInfo['sort_type'])
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

    //获取所有的事件
    public function get_all_events($pInfo,$processorID,$gid){
        //高级条件检索

        //查询条件构造
        $condition = array();
        // 时间条件
        if(!empty($pInfo['start_time'])){
            if ($pInfo["start_time"] != 0 && $pInfo["end_time"] != 0) {
                $condition[] = "ed.time > " . $pInfo["start_time"] . " AND ed.time < " . ($pInfo["end_time"] + 86400);
            } else if ($pInfo["start_time"] != 0 && $pInfo["end_time"] == 0) {
                $condition[] = "ed.time >= " . $pInfo["start_time"];
            } else if ($pInfo["start_time"] == 0 && $pInfo["end_time"] != 0) {
                $condition[] = "ed.time < " . ($pInfo["end_time"] + 86400);
            }
        }


        //单位事件条件
        if(($pInfo['is_group']) != ''){
            if($pInfo['is_group'] == 1){
                $condition[] = "ed.group !=''";
            }else{
                $condition[] = "ed.group";
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
            $data['aaData'] = $this->db->select("ed.event_id,e.title,e.rank,ed.time,ed.group,u.name,e.state,ed.description")
                ->from("event_designate as ed")
                ->join('event as e','e.id = ed.event_id','left')
                ->join('user as u','u.id = ed.manager','left')
                ->group_start()
                ->where('ed.processor',$processorID)
                ->or_where('ed.group',$gid)
                ->group_end()
                ->where($where)
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->limit($pInfo['length'],$pInfo['start'])
                ->order_by('ed.time',$pInfo['sort_type'])
                ->get()->result_array();

            $total = $this->db->select("ed.event_id,e.title,e.rank,ed.time,ed.group,u.name,e.state,ed.description")
                ->from("event_designate as ed")
                ->join('event as e','e.id = ed.event_id','left')
                ->join('user as u','u.id = ed.manager','left')
                ->group_start()
                ->where('ed.processor',$processorID)
                ->or_where('ed.group',$gid)
                ->group_end()
                ->where($where)
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->get()->num_rows();

        }else{
            $data['aaData'] = $this->db->select("ed.event_id,e.title,e.rank,ed.time,ed.group,u.name,e.state,ed.description")
                ->from("event_designate as ed")
                ->join('event as e','e.id = ed.event_id','left')
                ->join('user as u','u.id = ed.manager','left')
                ->group_start()
                ->where('ed.processor',$processorID)
                ->or_where('ed.group',$gid)
                ->group_end()
                ->group_start()
                ->like('e.title',$pInfo['search'])
                ->group_end()
                ->limit($pInfo['length'],$pInfo['start'])
                ->order_by('ed.time',$pInfo['sort_type'])
                ->get()->result_array();

            $total = $this->db->select("ed.event_id,e.title,e.rank,ed.time,ed.group,u.name,e.state,ed.description")
                ->from("event_designate as ed")
                ->join('event as e','e.id = ed.event_id','left')
                ->join('user as u','u.id = ed.manager','left')
                ->group_start()
                ->where('ed.processor',$processorID)
                ->or_where('ed.group',$gid)
                ->group_end()
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