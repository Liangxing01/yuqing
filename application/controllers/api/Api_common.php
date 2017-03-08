<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_common extends CI_Controller
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


    /**
     * 用户登出 接口
     */
    public function logout()
    {
        $this->identity->m_destroy();
    }
}