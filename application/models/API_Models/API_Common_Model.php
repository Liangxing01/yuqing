<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API_Common_Model extends CI_Model
{

    /**
     * 数据接口 返回值
     * @var array
     * code 0 请求成功
     */
    private $success;

    /**
     * 数据接口 返回值
     * @var array
     * code 1 参数错误
     */
    private $param_error;

    /**
     * 数据接口 返回值
     * @var array
     * code 2 权限错误
     */
    private $privilege_error;


    /**
     * Info_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 载入 权限模型
        $this->load->model("Verify_Model", "verify");
        // 载入 api 返回值
        $this->load->helper("api");
        $this->success = APIResponseBody::$success;
        $this->param_error = APIResponseBody::$param_error;
        $this->privilege_error = APIResponseBody::$privilege_error;
    }

    /**
     * 首页信息
     */
    public function get_summary_data()
    {
        $uid = $this->session->uid;
        $gid = $this->session->gid;
        $data = array(
            'total' => 0, // 消息总数
            'manager' => array(), // 指派人数据
            'processor' => array(), // 处理人数据
            'message_list' => array() // 消息列表
        );
        // 指派任务数据
        $data['manager'] = array(
            // 未查看
            'unread_num' => $this->db->select('id')->from('info')->where('state', 0)->get()->num_rows(),
            // 已指派
            'designate_num' => $this->db->select('id')->from('event')->where('state', '已指派')->get()->num_rows(),
            // 未审核
            'unconfirmed_num' => $this->db->select('id')->from('event')->where('state', '未审核')->get()->num_rows(),
            // 已完成
            'done_num' => $this->db->select('id')->from('event')->where('state', '已完成')->get()->num_rows()
        );
        // 处理任务数据
        $unread_num = $this->db->select('id')->from('event_designate')->where('processor', $uid)->where('state', '待处理')->get()->num_rows();
        $doing_num = $this->db->select('id')->from('event_designate')->where('processor', $uid)->where('state', '处理中')->get()->num_rows();
        $done_num = $this->db->select('e.id')->from('event_designate AS ed')->join('event AS e', 'e.id = ed.event_id', 'left')
            ->where('ed.processor', $uid)
            ->group_start()
            ->where('e.state', '未审核')
            ->or_where('e.state', '已完成')
            ->group_end()
            ->get()->num_rows();
        $data['processor'] = array(
            'unread_num' => $unread_num, // 未处理
            'doing_num' => $doing_num, // 正在处理
            'done_num' => $done_num, // 已完成
            'total_num' => $unread_num + $doing_num + $done_num // 总任务
        );
        // 消息列表
        if ($gid != "") {
            $time = strtotime(date('Y-m-d')); // 查询时间为当天00:00 - 24:00
            $group_id = explode(",", $gid);
            $data['message_list'] = $this->db->select('title, type, m_id, time')
                ->group_start()
                ->where("send_uid", $uid)
                ->or_where_in("send_gid", $group_id)
                ->group_end()
                ->where(array('time >=' => $time, 'time <' => $time + 24 * 3600))
                ->order_by('time', 'DESC')
                ->get("business_msg")->result_array();
        }
        // 最新消息数
        $data['total'] = count($data['message_list']);

        $this->success['data'] = $data;
        return $this->success;
    }
}