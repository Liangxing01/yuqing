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
     * POST: page_num: 当前页码, size: 每页显示数
     */
    public function get_summary_info()
    {
        $page_num = (int)$this->input->post("page_num");
        $size = (int)$this->input->post("size");
        $result = $this->common->get_summary_data($page_num, $size);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 修改个人信息
     * POST: name: 姓名, sex: 性别, avatar: 头像文件
     */
    public function update_info()
    {
        $name = $this->input->post('name');
        $sex = $this->input->post('sex');
        $is_avatar = $this->input->post('is_avatar');
        $result = $this->common->update_info($name, $sex, $is_avatar);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    /**
     * 修改密码
     * POST: old_pass: 旧密码, new_pass: 新密码
     */
    public function update_password()
    {
        $old = $this->input->post('old_pass');
        $new = $this->input->post('new_pass');
        $result = $this->common->update_password($old, $new);
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
