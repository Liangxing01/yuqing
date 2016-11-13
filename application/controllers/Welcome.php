<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'my_model');
        $this->identity->is_authentic();
    }


    /**
     * 首页视图 载入
     */
    public function index()
    {
        $this->assign("active_title", "home_page");
        $this->assign("active_parent", "home_parent");
        $weather = $this->my_model->weather();
        $this->assign($weather, null);
        $login = $this->my_model->get_login_list($this->session->userdata('uid'), 5);
        $this->assign('login', $login);
        $this->all_display('index.html');
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
                $all_num_arr['zp']=$zp_tasks_num_arr;
            }
            if (isset($handler_num_arr)) {
                $all_num_arr['handler']=$handler_num_arr;
            }
        }
        echo json_encode($all_num_arr);

    }

    /*
     * 主页接口：获取警告事件
     * 根据权限判断 来返回 警告事件
     */
    public function get_event_alert()
    {
        $uid = $this->session->userdata('uid');
        $pri = explode(",", $this->session->userdata('privilege'));
        $alarm_arr = array(
            'zp_alarm' => array(),
            'processor_alarm' => array()
        );
        foreach ($pri as $one) {
            switch ($one) {
                case 2 :
                    $zp_alarm = $this->my_model->get_desi_alert($uid);
                    $alarm_arr['zp_alarm']=$zp_alarm;
                    break;
                case 3 :
                    $processor_alarm = $this->my_model->get_processor_alert($uid);
                    $alarm_arr['processor_alarm']=$processor_alarm;
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
        if ($res) {
            $data = array(
                'res' => 1
            );
        } else {
            $data = array(
                'res' => 0
            );
        }
        echo json_encode($data);
    }


    /**
     * 事件详情页面 视图载入
     */
    public function event_detail()
    {
        $event_id = $this->input->get("eid");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }

        //检查 事件查看权限
        $this->load->model("Common_Model", "common");
        if (!$this->common->check_can_see($event_id)) {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $event = $this->designate->get_event($event_id);

        $this->assign("event", $event);
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_search");
        $this->all_display("designate/event_detail.html");
    }


    /**
     * 接口: 获取上报信息
     * 参数: 事件ID 信息ID
     * 返回: Json 字符串
     */
    public function get_event_info()
    {
        $event_id = $this->input->post("event_id");
        $info_id = $this->input->post("info_id");
        $this->load->model("Designate_Model", "designate");
        $info = $this->designate->get_event_info($event_id, $info_id);
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($info));
    }


    /*
     * 个人信息查看接口
     */
    public function get_my_info()
    {
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->get_user_info($uid);
        $res[0]['privilege'] = $this->session->userdata('privilege');
        echo json_encode($res);
    }

    /*
     * 个人信息修改接口
     */
    public function update_info()
    {
        $name = $this->input->post('name');
        $sex = $this->input->post('sex');
        $update_data = array(
            'name' => $name,
            'sex' => $sex
        );
        $uid = $this->session->userdata('uid');
        $res = $this->my_model->update_info($update_data, $uid);
        if ($res) {
            echo json_encode(
                array(
                    'res' => 1
                )
            );
        } else {
            echo json_encode(
                array(
                    'res' => 0
                )
            );
        }


    }

    /*
     * 修改密码接口
     */

    public function change_psw()
    {
        $old = $this->input->post('old_pass');
        $new = $this->input->post('new_pass');
        $uid = $this->session->userdata('uid');
        $check = $this->my_model->check_old_pass($old);
        if ($check) {
            $res = $this->my_model->update_psw($new, $uid);
            if ($res) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    //修改头像接口
    public function change_avatar()
    {
        $config['upload_path'] = './uploads/avatar/';
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['max_size'] = 10000;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('screenshot')) {
            $error = $this->upload->display_errors();
            $res = ['res' => 0, 'info' => $error];
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
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
        $this->all_display("change_psw.html");
    }

    /**
     * 用户登出 接口
     */
    public function logout()
    {
        $this->identity->destroy();
    }
}
