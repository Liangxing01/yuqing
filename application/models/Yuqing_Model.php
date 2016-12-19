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
        $uid = $this->session->userdata('uid');
        $m = array();
        array_push($m,$uid);
        $a = $this->mongo->select(array())
            ->where_in('yq_block_list',$m)
            ->find_one('rawdata');
        var_dump($a);
    }

    /**
     * ------------------------------上报人 功能------------------------------
     */

    /**
     * 分页获取 原始舆情数据
     * 未上报舆情
     * 参数 $query :search 标题关键字检索，length 每页长度,$page_num 页码
     * 垃圾标记 超过5个的不显示
     */
    public function get_raw_yqData($query,$page_num,$col_name){
        $uid = $this->session->userdata('uid');
        $block_list = array();
        array_push($block_list,$uid);

        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        if(empty($query['search'])){
            $yq_data['info'] = $this->mongo->select(array(),array('content'))//排除 content字段
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的无关舆情
                ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->where_lte('trash_num',5)                     //垃圾标记数 不超过5个的显示
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get($col_name);

            $yq_data['num'] = $this->mongo->select(array(),array('content'))//排除 content字段
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->where_lte('trash_num',5)                     //垃圾标记数 不超过5个的显示
                ->count('rawdata');
        }else{
            $yq_data['info'] = $this->mongo->select(array(),array('content'))
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
                ->where_lte('trash_num',5)                     //垃圾标记数 不超过5个的显示
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get($col_name);

            $yq_data['num'] = $this->mongo->select(array(),array('content'))//排除 content字段
                ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
                ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
                ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
                ->where_lte('trash_num',5)                     //垃圾标记数 不超过5个的显示
                ->count('rawdata');
        }
        return $yq_data;
    }

    /**
     * @param $yid 舆情 mongoID
     * 向数据库 该条 舆情 增加Block_list 字段，内容为当前用户uid
     */
    public function ignore_this($yid,$type){
        $uid = $this->session->userdata('uid');
        if($type == 'trash'){
            //垃圾信息计数+1，同时添加到trash_list
            $this->mongo->where(array('_id' => new MongoId($yid)))->inc(array('trash_num' => 1))->update('rawdata');
            $res = $this->mongo->where(array('_id' => new MongoId($yid)))->addtoset('yq_trash_list',$uid)->update('rawdata');
        }else if($type == 'useless'){
            $res = $this->mongo->where(array('_id' => new MongoId($yid)))->addtoset('yq_block_list',$uid)->update('rawdata');
        }
        return $res;
    }

    /**
     * @param $yid
     * 取消忽略该舆情
     */
    public function unset_ignore_this($yid,$type){
        $uid = $this->session->userdata('uid');
        if($type == 'trash'){
            //垃圾计数 -1
            $this->mongo->where(array('_id' => new MongoId($yid)))->inc(array('trash_num' => -1))->update('rawdata');
            $res = $this->mongo->where(array('_id' => new MongoId($yid)))
                ->pop(array('yq_trash_list',$uid))
                ->update('rawdata');
        }else if($type == 'useless'){
            $res = $this->mongo->where(array('_id' => new MongoId($yid)))
                ->pop(array('yq_block_list',$uid))
                ->update('rawdata');
        }
        return $res;
    }

    /**
     * @param $yid
     * @param $tag
     * 给舆情 打标签，文档存储 uid=>$uid,tag=>区级
     */
    public function tag_yq($yid,$tag){
        $uid = $this->session->userdata('uid');
        //找出舆情标题
        $title = $this->mongo->select(array('title'))
            ->where(array('_id' => new MongoId($yid)))
            ->find_one('rawdata');
        //插入 rep_info 集合
        $insert_info = array(
            'yq_id' => new MongoId($yid),
            'tag'   => $tag,
            'uid'   => $uid,
            'time'  => time(),
            'title' => $title['title']
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
        $res1 = $this->mongo->where(array('_id' => new MongoId($yid)))->push('yq_reporter',$uid)->update('rawdata');

        //给舆情 打标签
        $res2 = $this->tag_yq($yid,$tag);

        //舆情上报计数 +1
        $res3 = $this->mongo->where(array('_id' => new MongoId($yid)))->inc(array('yq_rep_num' => 1))->update('rawdata');

        return $res1 && $res2 && $res3;
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

    /**
     * @param $query
     * @param $page_num
     * 分页获取 已上报舆情
     */
    public function get_has_rep_yqData($query,$page_num){
        $uid = $this->session->userdata('uid');
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量

        if(empty($query['search'])){
            //先检索 rep_info 集合，找出符合条件的 id
            $rep_list = $this->mongo->select(array('yq_id'))
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rep_info');

            //循环rep_list 获取每条舆情的信息
            $yq_data = array();
            foreach ($rep_list as $item){
                $info = $this->mongo->select(array(),array('content'))
                    ->where(array('_id' => $item['yq_id']))
                    ->find_one('rawdata');
                array_push($yq_data['info'],$info);
            }

            $yq_data['num'] = $this->mongo->select(array('_id'))
                ->where(array('uid' => $uid))
                ->count('rep_info');
        }else{
            //先检索 rep_info 集合，找出符合条件的 id
            $rep_list = $this->mongo->select(array('yq_id'))
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rep_info');

            //循环rep_list 获取每条舆情的信息
            $yq_data = array();
            foreach ($rep_list as $item){
                $info = $this->mongo->select(array(),array('content'))
                    ->where(array('_id' => $item['yq_id']))
                    ->find_one('rawdata');
                array_push($yq_data['info'],$info);
            }

            $yq_data['num'] = $this->mongo->select(array('_id'))
                ->where(array('uid' => $uid))
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->count('rep_info');
        }
        return $yq_data;
    }

    /**
     * @param $yid
     * 获取该舆情 已被打的标签
     */
    public function get_tag_by_yid($yid){
        $tag = $this->mongo->select(array('tag'))
            ->where(array('yq_id' => new MongoId($yid)))
            ->find_one('rep_info');
        return array(
            'tag' => $tag['tag']
        );
    }


    /**
     * ------------------------上报人 无用信息------------------------
     */
    /**
     * @param $query
     * @param $page_num
     * 获取 与自己无关信息 的分页数据
     */
    public function get_useless_yqData($query,$page_num){
        $uid = $this->session->userdata('uid');
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        $u_list = array();
        array_push($u_list,$uid);

        if(empty($query['search'])){
            $yq_data['info'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_block_list',$u_list)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rawdata');

            $yq_data['num'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_block_list',$u_list)
                ->count('rawdata');
        }else{
            $yq_data['info'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_block_list',$u_list)
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rawdata');

            $yq_data['num'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_block_list',$u_list)
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->count('rawdata');
        }
        return $yq_data;
    }


    /**
     * @param $query
     * @param $page_num
     * 获取 垃圾广告 的分页数据
     */
    public function get_trash_yqData($query,$page_num){
        $uid = $this->session->userdata('uid');
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        $u_list = array();
        array_push($u_list,$uid);

        if(empty($query['search'])){
            $yq_data['info'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_trash_list',$u_list)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rawdata');

            $yq_data['num'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_trash_list',$u_list)
                ->count('rawdata');
        }else{
            $yq_data['info'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_trash_list',$u_list)
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->limit($query['length'])
                ->offset($offset)
                ->order_by(array($query['sort'] => 'DESC'))
                ->get('rawdata');

            $yq_data['num'] = $this->mongo->select(array(),array('content'))
                ->where_in('yq_trash_list',$u_list)
                ->like('title',$query['search'],'im',TRUE,TRUE)
                ->count('rawdata');
        }
        return $yq_data;
    }


    /**
     * -----------------------------指派人 功能 ----------------------------------
     */
    public function get_rep_yqData($query,$page_num){
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        //先查询 rep_info 集合，分类找出已经上报的 舆情id
        $rep_yq_list = $this->mongo->aggregate('rep_info',array(
                array('$sort' => array('time' => -1)),
                array('$limit' => (int)$query['length']),
                array('$skip' => $offset),
                array('$project' => array('yq_id'=>1,'uid'=>1,'tag'=>1,'time'=>1)),
                array('$match' => array('tag' => $query['tag'])),
                array('$group' => array(
                    '_id' => array('yq_id' => '$yq_id'),
                    'tag' => array('$addToSet' => '$tag'),
                    'rep_time' => array('$addToSet' => '$time')
                ))
            ));
        $rep_list = array();
        foreach ($rep_yq_list['result'] as $rep){
            array_push($rep_list,array(
                'yq_id' => $rep['_id']['yq_id'],
                'tag'   => $rep['tag'][0],
                'rep_time' => $rep['rep_time'][0]
            ));
        }
        var_dump($rep_list);
        //return $rep_list;
    }










}