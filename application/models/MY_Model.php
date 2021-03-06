<?php

/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/4
 * Time: 11:20
 */
class MY_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    //获取用户权限组
    public function get_privileges($uid)
    {
        $res = $this->db->select('up.pid')->from('user_privilege as up')
            ->where('uid', $uid)
            ->get()->result_array();
        return $res;
    }

    /**
     * @param $uid
     * @return boolean
     * 检查用户是否为初始密码
     */
    public function check_pwd_default(){
        $uid = $this->session->userdata('uid');
        $pwd = $this->db->select('password')
            ->from('user')
            ->where('id',$uid)
            ->get()->row_array();
        if(md5('123456') == $pwd['password']){
            return true;
        }else{
            return false;
        }
    }

    public function change_pwd($pass){
        $update_arr = array(
            'password' => md5($pass)
        );
        $uid = $this->session->userdata('uid');
        $this->db->where('id',$uid);
        $res = $this->db->update('user',$update_arr);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    //获取指派任务数
    public function get_zp_tasks($uid)
    {
        $unread_info_num = $this->db->select('id')
            ->from('info')
            ->where('state', 0)->get()->num_rows();
        $designate_num = $this->db->select('id')
            ->from('event')
            ->where('state', '已指派')->get()->num_rows();
        $un_confirm_num = $this->db->select('id')
            ->from('event')
            ->where('state', '未审核')
            ->get()->num_rows();
        $done_num = $this->db->select('id')
            ->from('event')
            ->where('state', '已完成')
            ->get()->num_rows();

        $num_arr = array(
            'unread_info_num' => $unread_info_num,
            'designate_num' => $designate_num,
            'un_confirm_num' => $un_confirm_num,
            'done_num' => $done_num
        );
        return $num_arr;
    }

    //获取处理任务数
    public function get_handler_num($uid)
    {
        $unread_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor', $uid)
            ->where('state', '未处理')->get()->num_rows();
        $doing_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor', $uid)
            ->where('state', '处理中')->get()->num_rows();
        $done_num = $this->db->select('e.id')->from('event_designate AS ed')
            ->join('event AS e', 'e.id = ed.event_id', 'left')
            ->where('ed.processor', $uid)
            ->group_start()
            ->where('e.state', '未审核')
            ->or_where('e.state', '已完成')
            ->group_end()
            ->get()->num_rows();
        $num_arr = array(
            'unread_num' => $unread_num,
            'doing_num' => $doing_num,
            'done_num' => $done_num,
            'all_num' => $unread_num + $doing_num + $done_num
        );
        return $num_arr;
    }

    //获取用户登录记录最新的6条
    public function get_login_list($uid, $num)
    {
        $res = $this->db->select('ip,from_unixtime(time) time')->from('login_log')
            ->where('uid', $uid)
            ->order_by('time', 'DESC')
            ->limit($num)
            ->get()->result_array();
        return $res;

    }

    /**
     * 获取天气信息
     * @param $login_info
     */
    public function weather()
    {
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, "http://www.weather.com.cn/data/sk/101040900.html");
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($output, TRUE);
        if (!$res) return array();
        else {
            $res = $res['weatherinfo'];
            $res['SD'] = substr($res['SD'], 0, -1);
            $res['wind'] = "$res[WD] $res[WS]";
            return $res;
        }
    }

    /*
     * 获取 处理人事件报警 提示
     */
    public function get_processor_alert($uid)
    {
        $data = $this->db->select('ea.id,ea.title,ea.event_id,from_unixtime(ea.time) time')->from('event_alert as ea')
            ->where(array('ea.uid' => $uid, "type" => 3))
            ->group_start()
            ->group_start()
            ->where(array(
                "ea.time - unix_timestamp(now()) >" => 0,
                "ea.time - unix_timestamp(now()) <" => 300, //5分钟内报警
                "state" => 2))
            ->group_end()
            ->or_group_start()
            ->where('ea.state', 1)
            ->group_end()
            ->group_end()
            ->get()->result_array();

        //取消报警
        $msg_id = array();
        foreach ($data AS $item) {
            $msg_id[] = $item["id"];
        }
        if (!empty($msg_id)) {
            $this->db->where_in("id", $msg_id)->update("event_alert", array("state" => 1));
        }

        return $data;
    }

    /*
     * 获取 指派人事件报警 超时报警
     */
    public function get_desi_alert($uid)
    {
        $data = $this->db->select('ea.id,ea.title,ea.event_id,from_unixtime(ea.time) time')->from('event_alert as ea')
            ->where(array('ea.uid' => $uid, "type" => 4))
            ->group_start()
            ->group_start()
            ->where(array(
                'unix_timestamp(now()) - ea.time >' => 0, // 超时开始报警
                "state" => 2))
            ->group_end()
            ->or_group_start()
            ->where('ea.state', 1)
            ->group_end()
            ->group_end()
            ->get()->result_array();

        //取消报警
        $msg_id = array();
        foreach ($data AS $item) {
            $msg_id[] = $item["id"];
        }
        if (!empty($msg_id)) {
            $this->db->where_in("id", $msg_id)->update("event_alert", array("state" => 1));
        }

        return $data;
    }

    //获取用户个人信息
    public function get_user_info($uid)
    {
        $data = $this->db->select('u.username,u.name,u.sex,u.avatar')->from('user AS u')
            ->where('u.id', $uid)
            ->get()->result_array();

        //查询所属单位
        $dep_data = $this->db->select('g.name')
            ->from('group as g')
            ->join('user_group as ug', 'ug.uid =' . $uid)
            ->where('g.id = ug.gid')
            ->where('ug.is_exist',1)
            ->get()->result_array();
        $deps = "";
        foreach ($dep_data as $dep) {
            $deps .= $dep['name'] . " ";
        }
        $data[0]['group_name'] = $deps;
        return $data;

    }

    //判断对这件事 是否有督办权
    public function check_duban($uid, $event_id)
    {
        $res = $this->db->select('watcher')->from('event_watch')
            ->where('event_id', $event_id)
            ->get()->result_array();
        if (!empty($res)) {
            foreach ($res as $one) {
                if ($one['watcher'] == $uid) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }

    //更换头像
    public function update_avatar($data)
    {
        $uid = $this->session->userdata('uid');
        //删除原头像
        $old_avatar = $this->db->select('avatar')->from('user')
            ->where('id', $uid)
            ->get()->row_array();
        if (!empty($old_avatar) && $old_avatar['avatar'] != "/img/avatar/avatar.png") {
            $old_avatar_url = $old_avatar['avatar'];
            @unlink($_SERVER['DOCUMENT_ROOT'] . $old_avatar_url);
        }

        $this->db->where('id', $uid);
        $res = $this->db->update('user', $data);
        $this->session->set_userdata("avatar", $data["avatar"]);
        return $res;
    }


    /**
     * 轮询获得30秒以内的消息
     */
    public function get_new_msg()
    {
        $pri = explode(",", $this->session->userdata('privilege'));
        foreach ($pri as $one) {
            switch ($one) {
                case 2:
                    $new_info = $this->db->select('id')->from('info')
                        ->where('unix_timestamp(now()) - time < 30')
                        ->where('state', 0)
                        ->get()->result_array();
                    continue;
                case 3:
                    $new_unhandler = $this->db->select('id')->from('event_designate')
                        ->where('unix_timestamp(now()) - time < 30')
                        ->where('processor', $this->session->userdata('uid'))
                        ->where('state', '未处理')
                        ->get()->result_array();
                    continue;
                default:
                    continue;
            }
        }

        if (!empty($new_unhandler)) {
            return "new_unhandle";
        } else if (!empty($new_info)) {
            return "new_info";
        } else {
            return false;
        }

    }


    public function insert_msg($data)
    {
        $this->db->insert('msg', $data);
    }


    /**
     * 首页最新消息接口
     * @return Json 字符串 [ {"title": "标题", "type": "1", "url": "跳转链接", "时间": "unix时间戳"} ]
     * 说明: 类型 0 = 信息上报消息; 1 = 事件指派消息; 2 = 事件督办消息
     */
    public function get_msg()
    {
        $uid = $this->session->uid;
        $gid = $this->session->gid;
        $result = array();
        if ($gid != "") {
            $group_id = explode(",", $gid);
            $result = $this->db->select('title, type, url, time')
                ->where("send_uid", $uid)
                ->or_where_in("send_gid", $group_id)
                ->limit(5)
                ->order_by('time', 'DESC')
                ->get("business_msg")->result_array();
        }
        return json_encode($result);
    }


    /**
     *  获取职位
     */
    public function get_job()
    {
        $res = $this->db->select('job')->from('user')
            ->where('id', $this->session->userdata('uid'))
            ->get()->row_array();
        return $res['job'];
    }


    /**
     * 首页全站消息通知列表
     */
    public function get_site_message_list()
    {
        return $this->db->select("id, title, content, time")
            ->where("type", "all")
            ->order_by("time", "desc")
            ->limit(5)
            ->get("announce")->result_array();
    }

    /**
     * -------------------------layerIM--------------------
     */
    public function getIMUserInfo(){
        $uid = $this->session->uid;
        $userInfo = $this->db->select('user.id,user.name,user.avatar,group.name as gname')
            ->from('user')
            ->join('user_group as ug','ug.uid = user.id')
            ->join('group','group.id = ug.gid')
            ->where('user.id',$uid)
            ->where('ug.is_exist',1)
            ->get()->row_array();
        $userInfo['name'] = $userInfo['name'].'('.$userInfo['gname'].')';
        return $userInfo;
    }

    public function getGroupMembers(){
        //连接消息服务器
        $this->load->library("Gateway");
        Gateway::$registerAddress = $this->config->item("VM_registerAddress");

        $users = $this->db->select("id,name,avatar")->get("user")->result_array();
        $userList = array();
        foreach ($users as $user){
            //判断用户是否在线
            if (Gateway::isUidOnline($user["id"]) != 0) {
                array_push($userList,array(
                    "username"  =>  $user['name'],
                    "id"        =>  $user['id'],
                    "avatar"    =>  $user['avatar'],
                    "sign"      =>  ""
                ));
            }
        }
        $res = array(
            'code'  =>  0,
            'msg'   =>  '',
            'data'  =>  array(
                'list'  =>  $userList
            )
        );
        return $res;
    }

}