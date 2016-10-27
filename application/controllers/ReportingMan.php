<?php
/**
 * Created by PhpStorm.
 * User: 黎佑民
 * Date: 2016/10/26
 * Time: 16:19
 */
class ReportingMan extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form','url'));
    }

    public function wantReport(){
        $this->load->view('wantReport',array('error'=>''));
    }



    public function report()
    {
       // $this->load->model('report');
        $config['upload_path']      = './uploads/';
        $config['allowed_types']    = 'gif|jpg|png|jpeg';
        $config['max_size']         = 1000000;
        $config['max_width']        = 102400;
        $config['max_height']       = 76800;
        $config['file_name']  = time();
        var_dump($_FILES);
        $this->load->library('upload', $config);
        $this->upload->do_upload('file_name');
        $error = array('error' => $this->upload->display_errors());

          var_dump($error);
//        if ( ! $this->upload->do_upload('file_name'))
//        {
//            $error = array('error' => $this->upload->display_errors());
//
//            var_dump($error);
//        }
//        else
//        {
//            $data = array('upload_data' => $this->upload->data());
//
//            $this->load->view('upload_success', $data);
//        }

    }

}