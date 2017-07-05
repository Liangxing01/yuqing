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
     * @param $login_type
     * @return bool
     */
    public function user_auth($username, $password, $login_type)
    {
        $this->CI->load->database();

        $password = md5($password);

        $user = $this->CI->db->select("id, username, name, password, ip, login_time, avatar")->where(array("username" => $username, "password" => $password, "is_exist !=" => 0))->get("user");

        if ($user->num_rows() > 0) {
            //更新用户认证token
            $token = md5($user->row()->username . time() . $login_type);
            if ($login_type == 1) {
                $this->CI->db->where("id", $user->row()->id)->update("user", array("m_token" => $token));
            } else {
                $this->CI->db->where("id", $user->row()->id)->update("user", array("token" => $token));
            }

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

            //设置token
            if ($login_type == 1) {
                $userdata["m_token"] = $token;
            } else {
                $userdata["p_token"] = $token; //pc端webscoket认证token
            }
            $this->CI->session->set_userdata($userdata);

            //下线不合法用户
//            $this->unofficial_offline();
            return $userdata;
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
     * @param $uid
     * @return string
     * 获取用户角色定位，指派人 督办人 都是指派角色，剩余 处理人
     */
    public function get_user_role($uid){
        $user_pri = $this->get_privilege($uid);
        $user_role = '';
        if(strpos($user_pri,'3') !== false){
            $user_role = 'handler';
        }
        if(strpos($user_pri,'4') !== false){
            $user_role = 'watcher';
        }
        if(strpos($user_pri,'2') !== false){
            $user_role = 'zhipai';
        }
        return $user_role;
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
            ->where("is_exist !=", 0)
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

    /**
     * 绑定websocket client_id 到 uid
     * @param $client_id
     */
    public function bind_uid($client_id)
    {
        try {
            //连接消息服务器
            Gateway::$registerAddress = $this->CI->config->item("VM_registerAddress");
            //绑定client_id到uid
            Gateway::bindUid($client_id, $this->CI->session->uid);
            //添加到用户组
            $gid = $this->CI->session->gid;
            if ($gid) {
                $user_group = explode(",", $gid);
                foreach ($user_group AS $group_id) {
                    Gateway::joinGroup($client_id, $group_id);
                }
            }
            //更新client认证token
            Gateway::setSession($client_id, array("p_token" => $this->CI->session->p_token, "uid" => $this->CI->session->uid));
        } catch (Exception $e) {
            log_message("error", $e->getMessage());
        }
    }

    //下线不合法用户
    public function unofficial_offline()
    {
        try {
            //连接消息服务器
            Gateway::$registerAddress = $this->CI->config->item("VM_registerAddress");
            //获取所有client
            $clients = Gateway::getClientIdByUid($this->CI->session->uid);
            foreach ($clients AS $client) {
                $client_session = Gateway::getSession($client);
                if ($client_session["p_token"] != $this->CI->session->p_token) {
                    Gateway::closeClient($client);
                }
            }
        } catch (Exception $e) {
            log_message("error", $e->getMessage());
        }
    }

    //检测用户认证信息
    public function is_authentic()
    {
//        $this->CI->load->database();
        if ($this->CI->session->has_userdata("p_token") && $this->CI->session->has_userdata("uid")) {
            //token 单用户登陆验证
//            $result = $this->CI->db->select("token")->where("id", $this->CI->session->uid)->get("user")->row();
//            if ($result->token != $this->CI->session->p_token) {
//                redirect(base_url("/login"));
//                exit(0);
//            }
            return true;
        } else {
            redirect(base_url("/login"));
            exit(0);
        }
    }

    //检测移动端用户登陆
    public function m_is_authentic()
    {
        $this->CI->load->database();
        $m_token = $this->CI->input->get_request_header("TOKEN", TRUE);
        if ($this->CI->session->has_userdata("m_token") && $this->CI->session->has_userdata("uid") && $m_token) {
            // 验证session是否过期
            return true;
        } else if ($m_token) {
            // 验证http头部的m_token值
            $user = $this->CI->db->select("id, username, name, password, ip, login_time, avatar")->where("m_token", $m_token)->get("user");
            if ($user->num_rows() > 0) {
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
                    "privilege" => $privilege ? $privilege : "",
                    "m_token" => $m_token
                );
                $this->CI->session->set_userdata($userdata);
                return true;
            } else {
                header("HTTP/1.1 504 User Expired");
            }
        } else {
            header("HTTP/1.1 504 User Expired");
        }
        exit(0);
    }

    //注销用户
    public function destroy()
    {
        session_destroy();
        redirect(base_url("/login"));
        exit(0);
    }

    //注销移动端用户
    public function m_destroy()
    {
        session_destroy();
        exit(0);
    }

}