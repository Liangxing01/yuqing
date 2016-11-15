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
    }

    /**
     * 展示督查事件列表
     */
    public function show_watch_list(){
        $this->assign("active_title", "watch_parent");
        $this->assign("active_parent", "watch_list");
        $this->all_display("watch/watch_list.html");
    }

    public function get_watch_list(){
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


        $data = $this->watcher_model->get_all_watch_list($pData);
        echo json_encode($data);
    }

    //交互显示事件处理进度
    public function show_tracer(){
        $this->load->model('Handler_Model','handler_model');
        $gid = $this->session->userdata('gid');
        $uid = $this->session->userdata('uid');
        //判断有无督办权限
        $pri = explode(",",$this->session->userdata('privilege'));
        foreach ($pri as $one){
            if($one == 4){
                $usertype = 1;
            }else{
                $usertype = 0;
            }
        }
        $event_id = $this->input->get('eid');


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

        //获取 参考文件
        $doc_arr = $this->handler_model->get_event_attachment($event_id);
        $this->assign('attachment',$doc_arr);
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

}