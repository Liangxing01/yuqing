<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/8
 * Time: 10:45
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller {
    public function __construct(){
        parent::__construct();

        header('Access-Control-Allow-Origin:*');
        $this->load->model('Admin_Model',"admin_model");
    }

    //展示上报信息查看页面
    public function show_all_info(){
        $type_list = $this->admin_model->get_info_type();  //获取类别列表
        $this->assign("type_list", $type_list);
        $this->assign("active_title","see_infos");
        $this->assign("active_parent","infos_parent");
        $this->all_display('admin/all_infos.html');
    }

    /**
     * 上报信息检索列表分页 数据接口
     */
    public function info_search_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第六列
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
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 5;
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

    //所有事件查看
    public function show_all_events(){
        $this->assign("active_title","see_events");
        $this->assign("active_parent","events_parent");
        $this->all_display('admin/all_events.html');
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
            $pData['sort_start'] = 0;
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
     * 展示用户管理界面
     */
    public function show_user_manage(){
        $this->assign("active_title","struct_parent");
        $this->assign("active_parent","user_manage");
        $this->all_display('admin/user_manage.html');
    }

    public function get_group(){
        $this->load->model("Tree_Model","tree_model");
        $res = $this->tree_model->get_group_tree();
        echo $res;
    }

    //添加用户
    public function add_person(){
        $data = $this->input->post();
        $res = $this->admin_model->add_person($data);
        if($res){
            echo json_encode(
                array(
                    'res' => 1
                )
            );
        }else{
            echo json_encode(
                array(
                    'res' => 0
                )
            );
        }
    }

    //更新用户
    public function update_user(){
        $data = $this->input->post();
        $res = $this->admin_model->update_user($data);
        if($res){
            echo json_encode(
                array(
                    'res' => 1
                )
            );
        }else{
            echo json_encode(
                array(
                    'res' => 0
                )
            );
        }
    }

    /**
     * 展示单位管理界面
     */
    public function show_group_manage(){
        $this->assign("active_title","struct_parent");
        $this->assign("active_parent","group_manage");
        $this->all_display('admin/group_manage.html');
    }

    //添加单位
    public function add_group(){
        $data = $this->input->post();
        $res = $this->admin_model->add_group($data);
        if($res){
            echo json_encode(
                array(
                    'res' => 1
                )
            );
        }else{
            echo json_encode(
                array(
                    'res' => 0
                )
            );
        }
    }

    public function get_node_info(){
        $data = $this->input->post();
        if (!empty($data)){
            $uid  = $data['id'];
            $type = $data['type'];//1为个人，0为单位
            if($type == 0){
                $res = $this->admin_model->get_group_info($uid);
            }else if($type == 1){
                $res = $this->admin_model->get_user_info($uid);
            }
            echo json_encode($res);
        }
    }

    /**
     *更新节点
     */
    public function update_info(){
        $data = $this->input->post();
        $type = $data['type'];//1为个人，0为单位
        if($type == 0){
            $res = $this->admin_model->update_group($data);
        }else if($type == 1){
            $res = $this->admin_model->update_user($data);
        }
        var_dump($res);
    }


}