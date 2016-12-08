<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'Gateway.php';

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

        $user = $this->CI->db->select("id, username, name, password, ip, login_time, avatar")->where(array("username" => $username, "password" => $password))->get("user");

        if ($user->num_rows() > 0) {
            //更新用户认证token
            $token = md5($user->row()->username . time());
            $this->CI->db->where("id", $user->row()->id)->update("user", array("token" => $token));
            //用户认证token存到cookie中
            setcookie("p_token", $token, $expire = 0, $path = "/");

            //用户认证session
            $privilege = $this->get_privilege($user->row()->id);
            $group = $this->get_group_info($user->row()->id);
            $userdata = array(
                "uid" => $user->row()->id,
                "username" => $user->row()->username,
                "name" => $user->row()->name,
                "gid" => $group["id"],
                "gname" => $group["name"],
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
     * @param $uid
     * @return string for success, false for failed
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


    /**
     * 获取用户 组织信息
     * @param $uid
     * @return array ["id", "name"]
     */
    private function get_group_info($uid)
    {
        $this->CI->load->database();
        $group = $this->CI->db->select("gid, group.name")
            ->join("group", "group.id = user_group.gid", "left")
            ->where("user_group.uid", $uid)
            ->get("user_group")->result_array();

        $info = array("id" => "", "name" => "");
        foreach ($group AS $item) {
            $info["id"] .= $item["gid"] . ",";
            $info["name"] .= $item["name"] . ",";
        }

        if ($info["id"] !== "") {
            $info["id"] = substr($info["id"], 0, strlen($info["id"]) - 1);
        }
        if ($info["name"] !== "") {
            $info["name"] = substr($info["name"], 0, strlen($info["name"]) - 1);
        }
        return $info;
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
        if (isset($_COOKIE["client_id"])) {
            Gateway::closeClient($_COOKIE["client_id"]); //关闭websocket 连接
        }
        session_destroy();
        setcookie("p_token", "", $expire = time() - 1, $path = "/");
        redirect(base_url("/login"));
        exit(0);
    }

}