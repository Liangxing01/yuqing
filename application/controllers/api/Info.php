<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 移动端 事件信息 接口
 */
class Info extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
        $this->load->model("API_Model/Info_Model", "info");
    }


    /**
     * 上报接口
     */
    public function report_info()
    {
    }


    /**
     * 提交记录 分页数据接口
     * POST参数:pageNum, size
     * @return string Json
     */
    public function get_info_record()
    {
        //默认按 时间 DESC 排序
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $result = $this->info->get_info_record_data($page_num, $size);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 信息详情
     * GET参数:信息ID
     * @return string Json
     */
    public function get_info_detail()
    {
        
    }


    /**
     * 信息检索 分页数据接口
     * POST参数:pageNum, size
     * @return string Json
     */
    public function search_info()
    {
        //分页参数 pageNum size
        //查询参数暂略
        //默认按 时间 DESC 排序
    }

}