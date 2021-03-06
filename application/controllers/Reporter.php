<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reporter extends MY_controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', "public"));
        $this->load->library('session');
        $this->load->model('Report_model', 'report');
        $this->identity->is_authentic();
    }

    /**
     * 提交页面，视图载入
     */
    public function wantReport()
    {
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "want_report");
        if (isMobile()) {
            //手机版 我要提交 页面
            $this->m_all_display("report/m_want_report.html");
        } else {
            $this->all_display("report/want_report.html");
        }
    }


    /**
     * 提交列表，视图载入
     */
    public function reportRecording()
    {
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "report_recording");
        if (isMobile()) {
            //手机版 提交列表 页面
            $this->m_all_display("report/m_report_record.html");
        } else {
            $this->all_display("report/report_recording.html");
        }
    }

    /**
     * 添加或更新事件 接口
     * 如果id为空添加事件，反之更新事件
     */
    public function reportOrUpdate()
    {
        $data = $this->input->post();
        $title = trim($data['title']);
        $url = $data['url'];
        $source = $data['source'] == 'other' ? $data['other'] : $data['source'];
        $description = $data['description'];
        $attachment = $data['attachment'];
        $uid = $this->session->userdata('uid');
        $data = array('title' => $title, 'source' => $source, 'url' => $url, 'description' => $description, 'uid' => $uid);
        $result = $this->report->add_info($data);
        if ($result !== false) {
            //插入附件信息
            if ($attachment != "") {
                $this->insert_attachment($attachment, $result);
            }
            echo json_encode(array("res" => 1));
        } else {
            echo json_encode(array("res" => 0));
        }
    }

    /**
     * @param $att
     * @param $info_id
     */
    public function insert_attachment($att, $info_id)
    {
        $this->report->insert_att_info($att, $info_id);
    }

    /**
     * 提交信息列表分页，数据接口
     */
    public function get_report_data()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 2;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }
        if ($pData['search'] == NULL) {
            $pData['search'] = '';
        }

        $uid = $this->session->userdata('uid');
        $data = $this->report->get_all_report($pData, $uid);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 单位记录 数据接口
     */
    public function get_group_record()
    {
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第2列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc

        if ($pData['start'] == NULL) {
            $pData['start'] = 0;
        }
        if ($pData['length'] == NULL) {
            $pData['length'] = 10;
        }
        if ($pData["sort_th"] == NULL) {
            $pData["sort_th"] = 1;
        }
        if ($pData['sort_type'] == NULL) {
            $pData['sort_type'] = "desc";
        }

        $data = $this->report->get_group_record($pData);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


    /**
     * 移动端 提交记录 滚动加载 数据接口
     * post: (int)page
     */
    public function scroll_record_data()
    {
        $page_num = $this->input->post("page");        //页码
        if (!isset($page_num) || $page_num == null || $page_num == "") {
            show_404();
        }

        $result = $this->report->scroll_record_pagination($page_num);
        $this->output
            ->set_content_type('application/json')
            ->set_output($result);
    }


    /**
     * 信息详情 页面载入
     */
    public function show_detail()
    {
        $info_id = $this->input->get("id", true);
        if (!isset($info_id) || $info_id == null || $info_id == "") {
            show_404();
        }

        $this->load->model("Designate_Model", "designate");
        $info = $this->designate->get_info($info_id);
        $this->assign("info", $info);
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "report_recording");
        if (isMobile()) {
            //信息详情 手机页面载入
            $this->m_all_display("report/m_info_detail.html");
        } else {
            $this->all_display("report/info_detail.html");
        }
    }


    /**
     * 修改时判断url是否重复
     * 判断是否和自己已经提交过的其它事件重复
     */

    public function edit_judge_url()
    {
        $url = $this->input->post('url');
        $id = $this->input->post('id');
        if ($url == null) {
            echo "-1";
        } else {
            $judge = $this->report->edit_judge_url($url, $id);
            echo json_encode($judge);
        }
    }

    /**
     * 提交时判断url是否重复
     * 判断是否和自己已经提交过的其它事件重复
     */

    public function judge_url()
    {
        $url = $this->input->post('url');
        $uid = $this->session->userdata('uid');
        $search = '~^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?~i';
        $url = trim($url);
        if ($url == null) {
            echo "-1";
        } else {
            $judge = $this->report->judge_url($url, $uid);
            //echo $judge;
            if ($judge) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    /**
     * 修改或删除前判断是否已经被查看
     */

    public function pre_state()
    {
        $info_id = $this->input->post('id');
        if (!isset($info_id) || $info_id == null || $info_id == "") {
            show_404();
        }
        $info = array('data' => $this->report->get_state($info_id));
        echo json_encode($info['data'][0]);
    }


    /**
     * 修改页面 视图加载
     */
//    public function edit()
//    {
//        $id = $this->input->get('id');
//        $info = $this->report->get_detail_by_id($id);
//        $this->assign("info", $info);
//        $this->all_display("report/edit_report.html");
//    }


    /**
     * 删除事件 数据接口
     */

    public function delete()
    {
        $id = $this->input->post('id');
        $res = $this->report->del($id);
        $result = array('data' => $res);
        echo json_encode($result);
    }

    /**
     * 上传截图接口
     */
    public function upload_pic()
    {

        $config = array();
        $config['upload_path'] = './uploads/temp/';
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['max_size'] = 10000;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0, 'info' => $error
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            $res = array(
                'res' => 1,
                'info' => array(
                    'name' => $upload_data['orig_name'],
                    'url' => '/uploads/pic/' . $upload_data['file_name'],
                    'new_name' => $upload_data['file_name'],
                    'type' => $upload_data['image_type']
                )
            );
            echo json_encode($res);

        }
    }


    /**
     * 视频上传接口
     */
    public function upload_video()
    {
        $config['upload_path'] = './uploads/temp/';
        $config['allowed_types'] = 'mp4|rmvb|flv|avi|rm|wmv|mkv';
        $config['max_size'] = 0;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;


        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $error = $this->upload->display_errors();
            $res = array(
                'res' => 0, 'info' => $error
            );
            echo json_encode($res);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $upload_data = $data['upload_data'];
            $res = array(
                'res' => 1,
                'info' => array(
                    'name' => $upload_data['client_name'],
                    'url' => '/uploads/video/' . $upload_data['file_name'],
                    'new_name' => $upload_data['file_name'],
                    'type' => $upload_data['file_type']
                )
            );
            echo json_encode($res);

        }
    }

}