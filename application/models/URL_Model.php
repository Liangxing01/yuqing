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

    private $mqtt; //mqtt链接
    private $mqttServer = '27.8.44.5';
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 4601 管控 命令执行
     */
    public function run4601($codes){
        //加载 主题配置文件
        /*$this->config->load('theme_cfg');
        $theme_cfg = $this->config->item('theme');

        //政府管控
        $host = $theme_cfg['4601_ip'];
        $port = 2222;
        $username = "root";
        $password = "axzb@4601";
        $connection = ssh2_connect($host, $port);
        ssh2_auth_password($connection, $username, $password);

        $str = "";
        foreach ($codes as $code){
            $str .= $code . " \n";
        }
        $run_code = substr($str,0,-1);
        //var_dump($run_code);
        ssh2_exec($connection, $run_code);*/


        /*//公司管控
        $com_host = "2001:250:221:9:7::1";
        $com_port = 2222;
        $com_username = "root";
        $com_password = "v6plusv6plus";
        $com_connection = ssh2_connect($com_host, $com_port);
        ssh2_auth_password($com_connection, $com_username, $com_password);

        $str = "";
        foreach ($codes as $code){
            $str .= $code . " \n";
        }
        $run_code = substr($str,0,-1);
        ssh2_exec($com_connection, $run_code);*/

    }

    /**
     * 检查用户是否有ipv6
     */
    private function checkIsIPv6(){
        //检查用户是否有ipv6地址
        $this->load->helper(array("public"));
        $userIP = get_ip();
        $checkIPv6 = validateIPv6($userIP);
        if(!$checkIPv6){
            echo json_encode(
                array(
                    'res' => 0,
                    'msg' => "没有IPV6网络"
                )
            );
            exit();
        }

    }

    /**
     * ------------------------旁路管控mqtt消息发送---------------------------------
     */
    /**
     * 初始化mqtt,并发送mqtt消息
     * @param 主题
     * @msg   消息
     */
    private function sendMqttMsg($topic,$msg){
        $this->mqtt = new Mosquitto\Client();
        $this->mqtt->connect($this->mqttServer,1883,5);
        //查询所有旁路设备
        $deviceList = $this->db->select('gwId')
            ->from('device_ctl_list')
            ->get()->result_array();
        foreach ($deviceList as $device){
            $this->mqtt->publish($device['gwId'].$topic,json_encode($msg),1);
        }
        $this->mqtt->disconnect();
    }

    /**
     * @param $domain
     * @param $ctl_url
     * @param $redi_url
     * 管控url
     */
    public function ctl_url($domain,$ctl_url,$redi_url,$alias){
        $this->checkIsIPv6();
        //旁路管控命令
        $topic = "/bpos/cmd/httpredirect";

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

            //旁路mqtt管控命令发送
            $mqttMsg = [
                "enable"    => -1,
                "action"    => 1,
                "url"       => $ctl_url,
                "rdurl"     => $has_domain['redirect_url'],
                "remote"    => 1
            ];
            $this->sendMqttMsg($topic,$mqttMsg);

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

            //旁路mqtt管控命令发送
            $mqttMsg = [
                "enable"    => -1,
                "action"    => 1,
                "url"       => $ctl_url,
                "rdurl"     => $redi_url,
                "remote"    => 1
            ];
            $this->sendMqttMsg($topic,$mqttMsg);
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
        $this->checkIsIPv6();
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

        //删除旁路管控命令
        $topic = '/bpos/cmd/httpredirect';
        $mqttMsg = array(
            "enable"    => -1,
            "action"    => 0,
            "url"       => $url,
            "rdurl"     => $data['redirect_url'],
            "remote"    => 1
        );
        $this->sendMqttMsg($topic,$mqttMsg);

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
        $dname = $this->db->select('cd.group_name,cu.ctl_url,cd.redirect_url')
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
        $this->checkIsIPv6();

        $ip = "202.202.2.42"; //重大ip
        $ipv6 = "2001:da8:c800:1005::3";
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

            //旁路管控命令
            $topic = "/bpos/cmd/dnshijackbydomain";
            $mqttMsg = array(
                "enable"    => -1,
                "action"    => 1,
                "domain"    => $ctl_domain,
                "ip"        => $ip,
                "family"    => 4,
                "remote"    => 1
            );
            $this->sendMqttMsg($topic,$mqttMsg);

            $mqttMsg = array(
                "enable"    => -1,
                "action"    => 1,
                "domain"    => $ctl_domain,
                "ip"        => $ipv6,
                "family"    => 6,
                "remote"    => 1
            );
            $this->sendMqttMsg($topic,$mqttMsg);
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
        $this->checkIsIPv6();
        $ip = "202.202.2.42"; //重大ip
        $ipv6 = "2001:da8:c800:1005::3";
        //删除dns 规则 和 域名组
        $gname = $this->db->select('cd.group_name,cd.value')
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

        //删除旁路管控命令
        $topic = "/bpos/cmd/dnshijackbydomain";
        $mqttMsg = array(
            "enable"    => -1,
            "action"    => 0,
            "domain"    => $gname['value'],
            "ip"        => $ip,
            "family"    => 4,
            "remote"    => 1
        );
        $this->sendMqttMsg($topic,$mqttMsg);

        $mqttMsg = array(
            "enable"    => -1,
            "action"    => 0,
            "domain"    => $gname['value'],
            "ip"        => $ipv6,
            "family"    => 6,
            "remote"    => 1
        );
        $this->sendMqttMsg($topic,$mqttMsg);

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
        $this->checkIsIPv6();

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

            //旁路管控
            $topic = "/bpos/cmd/tcpblockbyip";
            $mqttMsg = array(
                "enable"    => -1,
                "action"    => 1,
                "ip"        => $ip,
                "prefix"    => 32,
                "family"    => 4,
                "remote"    => 1
            );
            $this->sendMqttMsg($topic,$mqttMsg);
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
        $this->checkIsIPv6();
        $ctl_ip = $this->db->select('value')->from('ctl_domains')->where('id',$rule_id)->get()->row_array();

        $this->db->where('id',$rule_id);
        $res = $this->db->delete('ctl_domains');

        //拼接命令
        $code = array();
        array_push($code,"/usr/ramdisk/app/v6plus/ip_ctl.sh ipctl_rmvrule ".$rule_id);

        $this->run4601($code);

        //旁路管控
        $topic = "/bpos/cmd/tcpblockbyip";
        $mqttMsg = array(
            "enable"    => -1,
            "action"    => 0,
            "ip"        => $ctl_ip,
            "prefix"    => 32,
            "family"    => 4,
            "remote"    => 1
        );
        $this->sendMqttMsg($topic,$mqttMsg);

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


    /**
     * ------------------------------旁路管控-----------------------------------------
     */



}