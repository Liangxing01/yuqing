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

    //获取用户登录记录最新的6条
    public function get_login_list($uid,$num){
        $res = $this->db->select('ip,time')->from('login_log')
            ->where('uid',$uid)
            ->order_by('time','DESC')
            ->limit($num)
            ->get()->result_array();
        return $res;

    }

    //获取用户个人信息
    public function get_user_info($uid){
        $data = $this->db->select('u.group_id,u.username,u.name,u.sex,u.avatar,p.keyword,group.name')->from('user AS u')
            ->join('group','group.id = u.group_id','left')
            ->join('user_privilege as up','up.uid = '.$uid,'left')
            ->join('privilege as p','p.id = up.pid','left')
            ->where('u.id',$uid)
            ->get()->result_array();
        return $data;
    }

    //修改个人信息接口
    public function update_info($data,$uid){
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


}