<?php

class User extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    public function verify_users($username,$password){
        $res['data'] = $this->db->select("user.id,user.name,group_id as gid,group.name as gname,user_privilege.pid")
            ->from('user')
            ->join('group',"group.id = user.group_id","left")
            ->join('user_privilege',"user.id = user_privilege.uid","left")
            ->where(array('username'=>$username,
                'password'=>md5($password)))
            ->limit(1,0)
            ->get()->result_array();
        $res['flag'] = false;
        if($res['data'] !== null){
            $res['flag'] = true;
        }
        return $res;
    }

}