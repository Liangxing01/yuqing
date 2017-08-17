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
     * @param int $page_num
     * @param int $size
     * @return array
     */
    public function get_summary_data($page_num, $size)
    {
        // 检测参数
        if (is_int($page_num) && is_int($size)) {
            $start = ($page_num - 1) * $size;
        } else {
            return $this->param_error;
        }
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
            // 未确认信息
            'unconfirmed_info_num' => $this->db->select('id')->from('info')->where(array('state <' => 2, 'state >' => -1))->get()->num_rows(),
            // 全部事件数
            'all_info_num' => $this->db->select('id')->from('event')->get()->num_rows(),
            // 未审核
            'unverified_event_num' => $this->db->select('id')->from('event')->where('state', '未审核')->get()->num_rows(),
            // 已完成
            'all_event_num' => $this->db->select('id')->from('event')->where('state', '已完成')->get()->num_rows(),
        );
        // 处理任务数据
        $unread_num = $this->db->select('id')->from('event_designate')->where('processor', $uid)->where('state', '未处理')->get()->num_rows();
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

            $data['total'] = $this->db->group_start()
                ->where("send_uid", $uid)
                ->or_where_in("send_gid", $group_id)
                ->group_end()
                ->where(array('time >=' => $time, 'time <' => $time + 24 * 3600))
                ->count_all_results("business_msg", false);

            $data['message_list'] = $this->db->select('title, type, m_id, time')
                ->order_by('time', 'DESC')
                ->limit($size, $start)
                ->get()->result_array();
        }

        $this->success['data'] = $data;
        return $this->success;
    }


    /**
     * 修改个人信息
     * @param string $name
     * @param int $sex
     * @param int $is_avatar
     * @return array
     */
    public function update_info($name, $sex, $is_avatar)
    {
        switch ($is_avatar) {
            case 1 :
                // 修改头像
                $config['upload_path'] = './uploads/avatar/';
                $config['allowed_types'] = 'jpg|png|jpeg|gif';
                $config['max_size'] = 10000;
                $config['max_width'] = 0;
                $config['max_height'] = 0;
                $config['encrypt_name'] = true;

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('avatar')) {
                    $error = $this->upload->display_errors();
                    $this->param_error['message'] = $error;
                    return $this->param_error;
                } else {
                    $upload_data = $this->upload->data();
                    //更新头像
                    $link = '/uploads/avatar/' . $upload_data['file_name'];
                    $this->update_avatar($link);

                    $this->success['data'] = array(
                        'name' => $upload_data['orig_name'],
                        'url' => $link,
                        'new_name' => $upload_data['file_name'],
                        'type' => $upload_data['image_type']
                    );
                    $this->success['data'] = array('link' => $link);
                    return $this->success;
                }
                break;
            case 0 :
                if ($name == null && $sex == null) {
                    return $this->param_error;
                }
                // 修改个人信息
                $uid = $this->session->userdata('uid');
                $user_data = array('name' => $name, 'sex' => $sex);
                $this->db->trans_begin();
                $this->db->where('id', $uid)->update("user", $user_data);
                $this->db->where(array("uid" => $uid, "type" => 1))->update("relation", array("name" => $user_data["name"]));
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    return $this->param_error;
                } else {
                    $this->db->trans_commit();
                    $this->session->set_userdata(array('name' => $user_data["name"]));
                    $this->success['data'] = array('name' => $name, 'sex' => $sex);
                    return $this->success;
                }
                break;
            default:
                return $this->param_error;
        }
    }


    /**
     * 修改密码接口
     * @param string $old
     * @param string $new
     * @return array
     */
    public function update_password($old, $new)
    {
        $this->load->model("Common_Model", 'common_model');
        $check = $this->common_model->check_old_pass($old);
        if ($check) {
            $res = $this->common_model->update_psw($new);
            if ($res) {
                return $this->success;
            } else {
                return $this->param_error;
            }
        } else {
            $this->param_error['message'] = "原始密码验证失败";
            return $this->param_error;
        }
    }


    /**
     * 更新头像
     * @param $link
     * @return mixed
     */
    protected function update_avatar($link)
    {
        $uid = $this->session->userdata('uid');
        //删除原头像
        $old_avatar = $this->db->select('avatar')->from('user')
            ->where('id', $uid)
            ->get()->row_array();
        if (!empty($old_avatar) && $old_avatar['avatar'] != "/img/avatar/avatar.png") {
            $old_avatar_url = $old_avatar['avatar'];
            @unlink($_SERVER['DOCUMENT_ROOT'] . $old_avatar_url);
        }
        // 更新数据库
        $res = $this->db->where('id', $uid)->update('user', array('avatar' => $link));
        // 更新session
        $this->session->set_userdata("avatar", $link);
        return $res;
    }
}