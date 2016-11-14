<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/4
 * Time: 11:20
 */

class MY_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    //获取用户权限组
    public function get_privileges($uid){
        $res = $this->db->select('up.pid')->from('user_privilege as up')
            ->where('uid',$uid)
            ->get()->result_array();
        return $res;
    }

    //获取指派任务数
    public function get_zp_tasks($uid){
        $unread_info_num = $this->db->select('id')
            ->from('info')
            ->where('state',0)->get()->num_rows();
        $designate_num = $this->db->select('id')
            ->from('event')
            ->where('state','已指派')->get()->num_rows();
        $un_confirm_num = $this->db->select('id')
            ->from('event')
            ->where('state','未审核')
            ->get()->num_rows();
        $done_num = $this->db->select('id')
            ->from('event')
            ->where('state','已完成')
            ->get()->num_rows();

        $num_arr = array(
            'unread_info_num' => $unread_info_num,
            'designate_num'  => $designate_num,
            'un_confirm_num'   => $un_confirm_num,
            'done_num'    => $done_num
        );
        return $num_arr;
    }

    //获取处理任务数
    public function get_handler_num($uid){
        $unread_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor',$uid)
            ->where('state','待处理')->get()->num_rows();
        $doing_num = $this->db->select('id')
            ->from('event_designate')
            ->where('processor',$uid)
            ->where('state','处理中')->get()->num_rows();
        $done_num = $this->db->select('e.id')->from('event_designate AS ed')
            ->join('event AS e','e.id = ed.event_id','left')
            ->where('ed.processor',$uid)
            ->group_start()
            ->where('e.state','未审核')
            ->or_where('e.state','已完成')
            ->group_end()
            ->get()->num_rows();
        $num_arr = array(
            'unread_num' => $unread_num,
            'doing_num'  => $doing_num,
            'done_num'   => $done_num,
            'all_num'    => $unread_num+$doing_num+$done_num
        );
        return $num_arr;
    }

    //获取用户登录记录最新的6条
    public function get_login_list($uid,$num){
        $res = $this->db->select('ip,from_unixtime(time) time')->from('login_log')
            ->where('uid',$uid)
            ->order_by('time','DESC')
            ->limit($num)
            ->get()->result_array();
        return $res;

    }

    /**
     * 获取天气信息
     * @param $login_info
     */
    public function weather(){
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, "http://www.weather.com.cn/data/sk/101040900.html");
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $res=json_decode($output,TRUE);
        if (!$res) return array();
        else{
            $res=$res['weatherinfo'];
            $res['SD']=substr($res['SD'],0,-1);
            $res['wind']="$res[WD] $res[WS]";
            return $res;
        }
    }

    /*
     * 获取 处理人事件报警 提示
     */
    public function get_processor_alert($uid){
        $data = $this->db->select('ea.title,ea.event_id,from_unixtime(ea.time) time')->from('event_alert as ea')
            ->where('ea.uid',$uid)
            ->where('ea.time - unix_timestamp(now()) < 300')// 时间小于5分钟开始报警
            ->where('ea.state',1)
            ->limit(6)
            ->get()->result_array();
        return $data;
    }

    /*
     * 获取 指派人事件报警 超时报警
     */
    public function get_desi_alert($uid){
        $data = $this->db->select('ea.title,ea.event_id,from_unixtime(ea.time) time')->from('event_alert as ea')
            ->where('ea.uid',$uid)
            ->where('unix_timestamp(now()) > ea.time')// 超时开始报警
            ->where('ea.state',1)
            ->limit(6)
            ->get()->result_array();
        return $data;
    }

    //获取用户个人信息
    public function get_user_info($uid){
        $data = $this->db->select('u.group_id,u.username,u.name,u.sex,u.avatar,group.name as group_name')->from('user AS u')
            ->join('group','group.id = u.group_id','left')
            ->where('u.id',$uid)
            ->get()->result_array();
        return $data;
    }

    //修改个人信息接口
    public function update_info($data){
        $uid = $this->session->userdata('uid');

        $this->db->where('id',$uid);
        $res = $this->db->update('user',$data);
        return $res;
    }

    //更换头像
    public function update_avatar($data){
        $uid = $this->session->userdata('uid');
        //删除原头像
        $old_avatar = $this->db->select('avatar')->from('user')
            ->where('id',$uid)
            ->get()->row_array();
        if(!empty($old_avatar)){
            $old_avatar_url = $old_avatar['avatar'];
            unlink($_SERVER['DOCUMENT_ROOT'].$old_avatar_url);
        }

        $this->db->where('id',$uid);
        $res = $this->db->update('user',$data);
        return $res;
    }

    //检查 原密码是否正确
    public function check_old_pass($old,$uid){
        $pass = $this->db->select('password')->from('user')
            ->where('id',$uid)
            ->get()->result_array();
        if(empty($pass)){
            return false;
        }else{
            if(md5($old) == $pass[0]['password']){
                return true;
            }else{
                return false;
            }
        }
    }

    //更新密码
    public function update_psw($new,$uid){
        $data = array(
          'password' => md5($new)
        );
        $this->db->where('id',$uid);
        $res = $this->db->update('user',$data);
        if($res){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 轮询获得30秒以内的消息
     */
    public function get_new_msg(){
        //未处理事件
        $new_unhandler = $this->db->select('id')->from('event_designate')
            ->where('unix_timestamp(now()) - time < 30')
            ->where('processor',$this->session->userdata('uid'))
            ->get()->result_array();
        $new_msg = $this->db->select('id')->from('msg')
            ->where('unix_timestamp(now()) - time < 30')
            ->get()->result_array();
        if(!empty($new_unhandler) || !empty($new_msg)){
            return true;
        }else{
            return false;
        }

    }

    public function insert_msg($data){
        $this->db->insert('msg',$data);
    }


}