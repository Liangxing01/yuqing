<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Identity_Auth
{

    private $username;
    private $password;
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * 验证用户登录信息
     * 设置认证session[uid, username, name, gid, gname, privilege]
     * @param $username
     * @param $password
     * @return bool
     */
    public function user_auth($username, $password)
    {
        $this->CI->load->database();

        $password = md5($password);

        $user = $this->CI->db->query("SELECT yq_user.id, group_id, yq_group.name AS gname, username, yq_user.name, password, ip, login_time, avatar FROM yq_user LEFT JOIN yq_group ON yq_user.group_id = yq_group.id WHERE username = ? AND password = ?", array($username, $password));

        if ($user->num_rows() > 0) {
            //更新用户认证token
            $token = md5($user->row()->username . time());
            $this->CI->db->where("id", $user->row()->id)->update("user", array("token" => $token));
            //用户认证token存到cookie中
            setcookie("p_token", $token, $expire = 0, $path = "/");

            //用户认证session
            $privilege = $this->get_privilege($user->row()->id);
            $userdata = array(
                "uid" => $user->row()->id,
                "username" => $user->row()->username,
                "name" => $user->row()->name,
                "gid" => $user->row()->group_id,
                "gname" => $user->row()->gname,
                "avatar" => $user->row()->avatar,
                "privilege" => $privilege ? $privilege : ""
            );
            $this->CI->session->set_userdata($userdata);

            return true;
        } else {
            return false;
        }
    }


    /**
     * 获取权限id
     * return string for success, false for failed
     */
    private function get_privilege($uid)
    {
        $this->CI->load->database();
        $privileges = $this->CI->db->select("pid")->where("uid", $uid)->get("user_privilege")->result_array();
        if (!empty($privileges)) {
            $str = "";
            foreach ($privileges AS $p) {
                $str .= $p["pid"] . ",";
            }
            return substr($str, 0, strlen($str) - 1);
        } else {
            return false;
        }
    }


    //检测用户认证信息
    public function is_authentic()
    {
        if ($this->CI->session->has_userdata("uid")) {
            return true;
        } else {
            redirect(base_url("/login"));
            exit(0);
        }
    }

    //注销用户
    public function destroy()
    {
        session_destroy();
        setcookie("p_token", "", $expire = time() - 1, $path = "/");
        redirect(base_url("/login"));
        exit(0);
    }

}