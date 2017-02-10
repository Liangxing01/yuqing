<?php

/**
 * 移动端 事件信息 接口
 */
class Info extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->identity->m_is_authentic();
    }


    /**
     * 上报接口
     */
    public function report_info()
    {
        echo $this->session->uid;
    }


    /**
     * 提交记录 分页数据接口
     * POST参数:pageNum, size
     * @return string Json
     */
    public function get_info_record()
    {
        //分页参数 pageNum size
        //默认按 时间 DESC 排序
    }


    /**
     * 信息详情
     * GET参数:信息ID
     * @return string Json
     */
    public function get_info_detail()
    {

    }


    /**
     * 信息检索 分页数据接口
     * POST参数:pageNum, size
     * @return string Json
     */
    public function search_info()
    {
        //分页参数 pageNum size
        //查询参数暂略
        //默认按 时间 DESC 排序
    }

}