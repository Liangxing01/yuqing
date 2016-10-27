<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Designate extends MY_controller
{

    public function __construct()
    {
        parent::__construct();
        //TODO 登陆验证权限控制
        $this->session->set_userdata("uid", 2);
        $this->session->set_userdata("name", "李四");
    }


    /**
     * 待指派事件列表 视图载入
     */
    public function event_not_designate()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_not_designate");
        $this->all_display("designate/event_not_designate.html");
    }


    /**
     * 待指派事件列表分页 数据接口
     */
    public function event_not_designate_data()
    {
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

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->event_not_designate_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 事件指派 视图载入
     */
    public function event_designate()
    {
        $event_id = $this->input->get("id", true);
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $event_info = $this->designate->get_event_info($event_id);
        $this->assign("event", $event_info);
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_not_designate");
        $this->all_display("designate/event_designate.html");
    }


    /**
     * 事件指派 表单提交
     */
    public function commit_event_designate()
    {
        $data = $this->input->post();
        if (!isset($data["processor"]) || $data["event_id"] == "") {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $result = $this->designate->event_designate($data);
        if($result){
            echo "指派成功";
        }else{
            echo "指派失败";
        }
    }


    public function tree()
    {
        $this->load->model("Designate_Model", "designate");
        $this->designate->get_relation_tree();
    }

}