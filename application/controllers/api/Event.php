<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 移动端 事件操作接口
 */
class Event extends CI_Controller
{

    /**
     * Event constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
        $this->load->model("API_Models/Event_Model", "event");
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
     * 事件处理 发表回复
     * POST参数:id 事件id, pid 父评论id, content 评论内容
     * 父评论id 可选
     */
    public function post_comment()
    {
        $event_id = $this->input->post('id');
        $pid = $this->input->post('pid');
        $content = $this->input->post('content');
        $result = $this->event->insert_comment($event_id, $pid, $content);
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


    /**
     * 事件详情
     * POST参数: id 事件ID
     */
    public function get_event_detail()
    {
        $event_id = $this->input->post("id");
        $result = $this->event->get_event_detail_data($event_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
}