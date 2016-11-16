<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Common extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
    }


    /**
     * 事件详情页面 视图载入
     */
    public function event_detail()
    {
        $event_id = $this->input->get("eid");
        if (!isset($event_id) || $event_id == null || $event_id == "") {
            show_404();
        }

        //检查事件查看权限
        $this->load->model("Verify_Model", "verify");
        if (!$this->verify->can_see_event($event_id)) {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $event = $this->designate->get_event($event_id);

        $this->assign("event", $event);

        $this->all_display("designate/event_detail.html");
    }


    /**
     * 接口: 获取上报信息
     * 参数: 事件ID 信息ID
     * 返回: Json 字符串
     */
    public function get_event_info()
    {
        $event_id = $this->input->post("event_id");
        $info_id = $this->input->post("info_id");
        $this->load->model("Designate_Model", "designate");
        $info = $this->designate->get_event_info($event_id, $info_id);
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($info));
    }
}