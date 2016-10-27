<?php
//处理人模型
    class Handler_Model extends CI_Model{
        public function __construct(){
            $this->load->database();
        }

        //查询所有待处理事件
        public function get_all_unhandle($pInfo,$processorID){
            //多表联合查询
            $sql  = "SELECT a.event_id,b.title,t.name as type_name ,a.time,a.description,d.username as zpname FROM
(SELECT * FROM yq_event_designate  WHERE processor = ? ) as a LEFT JOIN yq_event as b on a.event_id = b.id
LEFT JOIN yq_user AS d on d.id = a.manager LEFT JOIN yq_event_type AS t on b.type = t.id WHERE b.state = \"已指派\" limit ?,?;";

            $sql2 = "SELECT a.event_id,b.title,t.name as type_name,a.time,a.description,d.username as zpname FROM
(SELECT * FROM yq_event_designate  WHERE processor = ? ) as a LEFT JOIN yq_event as b on a.event_id = b.id
LEFT JOIN yq_user AS d on d.id = a.manager LEFT JOIN yq_event_type AS t on b.type = t.id WHERE b.state = \"已指派\";";


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
            $data = $this->db->select("e.id,e.title,t.name as type_name,e.url,e.source,e.picture,e.description,yq_user.name,e.start_time")
                ->from("event AS e")
                ->join("event_type as t","t.id = e.type","left")
                ->join("user","e.manager = yq_user.id","left")
                ->where("e.id",(int)$id)
                ->where("e.state","已指派")
                ->get()->result_array();

            if(!empty($data)){
                return $data[0];
            }else{
                return false;
            }
        }

        //获取事件所有操作记录
        public function get_all_logs($eid,$uid){
            //判断是否为第一次处理，则插入一条父类记录
            $this->db->select('id,pid');
            $num = $this->db->get_where('event_log',array('event_id'=>$eid,'processor'=>$uid))->result_array();
            if(empty($num)){
                $this->insert_parent_log($eid,$uid);
            }
        }

        //开始执行处理任务，向数据库插入一条记录
        public function insert_parent_log($event_id,$uid){
            //根据事件id 查询指派记录id
            $this->db->select('id');
            $zpID = $this->db->get_where('event_designate',array('event_id' => $event_id,'processor' => $uid))->result_array();
            $zpID = $zpID[0]['id'];
            $arr = array(
                'event_id' => $event_id,
                ''
            );
        }

        //根据用户关键字查询待处理事件
        public function get_unhandle_by_keyword($key){
            $this->db->where('');
        }

        //查询正在处理事件
        public function get_doing_handle($pInfo,$processorID){
            $sql = "SELECT a.id,b.title,t.name as type_name, u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_log WHERE processor = ? and state ='处理中' and ISNULL(pid)) as a
LEFT JOIN yq_event as b on a.event_id = b.id LEFT JOIN yq_event_type as t on b.type = t.id LEFT JOIN yq_user as u on a.manager = u.id limit ?,?";

            $sql2 = "SELECT a.id,b.title,t.name as type_name, u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_log WHERE processor = ? and state ='处理中' and ISNULL(pid)) as a
LEFT JOIN yq_event as b on a.event_id = b.id LEFT  JOIN yq_event_type as t on b.type = t.id LEFT JOIN yq_user as u on a.manager = u.id";

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


    }