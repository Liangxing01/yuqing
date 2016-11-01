<?php


class Report_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    public function submit($arr){
        $dup = 0;
        if ($arr['url'] !== null){
            $judge = $this->db->select("*")
                ->from("info")
                ->where(array('url'=>$arr['url']))
                ->limit(10,0)
                ->get()->result_array();
            if ($judge != null){
                $dup = 1;
            }
        }

        $data = array(
            'title' => $arr['title'],
            'url' => $arr['url'],
            'source' => $arr['source'],
            'picture' => $arr['picture'],
            'description' => $arr['description'],
            'publisher' => $arr['uid'],
            'time' => $arr['time'],
            'state'=> 0,
            'duplicate'=>$dup
        );
        $this->db->insert('yq_info',$data);
    }

    public function update($arr){
        $dup = 0;
        if ($arr['url'] !== null){
            $judge = $this->db->select("*")
                ->from("info")
                ->where(array('url'=>$arr['url']))
                ->limit(10,0)
                ->get()->result_array();
            if ($judge != null){
                $dup = 1;
            }
        }
        $data = array(
            'title' => $arr['title'],
            'url' => $arr['url'],
            'source' => $arr['source'],
            'picture' => $arr['picture'],
            'description' => $arr['description'],
            'publisher' => $arr['uid'],
            'time' => $arr['time'],
            'state'=> 0,
            'duplicate'=>$dup
        );
        $this->db->where('info.id',$arr['id']);
        $this->db->update('info',$data);
    }


    /**
     * 提交记录 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function get_all_report($pInfo,$uid){
        $data['aaData'] = $this->db->select("info.id,title,url,source,description,user.name As publisher,time")
            ->from('info')
            ->join('user','user.id = info.publisher','left')
            ->where(array('user.id'=> (int)$uid))
            ->limit(10,0)
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("info.id,title,url,source,description,user.name As publisher,time")
            ->from('info')
            ->join('user','user.id = info.publisher','left')
            ->get()->num_rows();

        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        return $data;
    }

    public function get_detail_by_id($id){
        $data = $this->db->select("info.id,title,url,source,picture,description,user.name As publisher,time")
            ->from("info")
            ->join("user","user.id = info.publisher","left")
            ->where("info.id",(int)$id)
            ->get()->result_array();

        if(!empty($data)){
            return $data[0];
        }else{
            return false;
        }
    }

    public function judge_url($url){
        $data = $this->db->select("*")
            ->from('info')
            ->where(array('url'=>$url))
            ->limit(10,0)
            ->get()->result_array();
        if ($data == null){
            echo 0;
        }else{
            echo 1;
        }
    }

    public function get_state($id){
        $data = $this->db->select("id,state")
            ->from('info')
            ->where('id',(int)$id)
            ->get()->result_array();

        return $data;
    }

    public function del($id){
        $this->db->delete('info',array('id'=>$id));
        return $nu = $this->db->affected_rows();
    }
}