<?php
//处理人模型
    class Handler_model extends CI_Model{
        public function __construct(){
            $this->load->database();
        }

        //查询所有待处理事件
        public function get_all_unhandle($processorID,$per,$offset){
            //多表联合查询
            $sql  = "SELECT a.id,a.time,a.description,c.username as processor,d.username as zpname,b.title FROM
(SELECT * FROM yq_event_designate  WHERE processor = 3 ) as a JOIN yq_event as b on a.event_id = b.id
JOIN yq_user as c on c.id = a.processor JOIN yq_user AS d on d.id = a.manager WHERE b.state = \"已指派\";";

            $sql2 = "SELECT a.id,a.time,a.description,c.username as processor,d.username as zpname,b.title FROM
(SELECT * FROM yq_event_designate  WHERE processor = 3 ) as a JOIN yq_event as b on a.event_id = b.id
JOIN yq_user as c on c.id = a.processor JOIN yq_user AS d on d.id = a.manager WHERE b.state = \"已指派\";";
            $data = $this->db->query($sql,array($offset,$per))->result_array();
            $data['total'] = count($this->db->query($sql2)->result_array());

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


    }