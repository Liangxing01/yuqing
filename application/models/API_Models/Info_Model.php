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
        // 视频附件信息
        if ($pData['video_info']) {
            $video_info = json_decode($pData['video_info'], true);
            $video_file = array(
                'info_id' => $info_id,
                'name' => $video_info['client_name'],
                'url' => "/uploads/video/" . $video_info['file_name'],
                'type' => "video"
            );
            $this->db->insert('info_attachment', $video_file);
            // 移动文件
            @copy($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $video_info['file_name'], $_SERVER['DOCUMENT_ROOT'] . "/uploads/video/" . $video_info['file_name']);
            @unlink($_SERVER['DOCUMENT_ROOT'] . "/uploads/temp/" . $video_info['file_name']);
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
        $this->success["total"] = $this->db->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->like("info.title", $keyword)
            ->count_all_results("info", false);
        $this->success["data"] = $this->db->select("info.id, title, url, time")
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
        if (!$this->verify->is_manager()) {
            return $this->privilege_error;
        }
        // 查询数据
        switch ($data_type) {
            case "all_message" :
                // 所有数据
                $data = $this->db->select("info.id, info.title, source, type.name AS type, user.name AS publisher, time, duplicate, state")
                    ->from("info")
                    ->like("info.title", $keyword)
                    ->join("user", "user.id = info.publisher", "left")
                    ->join("type", "type.id = info.type", "left")
                    ->order_by("time", "desc")
                    ->limit($size, $start)
                    ->get()->result_array();
                $total = $this->db->select("info.id")
                    ->from("info")
                    ->like("info.title", $keyword)
                    ->get()->num_rows();
                $this->success['data'] = $data;
                $this->success['total'] = $total;
                return $this->success;
                break;
            case "undo_message":
                // 未确认数据
                $data = $this->db->select("info.id, info.title, source, type.name AS type, user.name AS publisher, time, duplicate, state")
                    ->from("info")
                    ->join("user", "user.id = info.publisher", "left")
                    ->join("type", "type.id = info.type", "left")
                    ->where(array("info.state >=" => 0, "info.state <" => 2))
                    ->like("info.title", $keyword)
                    ->order_by("time", "desc")
                    ->limit($size, $start)
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
        $info = $this->db->select("info.id, info.relate_scope, info.title, info.description, type.name AS type, info.url, info.state, info.source, user.name AS publisher, info.time")
            ->from("info")
            ->join("type", "type.id = info.type", "left")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("info.id" => $info_id))
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

}