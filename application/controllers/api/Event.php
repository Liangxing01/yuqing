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
     * POST参数 page_num: 页码, size: 条数, type: [f_record=父评论, c_record=子评论], id: 事件ID, pid: 父评论ID
     * 默认返回父评论
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
     * POST参数 id: 事件id, pid: 父评论id, content: 评论内容
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
     * POST参数 page_num: 页码, size: 条数, data_type: 数据类型, usertype: 用户类型, keyword: 模糊查询关键字
     * 类型参数可选(全部事件:all)默认返回全部
     */
    public function get_event_list()
    {
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $data_type = $this->input->post("data_type"); // 请求结果类型
        $user_type = $this->input->post("user_type"); // 用户类型
        $keyword = $this->input->post("keyword"); // 模糊搜索关键词
        $result = $this->event->get_event_list_data($page_num, $size, $data_type, $user_type, $keyword);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 事件详情
     * POST参数 id: 事件ID
     */
    public function get_event_detail()
    {
        $event_id = $this->input->post("id");
        $result = $this->event->get_event_detail_data($event_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 事件审核
     * POST参数 id: 事件ID, option: [verify_success=审核通过, verify_failed=审核不通过, commit=提交审核]
     */
    public function event_check()
    {
        $event_id = $this->input->post("id");
        $option = $this->input->post("option");
        $result = $this->event->event_check($event_id, $option);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 更新事件状态(待处理 -> 处理中)
     */
    public function event_state_update()
    {
        $event_id = $this->input->post("id");
        $result = $this->event->event_state_update($event_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

}