<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/13
 * Time: 15:30
 */

//督查组 控制器
class Watch extends MY_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('Watcher_Model','watcher_model');
    }

    /**
     * 展示督查事件列表
     */
    public function show_watch_list(){
        $this->assign("active_title", "watch_parent");
        $this->assign("active_parent", "watch_list");
        $this->all_display("watch/watch_list.html");
    }

    public function get_watch_list(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        //高级查询 关键字
        $pData["start_time"] = $this->input->post('start_time', true);  //查询起始时间 默认为0
        $pData["end_time"] = $this->input->post('end_time', true);      //查询截止时间 默认为0
        $pData['is_group'] = $this->input->post('is_group');
        $pData['rank']     = $this->input->post('rank');

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


        $data = $this->watcher_model->get_all_watch_list($pData);
        echo json_encode($data);
    }

}