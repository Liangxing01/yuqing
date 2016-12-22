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
        //$this->identity->is_authentic();
       // $this->load->model('Yuqing_Model','yq');
        header( 'Access-Control-Allow-Origin:*' );
        $this->session->set_userdata('uid',103);
    }

    public function test(){
        //$this->yq->test1();
        $t = array(
            'test' => '1'
        );
        unset($t['test']);
        var_dump($t);
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
     * 舆情  详情页面 载入
     */
    public function yq_detail(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_data");
        $this->all_display("yq_data/yq_data_detail.html");
    }

    /**
     * 上报记录 详情页面 载入
     */
    public function rec_detail(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_record");
        $this->all_display("yq_data/yq_data_detail.html");
    }

    /**
     *  指派人 舆情平台筛选 详情页面
     */
    public function filter_detail(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_has_rep");
        $this->all_display("yq_data/yq_data_detail.html");
    }

    /**
     * 指派人 筛选记录 详情页面
     */
    public function filter_rec_detail(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_cfm_list");
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
     * 已上报舆情 历史记录 页面 载入
     */
    public function yq_record(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_record");
        $this->all_display("yq_data/yq_record.html");
    }

    /**
     * 已上报舆情 历史记录 页面 载入
     */
    public function yq_trash_list(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_record");
        $this->all_display("yq_data/yq_trash_list.html");
    }

    /**
     * 已上报舆情 历史记录 页面 载入
     */
    public function yq_block_list(){
        $this->assign("active_title", "report_parent");
        $this->assign("active_parent", "yq_record");
        $this->all_display("yq_data/yq_block_list.html");
    }


    /**
     * 指派人 查看 上报上来的舆情 分页
     */
    public function show_all_has_rep(){
        $this->assign("active_title", "yq_has_rep");
        $this->assign("active_parent", "designate_parent");
        $this->all_display("yq_data/yq_has_rep.html");
    }

    /**
     * 指派人 查看 上报上来的舆情 分页
     */
     public function show_cfm_list(){
         $this->assign("active_title", "yq_cfm_list");
         $this->assign("active_parent", "designate_parent");
         $this->all_display("yq_data/yq_cfm_list.html");
     }


    /**
     * 分页 获取 原始舆情数据库 数据
     */
    public function get_yqData_by_page(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $col_name = 'rawdata'; //集合名称
        $yq_data  = $this->yq->get_raw_yqData($query,$page_num,$col_name);
        echo json_encode($yq_data);

    }


    /**
     * 接口：忽略掉此条舆情，不显示给 该用户看
     * type 区分是 垃圾信息 还是 无关自己的信息 ('trash' --垃圾信息 ,'useless'--无用)
     */
    public function ignore_this_yq(){
        $yid  = $this->input->post('yids');
        $type = $this->input->post('type');
        $res = $this->yq->ignore_this($yid,$type);
        if($res){
            echo json_encode(array(
                'res' => 1,
                'msg' => '操作成功'
            ));
        }else{
            echo json_encode(array(
                'res' => 0,
                'msg' => '操作失败'
            ));
        }
    }

    /**
     * 接口： 取消忽略该舆情
     */
    public function unset_ignore(){
        $yid = $this->input->post('yids');
        $type = $this->input->post('type');
        $res = $this->yq->unset_ignore_this($yid,$type);
        if($res){
            echo json_encode(array(
                'res' => 1,
                'msg' => '取消标记成功'
            ));
        }else{
            echo json_encode(array(
                'res' => 0,
                'msg' => '取消标记失败'
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
                'res' => 1,
                'msg' => '上报舆情成功'
            ));
        }else{
            echo json_encode(array(
                'res' => 0,
                'msg' => '上报舆情失败'
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
     * 接口： 查看 已确认舆情 详情
     *
     */
    public function show_cfm_detail(){
        $yid = $this->input->get('yid');
        $res = $this->yq->get_cfm_detail($yid);
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

    /**
     * 接口：根据舆情id 获取 上报人 打的标签
     * 参数： 舆情id
     */
    public function get_tag(){
        $yid = $this->input->get('yid');
        $tag = $this->yq->get_tag_by_yid($yid);
        echo json_encode($tag);
    }


    /**
     * ------------------------上报人 无用信息 功能------------------------
     */
    /**
     * 无用信息分页，非垃圾信息
     */
    public function useless_data(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $yq_data  = $this->yq->get_useless_yqData($query,$page_num);
        echo json_encode($yq_data);
    }

    /**
     * 垃圾信息分页
     */
    public function trash_data(){
        $query    = $this->input->post('query');
        $page_num = $this->input->post('page_num');
        $yq_data  = $this->yq->get_trash_yqData($query,$page_num);
        echo json_encode($yq_data);
    }


    /**
     * -------------------指派人 功能 --------------------------------------
     */
    /**
     * 指派人 查看  已经上报的舆情 分页
     * 参数： query[tag] 舆情员分类  区 市 国家级
     *
     */
     public function get_rep_data(){
         $query    = $this->input->post('query');
         $page_num = $this->input->post('page_num');
         $yq_data  = $this->yq->get_rep_yqData($query,$page_num);
         echo json_encode($yq_data);
     }

     /**
      * 指派人 确认 上报分类功能
      */
     public function cfm_yq(){
         $yid = $this->input->get('yid');
         $tag = $this->input->get('tag');
         $rep_id = $this->input->get('rep_id');

         $res = $this->yq->cfm_yq($yid,$tag,$rep_id);
         if($res){
             echo json_encode(array(
                 'res' => 1,
                 'msg' => '确认舆情成功'
             ));
         }else{
             echo json_encode(array(
                 'res' => 0,
                 'msg' => '确认舆情失败'
             ));
         }
     }

    /**
     * 根据 rep_id 获取 上报信息
     *
     */
     public function get_rep_info(){
         $rep_id = $this->input->get('rep_id');
         $res    = $this->yq->get_rep_by_id($rep_id);
         echo json_encode($res);
     }

     /**
      * 分页查看 已经确认过的 舆情
      */
     public function get_cfm_data(){
         $query    = $this->input->post('query');
         $page_num = $this->input->post('page_num');
         $yq_data  = $this->yq->get_cfm_yqData($query,$page_num);
         echo json_encode($yq_data);
     }








}