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

    public function wantReport(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "want_report");
        $this->all_display("report/want_report.html");
    }


    public function reportRecording()
    {
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "report_recording");
        $this->all_display("report/report_recording.html");
    }


    public function report()
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
        $title = $_POST['title'];
        $url = $_POST['url'];
        $source = $_POST['source'];
        if ($source == 'other'){
            $source = $_POST['other'];
        }
        $description = $_POST['description'];
        $uid = $this->session->userdata('uid');
        $picture = 'test.jpg';
        $data = array('title'=>$title,'source'=>$source,'picture'=>$picture,'url' => $url,'description'=>$description,'uid'=>$uid,'time'=>$_SERVER['REQUEST_TIME']);
        $this->report->submit($data);
        $this->reportRecording();
    }

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

    public function edit_judge_url(){
        $url = $_POST['url'];
        $id = $_POST['id'];
        if ($url == null){
            echo "-1";
        }else{
            $judge = $this->report->edit_judge_url($url,$id);
            $res = array('data',$judge);
            echo json_encode($res);
        }
    }

    public function judge_url(){
        $url = $_POST['url'];
        if ($url == null){
            echo "-1";
        }else{
            $judge = $this->report->judge_url($url);
            echo $judge;
        }
    }

    public function pre_edit(){
        $info_id = $_POST['id'];
        if(!isset($info_id) || $info_id == null || $info_id == ""){
            show_404();
        }
        $info = array('data'=> $this -> report -> get_state($info_id));
        echo json_encode($info['data'][0]);
    }

    public function edit(){
        $id = $_GET['id'];
        $info = $this->report->get_detail_by_id($id);
        $this->assign("info",$info);
        $this->all_display("report/edit_report.html");
    }

    public function update(){
        $id = $_POST['id'];
        $title = $_POST['title'];
        $url = $_POST['url'];
        $source = $_POST['source'];
        if ($source == 'other'){
            $source = $_POST['other'];
        }
        $description = $_POST['description'];
        $uid = $this->session->userdata('uid');
        $picture = 'test.jpg';
        $data = array('id'=>$id,'title'=>$title,'source'=>$source,'picture'=>$picture,'url' => $url,'description'=>$description,'uid'=>$uid,'time'=>$_SERVER['REQUEST_TIME']);
        $this->report->update($data);
        $this->reportRecording();
    }

    public function pre_del(){
        $info_id = $_POST['id'];
        if(!isset($info_id) || $info_id == null || $info_id == ""){
            show_404();
        }
        $info = array('data'=> $this -> report -> get_state($info_id));
        echo json_encode($info['data'][0]);
    }

    public function delete(){
        $id = $this->input->post('id');
        $res =  $this->report->del($id);
        $result = array('data'=> $res);
        echo json_encode($result);
    }
}