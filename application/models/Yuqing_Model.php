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
     * 分页获取 舆情数据
     * 参数 $query :search 标题关键字检索，length 每页长度,$page_num 页码
     */
    public function get_yqData($query,$page_num,$col_name){
        $uid = $this->session->userdata('uid');
        $block_list = array();
        array_push($block_list,$uid);

        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        if(empty($query['search'])){
            $yq_data = $this->mongo->select(array(),array('content'))//排除 content字段
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get($col_name);
        }else{
            $yq_data = $this->mongo->select(array(),array('content'))
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
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
     * @param $tag
     * 给舆情 打标签，文档存储 uid=>$uid,tag=>区级
     */
    public function tag_yq($yid,$tag){
        $uid = $this->session->userdata('uid');
        $res = $this->mongo->where(array('_id' => new MongoId($yid)))->set(array(
            'yq_tag_list' => array(
                'uid' => $uid,
                'tag' => $tag
            )
        ))->update('rawdata',array('upsert' => TRUE));
        return $res;
    }


}