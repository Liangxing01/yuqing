<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Handler extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('Handler_Model',"handler_model");

        $this->session->set_userdata("uid",3);
        $this->session->set_userdata('name',"王五");
        $this->session->set_userdata('gid',1);
    }

    public function index()
    {

        //$this->load->view('welcome_message');
        $this->all_display('index.html');
    }

    //展示待处理事件页面
    public function wait_to_handle(){
        $this->assign("active_title","wait_to_handle");
        $this->assign("active_parent","handle_parent");
        $this->all_display('handler/unhandle.html');
    }

    //分页显示待处理事件列表
    public function get_unhandle_data(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 2;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $uid = $this->session->userdata('uid');

        $data = $this->handler_model->get_all_unhandle($pData,$uid);
        echo json_encode($data);

    }

    //显示待处理事件详情信息
    public function show_detail(){
        $event_id = $this->input->get("id");
        if(!isset($event_id) || $event_id == null || $event_id == ""){
            show_404();
        }

        $event_info = $this->handler_model->get_detail_by_id($event_id);
        var_dump($event_info);

        $this->assign("event", $event_info);
        $this->assign("active_title","wait_to_handle");
        $this->assign("active_parent","handle_parent");
        $this->all_display("handler/show_unhandle_detail.html");
    }

    //显示处理交互界面
    public function show_handle_log(){
        $event_id = $this->input->get('id');
        $title = $this->input->get('title');
        /*
         * 点击开始处理后，向event_log表写一条记录，pid为空，后续
         * 日志已该id为pid
         */
        $uid = $this->session->userdata('uid');

        $this->assign("active_title","doing_handle");
        $this->assign("active_parent","handle_parent");
        $this->assign("title",$title);
        $this->assign("eid",$event_id);
        $this->all_display('handler/event_tracer.html');
    }

    //获取事件日志记录接口
    public function get_logs(){

        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        $pData['event_id'] = $this->input->post('eid', true);
        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 3;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $uid = $this->session->userdata('uid');
        $event_id = $pData['event_id'];
        //获取该事件所有的操作记录
        $res = $this->handler_model->get_all_logs($event_id,$uid);

        echo json_encode($res);
    }


    //显示正在处理的事件表
    public function show_doing_list(){
        $this->assign("active_title","doing_handle");
        $this->assign("active_parent","handle_parent");
        $this->all_display('handler/doing_unhandle.html');
    }



    //获取正在处理数据
    public function get_doing_list(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 2;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $uid = $this->session->userdata('uid');
        $data = $this->handler_model->get_doing_handle($pData,$uid);
        echo json_encode($data);
    }

    //交互显示事件处理进度
    public function show_tracer(){
        $gid = $this->session->userdata('gid');
        $event_id = $this->input->get('eid');
        $this->assign("active_title","doing_handle");
        $this->assign("active_parent","handle_parent");
        $einfo = $this->handler_model->get_title_by_eid($event_id);
        $done_btn = $this->handler_model->check_done_btn($gid,$event_id);
        $this->assign('title',$einfo['title']);
        $this->assign('rank',$einfo['rank']);
        $this->assign('eid',$event_id);
        $this->assign('can_show_done_btn',$done_btn);
        $this->all_display("handler/event_tracer.html");
    }

    /*
        接口：获取事件进度
        参数：事件id
    */
    public function get_event_logs(){
        $event_id = $this->input->post('eid');
        $data = $this->handler_model->get_all_logs_by_id($event_id);
        echo json_encode($data);
    }

    /*
        接口：发表评论
        参数：事件id，是否为总结性发言标志位，父总结性发言id
    */
    public function post_comment(){
        $event_id = $this->input->post('eid');
        $pid = $this->input->post('pid');
        $comment = $this->input->post('comment');
        $res = $this->handler_model->insert_comment($event_id,$pid,$comment);
        if($res){
            $data = array(
                'res' => 1
            );
        }else{
            $data = array(
                'res' => 0
            );
        }
        echo json_encode($data);
    }

    /*
     * 接口 : 确定事件已完成接口
     */
    public function confirm_done(){
        $eid = $this->input->post('eid');
        $gid = $this->session->userdata('gid');
        $res = $this->handler_model->confirm_done($eid,$gid);
        if($res){
            $data = array(
                'res' => 1
            );
        }else{
            $data = array(
                'res' => 0
            );
        }
        echo json_encode($data);
    }


    /*
     * 显示用户已完成事件的列表
     */
    public function show_done_list(){
        $this->assign("active_title","done_list");
        $this->assign("active_parent","handle_parent");
        $this->all_display('handler/done_list.html');
    }

    /*
     * 接口：获取用户已完成事件列表
     */
    public function get_all_done_list(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 2;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $uid = $this->session->userdata('uid');
        $data = $this->handler_model->get_done_list($pData,$uid);
        echo json_encode($data);
    }

    //展示组织结构
    public function show_structure(){
        $this->assign("active_title", "structure");
        $this->assign("active_parent", "manage_parent");
        $this->all_display("designate/show_structure.html");
    }



    public function test(){
        phpinfo();
    }


}