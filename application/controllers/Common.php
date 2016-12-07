<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Common extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
    }


    /**
     * 事件详情页面 视图载入
     */
    public function event_detail()
    {
        $option = $this->input->get("option");
        $event_id = $this->input->get("eid");

        //更新事件报警状态为0
        if ($option == "cancel_alert") {
            $this->load->model("Common_Model","common");
            $this->common->update_alarm_state($event_id, 0);
        }

        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }

        $this->load->model("Verify_Model", "verify");
        //检查事件查看权限
        if (!$this->verify->can_see_event($event_id)) {
            show_404();
        }

        $role = 0;

        //判断是否是指派人
        if ($this->verify->is_manager()) {
            $role = 2;
        }

        //判断是否是督办人
        if ($this->verify->is_watcher()) {
            $role = 4;
        }

        //判断是否是处理人
        //注意:处理人最后判断
        if ($this->verify->is_processor()) {
            $role = 3;
        }

        $this->load->model("Designate_Model", "designate");
        $event = $this->designate->get_event($event_id);

        $this->assign("role", $role);
        $this->assign("event", $event);

        $this->load->helper(array("public"));
        if(isMobile()){
            $this->m_all_display("designate/m_event_detail.html");

        }else{
            $this->all_display("designate/event_detail.html");
        }
    }


    /** 接口: 获取上报信息
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


    /**
     * 事件参考文件下载 接口
     * GET: 附件ID
     */
    public function attachment_download()
    {
        $id = $this->input->get("id", true);
        if (!isset($id) || $id == null || $id == "") {
            show_404();
        }

        //获取附件信息和鉴权
        $this->load->model("Common_Model", "common");
        $attachment = $this->common->event_attachment_download($id);
        if ($attachment) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $attachment["url"])) {
                $this->load->helper("download");
                $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $attachment["url"]);
                $name = $attachment["name"];
                force_download($name, $data);
            } else {
                show_404("文件不存在");
            }
        } else {
            show_404("文件不存在");
        }
    }


    /**
     * 事件信息 视频下载 接口
     * GET: 视频ID
     */
    public function video_download()
    {
        $id = $this->input->get("id");
        if (!isset($id) || $id == null || $id == "") {
            show_404();
        }
        //获取附件信息和鉴权
        $this->load->model("Common_Model", "common");
        $attachment = $this->common->info_video_download($id);
        if ($attachment) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $attachment["url"])) {
                $this->load->helper("download");
                $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $attachment["url"]);
                $name = $attachment["name"];
                force_download($name, $data);
            } else {
                show_404("文件不存在");
            }
        } else {
            show_404("文件不存在");
        }
    }


    /**
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
        $this->load->model("Common_Model", "common");
        $res = $this->common->update_info($update_data);
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

    /**
     * 修改密码接口
     */
    public function change_psw()
    {
        $old = $this->input->post('old_pass');
        $new = $this->input->post('new_pass');
        $this->load->model("Common_Model", "common");
        $check = $this->common->check_old_pass($old);
        if ($check) {
            $res = $this->common->update_psw($new);
            if ($res) {
                echo json_encode(array(
                    'res' => 1
                ));
            } else {
                echo json_encode(array(
                    'res' => 0
                ));
            }
        } else {
            echo json_encode(array(
                'res' => 0
            ));
        }
    }


    /**
     * 邮件页面 展示
     */
    public function show_emails(){
        $this->assign("active_title", "file_trans");
        $this->assign("active_parent", "file_parent");
        $this->all_display("document_trans.html");
    }

    /**
     * 我的云盘 页面 显示
     */

    public function show_my_pan(){
        $this->assign("active_title", "my_pan");
        $this->assign("active_parent", "file_parent");
        $this->all_display("my_pan.html");
    }

    /**
     * 接口：用户上传文档
     * 格式：doc pdf ppt excel
     */
    public function upload_file(){
        $this->load->model("Common_Model","common");
        $config = array();
        $config['upload_path'] = './uploads/file/';
        $config['allowed_types'] = 'doc|docx|ppt|pdf|pptx|xlsx|word';
        $config['max_size'] = 0;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0, 'info' => $error
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            $res = array(
                'res' => 1
            );
            $this->common->insert_file_info($upload_data);
            echo json_encode($res);

        }
    }

    /**
     * 接口：分页获取 用户云盘所有文件
     * 云盘
     */
    public function get_all_files(){
        $this->load->model('Common_Model','common');
        $query_data = $this->input->post();
        $files_info = $this->common->get_files_info($query_data);
        echo json_encode($files_info);
    }

    /**
     *
     * 云盘 文件下载 接口
     * GET: 附件ID
     */
    public function file_download(){
        $fid = $this->input->get('fid');
        if (!isset($id) || $id == null || $id == "") {
            show_404();
        }
        //获取文件信息和鉴权
        $this->load->model("Common_Model", "common");
        $attachment = $this->common->file_download($fid);
        if ($attachment) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $attachment["url"])) {
                $this->load->helper("download");
                $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $attachment["url"]);
                $name = $attachment["name"];
                force_download($name, $data);
            } else {
                show_404("文件不存在");
            }
        } else {
            show_404("文件不存在");
        }
    }

    /**
     * 云盘 删除接口
     */
    public function del_file(){
        $this->load->model("Common_Model", "common");
        $dels = $this->input->post('del_id');
        $del_arr = explode(",",$dels);
        if(empty($del_arr)){
            show_404();
        }

        $res = $this->common->del_file($del_arr);
        if($res){
            echo json_encode(array(
                'res' => 1
            ));
        }else{
            echo json_encode(array(
                'res' => 0
            ));
        }
    }



}