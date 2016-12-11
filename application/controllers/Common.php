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
     * GET: eid, option(可选)
     */
    public function event_detail()
    {
        $option = $this->input->get("option");
        $event_id = $this->input->get("eid");

        //更新事件报警状态为0
        if ($option == "cancel_alert") {
            $this->load->model("Common_Model","common");
            $this->common->update_alarm_state($event_id);
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
        $this->assign("active_title", "email_sys");
        $this->assign("active_parent", "file_parent");
        $this->all_display("email_sys.html");
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
     * -----------------------我的云盘 功能 接口------------------------
     */

    /**
     * 接口：用户上传文档
     * 格式：doc pdf ppt excel
     */
    public function upload_file(){
        $this->load->model("Common_Model","common");
        $config = array();
        $config['upload_path'] = './uploads/file/';
        ///$config['allowed_types'] = 'doc|docx|ppt|pdf|pptx|xlsx|word';
        $config['allowed_types'] = '*';
        $config['max_size'] = 500000;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $config['detect_mime'] = false;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0, 'msg' => $error
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            $upload_data['loc'] = '/uploads/file/' . $upload_data['file_name'];
            $re = $this->common->insert_file_info($upload_data,1,$config['allowed_types']);

            if($re['res'] == 1){
                $res = array(
                    'res' => 1,
                    'fid' => $re['fid'],
                    'msg' => $re['msg']
                );
            }else{
                $res = array(
                    'res' => 0,
                    'msg' => $re['msg']
                );
            }

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
        if (!isset($fid) || $fid == null || $fid == "") {
            show_404("文件不存在");
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

    /**
     * ----------------------邮件功能接口------------------------
     */

    /**
     * 展示 写邮件 界面
     * 参数：fid 如果有 则为 云盘勾选文件后 跳转过来的
     */
    public function show_write_email(){
        $this->load->model("Common_Model","common");
        $eid  = $this->input->get('eid');
        $fids = $this->input->get('fid');
        if($fids == NULL){
            //展示写邮件 页面
            $this->assign("active_title", "email_sys");
            $this->assign("active_parent", "file_parent");
            $this->all_display("email/write_email.html");
        }else{
            //先把云盘文件 拷贝到 邮件附件 目录下，并插入email_attachment表中，隔离文件
            $new_fid_arr = $this->common->copy_insert_att($fids,$eid);
            $this->assign('attID',implode(',',$new_fid_arr));
            //展示写邮件页面
            $this->assign("active_title", "email_sys");
            $this->assign("active_parent", "file_parent");
            $this->all_display("email/write_email.html");
        }
    }

    /**
     * 回复 邮件功能
     */
    public function reply_email(){
        $this->load->model('Common_Model','common');
        $reply_uid = $this->input->get('reply_uid');
        $reply_eid = $this->input->get('reply_eid');
        $reply_info = $this->common->get_reply_info($reply_uid,$reply_eid);
        $this->assign('reply_uid',$reply_uid);
        $this->assign('reply_name',$reply_info['name']);
        $this->assign('reply_title','回复 : '.$reply_info['title']);
        $this->all_display("email/write_email.html");
    }

    /**
     * 接口 ： 根据uid 获取 用户姓名，显示到 收件人
     */
    public function get_name_by_uid(){
        $this->load->model('Common_Model','common');
        $uid = $this->input->get('uid');
        $name = $this->common->get_name($uid);
        echo json_encode(array(
            'name' => $name
        ));
    }

    /**
     * 接口： 邮件 Body 上传图片
     */
    public function email_upload_img(){
        $this->load->model("Common_Model","common");
        $config = array();
        $config['upload_path'] = './uploads/pic/email_pic';
        $config['allowed_types'] = 'jpeg|png|jpg';
        $config['max_size'] = 250;//大小限制250kb
        $config['max_width'] = 1500;
        $config['max_height'] = 1500;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'code' => 2,
                'msg' => "大图片请用附件上传",
                'data' => array(
                    'src'   =>'',
                    'title' => ''
                )
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            //生成 文件保存 路径
            $upload_data['loc'] = '/uploads/pic/email_pic/' . $upload_data['file_name'];
            $re = $this->common->insert_img($upload_data,$config['allowed_types']);

            if($re['res'] == 1){
                $res = array(
                    'code' => 0,
                    'msg'  => $re['msg'],
                    'data' => array(
                        'src' => $re['src'],
                        'title' => ''
                    )
                );
            }else{
                $res = array(
                    'code' => 1,
                    'msg'  => $re['msg'],
                    'data' => array(
                        'src' => '',
                        'title' => ''
                    )
                );
            }

            echo json_encode($res);

        }
    }


    /**
     * 接口：通过 附件id 获取 附件信息
     */
    public function get_att_info(){
        $this->load->model("Common_Model","common");
        $attIDs = $this->input->post('att_ids');
        $eid = $this->input->post('eid');
        $att_info = $this->common->get_att_by_id($attIDs,$eid);

        echo json_encode($att_info);
    }

    /**
     * 写邮件 接口
     * 参数：邮件字段 附件
     */
    public function write_email(){
        $this->load->model("Common_Model", "common");
        $email_info = array(
            'title' => $this->input->post('title'),
            'body'  => $this->input->post('body'),
            'priority_level' => $this->input->post('priority_level')
        );

        $receiveID = array(
            'uids' => $this->input->post('uids') ? explode(',',$this->input->post('uids')) : array(),
            'gids' => $this->input->post('gids') ? explode(',',$this->input->post('gids')) : array()
        );

        $attID = $this->input->post('attID');
        if($attID != ''){
            $attID_arr = explode(',',$attID);
        }else{
            $attID_arr = array();
        }
        $res = $this->common->insert_email($email_info,$receiveID,$attID_arr);
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

    /**
     *收件箱  邮件 信息 查看 功能
     */
    public function rec_email_detail(){
        $this->load->model("Common_Model", "common");
        $eid = $this->input->get('id');
        if (!isset($eid) || $eid == null || $eid == "") {
            show_404();
        }

        $email_info = $this->common->rece_email_detail($eid);

        if($email_info == false){
            show_404();
        }else{
            $this->assign('info',$email_info['info']);
            $this->assign('attID', implode(',',$email_info['attID']));

        }

        $this->assign("active_title", "email_sys");
        $this->assign("active_parent", "file_parent");
        $this->all_display("email/rec_detail.html");


    }

    /**
     * 发件箱 邮件信息查看 功能
     */
    public function send_email_detail(){
        $this->load->model('Common_Model','common');
        $eid = $this->input->get('id');
        if (!isset($eid) || $eid == null || $eid == "") {
            show_404();
        }


        $email_info = $this->common->send_email_detail($eid);

        if($email_info == false){
            show_404();
        }else{
            $this->assign('info',$email_info['info']);
            $this->assign('attID',implode(',',$email_info['attID']));

        }

        $this->assign("active_title", "email_sys");
        $this->assign("active_parent", "file_parent");
        $this->all_display("email/send_detail.html");

    }

    /**
     * 接口：获取 邮件是否阅读 状态
     */
    public function get_has_read(){
        $this->load->model('Common_Model','common');
        $eid = $this->input->get('id');
        if (!isset($eid) || $eid == null || $eid == "") {
            show_404();
        }

        $user_read_state = $this->common->get_read_state($eid);

        echo json_encode($user_read_state);
    }

    /**
     * 接口：邮件 附件上传接口
     */
    public function email_att_upload(){
        $this->load->model("Common_Model","common");
        $config = array();
        //先保存在 temp 临时目录
        $config['upload_path'] = './uploads/temp/';
        //$config['allowed_types'] = 'doc|docx|ppt|pdf|pptx|xlsx|word|rar|zip|jpeg|png|jpg';
        $config['allowed_types'] = '*';
        $config['max_size'] = 1000000;//大小限制100M
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $config['detect_mime'] = false;
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
            //生成 文件保存 路径
            $upload_data['loc'] = '/uploads/temp/' . $upload_data['file_name'];
            $re = $this->common->insert_file_info($upload_data,0,$config['allowed_types']);

            if($re['res'] == 1){
                $res = array(
                    'res' => 1,
                    'fid' => $re['fid'],
                    'msg' => $re['msg'],
                    'file_name' => $re['file_name']

                );
            }else{
                $res = array(
                    'res' => 0,
                    'msg' => $re['msg']
                );
            }

            echo json_encode($res);

        }
    }


    /**
     * 接口：邮件 附件删除
     */
    public function del_att(){
        $this->load->model("Common_Model","common");
        $fid = $this->input->get('fid');
        $fid_arr = explode(',',$fid);
        $res = $this->common->del_file($fid_arr);
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


    /**
     * 接口：邮件附件 下载
     */
    public function att_download(){
        $fid = $this->input->get('fid');
        $eid = $this->input->get('eid');
        if (!isset($id) || $id == null || $id == "") {
            show_404();
        }
        //获取文件信息和鉴权
        $this->load->model("Common_Model", "common");
        $attachment = $this->common->att_download($fid,$eid);
        if ($attachment) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $attachment["loc"])) {
                $this->load->helper("download");
                $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $attachment["loc"]);
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
     * 分页 显示 发件箱 邮件
     */
    public function get_all_sends(){
        $this->load->model('Common_Model','common');
        $query_data = $this->input->post();
        $email_info = $this->common->get_send_emails_info($query_data);
        echo json_encode($email_info);
    }

    /**
     * 分页显示 收件箱 邮件
     */
    public function get_all_rec(){
        $this->load->model('Common_Model','common');
        $query_data = $this->input->post();
        $email_info = $this->common->get_rec_emails_info($query_data);
        echo json_encode($email_info);
    }


    public function test(){
        $a = "1481272759";
        echo (int)$a + 1209600;
    }


}