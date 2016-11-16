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
     * 事件参考文件下载 接口
     */
    public function attachment_download()
    {
        $id = $this->input->get("id", true);
        if (!isset($id) || $id == null || $id == "") {
            show_404();
        }

        //获取附件信息和鉴权
        $this->load->model("Common_Model", "common");
        $attachment = $this->common->event_attachment_download($id);
        if ($attachment) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $attachment["url"])) {
                $this->load->helper("download");
                $data = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $attachment["url"]);
                $name = $attachment["name"];
                force_download($name, $data);
            } else {
                show_404("文件不存在");
            }
        } else {
            show_404("文件不存在");
        }
    }
}