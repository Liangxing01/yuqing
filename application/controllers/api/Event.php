<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 移动端 事件操作接口
 */
class Event extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
    }


    /**
     * 事件查询 分页数据
     * POST参数:pageNum, size
     * @return string Json
     */
    public function search_event()
    {
        //分页参数 pageNum size
        //查询参数暂略
        //默认按 时间 DESC 排序
    }

}