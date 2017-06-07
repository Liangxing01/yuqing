<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2017/6/7
 * Time: 下午1:48
 * 各种统计图表生成控制器
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Chart_ctl extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->identity->is_authentic();
        $this->load->model('Chart_Model','chart');
    }

    /**指派人首页图表生成
     * 1、按月统计指派数量
     * 2、饼状图显示各等级舆情数量
     */
    public function get_event_counts_by_month(){
        $count_by_month = $this->chart->get_event_num_by_month();
        echo json_encode($count_by_month);
    }

    /**
     * 各类舆情统计饼状图
     */
    public function get_event_rank_counts_by_month(){
        $count_by_month = $this->chart->get_event_rank_by_month();
        echo json_encode($count_by_month);
    }


}