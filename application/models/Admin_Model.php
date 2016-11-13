<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/8
 * Time: 11:20
 */

class Admin_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    /**
     * 获取 信息类型 列表
     * @return array
     */
    public function get_info_type()
    {
        return $this->db->get("type")->result_array();
    }

    /**
     * @param $data
     */
    public function add_person($data){
        $userInfo = array(
            'username' => $data['username'],
            'password' => md5($data['password']),
            'name'     => $data['name'],
            'group_id' => $data['gid'],
            'sex'      => $data['sex']
        );
        $res1 = $this->db->insert('user',$userInfo);
        $uid = $this->db->insert_id();
        $res2 = false;

        //插入权限表
        $pri = $data['privilege'];
        $pri_arr = explode(",",$pri);
        foreach ($pri_arr as $one){
            $insert_pri = array(
                'pid' => $one,
                'uid' => $uid
            );
            $res2 = $this->db->insert('user_privilege',$insert_pri);
        }
        if($res1 && $res2){
            return true;
        }else{
            return false;
        }

    }




}