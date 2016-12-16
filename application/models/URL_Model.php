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
    public function ctl_url($domain,$ctl_url,$redi_url,$alias){
        $re = "/^((http|ftp|https):\/\/)?(.*)/";
        preg_match($re, $ctl_url,$res);
        $ctl_url = $res[3];

        //开始运行事务
        $this->db->trans_begin();

        $has_domain = $this->check_has_domain($domain."_http",'http');
        $ctl_code = array();

        //插入管控记录
        if($has_domain){
            $this->insert_ctl_url($has_domain['id'],$ctl_url);
            $rule_id = $this->db->insert_id();
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_add ".$has_domain['group_name']." ".$ctl_url);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh urlfilter_addrule ".$rule_id." ".$has_domain['group_name']." ".$redi_url);
        }else{
            //先 插入 新的域名 获取域名id
            $new_data = array(
                'value'        =>$domain, //http管制
                'group_name'   =>$domain."_http", //群组名
                'redirect_url' => $redi_url,
                'type'         => "http",
                'alias'        => $alias,
                'time'         => time()
            );
            $d_id = $this->insert_info('ctl_domains',$new_data);
            $this->insert_ctl_url($d_id,$ctl_url);
            $rule_id = $this->db->insert_id();

            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_addgrp ".$domain."_http");
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_add ".$domain."_http"." ".$ctl_url);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh urlfilter_addrule ".$d_id." ".$domain." ".$redi_url);
        }

        //执行命令
        $this->run4601($ctl_code);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array(
                'res' => 0,
                'msg' => '管控失败'
            );
        }else{
            $this->db->trans_commit();
            return array(
                'res' => 1,
                'msg' => '管控成功'
            );
        }

    }

    /**
     * 查询 主域名 是否已经存在 于 域名组
     */
    public function check_has_domain($domain,$type){
        $is_exist = $this->db->select('id,redirect_url,value,group_name')
            ->from('ctl_domains')
            ->where('group_name',$domain)
            ->where('type',$type)
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
        $dname = $data['group_name'];

        $this->db->where('id',$rid);
        $res = $this->db->delete('ctl_url');

        //拼接命令
        $code = array();
        array_push($code,"/usr/ramdisk/app/v6plus/http_ctl.sh urlfilter_rmvrule ".$rid);
        array_push($code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_remove_fun ".$dname." ".$url);

        $this->run4601($code);
        if($res){
            return array(
                'res' => 1,
                'msg' => '解除管控成功'
            );
        }else{
            return array(
                'res' => 0,
                'msg' => '解除管控失败'
            );
        }
    }

    /**
     * @param $rid
     * 获取 域名组名
     */
    public function get_domain_name($rid){
        $dname = $this->db->select('cd.group_name,cu.ctl_url')
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
        $data['aaData'] = $this->db->select("cu.id,cu.ctl_url,cd.value,cd.redirect_url,from_unixtime(cu.time) as time,cd.type,cd.alias")
            ->from('ctl_url as cu')
            ->join('ctl_domains as cd','cu.domain_id = cd.id')
            ->where('cd.type','http')
            ->order_by('cu.time',$pInfo['sort_type'])
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total =  $this->db->select("cu.id,cu.ctl_url,cd.value,cd.redirect_url,from_unixtime(cu.time)")
            ->from('ctl_url as cu')
            ->join('ctl_domains as cd','cu.domain_id = cd.id')
            ->where('cd.type','http')
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }

    /**
     * --------------------------------DNS管控--------------------------------
     */
    /**
     * dns管控，默认 定向到 重大
     */
    public function ctl_dns($ctl_domain,$alias){
        $ip = "202.202.2.42"; //重大ip
        $ctl_code = array();
        $gname = $ctl_domain."_dns";
        //检查有该 域名组没有
        $is_exist = $this->check_has_domain($gname,'dns');
        if($is_exist){
            return array(
                'res' => 0,
                'msg' => '该域名已DNS管控'
            );
        }else{
            $info = array(
                'value' => $ctl_domain,
                'group_name' => $gname,
                'type'  => "dns",
                'time'  => time(),
                'alias' => $alias
            );
            $this->db->insert('ctl_domains',$info);
            $dns_id = $this->db->insert_id();

            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_addgrp ".$gname);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_add ".$gname . " ".$ctl_domain);
            array_push($ctl_code,"/usr/ramdisk/app/v6plus/dns_ctl.sh dnsctl_addrule ".$dns_id." ".$gname. " ".$ip);
            $this->run4601($ctl_code);
        }

        if($dns_id){
            return array(
                'res' => 1,
                'msg' => 'DNS管控成功'
            );
        }else{
            return array(
                'res' => 0,
                'msg' => 'DNS管控失败'
            );
        }
    }

    /**
     * DNS 删除
     */
    public function del_dns($rule_id){
        //删除dns 规则 和 域名组
        $gname = $this->db->select('cd.group_name')
            ->from('ctl_domains as cd')
            ->where('cd.type','dns')
            ->where('cd.id',$rule_id)
            ->get()->row_array();

        //拼接命令
        $code = array();
        array_push($code,"/usr/ramdisk/app/v6plus/dns_ctl.sh dnsctl_rmvrule ".$rule_id);
        array_push($code,"/usr/ramdisk/app/v6plus/http_ctl.sh dns_rmvgrp ".$gname['group_name']);

        $this->run4601($code);

        $this->db->where('id',$rule_id);
        $res = $this->db->delete('ctl_domains');
        if($res){
            return array(
                'res' => 1,
                'msg' => '解除管控成功'
            );
        }else{
            return array(
                'res' => 0,
                'msg' => '解除管控失败'
            );
        }
    }

    /**
     * 分页 获取 dns 管控
     */
    public function show_ctl_dns($pInfo){
        $data['aaData'] = $this->db->select("cd.id,cd.value,from_unixtime(cd.time) as time,cd.type,cd.alias")
            ->from('ctl_domains as cd')
            ->where('cd.type','dns')
            ->order_by('cd.time',$pInfo['sort_type'])
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total =  $this->db->select("cd.id,cd.value,from_unixtime(cd.time) as time,cd.type,cd.alias")
            ->from('ctl_domains as cd')
            ->where('cd.type','dns')
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }

    /**
     * --------------------IP管控-------------------------
     */
    public function ctl_ip($ip,$alias){
        $ctl_code = array();
        $gname = $ip."_ip";
        //检查有该 域名组没有
        $is_exist = $this->check_has_domain($gname,'ip');
        if($is_exist){
            return array(
                'res' => 0,
                'msg' => '该IP已经被管控'
            );
        }else{
            $info = array(
                'value' => $ip,
                'group_name' => $gname,
                'type'  => "ip",
                'time'  => time(),
                'alias' => $alias
            );
            $this->db->insert('ctl_domains',$info);
            $ip_id = $this->db->insert_id();

            array_push($ctl_code,"/usr/ramdisk/app/v6plus/ip_ctl.sh ipctl_addrule ".$ip_id." ".$ip);
            $this->run4601($ctl_code);
        }

        if($ip_id){
            return array(
                'res' => 1,
                'msg' => 'IP管控成功'
            );
        }else{
            return array(
                'res' => 0,
                'msg' => 'IP管控失败'
            );
        }
    }


    /**
     * 删除 IP 管控
     */
    public function del_ip($rule_id){
        $this->db->where('id',$rule_id);
        $res = $this->db->delete('ctl_domains');

        //拼接命令
        $code = array();
        array_push($code,"/usr/ramdisk/app/v6plus/ip_ctl.sh ipctl_rmvrule ".$rule_id);

        $this->run4601($code);
        if($res){
            return array(
                'res' => 1,
                'msg' => '解除管控成功'
            );
        }else{
            return array(
                'res' => 0,
                'msg' => '解除管控失败'
            );
        }
    }

    /**
     * 分页 IP 管控
     */
    public function show_ip_list($pInfo){
        $data['aaData'] = $this->db->select("cd.id,cd.value,from_unixtime(cd.time) as time,cd.type,cd.alias")
            ->from('ctl_domains as cd')
            ->where('cd.type','ip')
            ->order_by('cd.time',$pInfo['sort_type'])
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total =  $this->db->select("cd.id,cd.value,from_unixtime(cd.time) as time,cd.type,cd.alias")
            ->from('ctl_domains as cd')
            ->where('cd.type','ip')
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }



}