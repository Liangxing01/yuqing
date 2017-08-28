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
     * 舆情管控 链接管控 视图载入
     */
    public function http_control(){
        $this->assign("active_title", "http_control");
        $this->assign("active_parent", "control_parent");
        $this->all_display("yq_control/http_control.html");
    }

    /**
     * 舆情管控 链接管控 视图载入
     */
    public function domain_control(){
        $this->assign("active_title", "domain_control");
        $this->assign("active_parent", "control_parent");
        $this->all_display("yq_control/domain_control.html");
    }

    /**
     * 舆情管控 链接管控 视图载入
     */
    public function ip_control(){
        $this->assign("active_title", "ip_control");
        $this->assign("active_parent", "control_parent");
        $this->all_display("yq_control/ip_control.html");
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
        $alias  =  $this->input->get('alias');

        //管控该url
        $res = $this->url_control->ctl_url($domain,$con_url,$redi_url,$alias);
        if($res){
            echo json_encode($res);
        }else{
            echo json_encode($res);
        }
    }

    /**
     * 接口：删除规则
     * 参数： rule_id 规则id
     */
    public function del_rule(){
        $rid = $this->input->get('rule_id');
        $res = $this->url_control->del_url($rid);
        if($res){
            echo json_encode($res);
        }else{
            echo json_encode($res);
        }
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

    /**
     * 接口：DNS 管控
     */
    public function dns_ctl(){
        $ctl_domain = $this->input->get('ctl_domain');
        $alias = $this->input->get('alias');
        $res = $this->url_control->ctl_dns($ctl_domain,$alias);
        if($res){
            echo json_encode($res);
        }else{
            echo json_encode($res);
        }
    }

    /**
     * 删除 DNS
     */
    public function del_dns(){
        $rid = $this->input->get('rule_id');
        $res = $this->url_control->del_dns($rid);
        if($res){
            echo json_encode($res);
        }else{
            echo json_encode($res);
        }
    }

    public function show_dns_list(){
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

        $data = $this->url_control->show_ctl_dns($pData);
        echo json_encode($data);
    }

    /**
     * IP 管控
     */
    public function ctl_ip(){
        $ctl_ip = $this->input->get('ctl_ip');
        $alias = $this->input->get('alias');
        if(!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ctl_ip)) {
            echo json_encode(array(
                'res' => 0,
                'msg' => "IP格式不正确"
            ));
        }else{
            $res = $this->url_control->ctl_ip($ctl_ip,$alias);
            if($res){
                echo json_encode($res);
            }else{
                echo json_encode($res);
            }
        }

    }

    /**
     * 删除 IP
     */
    public function del_ip(){
        $rid = $this->input->get('rule_id');
        $res = $this->url_control->del_ip($rid);
        if($res){
            echo json_encode($res);
        }else{
            echo json_encode($res);
        }
    }

    /**
     * 展示 ip 列表
     */
    public function show_ip_list(){
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

        $data = $this->url_control->show_ip_list($pData);
        echo json_encode($data);
    }




}