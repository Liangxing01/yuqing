<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/12/15
 * Time: 15:56
 * 4601 url 管控
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class URL_Model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 4601 管控 命令执行
     */
    public function run4601($codes){
        $host = "2001:250:221:9:7::1";
        $port = 2222;
        $username = "root";
        $password = "v6plusv6plus";
        $connection = ssh2_connect($host, $port);
        ssh2_auth_password($connection, $username, $password);

        $str = "";
        foreach ($codes as $code){
            $str .= $code . " \n";
        }
        $run_code = substr($str,0,-1);
        //var_dump($run_code);
        ssh2_exec($connection, $run_code);
    }

    /**
     * @param $domain
     * @param $ctl_url
     * @param $redi_url
     * 管控url
     */
    public function ctl_url($domain,$ctl_url,$redi_url){
        $re = "/^((http|ftp|https):\/\/)?(.*)/";
        preg_match($re, $ctl_url,$res);
        $ctl_url = $res[3];

        //开始运行事务
        $this->db->trans_begin();

        $has_domain = $this->check_has_domain($domain);
        $ctl_code = array();

        //插入管控记录
        if($has_domain){
            $this->insert_ctl_url($has_domain['id'],$ctl_url);
            $rule_id = $this->db->insert_id();
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_add ".$has_domain['domain']." ".$ctl_url);
        }else{
            //先 插入 新的域名 获取域名id
            $new_data = array(
                'domain'       =>$domain,
                'redirect_url' => $redi_url
            );
            $d_id = $this->insert_info('ctl_domains',$new_data);
            $this->insert_ctl_url($d_id,$ctl_url);
            $rule_id = $this->db->insert_id();

            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_addgrp ".$domain);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_add ".$domain." ".$ctl_url);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh urlfilter_addrule ".$d_id." ".$domain." ".$redi_url);
        }

        //执行命令
        $this->run4601($ctl_code);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return $rule_id;
        }

    }

    /**
     * 查询 主域名 是否已经存在 于 域名组
     */
    public function check_has_domain($domain){
        $is_exist = $this->db->select('id,redirect_url,domain')
            ->from('ctl_domains')
            ->where('domain',$domain)
            ->get()->row_array();
        if(empty($is_exist)){
            return false;
        }else{
            return $is_exist;
        }
    }

    /**
     * 插入 该 被管控url 进入 管控表
     */
    public function insert_ctl_url($domain_id,$ctl_url){
        $insert_info = array(
            'domain_id' => $domain_id,
            'ctl_url'   => $ctl_url,
            'time'      => time()
        );
        $this->db->insert('ctl_url',$insert_info);
    }

    /**
     * 插入 信息
     * 参数： 表名，数据
     */
    public function insert_info($tname,$data){
        $this->db->insert($tname,$data);
        return $this->db->insert_id();
    }

    /**
     * @param $rid 规则id
     * 删除规则
     */
    public function del_url($rid){
        //获取域名 组名
        $data= $this->get_domain_name($rid);
        $url   = $data['ctl_url'];
        $dname = $data['domain'];

        $this->db->where('id',$rid);
        $res = $this->db->delete('ctl_url');

        //拼接命令
        $code = array();
        array_push($code,"/usr/ramdisk/app/v6plus/http_ctl.sh urlfilter_rmvrule ".$rid);
        array_push($code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_remove_fun ".$dname." ".$url);

        $this->run4601($code);
        return $res;
    }

    /**
     * @param $rid
     * 获取 域名组名
     */
    public function get_domain_name($rid){
        $dname = $this->db->select('cd.domain,cu.ctl_url')
            ->from('ctl_url as cu')
            ->where('cu.id',$rid)
            ->join('ctl_domains as cd','cd.id = cu.domain_id')
            ->get()->row_array();
        return $dname;
    }


    /**
     * 分页 获取 已管控域名
     */
    public function show_all_list($pInfo){
        $data['aaData'] = $this->db->select("cu.id,cu.ctl_url,cd.domain,cd.redirect_url,from_unixtime(cu.time) as time")
            ->from('ctl_url as cu')
            ->join('ctl_domains as cd','cu.domain_id = cd.id')
            ->order_by('cu.time',$pInfo['sort_type'])
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total =  $this->db->select("cu.id,cu.ctl_url,cd.domain,cd.redirect_url,from_unixtime(cu.time)")
            ->from('ctl_url as cu')
            ->join('ctl_domains as cd','cu.domain_id = cd.id')
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }



}