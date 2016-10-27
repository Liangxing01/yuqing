<?php
//处理人模型
    class Handler_model extends CI_Model{
        public function __construct(){
            $this->load->database();
        }

        //查询所有待处理事件
        public function get_all_unhandle($pInfo,$processorID){
            //多表联合查询
            $sql  = "SELECT a.id,b.title,t.name as type_name ,a.time,a.description,d.username as zpname FROM
(SELECT * FROM yq_event_designate  WHERE processor = ? ) as a LEFT JOIN yq_event as b on a.event_id = b.id
LEFT JOIN yq_user AS d on d.id = a.manager LEFT JOIN yq_event_type AS t on b.type = t.id WHERE b.state = \"已指派\" limit ?,?;";

            $sql2 = "SELECT a.id,b.title,t.name as type_name,a.time,a.description,d.username as zpname FROM
(SELECT * FROM yq_event_designate  WHERE processor = 3 ) as a LEFT JOIN yq_event as b on a.event_id = b.id
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
                ->where(array("e.id"=>(int)$id))
                ->get()->result_array();

            if(!empty($data)){
                return $data[0];
            }else{
                return false;
            }
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