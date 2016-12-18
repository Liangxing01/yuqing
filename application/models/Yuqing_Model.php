<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/12/14
 * Time: 11:31
 * 链接 同步舆情的 mongodb数据库
 */

class Yuqing_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(array("Mongo_db" => "mongo"));
    }

    public function test1(){
        $a = $this->mongo->limit(10)->get('rawdata');
        var_dump($a);
    }

    /**
     * ------------------------------上报人 功能------------------------------
     */

    /**
     * 分页获取 原始舆情数据
     * 未上报舆情
     * 参数 $query :search 标题关键字检索，length 每页长度,$page_num 页码
     */
    public function get_raw_yqData($query,$page_num,$col_name){
        $uid = $this->session->userdata('uid');
        $block_list = array();
        array_push($block_list,$uid);

        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        if(empty($query['search'])){
            $yq_data = $this->mongo->select(array(),array('content'))//排除 content字段
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get($col_name);
        }else{
            $yq_data = $this->mongo->select(array(),array('content'))
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get($col_name);
        }
        return $yq_data;
    }

    /**
     * @param $yid 舆情 mongoID
     * 向数据库 该条 舆情 增加Block_list 字段，内容为当前用户uid
     */
    public function ignore_this($yid){
        $uid = $this->session->userdata('uid');
        $res = $this->mongo->where(array('_id' => new MongoId($yid)))->addtoset('yq_block_list',$uid)->update('rawdata');
        return $res;
    }

    /**
     * @param $yid
     * 取消忽略该舆情
     */
    public function unset_ignore_this($yid){
        $uid = $this->session->userdata('uid');
        $res = $this->mongo->where(array('_id' => new MongoId($yid)))
            ->pop(array('yq_block_list',$uid))
            ->update('rawdata');
        return $res;
    }

    /**
     * @param $yid
     * @param $tag
     * 给舆情 打标签，文档存储 uid=>$uid,tag=>区级
     */
    public function tag_yq($yid,$tag){
        $uid = $this->session->userdata('uid');
        //插入 rep_info 集合
        $insert_info = array(
            'yq_id' => new MongoId($yid),
            'tag'   => $tag,
            'uid'   => $uid,
            'time'  => time()
        );
        $tag_id = $this->mongo->insert('rep_info',$insert_info);

        //插入 rawdata 集合 的tag_list
        $res = $this->mongo->where(array('_id' => new MongoId($yid)))->push('yq_tag_list',$tag_id)->update('rawdata');

        return $res;
    }

    /**
     * @param $yid
     * @param $tag
     * 上报舆情
     */
    public function rep_yq($yid,$tag){
        $uid = $this->session->userdata('uid');
        //插入uid 到该舆情的 reporter_list
        $res1 = $this->mongo->where(array('_id' => new MongoId($yid)))->push('yq_reporter_list',$uid)->update('rawdata');

        //给舆情 打标签
        $res2 = $this->tag_yq($yid,$tag);

        return $res1 && $res2;
    }

    /**
     * @param $yid
     * 获取舆情详细信息
     */
    public function get_yq_detail($yid){
        $res = $this->mongo->select(array(),array('sid','pubdate','_version','reloadtag','timestamp'))
            ->where(array('_id' => new MongoId($yid)))
            ->get('rawdata');
        return $res[0];
    }

    /**
     * -------------------------上报人 已上报舆情--------------------------
     */










}