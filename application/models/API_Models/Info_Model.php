<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Info_Model extends CI_Model
{

    /**
     * 数据接口 返回值
     * @var array
     * code 0 请求成功
     */
    private $success = array(
        "code" => 0,
        "message" => "Request Success",
        "data" => array()
    );

    /**
     * 数据接口 返回值
     * @var array
     * code 1 参数错误
     */
    private $param_error = array(
        "code" => 1,
        "message" => "Bad Request Params",
    );

    /**
     * 数据接口 返回值
     * @var array
     * code 2 权限错误
     */
    private $privilege_error = array(
        "code" => 2,
        "message" => "No Privilege",
    );


    /**
     * Info_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 载入 权限模型
        $this->load->model("Verify_Model", "verify");
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
     * @param $page_num int 页码
     * @param $size int 每页大小
     * @return array
     */
    public function get_info_record_data($page_num, $size)
    {
        // 检测参数
        if (!(is_int($page_num) && is_int($size))) {
            return $this->param_error;
        }
        // 查询数据
        $start = ($page_num - 1) * $size;
        $uid = $this->session->uid;
        $this->success["data"] = $this->db->select("info.id, title, url, time")
            ->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->limit($size, $start)
            ->order_by("time", "desc")
            ->get("info")->result_array();

        $this->success["total"] = $this->db->join("user", "user.id = info.publisher", "left")
            ->where(array("user.id" => $uid))
            ->get("info")->num_rows();
        return $this->success;
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
        $attachments = $this->db->select("id, name, url, type")
            ->where("info_id", $info_id)
            ->get("info_attachment")->result_array();
        $info['video'] = array();
        $info['picture'] = array();
        foreach ($attachments AS $attachment) {
            if ($attachment["type"] == "pic") {
                $info['video'][] = $attachment;
            } else {
                $info['picture'][] = $attachment;
            }
        }

        $this->success['data'] = $info;
        return $this->success;
    }

}