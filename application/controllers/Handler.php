<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Handler extends MY_Controller {

    public function __construct(){
        parent::__construct();
        header('Access-Control-Allow-Origin:*');
        $this->load->model('Handler_Model',"handler_model");
    }

    public function index()
    {

        //$this->load->view('welcome_message');
        $this->all_display('index.html');
    }

    /*
     * 主页接口：获取各项任务数统计数字
     */
    public function get_tasks_num(){
        $uid = $this->session->userdata('uid');
        $res = $this->handler_model->get_tasks_num($uid);
        echo json_encode($res);
    }

    /*
     * 主页接口：获取任务列表，3个待处理，3个正在处理
     */
    public function get_tasks_list(){
        $uid = $this->session->userdata('uid');
        $res = $this->handler_model->get_tasks_list($uid);
        echo json_encode($res);
    }

    /*
     * 主页接口：获取用户最近登录的5条记录
     */
    public function login_list(){
        $uid = $this->session->userdata('uid');
        //MY_controller 方法
        $res = $this->get_login_list($uid,6);
        echo json_encode($res);
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

        //高级查询 关键字
        $pData["start_time"] = $this->input->post('start_time', true);  //查询起始时间 默认为0
        $pData["end_time"] = $this->input->post('end_time', true);      //查询截止时间 默认为0
        $pData['is_group'] = $this->input->post('is_group');
        $pData['rank']     = $this->input->post('rank');

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
        $event_id = $this->input->get("eid");
        if(!isset($event_id) || $event_id == null || $event_id == ""){
            show_404();
        }

        $this->assign('eid',$event_id);
        $this->assign("active_title","wait_to_handle");
        $this->assign("active_parent","handle_parent");

        //检查 事件查看权限
        $this->load->model("Common_Model", "common");
        if (!$this->common->check_can_see($event_id)) {
            show_404();
        }
        //$this->common->check_can_see($event_id);

        $this->load->model("Designate_Model", "designate");
        $event = $this->designate->get_event($event_id);

        $this->assign("event", $event);

        $this->all_display("handler/event_detail.html");
    }

    /*
     * 接口：获取待处理事件 详细信息
     * 参数：eid
     */
    public function get_detail(){
        $event_id = $this->input->post('eid');
        $uid = $this->session->userdata('uid');
        $event_info = $this->handler_model->get_detail_by_id($event_id,$uid);
        $this->handler_model->cancel_alarm_state($event_id,$uid);
        echo json_encode($event_info);
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

        $pData['rank'] = $this->input->post('rank');

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
        $this->load->model('MY_Model','my_model');
        $gid = $this->session->userdata('gid');
        $uid = $this->session->userdata('uid');
        //判断有无督办权限
        $pri = explode(",",$this->session->userdata('privilege'));

        /*foreach ($pri as $one){
            if($one == 4){
                $usertype = 1;
            }else{
                $usertype = 0;
            }
        }*/
        $event_id = $this->input->get('eid');
        //判断对这样事 有督办全
        $duban = $this->my_model->check_duban($uid,$event_id);
        if ($duban){
            $usertype = 1;
        }else{
            $usertype = 0;
        }
        $first = $this->input->get('first');
        //更新事件状态为处理中
        if(!empty($first)){
            $this->handler_model->update_doing_state($event_id);
        }

        $this->assign("active_title","doing_handle");
        $this->assign("active_parent","handle_parent");
        $einfo = $this->handler_model->get_title_by_eid($event_id);
        $done_btn = $this->handler_model->check_done_btn($gid,$event_id);
        $this->assign('title',$einfo['title']);
        $this->assign('rank',$einfo['rank']);
        if($einfo['state'] == "已完成" || $einfo['state'] == '未审核'){
            $done_state = 1;
        }else{
            $done_state = 0;
        }
        if(!empty($einfo['end_time'])){
            $this->assign('end_time',$einfo['end_time']);
        }else{
            $this->assign('end_time',"");
        }
        $this->assign('done_state',$done_state);
        $this->assign('eid',$event_id);
        $this->assign('can_show_done_btn',$done_btn);

        //个人信息

        $user_info = $this->my_model->get_user_info($uid);
        $this->assign('username',$user_info[0]['username']);
        $this->assign('useracter',$user_info[0]['avatar']);
        $this->assign('usertype',$usertype);

        //获取 参考文件
        $doc_arr = $this->handler_model->get_event_attachment($event_id);
        $this->assign('attachment',$doc_arr);
        $this->all_display("handler/event_tracer.html");
    }

    //显示事件检索页面
    public function show_all_events(){
        $this->assign("active_title","search");
        $this->assign("active_parent","handle_parent");
        $this->all_display('handler/event_search.html');
    }

    //分页显示所有事件
    public function get_all_events_data(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        //高级查询 关键字
        $pData["start_time"] = $this->input->post('start_time', true);  //查询起始时间 默认为0
        $pData["end_time"] = $this->input->post('end_time', true);      //查询截止时间 默认为0
        $pData['is_group'] = $this->input->post('is_group');
        $pData['rank']     = $this->input->post('rank');

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
        $gid = $this->session->userdata('gid');

        $data = $this->handler_model->get_all_events($pData,$uid,$gid);
        echo json_encode($data);
    }

    /*
        接口：获取事件进度
        参数：事件id
    */
    public function get_event_logs(){
        $this->load->model('Common_Model','common_model');
        $event_id = $this->input->post('eid');
        $data = $this->common_model->get_all_logs_by_id($event_id);
        echo json_encode($data);
    }

    /*
     * 接口：返回事件 参考文献
     * 参数：事件id
     */
    public function get_attachment(){
        $event_id = $this->input->post('eid');
        $uid = $this->session->userdata('uid');
        $data = $this->handler_model->get_attachment_by_id($event_id,$uid);
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





    public function test(){
        //phpinfo();
        //$this->handler_model->cancel_alarm_state(1,3);
        $this->get_my_info();
    }


}