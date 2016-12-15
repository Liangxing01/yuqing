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
     * 舆情管控 视图载入
     */
    public function index(){
        $this->all_display("yq_control/yuqing_control.html");
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
        echo $res;
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

    public function test(){
        $set = "http://werewr.erwer/weraw.er.aewr.awe.re";
        $re = "/^((http|ftp|https):\/\/)?(.*)/";
        preg_match($re, $set,$res);
        var_dump($res[3]);
    }


}