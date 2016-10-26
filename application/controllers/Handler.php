<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Handler extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('handler_model');
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

        $data = $this->handler_model->get_all_unhandle($pData,3);
        echo json_encode($data);

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


        $data = $this->handler_model->get_doing_handle($pData,3);
        echo json_encode($data);
    }


}