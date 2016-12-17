<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/12/14
 * Time: 11:41
 * 舆情 数据 控制器
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Yuqing extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
        $this->load->model('Yuqing_Model','yq');
    }

    public function test(){
        $this->load->model('Yuqing_Model','yq');
        $this->yq->test1();
    }


    /**
     * 舆情数据筛选 视图载入
     */
    public function yq_data()
    {
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_data");
        $this->all_display("yq_data/yq_data_list.html");
    }


    /**
     * 分页 获取 舆情数据库 数据
     */
    public function get_yqData_by_page(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $col_name = 'rawdata'; //集合名称
        $yq_data  = $this->yq->get_yqData($query,$page_num,$col_name);
        print_r($yq_data);

    }


    /**
     * 接口：忽略掉此条舆情，不显示给 该用户看
     */
    public function ignore_this_yq(){
        $yid = $this->input->get('yid');
        $res = $this->yq->ignore_this($yid);
        if($res){
            echo json_encode(array(
                'res' => 1
            ));
        }else{
            echo json_encode(array(
                'res' => 0
            ));
        }
    }

    /**
     * 接口： 给舆情打标签----区级、市级、国家级
     */
    public function tag_this_yq(){
        $yid = $this->input->get('yid');
        $tag = $this->input->get('tag');
        $res = $this->yq->tag_yq($yid,$tag);
        if($res){
            echo json_encode(array(
                'res' => 1
            ));
        }else{
            echo json_encode(array(
                'res' => 0
            ));
        }
    }




}