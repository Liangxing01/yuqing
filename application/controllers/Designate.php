<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate extends MY_controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("Designate_Model", "designate");
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

        $result = $this->designate->info_commit($data);
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 判断提交的url是否重复
     */
    public function check_url()
    {
        $url = $this->input->post('url');
        $id = $this->input->post('id');

        $res = $this->designate->check_url($url, $id);
        if ($res['res']) {
            echo json_encode(array(
                'res' => 1,
                'dup_id' => $res['dup_id']
            ));
        } else {
            echo json_encode(array(
                'res' => 0
            ));
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
        $id = $this->input->post("id");
        $this->load->model("Tree_Model", "tree");

        if (!isset($id) || $id == null || $id == "") {
            $nodes = $this->tree->get_processor_group_tree();
        } else {
            $nodes = $this->tree->get_processor_nodes($id);
        }
        $this->output->set_content_type('application/json')
            ->set_output($nodes);
    }


    /**
     * 呼叫人(单位)树 接口
     */
    public function get_caller_tree()
    {
        $id = $this->input->post("id");
        $this->load->model("Tree_Model", "tree");

        if ($id == null || $id == "") {
            $nodes = $this->tree->get_processor_group_tree();
        } else {
            $nodes = $this->tree->get_caller_nodes($id);
        }
        $this->output->set_content_type('application/json')
            ->set_output($nodes);
    }


    /**
     * 事件处理人(单位)树 接口
     */
    public function get_event_processor_tree()
    {
        $event_id = $this->input->get("eid");
        $node_id = $this->input->post("id");
        $is_group_event = $this->input->post("group");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $this->load->model("Tree_Model", "tree");

        //判断是否获取子节点
        if (!isset($node_id) || $node_id == null || $node_id == "") {
            $nodes = $this->tree->get_event_processor_tree($event_id);
        } else {
            $nodes = $this->tree->get_event_processor_node($node_id, $event_id, $is_group_event);
        }
        $this->output->set_content_type('application/json')
            ->set_output($nodes);
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

        //判断是否是改派，改派先删除原有记录 再添加记录
        $flag = $this->input->post('reset');
        $eid = $this->input->post('eid');
        if ($flag == 1) {
            $this->designate->del_designate($eid);
        }
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
        $config['allowed_types'] = 'doc|docx|ppt|pdf|pptx|zip|rar|xlsx|word';
        $config['max_size'] = 0;
        $config['encrypt_name'] = true;

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
     * ----------------------------事件改派 接口----------------------
     */

    /**
     * 接口: 获取已指派的事件信息 填充到页面表单
     */

    public function get_designate_info()
    {
        $eid = $this->input->get('eid');
        $info = $this->designate->designate_info($eid);

        echo json_encode($info);
    }



    /**
     * -------------------------------------------------------------
     */

    /**
     * 事件处理 timeline 视图载入
     */
    public function event_tracer()
    {
        $this->load->helper(array("public"));

        $event_id = $this->input->get('eid');
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        $uid = $this->session->userdata('uid');

        //判断能不能看这个 页面
        $this->load->model('Verify_Model', 'verify');
        $ver = $this->verify->can_see_event($event_id);
        if (!$ver) {
            show_404();
        }

        //判断有无督办权限
        $this->load->model("MY_Model", "my_model");
        $duban = $this->my_model->check_duban($uid, $event_id);
        if ($duban) {
            $usertype = 1;
        } else {
            $usertype = 0;
        }

        //首回事件设置
        $reply_time_setting = $this->designate->show_reply_time_setting($event_id);
        $this->assign("reply_time_setting", $reply_time_setting);

        $this->load->model("Handler_Model", "handler");
        $einfo = $this->handler->get_title_by_eid($event_id);
        $done_btn = $this->designate->check_done_btn($event_id);   //事件审核按钮
        $this->assign('title', $einfo['title']);
        $this->assign('rank', $einfo['rank']);
        if ($einfo['state'] == "已完成") {
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
        $this->assign('state', $einfo['state']);

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

        if (isMobile()) {
            $this->m_all_display("designate/event_tracer.html");
        } else {
            $this->all_display("designate/event_tracer.html");
        }

    }


    /**
     * 事件审核
     */
    public function event_confirm_done()
    {
        $flag = $this->input->post("flag");
        $eid = $this->input->post("eid");

        $result = $this->designate->event_confirm_done($eid, $flag);
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 事件 首回时间
     */
    public function event_reply_time()
    {
        $event_id = $this->input->post("eid");
        $reply_time = $this->input->post("reply_time");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }
        if (!isset($reply_time) || $reply_time == null || $reply_time == "") {
            show_404();
        }

        $reply_time = strtotime($reply_time);

        $result = $this->designate->commit_event_reply_time($event_id, $reply_time);
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

        $result = $this->designate->event_alter($data);
        //返回结果
        if ($result) {
            echo 1;
        } else {
            echo 0;
        }
    }


    /**
     * 修改事件等级
     * POST: rank, event_id
     */
    public function edit_event_rank()
    {
        $event_id = $this->input->post("event_id");
        $rank = $this->input->post("rank");
        $result = $this->designate->edit_event_rank($event_id, $rank);
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
        $this->load->helper(array("public"));
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_search");
        if (isMobile()) {
            //事件跟踪列表 手机  视图载入
            $this->m_all_display("designate/m_event_search.html");
        } else {
            $this->all_display("designate/event_search.html");
        }
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

        $data = $this->designate->event_search_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 移动端 事件追踪 滚动加载 数据接口
     * post: (int)page
     */
    public function scroll_event_data()
    {
        $page_num = $this->input->post("page");        //页码
        if (!isset($page_num) || $page_num == null || $page_num == "") {
            show_404();
        }

        $result = $this->designate->scroll_event_pagination($page_num);
        $this->output
            ->set_content_type('application/json')
            ->set_output($result);
    }


    /**
     * 上报信息检索 视图载入
     */
    public function info_search()
    {
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

        $data = $this->designate->info_search_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 用户在线 视图载入
     */
    public function online_tree()
    {
        $this->assign("active_title", "rollcall_parent");
        $this->assign("active_parent", "onlineTree");
        $online_user_num = $this->designate->count_online_user();
        $this->assign("online_user_num", $online_user_num);
        $this->all_display("designate/online_user.html");
    }


    /**
     * 点名 视图载入
     */
    public function roll_call()
    {
        $this->assign("active_title", "rollcall_parent");
        $this->assign("active_parent", "rollCall");
        $this->all_display("designate/roll_call.html");
    }


    /**
     * 获取组
     * POST: type, keyword
     */
    public function get_call_list()
    {
        $type = $this->input->post("type");
        $keyword = $this->input->post("keyword");
        if ($type == null) {
            show_404();
        }
        $result = $this->designate->get_call_list($type, $keyword);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 统计在线情况
     */
    public function get_online_tree()
    {
        $this->load->model("Tree_Model", "tree");
        $tree_data = $this->tree->get_online_tree();
        $this->output
            ->set_content_type('application/json')
            ->set_output($tree_data);
    }


    // demo TODO
    public function tree()
    {
        echo $this->designate->get_relation_tree();
    }


    public function count_user()
    {
        $this->load->library("Gateway");
        Gateway::$registerAddress = $this->config->item("VM_registerAddress");
        echo Gateway::getAllClientCount();
    }

    /**
     *  --------------------------点名系统------------------------------------
     */
    /**
     * 发起点名
     */
    public function make_call()
    {
        $msg = $this->input->post('message');
        $delay_time = $this->input->post('delay_time');
        $gids = $this->input->post('gids');
        if ((int)$delay_time > 5) {
            echo 'Error Time';
        } else {
            $start_time = $delay_time * 60;
            $res = $this->designate->make_call($gids, $msg, $start_time);
            if ($res) {
                echo json_encode(array(
                        'res' => 1,
                        'msg' => '发起点名成功，请2分钟后查看点名结果'
                    )
                );
            } else {
                echo json_encode(array(
                        'res' => 0,
                        'msg' => '点名失败'
                    )
                );
            }
        }
    }


    /*----------------------------------通知系统----------------------------------*/
    /**
     * 显示 通知列表
     */
    public function show_notice_list()
    {
        $this->assign("active_title", "notice_parent");
        $this->assign("active_parent", "notice_list");
        $this->all_display("designate/notice_list.html");
    }


    /**
     * 获取 通知列表 分页数据
     */
    public function get_notice_list_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }

        $data = $this->designate->get_notice_list_data($pData);
        echo json_encode($data);
    }


    /**
     * 删除 通知
     * POST: nid 通知id
     * 1: 删除成功; 0: 删除失败
     */
    public function delete_notice()
    {
        $notice_id = $this->input->post("nid");
        if (!is_null($notice_id)) {
            echo $this->designate->delete_notice($notice_id) ? 1 : 0;
        } else {
            echo 0;
        }
    }


    /**
     * 获取 通知详情
     * POST: nid 通知id
     */
    public function get_notice_detail()
    {
        $notice_id = $this->input->post("nid");
        $result = $this->designate->get_notice_detail($notice_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 发布通知
     * POST: title: 标题; content: 通知内容
     * 1: 成功; 0: 失败
     */
    public function post_notice()
    {
        $title = $this->input->post("title");
        $content = $this->input->post("content");
        if ($content && $title) {
            echo $this->designate->post_notice($title, $content) ? 1 : 0;
        } else {
            echo 0;
        }
    }


    /**
     * ------------------------指令系统--------------------
     */

    /**
     * 分页显示发出的指令
     */
    public function get_send_notice()
    {
        $this->load->model('Common_Model', 'common');
        $query_data = $this->input->post();
        $email_info = $this->common->get_send_emails_info($query_data,'notice');
        echo json_encode($email_info);
    }

    /**
     * 显示 指令详情
     * 角色 发送者
     */
    public function show_notice_detail(){
        $this->load->model("Common_Model", "common");
        $eid = $this->input->get('id');

        if (!isset($eid) || $eid == null || $eid == "") {
            show_404();
        }


        $email_info = $this->common->send_email_detail($eid);


        if($email_info == false){
            show_404();
        }else{
            $this->assign('info',$email_info['info']);
            $this->assign('attID', implode(',',$email_info['attID']));

        }

        $this->assign("role",'sender');
        $this->assign("has_res",0);
        $this->assign("active_title", "email_sys");
        $this->assign("active_parent", "file_parent");
        $this->all_display("email/notice_detail.html");
    }

    /**
     * 发布指令
     */
    public function publish_notice()
    {
        $this->load->model("Common_Model", "common");
        $email_info = array(
            'title' => $this->input->post('title'),
            'body' => $this->input->post('body'),
            'priority_level' => $this->input->post('priority_level')
        );

        $receiveID = array(
            'uids' => $this->input->post('uids') ? explode(',', $this->input->post('uids')) : array(),
            'gids' => $this->input->post('gids') ? explode(',', $this->input->post('gids')) : array()
        );

        $attID = $this->input->post('attID');
        if ($attID != '') {
            $attID_arr = explode(',', $attID);
        } else {
            $attID_arr = array();
        }

        $res = $this->common->insert_email($email_info, $receiveID, $attID_arr, 'notice');
        if ($res) {
            echo json_encode(array(
                'res' => 1
            ));
        } else {
            echo json_encode(array(
                'res' => 0
            ));
        }
    }

    function call() {
        $this->assign("active_title", "call");
        $this->assign("active_parent", "file_parent");
        $this->all_display('call/call.html');
	}
	
	function addCall() {
		$input=$this->input->post(['content','target']);
		if (!$input) show_404();
		$input['uid']=$this->session->get_userdata('uid');
		$content=urlencode($input['content']);
		$target=json_decode($input['target'],true);
		if (!$target||count($target)<=0)
			die('请选择对象');
		$target=$this->db->where_in('id',$target)->select('id,phone')->order_by('id','asc')
		->get('user')->result_array();
		$input['target']=[];
		$phone=[];
		foreach ($target as $item) {
			if (strlen($item['phone'])==11){
				$item['status']=0;
				$item['id']=(int)$item['id'];
				$input['target'][]=$item;
				$phone[]=$item['phone'];
			}
		}
		if (empty($phone)) die('请确认选择的用户是否完善了手机号码');
		$input['target']=json_encode($input['target']);
		$this->db->insert('call_log',$input);
		$taskId=$this->db->insert_id();
		$url="http://113.31.89.53:4200/ipcc.html?cmd=40&format=json&taskid=25&calloid=$taskId&remark=$content&telnum=".join('-', $phone);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$output=json_decode(substr($output, 1,-1),true);
		if (!$output||$output['call'][0]['result']!=200){
			$this->db->delete('call_log',['id'=>$taskId]);
			die('呼叫平台出错！');
		}
		curl_close($ch);
		echo 'ok';
	}
	
	function history($id=0) {
		if($id==0){
			$page=(int)$this->input->get('page');
            $count=$this->input->get('count')?:15;
            $t=$this->input->get(['begin','end']);
            if ($t['begin']!=''){
                $this->db->where(['yq_call_log.time >='=>date('Y-m-d',$t['begin']),'yq_call_log.time <'=>date('Y-m-d',$t['end'])]);
            }
            if ($t=$this->input->get('sSearch')){
                $this->db->like('content',$t);
            }
            $total=$this->db->count_all_results('call_log',false);
            $data=$this->db->select('yq_call_log.id,content,yq_call_log.time,status,user.name')
            ->join('user', 'user.id=yq_call_log.uid')->order_by('yq_call_log.id','desc')
            ->get('',$count,$page*$count)->result_array();
            echo json_encode(['aaData'=>$data,'iTotalRecords'=>$total,'iTotalDisplayRecords'=>$total]);
		}else{
			if(!is_numeric($id))
				show_404();
			$data=$this->db->where('call_log.id',$id)
			->select('call_log.*,user.name')
			->join('user', 'yq_user.id=yq_call_log.uid')->get('call_log')->row_array();
			if($data){
				$data['target']=json_decode($data['target'],true);
				echo json_encode($data);
			}else die('此数据不存在！');
		}
	}
}