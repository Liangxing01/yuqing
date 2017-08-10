<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 移动端 事件信息 接口
 */
class Info extends CI_Controller
{

    /**
     * Info constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
        $this->load->model("API_Models/Info_Model", "info");
    }


    /**
     * 上报接口
     */
    public function commit_info()
    {
        // 接收表单数据
        $pData['title'] = trim($this->input->post('title'));
        $pData['url'] = $this->input->post('url');
        $pData['source'] = $this->input->post('source');
        $pData['description'] = $this->input->post('description');
        $pData['video_info'] = $this->input->post('video_info');
        // 插入数据
        $result = $this->info->add_info_data($pData, 'photos');
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 上报信息 视频上传接口
     * POST参数:video 视频文件(单个)
     */
    public function upload_video()
    {
        $result = $this->info->upload_video();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 上报信息列表 分页数据接口 (上报)
     * POST参数 page_num: 页码, size: 条数, keyword: 搜索关键词
     * @return string Json
     */
    public function get_info_record()
    {
        //默认按 时间 DESC 排序
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $keyword = $this->input->post("keyword");
        $result = $this->info->get_info_record_data($page_num, $size, $keyword);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 上报信息列表 分页数据接口 (指派)
     * POST参数:page_num 页码, size 条数, data_type 记录类型, keyword 关键词搜索
     * 记录类型参数可选(所有记录:all_message, 未确认记录:undo_message)默认返回所有记录
     * @return string Json
     */
    public function get_info_list()
    {
        //默认按 时间 DESC 排序
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $data_type = $this->input->post("data_type");
        $keyword = $this->input->post("keyword");
        $result = $this->info->get_info_list_data($page_num, $size, $data_type, $keyword);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 信息详情
     * GET参数:id 信息id
     * @return string Json
     */
    public function get_info_detail()
    {
        $info_id = $this->input->post("id");
        $result = $this->info->get_info_detail($info_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 获取 信息类型 涉及领域
     */
    public function get_info_type()
    {
        $result = $this->info->get_info_type();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 信息检索 分页数据接口
     * POST参数:pageNum 页码, size 条数
     * @return string Json
     */
    public function search_info()
    {
        //分页参数 pageNum size
        //查询参数暂略
        //默认按 时间 DESC 排序
    }

}