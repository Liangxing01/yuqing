<?php
/**
 * Created by PhpStorm.
 * User: 黎佑民
 * Date: 2016/10/26
 * Time: 17:32
 */

class Report_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    public function submit($arr){
        $data = array(
            'title' => $arr['title'],
            'url' => $arr['url'],
            'source' => $arr['source'],
            'picture' => $arr['picture'],
            'publisher' => $arr['uid'],
            'start_time' => $arr['start_time'],
            'description' => $arr['description']
        );
        $this->db->insert('yq_event',$data);
    }


    /**
     * 提交记录 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function get_all_report($pInfo){
        $data['aaData'] = $this->db->select("event.id,title,url,source,description,user.name As publisher,start_time as time")
            ->from('event')
            ->join('user','user.id = event.publisher','left')
            ->limit(10,0)
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("event.id,title,url,source,description,user.name As publisher,start_time as time")
            ->from('event')
            ->join('user','user.id = event.publisher','left')
            ->get()->num_rows();

        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        return $data;
    }
}