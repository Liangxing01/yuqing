<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Info_Model extends CI_Model
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
     * 提交上报信息
     * @param array $pData 表单数据
     * @param string $upload_filed 图片上传字段
     * @return array
     */
    public function add_info_data($pData, $upload_filed)
    {
        // 检测url是否重复
        $dup = 0;//不重复
        if ($pData['url'] !== null) {
            $judge = $this->db->where(array('url' => $pData['url']))->get('info')->num_rows();
            if ($judge > 0) {
                $dup = 1;//重复
            }
        }
        // 事件信息数据
        $data = array(
            'title' => $pData['title'],
            'url' => $pData['url'],
            'source' => $pData['source'],
            'description' => $pData['description'],
            'publisher' => $this->session->uid,
            'time' => time(),
            'state' => 0,
            'duplicate' => $dup
        );
        // 开始添加信息事务
        $this->db->trans_begin();
        // 插入信息数据
        $this->db->insert('info', $data);
        $info_id = $this->db->insert_id();
        // 信息上报推送消息数据
        $info_msg = array();
        $this->load->model("User_Model", "user");
        $managers = $this->user->get_managers();
        $time = time();
        foreach ($managers AS $manager) {
            $info_msg[] = array(
                "title" => $data["title"],
                "type" => 0,    //上报消息类型
                "send_uid" => $manager["id"],
                "send_gid" => null,
                "time" => $time,
                "url" => "/designate/info_detail?id=" . $info_id,
                "m_id" => $info_id,
                "state" => 0    //消息未读
            );
        }
        if (!empty($info_msg)) {
            $this->db->insert_batch("business_msg", $info_msg); // 插入数据
        }
        // 视频附件信息
        if ($pData['video_info']) {
            $video_info = json_decode($pData['video_info'], true);
            $video_file = array();
            foreach ($video_info AS $item) {
                $video_file[] = array(
                    'info_id' => $info_id,
                    'name' => $item['client_name'],
                    'url' => "/uploads/video/" . $item['file_name'],
                    'type' => "video"
                );
                // 移动文件
                @copy($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $item['file_name'], $_SERVER['DOCUMENT_ROOT'] . "/uploads/video/" . $item['file_name']);
                @unlink($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $item['file_name']);
            }
            $this->db->insert_batch('info_attachment', $video_file);
        }
        // 检测并上传图片
        if (!empty($_FILES)) {
            $pic_data = array();
            $config = array();
            $config['upload_path'] = './uploads/pic/';
            $config['allowed_types'] = 'jpg|png|jpeg';
            $config['max_size'] = 10000;
            $config['max_width'] = 0;
            $config['max_height'] = 0;
            $config['encrypt_name'] = true;
            $config['multi'] = 'all';
            $this->load->library("upload");
            $this->load->library("Multi_Upload", $config);
            if (!$this->multi_upload->do_upload($upload_filed)) {
                // 上传失败
                $this->db->trans_rollback();
                $this->param_error['message'] = $this->multi_upload->display_errors();
                return $this->param_error;
            } else {
                // 上传成功
                $pics = $this->multi_upload->data();
                if (count($pics) == count($pics, 1)) {
                    $pic_data[] = array(
                        'info_id' => $info_id,
                        'name' => $pics['client_name'],
                        'url' => "/uploads/pic/" . $pics['file_name'],
                        'type' => "pic");
                } else {
                    foreach ($pics AS $pic) {
                        $pic_data[] = array(
                            'info_id' => $info_id,
                            'name' => $pic['client_name'],
                            'url' => "/uploads/pic/" . $pic['file_name'],
                            'type' => "pic");
                    }
                }
            }
            // 插入附件数据
            $this->db->insert_batch('info_attachment', $pic_data);
        }
        // 完成添加信息事务
        if ($this->db->trans_status() === FALSE) {
            // 数据入库失败
            $this->db->trans_rollback();
            return $this->param_error;
        } else {
            // 数据入库成功
            $this->db->trans_commit();
            try {
                //连接消息服务器
                $this->load->library("Gateway");
                Gateway::$registerAddress = $this->config->item("VM_registerAddress");
                //业务消息推送
                //用户 上报信息 推送给在线指派人
                $managers = $this->db->select("user.id")
                    ->join("user_privilege", "user_privilege.uid = user.id")
                    ->where("user_privilege.pid", 2)
                    ->get("user")->result_array();
                foreach ($managers AS $manager) {
                    Gateway::sendToUid($manager["id"], json_encode(array(
                        "title" => $data["title"],
                        "time" => $data["time"],
                        "type" => 0,
                        "url" => "/designate/info_detail?id=" . $info_id
                    )));
                }
            } catch (Exception $e) {
                log_message("error", $e->getMessage());
            }
            $this->success['message'] = 'commit success';
            return $this->success;
        }
    }


    /**
     * 视频上传
     */
    public function upload_video()
    {
        $config['upload_path'] = './uploads/temp/';
        $config['allowed_types'] = 'mp4|rmvb|flv|avi|rm|wmv|mkv';
        $config['max_size'] = 0;
        $config['max_width'] = 0;
        $config['max_height'] = 0;
        $config['encrypt_name'] = true;
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('video')) {
            $this->param_error['message'] = $this->upload->display_errors('', '');
            return $this->param_error;
        } else {
            $upload_data = $this->upload->data();
            $this->success['message'] = 'upload success';
            $this->success['data'] = array('client_name' => $upload_data['client_name'], 'file_name' => $upload_data['file_name']);
            return $this->success;
        }
    }


    /**
     * 获取上报记录
     * @param int $page_num 页码
     * @param int $size 每页大小
     * @param string $keyword
     * @return array
     */
    public function get_info_record_data($page_num, $size, $keyword)
    {
        // 检测参数
        if (!(is_int($page_num) && is_int($size))) {
            return $this->param_error;
        }
        // 查询数据
        $start = ($page_num - 1) * $size;
        $uid = $this->session->uid;
        $keyword = is_null($keyword) ? "" : $keyword;
        // 返回上报记录
        $this->success["total"] = $this->db->select("count('info.id')")
            ->from('info')
            ->join("user", "user.id = info.publisher", "left")
            ->join("info_attachment", "info_attachment.info_id = info.id AND info_attachment.type = 'pic'", "left")
            ->where(array("user.id" => $uid))
            ->group_by("info.id")
            ->like("info.title", $keyword)
            ->get()->num_rows();
        $this->success["data"] = $this->db->select("info.id, title, info.source, time, info_attachment.url AS pic")
            ->from('info')
            ->join("user", "user.id = info.publisher", "left")
            ->join("info_attachment", "info_attachment.info_id = info.id AND info_attachment.type = 'pic'", "left")
            ->where(array("user.id" => $uid))
            ->group_by("info.id")
            ->like("info.title", $keyword)
            ->limit($size, $start)
            ->order_by("time", "desc")
            ->get()->result_array();

        return $this->success;
    }


    /**
     * 获取上报信息列表
     * @param int $page_num
     * @param int $size
     * @param string $data_type
     * @param string $keyword
     * @return array
     */
    public function get_info_list_data($page_num, $size, $data_type, $keyword)
    {
        // 检测参数
        if (is_int($page_num) && is_int($size)) {
            $start = ($page_num - 1) * $size;
        } else {
            return $this->param_error;
        }
        // 检测权限
        if (!($this->verify->is_manager() || $this->verify->is_watcher())) {
            return $this->privilege_error;
        }
        // 查询数据
        switch ($data_type) {
            case "all_message" :
                // 所有数据
                $data = $this->db->select("info.id, info.title, info.source, user.name AS publisher, group.name AS group_name, time, info_attachment.url AS pic")
                    ->from("info")
                    ->where(array('state' => 2, 'duplicate' => 0))
                    ->like("info.title", $keyword)
                    ->join("user", "user.id = info.publisher", "left")
                    ->join("user_group", 'user_group.uid = info.publisher', 'left')
                    ->join("group", "group.id = user_group.gid", 'left')
                    ->join("info_attachment", "info_attachment.info_id = info.id AND info_attachment.type = 'pic'", "left")
                    ->order_by("time", "desc")
                    ->limit($size, $start)
                    ->group_by("info.id")
                    ->get()->result_array();
                $total = $this->db->select("info.id")
                    ->from("info")
                    ->where(array('state' => 2, 'duplicate' => 0))
                    ->like("info.title", $keyword)
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "undo_message":
                // 未确认数据
                $data = $this->db->select("info.id, info.title, info.source, user.name AS publisher, group.name AS group_name, time, info_attachment.url AS pic")
                    ->from("info")
                    ->join("user", "user.id = info.publisher", "left")
                    ->join("user_group", 'user_group.uid = info.publisher', 'left')
                    ->join("group", "group.id = user_group.gid", 'left')
                    ->join("info_attachment", "info_attachment.info_id = info.id AND info_attachment.type = 'pic'", "left")
                    ->where(array("info.state >=" => 0, "info.state <" => 2))
                    ->like("info.title", $keyword)
                    ->order_by("time", "desc")
                    ->limit($size, $start)
                    ->group_by("info.id")
                    ->get()->result_array();
                $total = $this->db->select("info.id")
                    ->from("info")
                    ->where(array("info.state >=" => 0, "info.state <" => 2))
                    ->like("info.title", $keyword)
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            default:
                return $this->param_error;
                break;
        }
    }


    /**
     * 获取上报信息详情
     * @param int $info_id 信息id
     * @return array
     */
    public function get_info_detail($info_id)
    {
        // 检测参数
        if (!$info_id) {
            return $this->param_error;
        }
        // 检测权限
        // 查询数据
        $info = $this->db->select("info.id, info.relate_scope, info.title, info.description, type.name AS type, info.url, info.state, info.source, user.name AS publisher, group.name AS group, info.time")
            ->from("info")
            ->join("type", "type.id = info.type", "left")
            ->join("user", "user.id = info.publisher", "left")
            ->join("user_group", "user_group.uid = info.publisher", "left")
            ->join("group", "group.id = user_group.gid", "left")
            ->where(array("info.id" => $info_id, "user_group.is_exist" => 1))
            ->get()->row_array();
        // 附件信息
        $attachments = $this->db->select("id, type, url")
            ->where("info_id", $info_id)
            ->get("info_attachment")->result_array();
        $info['picture'] = array();
        $info['video'] = array();
        foreach ($attachments AS $attachment) {
            if ($attachment["type"] == "pic") {
                unset($attachment['type']);
                $info['picture'][] = $attachment;
            } else {
                unset($attachment['type']);
                $info['video'][] = $attachment;
            }
        }

        $this->success['data'] = $info;
        return $this->success;
    }


    /**
     * 获得 信息类型 涉及领域
     * @return array
     */
    public function get_info_type()
    {
        $data = $this->db->select('id, name')->get('yq_type')->result_array();
        $this->success['data'] = $data;
        return $this->success;
    }


    /**
     * 确认上报信息
     * @param $id
     * @param $type
     * @param $duplicate
     * @param $relate_scope
     * @param $trash
     * @param $source
     * @return array
     */
    public function verify_info($id, $type, $duplicate, $relate_scope, $trash, $source)
    {
        // 参数验证
        if ($id === NULL || $type === NULL || $duplicate === NULL || $trash === NULL) {
            return $this->param_error;
        }
        //判断信息是否是无效信息
        if ($trash == 1) {
            $state = -1;
        } else {
            $state = 2;
        }
        $result = $this->db->set(array("type" => $type, "duplicate" => $duplicate, "relate_scope" => $relate_scope,
            "state" => $state, "source" => $source))
            ->where(array("id" => $id))
            ->update("info");
        if ($result) {
            // 确认成功
            return $this->success;
        } else {
            return $this->param_error;
        }
    }

}