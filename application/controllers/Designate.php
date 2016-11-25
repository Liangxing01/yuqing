<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate extends MY_controller
{

    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
    }


    /**
     * 未确认信息 视图载入
     */
    public function info_not_handle()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "info_not_handle");
        $this->all_display("designate/info_not_handle.html");
    }


    /**
     * 未确认信息列表分页 数据接口
     */
    public function info_not_handle_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->info_not_handle_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 已确认信息列表分页 数据接口
     */
    public function info_is_handle_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 5;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->info_is_handle_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 信息详情页面 视图载入
     */
    public function info_detail()
    {
        $info_id = $this->input->get("id", true);
        if (!isset($info_id) || $info_id == null || $info_id == "") {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        //设置信息为已查看状态
        $this->designate->set_info_seen($info_id);
        $info = $this->designate->get_info($info_id);
        $type_list = $this->designate->get_info_type();
        $this->assign("info", $info);
        $this->assign("type", $type_list);
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "info_not_handle");
        $this->all_display("designate/info_detail.html");
    }


    /**
     * 提交 信息确认
     */
    public function commit_info()
    {
        $data = $this->input->post();
        if (!isset($data["id"]) || $data["id"] == "") {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->info_commit($data);
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 事件指派 视图载入
     */
    public function event_designate()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_designate");
        $this->all_display("designate/event_designate.html");
    }


    /**
     * 处理人(单位)树 接口
     */
    public function get_processor_tree()
    {
        $this->load->model("Tree_Model", "tree");
        $this->output->set_content_type('application/json')
            ->set_output($this->tree->get_processor_tree());
    }


    /**
     * 事件处理人(单位)树 接口
     */
    public function get_event_processor_tree()
    {
        $event_id = $this->input->get("eid");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $this->load->model("Tree_Model", "tree");
        $this->output->set_content_type('application/json')
            ->set_output($this->tree->get_event_processor_tree($event_id));
    }


    /**
     * 督办人树 接口
     */
    public function get_watcher_tree()
    {
        $this->load->model("Tree_Model", "tree");
        $this->output->set_content_type('application/json')
            ->set_output($this->tree->get_watcher_tree());
    }


    /**
     * 事件督办人树 接口
     */
    public function get_event_watcher_tree()
    {
        $event_id = $this->input->get("eid");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $this->load->model("Tree_Model", "tree");
        $this->output->set_content_type('application/json')
            ->set_output($this->tree->get_event_watcher_tree($event_id));
    }


    /**
     * 事件指派 表单提交
     */
    public function commit_event_designate()
    {
        $data["title"] = $this->input->post("title", true);                   //标题
        $data["description"] = $this->input->post("description", true);       //描述
        $data["info_id"] = $this->input->post("info_id", true);               //事件信息ID
        $data["rank"] = $this->input->post("rank", true);                     //事件等级
        $data["reply_time"] = $this->input->post("reply_time", true);         //首回时间
        $data["relate_event"] = $this->input->post("relate_event", true);     //相关事件
        $data["processor"] = $this->input->post("processor", true);           //处理人(单位)
        $data["main_processor"] = $this->input->post("main_processor", true); //牵头人(单位)
        $data["watcher"] = $this->input->post("watcher", true);               //督办人
        $data["attachment"] = $this->input->post("attachment", true);         //附件

        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->event_designate($data);
        if ($result) {
            echo 1;   //指派成功
        } else {
            echo 0;   //指派失败
        }
    }


    /**
     * 事件指派 附件上传
     */
    public function attachment_upload()
    {
        $config['upload_path'] = './uploads/temp/';
        $config['allowed_types'] = '*';
        $config['max_size'] = 0;
        $config['encrypt_name'] = true;
        $config['detect_mime'] = false;

        $this->load->library('upload', $config);

        //处理上传文件
        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0,
                "info" => $error
            );
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            $res = array(
                'res' => 1,
                'info' => array(
                    'name' => $upload_data['client_name'],
                    'url' => '/uploads/document/' . $upload_data['file_name'],
                    'new_name' => $upload_data['file_name'],
                    'type' => "document"
                )
            );
        }
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($res));
    }


    /**
     * 事件处理 timeline 视图载入
     */
    public function event_tracer()
    {
        $event_id = $this->input->get('eid');
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $uid = $this->session->userdata('uid');

        //判断能不能看这个 页面
        $this->load->model('Verify_Model','verify');
        $ver = $this->verify->can_see_event($event_id);
        if(!$ver){
            show_404();
        }

        //判断有无督办权限
        $this->load->model("MY_Model", "my_model");
        $duban = $this->my_model->check_duban($uid,$event_id);
        if ($duban){
            $usertype = 1;
        }else{
            $usertype = 0;
        }

        $this->load->model("Handler_Model", "handler");
        $this->load->model("Designate_Model", "designate");
        $einfo = $this->handler->get_title_by_eid($event_id);
        $done_btn = $this->designate->check_done_btn($event_id);   //事件审核按钮
        $this->assign('title', $einfo['title']);
        $this->assign('rank', $einfo['rank']);
        if ($einfo['state'] == "已完成" || $einfo['state'] == '未审核') {
            $done_state = 1;
        } else {
            $done_state = 0;
        }
        if (!empty($einfo['end_time'])) {
            $this->assign('end_time', $einfo['end_time']);
        } else {
            $this->assign('end_time', "");
        }
        $this->assign('done_state', $done_state);
        $this->assign('eid', $event_id);
        $this->assign('can_show_done_btn', $done_btn);

        //个人信息
        $this->load->model('MY_Model', 'my_model');
        $user_info = $this->my_model->get_user_info($uid);
        $this->assign('username', $user_info[0]['name']);
        $this->assign('useracter', $user_info[0]['avatar']);
        $this->assign('usertype', $usertype);

        //获取事件信息链接
        $links = $this->handler->get_info_url($event_id);
        $this->assign("links", $links);

        //获取 参考文件
        $doc_arr = $this->handler->get_event_attachment($event_id);
        $this->assign('attachment', $doc_arr);

        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_search");
        $this->all_display("designate/event_tracer.html");
    }


    /**
     * 事件审核
     */
    public function event_confirm_done()
    {
        $flag = $this->input->post("flag");
        $eid = $this->input->post("eid");

        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->event_confirm_done($eid, $flag);
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 事件重启
     */
    public function event_restart()
    {
        $event_id = $this->input->post("eid");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->event_restart($event_id);
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 事件增派
     */
    public function event_alter()
    {
        $event_id = $this->input->get('eid');
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $this->load->model("Designate_Model", "designate");
        $main = $this->designate->get_event_main($event_id);    //获得牵头人(单位)
        $event = $this->designate->get_event($event_id);        //获取事件详情

        //关联事件ID
        $relate_event = "";
        foreach ($event["relate_event"] as $relate) {
            $relate_event .= $relate["id"] . ",";
        }
        $relate_event = substr($relate_event, 0, strlen($relate_event) - 1);

        $this->assign("relate_event", $relate_event);
        $this->assign("event_id", $event_id);
        $this->assign("main", $main);
        $this->assign("title", $event["title"]);
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_designate");
        $this->all_display("designate/event_alter.html");
    }


    /**
     * 提交事件增派
     */
    public function commit_event_alter()
    {
        $data["event_id"] = $this->input->post("event_id", true);             //事件ID
        $data["processor"] = $this->input->post("processor", true);           //处理人(单位)
        $data["watcher"] = $this->input->post("watcher", true);               //督办人
        $data["relate_event"] = $this->input->post("relate_event", true);     //关联事件ID
        $data["info_id"] = $this->input->post("info_id", true);               //事件信息ID

        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->event_alter($data);
        //返回结果
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 事件跟踪列表 视图载入
     */
    public function event_search()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_search");
        $this->all_display("designate/event_search.html");
    }


    /**
     * 事件检索列表分页 数据接口
     */
    public function event_search_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_start'] = $this->input->post('sSortDir_0', true);  //开始时间排序的方向 默认 desc
        $pData['sort_end'] = $this->input->post('sSortDir_1', true);    //结束时间排序的方向 默认 desc

        //高级查询 关键字
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空
        $pData['rank'] = $this->input->post('rank', true);              //查询事件等级 默认为空
        $pData['state'] = $this->input->post('state', true);            //查询事件状态 默认为空
        $pData['start_start'] = $this->input->post('start_start', true);//查询事件开始时间 起始范围 默认为0
        $pData['start_end'] = $this->input->post('start_end', true);    //查询事件开始时间 结束范围 默认为0
        $pData['end_start'] = $this->input->post('end_start', true);    //查询事件结束时间 起始范围 默认为0
        $pData['end_end'] = $this->input->post('end_end', true);        //查询事件结束时间 结束范围 默认为0

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData['sort_start'] == NULL) {
            $pData['sort_start'] = "desc";
        }
        if ($pData['sort_end'] == NULL) {
            $pData['sort_end'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }
        if ($pData['rank'] == NULL) {
            $pData['rank'] = '';
        }
        if ($pData['state'] == NULL) {
            $pData['state'] = '';
        }
        if ($pData['start_start'] == NULL) {
            $pData['start_start'] = 0;
        }
        if ($pData['start_end'] == NULL) {
            $pData['start_end'] = 0;
        }
        if ($pData['end_start'] == NULL) {
            $pData['end_start'] = 0;
        }
        if ($pData['end_end'] == NULL) {
            $pData['end_end'] = 0;
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->event_search_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 上报信息检索 视图载入
     */
    public function info_search()
    {
        $this->load->model("Designate_Model", "designate");
        $type_list = $this->designate->get_info_type();  //获取类别列表
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "info_search");
        $this->assign("type_list", $type_list);
        $this->all_display("designate/info_search.html");
    }


    /**
     * 上报信息检索列表分页 数据接口
     */
    public function info_search_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc

        //高级查询 关键字
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空
        $pData["start_time"] = $this->input->post('start_time', true);  //查询起始时间 默认为0
        $pData["end_time"] = $this->input->post('end_time', true);      //查询截止时间 默认为0
        $pData["type"] = $this->input->post("type", true);              //查询类别
        $pData["state"] = $this->input->post("state", true);            //查询状态
        $pData["duplicate"] = $this->input->post("duplicate", true);    //查询是否重复

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }
        if ($pData['start_time'] == NULL) {
            $pData['start_time'] = 0;
        }
        if ($pData['end_time'] == NULL) {
            $pData['end_time'] = 0;
        }
        if ($pData['type'] == NULL) {
            $pData['type'] = "";
        }
        if ($pData['state'] == NULL) {
            $pData['state'] = "";
        }
        if ($pData['duplicate'] == NULL) {
            $pData['duplicate'] = "";
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->info_search_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    // demo TODO
    public function tree()
    {
        $this->load->model("Designate_Model", "designate");
        echo $this->designate->get_relation_tree();
    }

}