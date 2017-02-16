<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API_Common extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
        $this->load->model("API_Models/API_Common_Model", "common");
    }


    /**
     * 移动端APP 首页数据接口
     */
    public function get_summary_info()
    {
        $result = $this->common->get_summary_data();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
}
