<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate_Model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    public function event_not_designate_pagination($pInfo)
    {
        $data['aaData'] = $this->db->select("event.id, event.title, event_type.name AS type, source, user.username AS publisher, start_time")
                        ->from("event")
                        ->join("user", "user.id = event.publisher", "left")
                        ->join("event_type", "event_type.id = event.type", "left")
                        ->where(array("state" => "已上报"))
                        ->limit(10, 0)
                        ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("event.id, event.title, event_type.name AS type, source, user.username AS publisher, start_time")
                        ->from("event")
                        ->join("user", "user.id = event.publisher", "left")
                        ->join("event_type", "event_type.id = event.type", "left")
                        ->where(array("state" => "已上报"))
                        ->get()->num_rows();

        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        return $data;

    }


    public function test()
    {
        return $this->db->get("event")->result_array();
    }

}