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
(SELECT * FROM yq_event_designate  WHERE processor = ? ) as a JOIN yq_event as b on a.event_id = b.id
JOIN yq_user AS d on d.id = a.manager JOIN yq_event_type AS t on b.type = t.id WHERE b.state = \"已指派\" limit ?,?;";

            $sql2 = "SELECT a.id,b.title,t.name as type_name,a.time,a.description,d.username as zpname FROM
(SELECT * FROM yq_event_designate  WHERE processor = 3 ) as a JOIN yq_event as b on a.event_id = b.id
JOIN yq_user AS d on d.id = a.manager JOIN yq_event_type AS t on b.type = t.id WHERE b.state = \"已指派\";";


            $data['aaData'] = $this->db->query($sql,array($processorID,0,10))->result_array();
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

        //根据用户关键字查询待处理事件
        public function get_unhandle_by_keyword($key){
            $this->db->where('');
        }

        //查询正在处理事件
        public function get_doing_handle($pInfo,$processorID){
            $sql = "SELECT a.id,b.title,t.name as type_name, u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_log WHERE processor = ? and state ='处理中' and ISNULL(pid)) as a
JOIN yq_event as b on a.event_id = b.id JOIN yq_event_type as t on b.type = t.id JOIN yq_user as u on a.manager = u.id limit ?,?";

            $sql2 = "SELECT a.id,b.title,t.name as type_name, u.username as zpname,a.time,a.state FROM
(SELECT * from  yq_event_log WHERE processor = ? and state ='处理中' and ISNULL(pid)) as a
JOIN yq_event as b on a.event_id = b.id JOIN yq_event_type as t on b.type = t.id JOIN yq_user as u on a.manager = u.id";

            $data['aaData'] = $this->db->query($sql,array($processorID,0,10))->result_array();
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