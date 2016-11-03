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
     * 未处理信息 视图载入
     */
    public function info_not_handle()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "info_not_handle");
        $this->all_display("designate/info_not_handle.html");
    }


    /**
     * 未处理信息列表分页 数据接口
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
     * 舆情信息详情页面 视图载入
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
            echo "提交成功";
        } else {
            echo "提交失败";
        }
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
        if ($result) {
            echo "指派成功";
        } else {
            echo "指派失败";
        }
    }


    /**
     * 已指派事件列表 视图载入
     */
    public function event_is_designate()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_is_designate");
        $this->all_display("designate/event_is_designate.html");
    }


    /**
     * 已指派事件列表分页 数据接口
     */
    public function event_is_designate_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第六列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 5;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->event_is_designate_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 事件追踪 视图载入
     */
    public function event_tracer()
    {
        $this->assign("active_title", "designate_parent");
        $this->assign("active_parent", "event_is_designate");
        $this->all_display("designate/event_tracer.html");
    }


    /**
     * 事件检索列表 视图载入
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
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第六列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 5;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $this->load->model("Designate_Model", "designate");
        $data = $this->designate->event_search_pagination($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    // demo
    public function tree()
    {
        $this->load->model("Designate_Model", "designate");
        $this->designate->get_relation_tree();
    }

}