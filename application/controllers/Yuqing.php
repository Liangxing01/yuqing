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
     * 舆情详情页面 载入
     */
    public function yq_detail(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_data");
        $this->all_display("yq_data/yq_data_detail.html");
    }

    /**
     * 已上报舆情 页面 载入
     */
    public function yq_report_db(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_data");
        $this->all_display("yq_data/yq_report_db.html");
    }


    /**
     * 分页 获取 原始舆情数据库 数据
     */
    public function get_yqData_by_page(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $col_name = 'rawdata'; //集合名称
        $yq_data  = $this->yq->get_raw_yqData($query,$page_num,$col_name);
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
     * 接口： 取消忽略该舆情
     */
    public function unset_ignore(){
        $yid = $this->input->get('yid');
        $res = $this->yq->unset_ignore_this($yid);
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
     * 接口： 上报这条舆情
     * 参数： yid 舆情id
     *       tag 标签 ---区级 市级 国家级
     *
     */
    public function rep_this_yq(){
        $yid = $this->input->get('yid');
        $tag = $this->input->get('tag');
        $res = $this->yq->rep_yq($yid,$tag);
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
     * 接口 ： 查看舆情详情
     * get 参数 舆情id
     */
    public function show_yq_detail(){
        $yid = $this->input->get('yid');
        $res = $this->yq->get_yq_detail($yid);
        echo json_encode($res);
    }

    /**
     * -------------------------上报人 已上报舆情 -------------------------
     */

    /**
     * 接口：已上报舆情 分页
     */
    public function has_rep_yq(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $yq_data  = $this->yq->get_has_rep_yqData($query,$page_num);
        echo json_encode($yq_data);
    }









}