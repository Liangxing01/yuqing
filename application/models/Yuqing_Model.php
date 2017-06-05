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
        //拼接查询条件
        $query_where = array();
        if($query['yq_tag'] != '全部'){
            $query_where['yq_tag'] = $query['yq_tag'];
        }
        if(!empty($query['search'])){
            $query_where['$or'] = array(
                array('title'   => array('$regex' => $query['search'])),
                array('content' => array('$regex' => $query['search']))
            );
        }
        if($query['media_type'] != '全部'){
            $query_where['yq_media_type'] = $query['media_type'];
        }

        $yq_data['info'] = $this->mongo->select(array(),array('content','yq_block_list','yq_trash_list','yq_reporter_list',
            'yq_rep_num'))//排除 content字段
            ->where($query_where)
            ->where('yq_tag_lock',0)                    //未被确认的舆情
            ->where_lte('yq_rep_num',10)                //上报人小于10个
            ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的无关舆情
            ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
            ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
            ->where_lte('yq_trash_num',5)                     //垃圾标记数 不超过5个的显示
            ->limit($query['length'])
            ->offset($offset)
            ->order_by(array('yq_pubdate' => $query['sort']))
            ->get($col_name);

        $yq_data['num'] = $this->mongo->select(array('_id'),array('content'))//排除 content字段
            ->where($query_where)
            ->where('yq_tag_lock',0)                    //未被确认的舆情
            ->where_lte('yq_rep_num',10)                //上报人小于10个
            ->where_not_in('yq_block_list',$block_list) // 排除 自己已经 忽略的舆情
            ->where_not_in('yq_trash_list',$block_list) // 排除 自己认为 垃圾的舆情
            ->where_not_in('yq_reporter_list',$block_list) // 不显示已经上报的舆情
            ->where_lte('yq_trash_num',5)                     //垃圾标记数 不超过5个的显示
            ->count('rawdata');

        return $yq_data;
    }

    /**
     * @param $yid 舆情 mongoID
     * 向数据库 该条 舆情 增加Block_list 字段，内容为当前用户uid
     */
    public function ignore_this($yid,$type){
        $uid = $this->session->userdata('uid');
        $yid_arr = explode(',',$yid);
        $u_arr = array($uid);
        $yid_mongo = array();
        //转换 yid 成 MongoId类型
        foreach ($yid_arr as $yid){
            array_push($yid_mongo,new MongoId($yid));
        }
        if($type == 'trash'){
            //垃圾信息计数+1，同时添加到trash_list
            foreach ($yid_mongo as $one){
                $this->mongo->where('_id',$one)->inc(array('yq_trash_num' => 1))->update('rawdata');
                $this->mongo->where('_id',$one)->addtoset('yq_trash_list',$u_arr)->update('rawdata');
            }
            $res = true;

        }else if($type == 'useless'){
            foreach ($yid_mongo as $one){
                $this->mongo->where('_id',$one)->addtoset('yq_block_list',$u_arr)->update('rawdata');
            }
            $res = true;
        }else{
            $res = false;
        }
        return $res;
    }

    /**
     * @param $yid
     * 取消忽略该舆情
     */
    public function unset_ignore_this($yid,$type){
        $uid = $this->session->userdata('uid');
        $yid_arr = explode(',',$yid);
        //$u_arr = array($uid);
        $yid_mongo = array();
        //转换 yid 成 MongoId类型
        foreach ($yid_arr as $yid){
            array_push($yid_mongo,new MongoId($yid));
        }
        if($type == 'trash'){
            //垃圾计数 -1
            foreach ($yid_mongo as $one){
                $this->mongo->where(array('_id' => $one))->inc(array('trash_num' => -1))->update('rawdata');
                $this->mongo->where(array('_id' => $one))
                    ->pull('yq_trash_list', $uid)
                    ->update('rawdata');
            }
            $res = true;


        }else if($type == 'useless'){
            foreach ($yid_mongo as $one){
                $this->mongo->where(array('_id' => $one))
                    ->pull('yq_block_list',$uid)
                    ->update('rawdata');
            }
            $res = true;

        }else{
            $res = false;
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
        $yq_info = $this->mongo->select(array('title','yq_media_type','content'))
            ->where(array('_id' => new MongoId($yid)))
            ->find_one('rawdata');
        //插入 rep_info 集合
        $insert_info = array(
            'yq_id' => new MongoId($yid),
            'tag'   => $tag,
            'uid'   => $uid,
            'time'  => time(),
            'title' => $yq_info['title'],
            'media_type' => $yq_info['yq_media_type'],
            'content'    => $yq_info['content'],
            'is_cfm'=> 0//是否被指派人确认过 0 未查看 1 被采纳 -1 未被采纳
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

        //舆情上报计数 +1
        $res3 = $this->mongo->where(array('_id' => new MongoId($yid)))->inc(array('yq_rep_num' => 1))->update('rawdata');

        return $res1 && $res2 && $res3;
    }

    /**
     * @param $yid
     * 获取舆情详细信息
     */
    public function get_yq_detail($yid){
        $res = $this->mongo->select(array(),array('sid','pubdate','_version','reloadtag','timestamp','yq_block_list',
            'yq_trash_list','yq_reporter_list', 'yq_rep_num'))
            ->where(array('_id' => new MongoId($yid)))
            ->find_one('rawdata');
        return $res;
    }

    /**
     * @param $yid
     * 已确认舆情详情
     */
    public function get_cfm_detail($yid){
        $res = $this->mongo->select(array(),array('sid','pubdate','_version','reloadtag','timestamp','yq_block_list',
            'yq_trash_list','yq_reporter_list', 'yq_rep_num'))
            ->where(array('_id' => new MongoId($yid)))
            ->find_one('useful_yq');
        return $res;
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

        //拼接查询条件
        $query_where = array();
        if(!empty($query['search'])){
            $query_where['$or'] = array(
                array('title'   => array('$regex' => $query['search'])),
                array('content' => array('$regex' => $query['search']))
            );
        }
        if($query['media_type'] != '全部'){
            $query_where['media_type'] = $query['media_type'];
        }

        //先检索 rep_info 集合，找出符合条件的 id
        $rep_list = $this->mongo->select(array('yq_id','is_cfm'))
            ->where($query_where)
            ->where('uid',$uid)
            ->where('tag',$query['tag'])
            ->limit($query['length'])
            ->offset($offset)
            ->order_by(array('time' => $query['sort']))
            ->get('rep_info');

        $yq_data['num'] = $this->mongo->select(array('_id'))
            ->where($query_where)
            ->where('tag',$query['tag'])
            ->where(array('uid' => $uid))
            ->count('rep_info');


        //循环rep_list 获取每条舆情的信息
        $yq_data = array(
            'info' => array(),
            'num'  => $yq_data['num']
        );
        foreach ($rep_list as $item){
            $info = $this->mongo->select(array(),array('content'))
                ->where(array('_id' => $item['yq_id']))
                ->find_one('rawdata');
            $info['is_cfm'] = $item['is_cfm'];
            array_push($yq_data['info'],$info);
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

        //拼接查询条件
        $query_where = array();
        if(!empty($query['search'])){
            $query_where['$or'] = array(
                array('title'   => array('$regex' => $query['search'])),
                array('content' => array('$regex' => $query['search']))
            );
        }
        if($query['media_type'] != '全部'){
            $query_where['yq_media_type'] = $query['media_type'];
        }

        $yq_data['info'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->where_in('yq_block_list',$u_list)
            ->limit($query['length'])
            ->offset($offset)
            ->order_by(array('yq_pubdate' => $query['sort']))
            ->get('rawdata');

        $yq_data['num'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->where_in('yq_block_list',$u_list)
            ->count('rawdata');


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

        //拼接查询条件
        $query_where = array();
        if(!empty($query['search'])){
            $query_where['$or'] = array(
                array('title'   => array('$regex' => $query['search'])),
                array('content' => array('$regex' => $query['search']))
            );
        }
        $yq_data['info'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->where_in('yq_trash_list',$u_list)
            ->limit($query['length'])
            ->offset($offset)
            ->order_by(array('yq_pubdate' => $query['sort']))
            ->get('rawdata');

        $yq_data['num'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->where_in('yq_trash_list',$u_list)
            ->count('rawdata');

        return $yq_data;
    }


    /**
     * -----------------------------指派人 功能 ----------------------------------
     */
    /**
     * @param $query
     * @param $page_num
     * @return array
     * 分页查看 舆情员 已经分类的舆情
     */
    public function get_rep_yqData($query,$page_num){
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量
        if($query['sort'] == 'DESC'){
            $sort = 1;
        }else{
            $sort = -1;
        }

        //先查询 rep_info 集合，分类找出已经上报的 舆情id
        if($query['media_type'] == '全部'){
            $rep_yq_list = $this->mongo->aggregate('rep_info',array(
                array('$sort' => array('time' => $sort)),
                array('$limit' => (int)$query['length']),
                array('$skip' => $offset),
                array('$project' => array('_id'=>1,'yq_id'=>1,'content'=>1,'uid'=>1,'tag'=>1,'time'=>1,'is_cfm'=>1,'title'=>1)),
                array('$match' => array(
                    'tag'           => $query['tag'],
                    'is_cfm'        => 0,
                    '$or'           => array(
                        array('title'   => array('$regex' => $query['search'])),
                        array('content' => array('$regex' => $query['search']))
                    )

                )),
                array('$group' => array(
                    '_id' => array('yq_id' => '$yq_id'),  //舆情id
                    'id'  => array('$first' => '$_id'),   //上报id
                    'uid' => array('$first' => '$uid'),   //第一个上报人的 uid
                    'tag' => array('$first' => '$tag'),
                    'rep_time' => array('$max' => '$time')
                ))
            ));
            // 结果总数
            $num = $this->mongo->aggregate('rep_info', array(
                array('$match' => array(
                    'tag'           => $query['tag'],
                    'is_cfm'        => 0,
                    '$or'           => array(
                        array('title'   => array('$regex' => $query['search'])),
                        array('content' => array('$regex' => $query['search']))
                    )
                )),
                array('$group' => array(
                    '_id' => array('yq_id' => '$yq_id')
                ))
            ));
        }else{
            $rep_yq_list = $this->mongo->aggregate('rep_info',array(
                array('$sort' => array('time' => $sort)),
                array('$limit' => (int)$query['length']),
                array('$skip' => $offset),
                array('$project' => array('_id'=>1,'yq_id'=>1,'content'=>1,'uid'=>1,'tag'=>1,'time'=>1,'is_cfm'=>1,'title'=>1)),
                array('$match' => array(
                    'tag'           => $query['tag'],
                    'is_cfm'        => 0,
                    'media_type'    => $query['media_type'],
                    '$or'           => array(
                        array('title'   => array('$regex' => $query['search'])),
                        array('content' => array('$regex' => $query['search']))
                    )
                )),
                array('$group' => array(
                    '_id' => array('yq_id' => '$yq_id'),  //舆情id
                    'id'  => array('$first' => '$_id'),   //上报id
                    'uid' => array('$first' => '$uid'),   //第一个上报人的 uid
                    'tag' => array('$first' => '$tag'),
                    'rep_time' => array('$max' => '$time')
                ))
            ));
            // 结果总数
            $num = $this->mongo->aggregate('rep_info', array(
                array('$match' => array(
                    'tag'           => $query['tag'],
                    'is_cfm'        => 0,
                    'media_type'    => $query['media_type'],
                    '$or'           => array(
                        array('title'   => array('$regex' => $query['search'])),
                        array('content' => array('$regex' => $query['search']))
                    )
                )),
                array('$group' => array(
                    '_id' => array('yq_id' => '$yq_id')
                ))
            ));
        }

        $rep_list = array();
        foreach ($rep_yq_list['result'] as $rep){
            array_push($rep_list,array(
                'yq_id'     => $rep['_id']['yq_id'],
                'tag'       => $rep['tag'],
                'rep_time'  => $rep['rep_time'],
                'id'        => $rep['id'],
                'first_uid' => $rep['uid']
            ));
        }
        $res_arr = array(
            'info' => array()
        );
        //遍历 上报数组，查询rawdata 集合，显示 每条舆情的信息
        foreach ($rep_list as $item){
            $yid = $item['yq_id'];
            //查询 舆情 信息
            $yq_info = $this->mongo->select(array(),array('content','yq_tag_list','yq_block_list'))
                ->where('_id',$yid)
                ->find_one('rawdata');

            //查询第一个人的名字 mysql查询
            $first_name = $this->db->select('name')
                ->from('user')
                ->where('id',$item['first_uid'])
                ->get()->row_array();
            //增加一些字段信息
            $yq_info['first_name'] = $first_name['name'];
            $yq_info['tag']        = $item['tag'];
            $yq_info['rep_time']   = $item['rep_time'];
            $yq_info['first_uid']  = $item['first_uid'];
            $yq_info['rep_id']     = $item['id'];      //rep_info 信息的id

            array_push($res_arr['info'],$yq_info);
        }
        $res_arr['num'] = count($num['result']);

        return $res_arr;
    }


    /**
     * @param $yid
     * @param $tag
     * 指派人 确认  这个舆情的分类
     */
    public function cfm_yq($yid,$tag,$rep_id){
        //遍历 rep_info 把is_cfm 设置为1
        if($tag == '无效信息'){
            $res1 = $this->mongo->where(array('yq_id' => new MongoId($yid)))->set('is_cfm',-1)->update('rep_info');
        }else{
            $res1 = $this->mongo->where(array('yq_id' => new MongoId($yid)))->set('is_cfm',1)->update('rep_info');
        }

        //设置 rawdata 集合中 tag_lock 为1
        $res2 = $this->mongo->where(array('_id' => new MongoId($yid)))->set('yq_tag_lock',1)->update('rawdata');

        //查询出 raw_db 数据信息 导入 到 有用信息 集合中 yq_useful_db
        $raw_yq_info = $this->mongo->select(array('_id','title','url','summary','content','nrtags','keyword','yq_media_type',
            'yq_author','yq_relevance','yq_sentiment','yq_pubdate','yq_tag','yq_reporter_list','yq_block_list','source'),array())
            ->where(array('_id' => new MongoId($yid)))
            ->find_one('rawdata');

        $raw_yq_info['raw_yq_id'] = $raw_yq_info['_id'];
        unset($raw_yq_info['_id']);
        //新增一些 字段
        $rep_info = $this->mongo->select(array(),array())
            ->where(array('_id' => new MongoId($rep_id)))
            ->find_one('rep_info');

        $raw_yq_info['first_rep_stamp'] = $rep_info['time'];
        $raw_yq_info['final_tag']       = $tag;
        $raw_yq_info['first_rep_uid']   = $rep_info['uid'];
        $raw_yq_info['time']            = time();
        //var_dump($raw_yq_info);

        //插入数据到 有用信息集合
        $res3 = $this->mongo->insert('useful_yq',$raw_yq_info);

        //如果 tag 标记为 区级，则导入数据到  mysql表中
        if($tag == "全区"){
            $insert_info = array(
                'title'         => $raw_yq_info['title'],
                'type'          => 100, // 来自舆情平台
                'url'           => $raw_yq_info['url'],
                'source'        => $raw_yq_info['source'],
                'description'   => $raw_yq_info['summary'],
                'publisher'     => $rep_info['uid'],
                'time'          => $rep_info['time'],
                'state'         => 0,
                'duplicate'     => 0
            );
            $this->db->insert('info',$insert_info);
        }
        if($res1 && $res2 && $res3){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $rid
     * 根据rep_id获取 上报信息,获取 用户姓名
     */
    public function get_rep_by_id($rid){
        $res = $this->mongo->select(array())
            ->where('_id',new MongoId($rid))
            ->find_one('rep_info');
        //根据uid 换成 用户姓名
        $first_name = $this->db->select('name')
            ->from('user')
            ->where('id',$res['uid'])
            ->get()->row_array();
        $res['first_name'] = $first_name['name'];
        return $res;
    }

    /**
     * @param $query
     * @param $page_num
     * 分页 查看 已经确认的信息
     */
    public function get_cfm_yqData($query,$page_num){
        $offset = ((int)$page_num - 1) * $query['length'];  //数据 偏移量

        //拼接查询条件
        $query_where = array();
        if(!empty($query['search'])){
            $query_where['$or'] = array(
                array('title'   => array('$regex' => $query['search'])),
                array('content' => array('$regex' => $query['search']))
            );
        }
        if($query['media_type'] != '全部'){
            $query_where['yq_media_type'] = $query['media_type'];
        }

        $yq_data['info'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->limit($query['length'])
            ->where('final_tag',$query['tag'])
            ->offset($offset)
            ->order_by(array('time' => $query['sort']))
            ->get('useful_yq');

        $yq_data['num'] = $this->mongo->select(array(),array('content'))
            ->where($query_where)
            ->where('final_tag',$query['tag'])
            ->count('useful_yq');

        return $yq_data;
    }




}