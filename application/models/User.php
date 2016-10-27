<?php
/**
 * Created by PhpStorm.
 * User: 黎佑民
 * Date: 2016/10/26
 * Time: 14:47
 */

class User extends CI_Model {

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
    }

    public function verify_users($username,$password){
        $pwd = md5($password);
        $sql ="SELECT *  FROM yq_user WHERE username = ? AND password = ?";
        $query =  $this->db->query($sql,array($username,$pwd))->row();
        $flag = false;
        $val = -1;
        if($query !== null){
            $flag = true;
            $val = $query->id;
        }
        $res = array(
            "flag" => $flag,
            "id" => $val
            );
        return $res;
    }

}