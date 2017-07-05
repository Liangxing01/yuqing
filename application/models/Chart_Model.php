<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2017/6/7
 * Time: 下午1:53
 * 图表生成 模型
 */

class Chart_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 按月统计平台事件数
     */
    public function get_event_num_by_month(){
        $data = $this->db->select('count(*) as event_count,date_format(FROM_UNIXTIME(start_time),"%Y-%m")  as time')
            ->from('event')
            ->group_by('time')
            ->get()
            ->result_array();
        return $data;
    }

    /**
     * 按月统计 上报信息数
     */
    public function get_info_num_by_month(){
        $data = $this->db->select('count(*) as info_count,date_format(FROM_UNIXTIME(time),"%Y-%m") as rep_time')
            ->from('info')
            ->group_by('rep_time')
            ->get()
            ->result_array();
        return $data;
    }



    /**
     * 按月统计舆情等级排行
     */
    public function get_event_rank_by_month(){
        $data = $data = $this->db->select('count(*) as count,rank')
            ->from('event')
            ->group_by('rank')
            ->get()
            ->result_array();
        return $data;
    }



}