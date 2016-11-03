<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reporter extends MY_controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form','url'));
        $this->load->library('session');
        $this->load->model('Report_model','report');
    }

    /**
     * 提交页面，视图载入
     */
    public function wantReport(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "want_report");
        $this->all_display("report/want_report.html");
    }

    /**
     * 提交列表，视图载入
     */
    public function reportRecording()
    {
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "report_recording");
        $this->all_display("report/report_recording.html");
    }

    /**
     * 添加或更新事件 接口
     * 如果id为空添加事件，反之更新事件
     */
    public function reportOrUpdate()
    {
//        $config['upload_path']      = './uploads/';
//        $config['allowed_types']    = 'gif|jpg|png|jpeg';
//        $config['max_size']         = 1000000;
//        $config['max_width']        = 102400;
//        $config['max_height']       = 76800;
//        $config['file_name']  = time();
//        var_dump($_FILES);
//        $this->load->library('upload', $config);
//        $this->upload->do_upload('file_name');
//        $error = array('error' => $this->upload->display_errors());
        $data = $this->input->post('data');
        $id = $data[0]['value'];
        $title = $data[1]['value'];
        $url = $data[2]['value'];
        $source = $data[3]['value'] == 'other'?$data[4]['value']:$data[3]['value'];
        $description = $data[5]['value'];
        $picture = 'test.jpg';
        $uid = $this->session->userdata('uid');
        $data = array('id'=>$id,'title'=>$title,'source'=>$source,'picture'=>$picture,'url' => $url,'description'=>$description,'uid'=>$uid,'time'=>$_SERVER['REQUEST_TIME']);
        $nu = $this->report->add_or_update($data);
        $res = array(
            "res"=> $nu
        );
        echo json_encode($res);
    }

    /**
     * 提交信息列表分页，数据接口
     */
    public function get_report_data(){
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
        $data = $this->report->get_all_report($pData,$uid);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * 提交详情 页面载入
     */
    public function show_detail(){
        $info_id = $this->input->get("id");
        if(!isset($info_id) || $info_id == null || $info_id == ""){
            show_404();
        }

        $info = $this->report->get_detail_by_id($info_id);
        $this->assign("event", $info);
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "report_recording");
        $this->all_display("report/show_report_detail.html");
    }

    /**
     * 修改时判断url是否重复
     * 判断是否和自己已经提交过的其它事件重复
     */

    public function edit_judge_url(){
        $url = $this->input->post('url');
        $id = $this->input->post('id');
        if ($url == null){
            echo "-1";
        }else{
            $judge = $this->report->edit_judge_url($url,$id);
            echo json_encode($judge);
        }
    }

    /**
     * 提交时判断url是否重复
     * 判断是否和自己已经提交过的其它事件重复
     */

    public function judge_url(){
        $url = $this->input->post('url');
        $uid = $this->session->userdata('uid');
        if ($url == null){
            echo "-1";
        }else{
            $judge = $this->report->judge_url($url,$uid);
            echo $judge;
        }
    }

    /**
     * 修改或删除前判断是否已经被查看
     */

    public function pre_state(){
        $info_id = $this->input->post('id');
        if(!isset($info_id) || $info_id == null || $info_id == ""){
            show_404();
        }
        $info = array('data'=> $this -> report -> get_state($info_id));
        echo json_encode($info['data'][0]);
    }


    /**
     * 修改页面 视图加载
     */

    public function edit(){
        $id = $this->input->get('id');
        $info = $this->report->get_detail_by_id($id);
        $this->assign("info",$info);
        $this->all_display("report/edit_report.html");
    }



    /**
     * 删除事件 数据接口
     */

    public function delete(){
        $id = $this->input->post('id');
        $res =  $this->report->del($id);
        $result = array('data'=> $res);
        echo json_encode($result);
    }
}