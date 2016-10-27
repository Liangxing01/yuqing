<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reporter extends MY_controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form','url'));
        $this->load->library('session');
    }

    public function wantReport(){
        $this->load->view('report/want_report',array('error'=>''));
    }


    public function reportRecording()
    {
//        $this->assign("active_title", "designate_parent");
//        $this->assign("active_parent", "report_recording");
        $this->all_display("report/report_recording.html");
    }


    public function report()
    {
        $this->load->model('Report_model','report');
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
        $description = $_POST['description'];
        $uid = $this->session->userdata('uid');
        $picture = 'test.jpg';
        $data = array('title'=>$title,'source'=>$source,'picture'=>$picture,'url' => $url,'description'=>$description,'uid'=>$uid,'start_time'=>$_SERVER['REQUEST_TIME']);
        $this->report->submit($data);
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

        $this->load->model('Report_model','report');
        $data = $this->report->get_all_report($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

}