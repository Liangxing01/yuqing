<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/13
 * Time: 15:30
 */

//督查组 控制器
class Watch extends MY_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Watcher_Model','watcher_model');
        $this->identity->is_authentic();
    }

    /**
     * 展示督查事件列表
     */
    public function show_watch_list(){
        $this->assign("active_title", "watch_parent");
        $this->assign("active_parent", "watch_list");
        $this->all_display("watch/watch_list.html");
    }


    /**
     * 督查事件列表分页 数据接口
     */
    public function get_watch_list(){

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


        $this->load->model("Watcher_Model", "watcher");
        $data = $this->watcher->get_all_watch_list($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    //交互显示事件处理进度
    public function show_tracer(){
        $this->load->helper(array("public"));

        $this->load->model('Handler_Model','handler_model');

        $gid = $this->session->userdata('gid');
        $uid = $this->session->userdata('uid');

        $event_id = $this->input->get('eid');

        //判断能不能看这个 页面
        $this->load->model('Verify_Model','verify');
        $ver = $this->verify->can_see_event($event_id);
        if(!$ver){
            show_404();
        }
        //判断对这样事 有督办全
        $this->load->model("MY_Model", "my_model");
        $duban = $this->my_model->check_duban($uid,$event_id);
        if ($duban){
            $usertype = 1;
        }else{
            $usertype = 0;
        }

        $this->assign("active_title","watch_list");
        $this->assign("active_parent","watch_parent");
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
        $this->load->model('MY_Model','my_model');
        $user_info = $this->my_model->get_user_info($uid);
        $this->assign('username',$user_info[0]['name']);
        $this->assign('useracter',$user_info[0]['avatar']);
        $this->assign('usertype',$usertype);

        //获取事件信息链接
        $links = $this->handler_model->get_info_url($event_id);
        $this->assign("links", $links);

        //获取 参考文件
        $doc_arr = $this->handler_model->get_event_attachment($event_id);
        $this->assign('attachment',$doc_arr);

        if(isMobile()){
            $this->m_all_display("watch/event_tracer.html");
        }
        $this->all_display("watch/event_tracer.html");
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

    /**
     * 显示待处理事件详情信息
     */
    public function show_detail(){
        $event_id = $this->input->get("eid");
        if(!isset($event_id) || $event_id == null || $event_id == ""){
            show_404();
        }

        $this->assign('eid',$event_id);
        $this->assign("active_title","watch_list");
        $this->assign("active_parent","watch_parent");

        //检查 事件查看权限
        $this->load->model("Common_Model", "common");
        if (!$this->common->check_can_see($event_id)) {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $event = $this->designate->get_event($event_id);

        $this->assign("event", $event);

        $this->all_display("handler/event_detail.html");
    }

}