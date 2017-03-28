<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Analyze_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array("Mongo_db" => "mongo"));
        $this->load->database();
    }


    /**
     * 查询传播分析 任务列表
     * @param $pInfo
     * @return mixed
     */
    public function get_analyze_task_list($pInfo)
    {
        $data['aaData'] = $this->mongo->select(array("title", "state", "info_id", "create_time"))
            ->limit($pInfo["length"])
            ->offset($pInfo["start"])
            ->order_by(array('create_time' => $pInfo["sort_type"]))
            ->get("spread_task");

        //查询总记录条数
        $total = $this->mongo->select(array('_id'))->count('spread_task');

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }


    /**
     * 创建 舆情传播分析任务
     * @param $info_id
     * @return bool
     */
    public function create_task($info_id)
    {
        $info = $this->db->select("id, title, url ")->where('id', $info_id)->get("info")->row_array();
        if (is_null($info)) {
            return false;
        }
        $task_info = array(
            'title' => $info['title'],
            'info_id' => $info['id'],
            'state' => 'ready',
            'result' => '',
            'create_time' => time()
        );
        $task_id = $this->mongo->insert('spread_task', $task_info);
        if ($task_id !== FALSE) {
            // 运行任务
            if ($this->run_spread_task($task_id->{'$id'})) {
                return true;
            } else {
                // 运行失败则删除任务 并返回 false
                $this->mongo->where("_id", $task_id)->delete('spread_task');
                return false;
            }
        }
        return false;
    }


    /**
     * 删除 舆情传播分析任务
     * @param string $task_id
     * @return int
     * 0:成功; 1:失败; 2:任务进行中
     */
    public function delete_task($task_id)
    {
        $result = $this->mongo->where(array("_id" => new MongoId($task_id)))->find_one("spread_task");
        if (isset($result["state"])) {
            if ($result["state"] == "finish") {
                // 返回query执行结果
                return $this->mongo->where(array("_id" => new MongoId($task_id), "state" => "finish"))->delete("spread_task") ? 0 : 1;
            } else {
                // 任务进行中
                return 2;
            }
        } else {
            // 删除失败
            return 1;
        }
    }


    /**
     * 给socket服务端发送任务执行请求
     * @param string $task_id 任务id (MongoDB ObjectId)
     * @return bool
     */
    protected function run_spread_task($task_id)
    {
        error_reporting(E_ERROR);  // PHP错误提示等级
        set_time_limit(0);  // 确保在连接客户端时不会超时
        $port = 2000;
        $ip = "127.0.0.1";
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            return false;
        }
        // 连接socket服务端
        $result = socket_connect($socket, $ip, $port);
        if ($result < 0) {
            return false;
        }
        // 给socket服务端发送数据
        $in = trim($task_id);
        if (!socket_write($socket, $in, strlen($in))) {
            return false;
        }
        // 关闭连接
        socket_close($socket);
        return true;
    }
}