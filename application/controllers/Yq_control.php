<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/12/15
 * Time: 15:53
 * 舆情 管控 控制器，联动 4601
 */

class Yq_control extends MY_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('URL_Model','url_control');
        $this->identity->is_authentic();
    }



    /**
     * 重定向 url
     * 参数：control_url 被管控url
     *      redirect_url 重定向404 url
     *      domain    主站域名
     */
    public function control_url(){
        $con_url = $this->input->get('control_url');
        $redi_url = $this->input->get('redirect_url');
        $domain  = $this->input->get('domain');



        //管控该url
        $res = $this->url_control->ctl_url($domain,$con_url,$redi_url);
        var_dump($res);

    }

    /**
     * 接口：删除规则
     * 参数： rule_id 规则id
     */
    public function del_rule(){
        $rid = $this->input->get('rule_id');
        $res = $this->url_control->del_url($rid);
        var_dump($res);
    }


    /**
     * 展示 已经 管控的链接
     */
    public function show_all(){
        $pData['sEcho'] = $this->input->post('psEcho', true);           //DataTables 用来生成的信息
        $pData['start'] = $this->input->post('iDisplayStart', true);    //显示的起始索引
        $pData['length'] = $this->input->post('iDisplayLength', true);  //每页显示的行数
        $pData['sort_th'] = $this->input->post('iSortCol_0', true);     //排序的列 默认第三列
        $pData['sort_type'] = $this->input->post('sSortDir_0', true);   //排序的方向 默认 desc
        $pData['search'] = $this->input->post('sSearch', true);         //全局搜索关键字 默认为空

        $pData['rank'] = $this->input->post('rank');

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

        $data = $this->url_control->show_all_list($pData);
        echo json_encode($data);
    }

    public function test(){
        $set = "http://werewr.erwer/weraw.er.aewr.awe.re";
        $re = "/^((http|ftp|https):\/\/)?(.*)/";
        preg_match($re, $set,$res);
        var_dump($res[3]);
    }




}