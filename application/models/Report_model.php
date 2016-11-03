<?php


class Report_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    public function add_or_update($arr){
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
        $id = $arr['id'];

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
        if ($id == null){
            $this->db->insert('yq_info',$data);
        }else{
            $this->db->where('info.id',$arr['id']);
            $this->db->update('info',$data);
        }
        return $nu = $this->db->affected_rows();
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
            ->limit($pInfo["length"], $pInfo["start"])
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("info.id,title,url,source,description,user.name As publisher,time")
            ->from('info')
            ->join('user','user.id = info.publisher','left')
            ->where(array('user.id'=> (int)$uid))
            ->get()->num_rows();

        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        return $data;
    }

    /**
     * 获取详细记录
     * @param $id 记录id
     * @return bool 没有获取到记录
     */
    public function get_detail_by_id($id){
        $data = $this->db->select("info.id,title,url,source,picture,description,user.name As publisher,time")
            ->from("info")
            ->join("user","user.id = info.publisher","left")
            ->where("info.id",(int)$id)
            ->get()->result_array();

        return $data == null?false:$data[0];
    }

    /**
     * 修改页面url判断
     * @param $url 输入的url
     * @param $id 记录id
     * @return int 有无重复，0表示没有重复，反之取出第一条
     */

    public function edit_judge_url($url,$id){
        $data = $this->db->select("*")
            ->from('info')
            ->where(array('url'=>$url,
                'id!='=>$id))
            ->limit(10,0)
            ->get()->result_array();
        return $data == null? 0:$data[0];
    }

    /**
     * 提交页面url判断
     * @param $url 输入的url
     * @param $uid 自己的id
     * @return int 有无重复，0表示没有重复
     */

    public function judge_url($url,$uid){
        $data = $this->db->select("*")
            ->from('info')
            ->where(array('url'=>$url,
                'publisher'=>$uid))
            ->limit(10,0)
            ->get()->result_array();

        return $data == null?0:1;
    }

    /**
     * 获取状态值
     * @param $id 记录id
     * @return mixed 返回记录id和其状态值
     */

    public function get_state($id){
        $data = $this->db->select("id,state")
            ->from('info')
            ->where('id',(int)$id)
            ->get()->result_array();

        return $data;
    }

    /**
     * 删除记录
     * @param $id  记录id
     * @return mixed 影响条数
     */
    public function del($id){
        $this->db->delete('info',array('id'=>$id));
        return $nu = $this->db->affected_rows();
    }
}