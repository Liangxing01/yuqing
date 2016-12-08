<?php


class Report_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    /**
     * 添加事件信息
     * @param $arr 事件信息
     * @return mixed 影响条数
     */
    public function add_info($arr)
    {
        //检测url是否重复
        $dup = 0;
        if ($arr['url'] !== null) {
            $judge = $this->db->select("*")
                ->from("info")
                ->where(array('url' => $arr['url']))
                ->limit(10, 0)
                ->get()->result_array();
            if ($judge != null) {
                $dup = 1;
            }
        }
        //事件信息数据
        $data = array(
            'title' => $arr['title'],
            'url' => $arr['url'],
            'source' => $arr['source'],
            'description' => $arr['description'],
            'publisher' => $arr['uid'],
            'time' => time(),
            'state' => 0,
            'duplicate' => $dup
        );
        $res = $this->db->insert('yq_info', $data);
        $info_id = $this->db->insert_id();
        if ($res) {
            //TODO 上报消息 推送
            try {
                //连接消息服务器
                $this->load->library("Gateway");
                Gateway::$registerAddress = $this->config->item("VM_registerAddress");

                //业务消息推送
                //用户 上报信息 推送给在线指派人
                $managers = $this->db->select("user.id")
                    ->join("user_privilege", "user_privilege.uid = user.id")
                    ->where("user_privilege.pid", 2)
                    ->get("user")->result_array();
                foreach ($managers AS $manager) {
                    Gateway::sendToUid($manager["id"], json_encode(array(
                        "title" => $data["title"],
                        "time" => $data["time"],
                        "type" => 0,
                        "url" => "/designate/info_detail?id=" . $info_id
                    )));
                }
            } catch (Exception $e) {
                log_message("error", $e->getMessage());
            }
            return $info_id;
        } else {
            return false;
        }
    }

    /**
     * 插入附件信息
     * @param $att
     * @param $info_id
     * @return bool
     */
    public function insert_att_info($att,$info_id){
        $res = false;
        foreach ($att as $one){
            if($one['isPic'] == 1){
                $data = array(
                    'info_id' => $info_id,
                    'name'    => $one['name'],
                    'url'     => "/uploads/pic/".$one['new_name'],
                    'type'    => "pic"
                );
                $res = $this->db->insert('info_attachment',$data);
                //移动文件
                copy($_SERVER['DOCUMENT_ROOT']."/uploads/temp/".$one['new_name'],$_SERVER['DOCUMENT_ROOT']."/uploads/pic/".$one['new_name']);
                unlink($_SERVER['DOCUMENT_ROOT']."/uploads/temp/".$one['new_name']);
                if(!$res){
                    break;
                }
            }else{
                $data = array(
                    'info_id' => $info_id,
                    'name'    => $one['name'],
                    'url'     => "/uploads/video/".$one['new_name'],
                    'type'    => "video"
                );
                $res = $this->db->insert('info_attachment',$data);
                //移动文件
                copy($_SERVER['DOCUMENT_ROOT']."/uploads/temp/".$one['new_name'],$_SERVER['DOCUMENT_ROOT']."/uploads/video/".$one['new_name']);
                unlink($_SERVER['DOCUMENT_ROOT']."/uploads/temp/".$one['new_name']);
                if(!$res){
                    break;
                }
            }
        }
        return $res;
    }


    /**
     * 提交记录 分页数据
     * @param $pInfo
     * @return mixed
     */
    public function get_all_report($pInfo,$uid){
        $data['aaData'] = $this->db->select("info.id,title,url,source,description,user.name As publisher,time,state")
            ->from('info')
            ->join('user','user.id = info.publisher','left')
            ->where(array('user.id'=> (int)$uid))
            ->order_by("time", $pInfo["sort_type"])
            ->limit($pInfo["length"], $pInfo["start"])
            ->get()->result_array();

        //查询总记录条数
        $total = $this->db->select("id")
            ->from('info')
            ->where(array('publisher'=> (int)$uid))
            ->get()->num_rows();

        $data['sEcho']                = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords']        = $total;

        return $data;
    }


    /**
     * 移动端 提交记录 分页数据
     * @param $page_num 当前页码
     * @return string Json字符串
     */
    public function scroll_record_pagination($page_num)
    {
        $length = 10; //默认查10条记录
        $start = ($page_num - 1) * $length;
        $uid = $this->session->uid;
        $result = $this->db->select("info.id, title, url, time")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->limit($length, $start)
            ->order_by("time", "desc")
            ->get("info")->result_array();
        return json_encode($result);
    }


    /**
     * 获取详细记录
     * @param $id 记录id
     * @return bool 没有获取到记录
     */
    public function get_detail_by_id($id){
        $data = $this->db->select("info.id,title,url,source,description,user.name As publisher,time")
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