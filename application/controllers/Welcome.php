<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'my_model');
        $this->identity->is_authentic();
        $this->load->helper(array("public"));
    }


    /**
     * 首页视图 载入
     */
    public function index()
    {
        $this->load->helper(array("public"));
        //获取用户权限
        $user_role = $this->identity->get_user_role($this->session->uid);
        $this->assign('user_role',$user_role);
        $this->assign("active_title", "home_page");
        $this->assign("active_parent", "home_parent");
        /*$weather = $this->my_model->weather();
        $this->assign($weather, null);*/
        $login = $this->my_model->get_login_list($this->session->userdata('uid'), 5);
        $this->assign("avatar", $this->session->avatar);
        $this->assign('login', $login);
        if (isMobile()) {
            $this->m_all_display("m_index.html");
        } else {
            $this->all_display('index.html');
        }
    }

    /**
     * 接口：检查密码是否为 123456 默认密码，如果是强制弹出修改密码
     */
    public function is_default_pwd(){
        $res = $this->my_model->check_pwd_default();
        if($res){
            echo 1;
        }else{
            echo 0;
        }
    }

    /**
     * 接口：强制修改密码
     */
    public function change_pwd(){
        $new_pass = $this->input->get('new_pass');
        $res = $this->my_model->change_pwd($new_pass);
        if($res){
            echo 1;
        }else{
            echo 0;
        }
    }


    /*
     * 主页接口：获取指派工作和处理工作任务数接口
     */
    public function get_all_tasks_num()
    {
        $uid = $this->session->userdata('uid');
        //$pri = $this->my_model->get_privileges($uid);
        $pri = explode(",", $this->session->userdata('privilege'));
        $all_num_arr = array();
        if (!empty($pri)) {
            foreach ($pri as $one) {
                switch ($one) {
                    case 2 :
                        $zp_tasks_num_arr = $this->my_model->get_zp_tasks($uid);
                        break;
                    case 3:
                        $handler_num_arr = $this->my_model->get_handler_num($uid);
                        break;
                }

            }
            if (isset($zp_tasks_num_arr)) {
                $all_num_arr['zp'] = $zp_tasks_num_arr;
            }
            if (isset($handler_num_arr)) {
                $all_num_arr['handler'] = $handler_num_arr;
            }
        }
        echo json_encode($all_num_arr);

    }


    /**
     * 首页全站通知列表
     */
    public function get_site_message()
    {
        $result = $this->my_model->get_site_message_list();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /*
     * 主页接口：获取警告事件
     * 根据权限判断 来返回 警告事件
     */
    public function get_event_alert()
    {
        $uid = $this->session->userdata('uid');
        $pri = explode(",", $this->session->userdata('privilege'));
        $alarm_arr = array();
        foreach ($pri as $one) {
            switch ($one) {
                //指派人提醒
                case 2 :
                    $zp_alarm = $this->my_model->get_desi_alert($uid);
                    $alarm_arr['zp_alarm'] = $zp_alarm;
                    break;
                //处理人提醒
                case 3 :
                    $processor_alarm = $this->my_model->get_processor_alert($uid);
                    $alarm_arr['processor_alarm'] = $processor_alarm;
                    break;
            }
        }

        echo json_encode($alarm_arr);
    }


    /**
     * 接口：发表评论
     * 参数：事件id，是否为总结性发言标志位，父总结性发言id
     * event tracer 时间线视图回复接口
     */
    public function post_comment()
    {
        $this->load->model("Handler_Model", "handler_model");
        $event_id = $this->input->post('eid');
        $pid = $this->input->post('pid');
        $comment = $this->input->post('comment');
        $res = $this->handler_model->insert_comment($event_id, $pid, $comment);
        //更新其他人阅读状态为0，即提醒有新消息
        $this->load->model('Common_Model','common');
        $this->common->update_other_unread($event_id,$this->session->uid);

        if ($res['res']) {
            $data = array(
                'res' => 1,
                'id' => $res['id']
            );
        } else {
            $data = array(
                'res' => 0
            );
        }
        echo json_encode($data);
    }


    /**
     * 展示个人信息页面
     */
    public function show_my_info()
    {
        $this->assign("active_title", "my_info");
        $this->assign("active_parent", "manage_parent");
        $data = $this->get_my_info();
        $this->assign('userinfo', $data);
        if (isMobile()) {
            $this->m_all_display("user_info.html");
        } else {
            $this->all_display("user_info.html");
        }
    }


    /**
     * 个人信息查看接口
     */
    public function get_my_info()
    {
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->get_user_info($uid);
        $pri = explode(',', $this->session->userdata('privilege'));
        $priArr = array(
            '1' => '上报权限', '2' => '指派权限', '3' => '处理权限', '4' => '督查权限', '5' => '管理员'
        );
        foreach ($pri as &$value) {
            $value = $priArr[$value];
        }
        $res[0]['privilege'] = implode(' ', $pri);
        return $res[0];
    }


    //修改头像接口
    public function change_avatar()
    {
        $config['upload_path'] = './uploads/avatar/';
        $config['allowed_types'] = 'jpg|png|jpeg|gif';
        $config['max_size'] = 10000;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('avatar')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0, 'info' => $error
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            //更新头像
            $this->my_model->update_avatar(array(
                'avatar' => '/uploads/avatar/' . $upload_data['file_name']
            ));

            $res = array(
                'res' => 1,
                'info' => array(
                    'name' => $upload_data['orig_name'],
                    'url' => '/uploads/avatar/' . $upload_data['file_name'],
                    'new_name' => $upload_data['file_name'],
                    'type' => $upload_data['image_type']
                )
            );
            echo json_encode($res);

        }
    }

    //展示修改密码界面
    public function show_change_psw()
    {
        $this->assign("active_title", "change_psw");
        $this->assign("active_parent", "manage_parent");
        if (isMobile()) {
            $this->m_all_display("change_psw.html");
        } else {
            $this->all_display("change_psw.html");
        }
    }


    /**
     * 6M 视频会议 视图载入
     */
    public function meeting()
    {
        $this->assign("active_title", "6m_page");
        $this->assign("active_parent", "6m_parent");
        $this->all_display("6m_meeting.html");
    }

    /**
     * 轮询检测最新的任务和消息通知
     *
     */
    public function get_new_msg()
    {
        $res = $this->my_model->get_new_msg();
        if ($res == "new_unhandle") {
            echo json_encode(
                array(
                    'res' => "new_unhandle"
                )
            );
        } else if ($res == "new_info") {
            echo json_encode(
                array(
                    'res' => "new_info"
                )
            );
        }
    }

    /**
     * 一个开直播，通知所有人
     */
    public function open_meeting()
    {
        $data = array(
            'publisher_id' => $this->session->userdata('uid'),
            'name' => $this->session->userdata('name'),
            'msg' => '我开视频会议啦！',
            'time' => time(),
            'url' => 'https://m.v6plus.com/' . $this->session->userdata('username')
        );
        $this->my_model->insert_msg($data);
    }


    /**
     * 首页 最新消息接口
     * Json 字符串 [ {"title": "标题", "type": "1", "url": "跳转链接", "时间": "unix时间戳"} ]
     * 说明: 类型 0 = 信息上报消息; 1 = 事件指派消息; 2 = 事件督办消息
     */
    public function get_msg()
    {
        $res = $this->my_model->get_msg();
        $this->output
            ->set_content_type('application/json')
            ->set_output($res);
    }


    /**
     * 绑定websocket客户端 client_id 到 uid
     */
    public function bind_uid()
    {
        $client_id = $this->input->post("client_id");
        if (!isset($client_id) || $client_id == null || $client_id == "") {
            show_404();
        }
        $this->identity->bind_uid($client_id);
    }


    /**
     * ----------------layerIM即时聊天--------------------------
     */
    public function getBoardInfo(){
        $this->config->load('theme_cfg');
        $theme_cfg = $this->config->item('theme');
        //查询用户信息
        $userInfo = $this->my_model->getIMUserInfo();
        $resArr = array(
            "code"  => 0,
            "msg"   => "",
            "data"  => array(
                "mine"  => array(
                    "username"  =>  $userInfo['name'],
                    "id"        =>  $userInfo['id'],
                    "status"    =>  "online",
                    "sign"      =>  "网信平台加密即时无痕聊天",
                    "avatar"    =>  $userInfo['avatar']
                ),
                "group" => array(
                    array(
                        "groupname" =>  $theme_cfg['im_group'],
                        "id"        =>  "10000",
                        "avatar"    =>  "/img/logo.png"
                    )
                )
            )
        );
        echo json_encode($resArr);
    }

    /**
     * 获取 在线群成员 信息
     */
    public function getOnlineGroupMembers(){
        $members = $this->my_model->getGroupMembers();
        echo json_encode($members);
    }

    /**
     * 用户登出 接口
     */
    public function logout()
    {
        $this->identity->destroy();
    }
}
