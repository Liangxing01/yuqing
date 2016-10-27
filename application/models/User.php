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

    public function user_select($username){
        $sql = "SELECT * FROM yq_user WHERE username = ?";
        $query = $this->db->query($sql,$username);
        $row = $query->row();
        return $row;
    }

}