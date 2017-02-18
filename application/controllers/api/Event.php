<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 移动端 事件操作接口
 */
class Event extends CI_Controller
{

    /**
     * 数据接口 返回值
     * @var array
     * code 0 请求成功
     */
    private $success;

    /**
     * 数据接口 返回值
     * @var array
     * code 1 参数错误
     */
    private $param_error;

    /**
     * 数据接口 返回值
     * @var array
     * code 2 权限错误
     */
    private $privilege_error;


    /**
     * Info_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
        $this->load->model("API_Models/Event_Model", "event");
    }


    /**
     * 事件查询 分页数据
     * POST参数:pageNum, size
     * @return string Json
     */
    public function search_event()
    {
        //分页参数 pageNum size
        //查询参数暂略
        //默认按 时间 DESC 排序
    }


    /**
     * 事件处理 回复时间线数据
     * POST参数:page_num 页码, size 条数, type 类型, id , pid
     * 记录类型参数可选(未确认记录:f_record, 上报记录:c_record)默认返回父评论
     */
    public function response_timeline()
    {
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $type = $this->input->post("type"); // 请求结果类型
        $event_id = $this->input->post("id"); // 事件ID
        $parent_id = $this->input->post("pid"); // 父评论ID
        $result = $this->event->get_response_timeline_data($page_num, $size, $type, $event_id, $parent_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 事件列表
     * POST参数: page_num 页码, size 条数, data_type 数据类型, usertype: 用户类型
     * 类型参数可选(全部事件:all)默认返回全部
     */
    public function get_event_list()
    {
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $data_type = $this->input->post("data_type"); // 请求结果类型
        $user_type = $this->input->post("user_type"); // 用户类型
        $result = $this->event->get_event_list_data($page_num, $size, $data_type, $user_type);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

}