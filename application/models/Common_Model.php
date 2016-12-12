<?php

/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/11
 * Time: 15:28
 */
class Common_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 用户是否有权限查看这件事
     * @param $eid
     * @return bool
     */
    public function check_can_see($eid)
    {
        $uid = $this->session->userdata('uid');
        $pri = explode(",", $this->session->userdata('privilege'));
        $flag = false;
        foreach ($pri as $one) {
            switch ($one) {
                case 2 :
                    $flag = true;
                    break;
                case 3 :
                    $res = $this->check_processor_see($eid, $uid);
                    if ($res) {
                        $flag = true;
                    }
                    break;
                case 4 :
                    $res = $this->check_watcher_see($eid, $uid);
                    if ($res) {
                        $flag = true;
                    }
                    break;
                case 5 :
                    $flag = true;
                    break;
                default :
                    break;
            }
        }
        return $flag;
    }

    //权限:判断处理人是否能查看
    public function check_processor_see($eid, $uid)
    {
        $res = $this->db->select('id')->from('event_designate')
            ->where('event_id', $eid)
            ->where('processor', $uid)
            ->get()->result_array();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    //权限：判断 督查者是否能看
    public function check_watcher_see($eid, $uid)
    {
        $res = $this->db->select('id')->from('event_watch')
            ->where('event_id', $eid)
            ->where('watcher', $uid)
            ->get()->result_array();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    //获取事件的所有交互记录，pid为总结性发言
    public function get_all_logs_by_id($eid)
    {
        //检查权限
        $check = $this->check_can_see($eid);
        if (!$check) {
            return false;
        }
        $data = $this->db->select("l.description,l.pid,l.id,l.time,user.name,l.speaker,user.avatar")
            ->from('event_log AS l')
            ->where('l.event_id', $eid)
            ->join('user','l.speaker = user.id')
            ->order_by('time', 'DESC')->get()
            ->result_array();
        if (empty($data)) {
            return false;
        } else {
            //以总结性的话为开头，子数组为评论组成数组
            $summary_array = array();
            $comment_array = array();
            foreach ($data as $words) {
                //var_dump($words);
                if ($words['pid'] == "") {
                    //为总结性发言,记录id值
                    $info = array(
                        'id' => $words['id'],
                        'time' => $words['time'],
                        'desc' => $words['description'],
                        'name' => $words['name'],
                        'avatar' => $words['avatar'],
                        'usertype' => $this->check_is_watcher($eid, $words['speaker']) ? 1 : 0,
                        'comment' => array()
                    );
                    array_push($summary_array, $info);
                } else {
                    //评论加入评论数组
                    $com = array(
                        'id' => $words['id'],
                        'time' => $words['time'],
                        'desc' => $words['description'],
                        'name' => $words['name'],
                        'avatar' => $words['avatar'],
                        'pid' => $words['pid']
                    );
                    array_push($comment_array, $com);
                }
            }

            foreach ($comment_array as $words) {
                foreach ($summary_array as $k => $sum) {
                    if ($words['pid'] == $sum['id']) {
                        //var_dump($sum);
                        $info = array(
                            'id' => $words['id'],
                            'time' => $words['time'],
                            'name' => $words['name'],
                            'desc' => $words['desc'],
                            'avatar' => $words['avatar'],
                            'pid' => $words['pid']
                        );
                        array_push($summary_array[$k]['comment'], $info);

                    }
                }


            }
            return $summary_array;
        }
    }

    //判断用户是否为督察组
    public function check_is_watcher($eid, $uid)
    {
        $res = $this->db->select('watcher')->from('event_watch')
            ->where('event_id', $eid)
            ->get()->result_array();
        if (!empty($res)) {
            foreach ($res as $one) {
                if ($one['watcher'] == $uid) {
                    return 1;
                }
            }
            return 0;
        } else {
            return false;
        }

    }


    /**
     * 事件附件下载
     * @param $attachment_id
     * @return mixed
     */
    public function event_attachment_download($attachment_id)
    {
        $attachment_info = $this->db->select("id, event_id, name, url, type")
            ->where("id", $attachment_id)
            ->get("event_attachment")->row_array();
        if (empty($attachment_info)) {
            return false;
        }
        //用户是否可以下载该文件
        $this->load->model("Verify_Model", "verify");
        $e_can = $this->verify->can_see_event($attachment_info["event_id"]);
        if (!$e_can) {
            return false;
        }
        return $attachment_info;
    }


    /**
     * @param $video_id
     * @return mixed
     * TODO 鉴权
     */
    public function info_video_download($video_id){
        $video_info = $this->db->select("id, info_id, name, url, type")
            ->where("id", $video_id)
            ->get("info_attachment")->row_array();
        if (empty($video_info)) {
            return false;
        }
        return $video_info;
    }


    //检查 原密码是否正确
    public function check_old_pass($old){
        $uid = $this->session->userdata('uid');
        $pass = $this->db->select('password')->from('user')
            ->where('id',$uid)
            ->get()->result_array();
        if(empty($pass)){
            return false;
        }else{
            if(md5($old) == $pass[0]['password']){
                return true;
            }else{
                return false;
            }
        }
    }

    //更新密码
    public function update_psw($new){
        $uid = $this->session->userdata('uid');
        $data = array(
            'password' => md5($new)
        );
        $this->db->where('id',$uid);
        $res = $this->db->update('user',$data);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    //修改个人信息接口
    public function update_info($data)
    {
        $uid = $this->session->userdata('uid');

        $this->db->trans_begin();
        $this->db->where('id', $uid)->update("user", $data);
        $this->db->where(array("uid" => $uid, "type" => 1))->update("relation", array("name" => $data["name"]));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            $this->session->set_userdata(array('name' => $data["name"]));
            return true;
        }
    }


    //取消 事件首回报警
    public function update_alarm_state($eid)
    {
        $this->db->where(array("event_id" => $eid, "uid" => $this->session->uid))
            ->update("event_alert", array("state" => 0));
    }


    /**
     * @param $file_data
     * 插入上传文件信息
     * 参数:$check_all_size 是否检查 云盘总容量 限定 每个用户 云盘容量上限150M
     * return fid文件id
     */
    public function insert_file_info($file_data,$check_all_size,$allow_mime){
        //判断Mime  转换 文件格式
        /*$mimes = get_mimes();
        $file_type = '';
        foreach (explode('|',$allow_mime) as $item){
            if(is_array($mimes[$item])){
                if(in_array($file_data['file_type'],$mimes[$item])){
                    $file_type = $item;
                    break;
                }
            }else{
                if($mimes[$item] == $file_data['file_type']){
                    $file_type = $item;
                    break;
                }
            }

        }*/

        $finfo = array(
            'old_name' => $file_data['client_name'],
            'new_name' => $file_data['file_name'],
            'type'     => '',
            'size'     => $file_data['file_size'],
            'upload_time' => time(),
            'loc'      => $file_data['loc'],
            'is_exist' => 1
        );

        if($check_all_size == 1){
            //检查云盘总容量
            $now_all_size = $this->db->select_sum('size')
                ->from('file')
                ->join('file_user as fu','fu.uid =' . $this->session->userdata('uid'))
                ->get()->row_array();
            if($now_all_size['size'] > 1500000){
                return array(
                    'res' => 0,
                    'msg' => '您的云盘容量已达到150M上限，请删除多余文件后上传'
                );
            }else{
                //开始运行事务
                $this->db->trans_begin();

                $this->db->insert('file',$finfo);
                $fid = $this->db->insert_id();

                $fuser = array(
                    'fid' => $fid,
                    'uid' => $this->session->userdata('uid')
                );
                $this->db->insert('file_user',$fuser);

                if($this->db->trans_status() === FALSE){
                    $this->db->trans_rollback();
                    return array(
                        'res' => 0,
                        'msg' => '上传失败'
                    );
                }else{
                    $this->db->trans_commit();
                    return array(
                        'res' => 1,
                        'msg' => '上传成功',
                        'fid' => $fid
                    );
                }
            }
        }else{
            //邮件上传的文件
            $finfo['belong_email'] = 1;
            //开始运行事务
            $this->db->trans_begin();

            $this->db->insert('file',$finfo);
            $fid = $this->db->insert_id();

            $fuser = array(
                'fid' => $fid,
                'uid' => $this->session->userdata('uid')
            );
            $this->db->insert('file_user',$fuser);

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                return array(
                    'res' => 0,
                    'msg' => '上传失败'
                );
            }else{
                $this->db->trans_commit();
                return array(
                    'res' => 1,
                    'msg' => '上传成功',
                    'fid' => $fid,
                    'file_name' =>$file_data['client_name']
                );
            }
        }


    }


    /**
     * @param $q
     * @return array
     * 分页获取 用户云盘的文档信息
     */
    public function get_files_info($q){
        $uid = $this->session->userdata('uid');
        $res = array();
        $res['files'] = $this->db->select('f.old_name as file_name,f.size,f.upload_time,f.id')
            ->from('file_user as fu')
            ->join('file as f','f.id = fu.fid')
            ->where('fu.uid',$uid)
            ->where('f.belong_email IS NULL')
            ->like('f.old_name',$q['search'])
            ->limit($q['length'],$q['start'])
            ->order_by('f.upload_time','DESC')
            ->get()->result_array();

        $res['num'] = $this->db->select('f.old_name as file_name,f.size,f.upload_time,f.id')
            ->from('file_user as fu')
            ->join('file as f','f.id = fu.fid')
            ->where('fu.uid',$uid)
            ->where('f.belong_email IS NULL')
            ->like('f.old_name',$q['search'])
            ->get()->num_rows();

        return $res;

    }

    /**
     * @param $fid
     * @return bool
     * 云盘 文件下载
     */
    public function file_download($fid){
        //判断是否属于该用户的文件
        $uid = $this->session->userdata('uid');
        $check = $this->db->select('id')->from('file_user')
            ->where('uid',$uid)
            ->where('fid',$fid)
            ->get()->result_array();
        if(empty($check)){
            return false;
        }

        //查出该文件的信息
        $res = $this->db->select('loc as url,old_name as name')
            ->from('file')
            ->where('id',$fid)
            ->get()
            ->row_array();
        return $res;
    }


    /**
     * @param $del_arr
     * 删除 云盘文件
     */
    public function del_file($del_arr){
        $uid = $this->session->userdata('uid');


        //开始运行事务
        $this->db->trans_begin();

        foreach ($del_arr as $del){

            //判断该 文件 是否属于这个人
            $check = $this->db->select('id')->from('file_user')
                ->where('uid',$uid)
                ->where('fid',$del)
                ->get()->row_array();

            if(empty($check)){
                return false;
            }else{

                //删除file_user 表数据
                $this->db->where('fid',$del);
                $this->db->delete('file_user');

                //更新 file 表 is_exist = 0
                $update = array(
                    'is_exist' => 0
                );
                $this->db->where('id',$del);
                $this->db->update('file',$update);

                //删除本地文件
                //查询出路径
                $file_info = $this->db->select('loc')->from('file')
                    ->where('id',$del)
                    ->get()->row_array();

                @unlink($_SERVER['DOCUMENT_ROOT'] . $file_info['loc']);
                $res = true;
            }
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $res = false;
        }else{
            $this->db->trans_commit();
            $res = true;
        }

        return $res;
    }


    /**
     * ---------------------邮件 功能-------------------------
     */

    /**
     * 复制 移动 云盘文件 并 插入email_attachment数据库
     * 参数 : fid 云盘文件id
     */
    public function copy_insert_att($fids,$eid){
        $fid_arr = explode(',',$fids);
        $new_fids = array();
        foreach ($fid_arr as $fid){
            //遍历 file表数据 copy 并插入新表中
            $file = $this->db->select('old_name as file_name,new_name,type,size,loc,upload_time')
                ->from('file')
                ->where('id',$fid)
                ->get()->row_array();
            //复制到 eUploads目录下
            copy($_SERVER['DOCUMENT_ROOT'] . $file['loc'],$_SERVER['DOCUMENT_ROOT'].'/uploads/eUploads/'.$file['new_name']);
            //插入到 数据库中
            $insert_info = array(
                'eid'       => $eid,
                'file_name' => $file['file_name'],
                'loc'       => '/uploads/eUploads/'.$file['new_name'],
                'size'      => $file['size'],
                'type'      => $file['type'],
                'is_exist'  => 1,
                'upload_time' => $file['upload_time'],
                'expire_time' => (int)$file['upload_time'] + 1209600
            );
            $this->db->insert('email_attachment',$insert_info);
            $new_id = $this->db->insert_id();
            array_push($new_fids,$new_id);
        }
        return $new_fids;
    }

    /**
     * 获取 回复 邮件的信息
     * 参数：回复人的 id, 回复邮件的id
     */
    public function get_reply_info($r_uid,$r_eid){
        $r_name = $this->db->select('name')
            ->from('user')
            ->where('id',$r_uid)
            ->get()->row_array();

        $r_etitle = $this->db->select('title')
            ->from('email')
            ->where('id',$r_eid)
            ->get()->row_array();

        return array(
            'r_name'   => $r_name['name'],
            'r_etitle' => $r_etitle['title']
        );
    }

    /**通过 uid 获取用户姓名
     *
     */
    public function get_name($uid){
        $res = $this->db->select('name')
            ->from('user')
            ->where('id',$uid)
            ->get()->row_array();
        if(!empty($res)){
            return $res['name'];
        }else{
            return false;
        }
    }

    /**
     * 邮件 Body 插入图片
     * return 路径
     */
    public function insert_img($file_data,$allow_mime){
        //判断Mime  转换 文件格式
        $mimes = get_mimes();
        $file_type = '';
        foreach (explode('|',$allow_mime) as $item){
            if(is_array($mimes[$item])){
                if(in_array($file_data['file_type'],$mimes[$item])){
                    $file_type = $item;
                    break;
                }
            }else{
                if($mimes[$item] == $file_data['file_type']){
                    $file_type = $item;
                    break;
                }
            }

        }

        $finfo = array(
            'old_name' => $file_data['client_name'],
            'new_name' => $file_data['file_name'],
            'type'     => $file_type,
            'size'     => $file_data['file_size'],
            'upload_time' => time(),
            'loc'      => $file_data['loc'],
            'is_exist' => 1,
            'belong_email' => 1
        );

        //开始运行事务
        $this->db->trans_begin();

        $this->db->insert('file',$finfo);
        $fid = $this->db->insert_id();

        $fuser = array(
            'fid' => $fid,
            'uid' => $this->session->userdata('uid')
        );
        $this->db->insert('file_user',$fuser);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array(
                'res' => 0,
                'msg' => '上传失败'
            );
        }else{
            $this->db->trans_commit();
            return array(
                'res' => 1,
                'msg' => '上传成功',
                'src' => $file_data['loc']
            );
        }
    }

    /**
     * 通过 附件id 获取附件信息
     * $eid 校验是否 属于 该邮件的附件
     */
    public function get_att_by_id($ids,$eid){
        $id_arr = explode(',',$ids);
        $att_info_arr = array();
        foreach ($id_arr as $id){
            $info = $this->db->select('id,file_name,loc,size,is_exist')
                ->from('email_attachment')
                ->where('id',$id)
                ->where('eid',$eid)
                ->get()->row_array();

            array_push($att_info_arr,$info);
        }
        return $att_info_arr;
    }

    /**
     * @param $einfo
     * @param $receID
     * @param $attID
     * @return bool
     * 写入 邮件信息
     */
    public function insert_email($einfo,$rec,$attID){
        $einfo['sender'] = $this->session->userdata('uid');
        $einfo['time']   = time();

        //开始运行事务
        $this->db->trans_begin();

        //插入email信息表
        $this->db->insert('email',$einfo);
        $eid = $this->db->insert_id();

        //判断 是否 有附件
        if(!empty($attID)){
            //插入附件信息表，先通过fid 找到file表的信息，然后插入到 email_attach 表里面
            $att_arr = array();
            foreach ($attID as $att){
                $file_info = $this->db->select('old_name as file_name,type,size,upload_time,new_name')
                    ->from('file')
                    ->where('id',$att)
                    ->get()->row_array();
                $file_info['eid'] = $eid;
                $file_info['is_exist'] = 1;
                $file_info['expire_time'] = (int)$file_info['upload_time'] + 1209600;
                $file_info['loc'] = '/uploads/eUploads/' . $file_info['new_name'];

                //copy temp目录下的文件 到 eUploads目录，并删除 原有文件
                copy($_SERVER['DOCUMENT_ROOT'] . '/uploads/temp/' . $file_info['new_name'],$_SERVER['DOCUMENT_ROOT'] . '/uploads/eUploads/'.$file_info['new_name']);
                @unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/temp/' . $file_info['new_name']);

                unset($file_info['new_name']);
                array_push($att_arr,$file_info);
            }

            $this->db->insert_batch('email_attachment',$att_arr);
        }


        //插入email_user表

        //分别遍历 gid uid组
        if(!empty($rec['uids'])){
            $uid_arr = array();
            foreach ($rec['uids'] as $uid){
                $uid_one = array(
                    'email_id' => $eid,
                    'receiver_id' => $uid,
                    'state'    => 0
                );
                array_push($uid_arr,$uid_one);
            }

            $this->db->insert_batch('email_user',$uid_arr);
        }

        if(!empty($rec['gids'])){
            $gid_arr = array();
            foreach ($rec['gids'] as $gid){
                $gid_one = array(
                    'email_id' => $eid,
                    'receiver_gid' => $gid,
                    'state'    => 0
                );
                array_push($gid_arr,$gid_one);
            }

            $this->db->insert_batch('email_user',$gid_arr);
        }


        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $res = false;
        }else{
            $this->db->trans_commit();
            $res = true;
        }

        return $res;

    }

    /**
     * 查看 收件箱 邮件
     * 参数：eid
     */
    public function rece_email_detail($eid){
        //判断邮件是否属于这个人或他的单位
        $uid  = $this->session->userdata('uid');
        $gids = $this->session->userdata('gid');
        $gid_arr = explode(',',$gids);
        //判断收件权
        $check_rece= $this->db->select('id')
            ->from('email_user')
            ->where('email_id',$eid)
            ->group_start()
            ->where('receiver_id',$uid)
            ->or_where_in('receiver_gid',$gid_arr)
            ->group_end()
            ->get()->row_array();

        if(!empty($check_rece)){
            //查询邮件信息
            $info = $this->db->select('e.id,e.sender,e.priority_level,e.title,e.body,u.name as sender_name,from_unixtime(e.time) as time')
                ->from('email as e')
                ->join('user as u','u.id = e.sender')
                ->where('e.id',$eid)
                ->get()->row_array();

            $attID = $this->db->select('id')
                ->from('email_attachment')
                ->where('eid',$eid)
                ->get()->result_array();

            $attIDs = array();
            foreach ($attID as $id){
                array_push($attIDs,$id['id']);
            }

            $res = array(
                'info' => $info,
                'attID'  => $attIDs
            );

            //更新阅读状态为已读
            $update_state = array(
                'state' => 1
            );
            $this->db->where('email_id',$eid);
            $this->db->update('email_user',$update_state);

            return $res;

        }else{
            return false;
        }
    }


    /**
     * 发件箱 邮件 详情
     */
    public function send_email_detail($eid){
        //判断邮件是否属于这个人
        $uid  = $this->session->userdata('uid');
        //判断是否 是他 发送的邮件
        $check_send = $this->db->select('id')
            ->from('email')
            ->where('sender',$uid)
            ->get()->result_array();

        if(!empty($check_send)){
            //查询邮件信息
            $info = $this->db->select('id,title,body,priority_level,from_unixtime(time) as time')
                ->from('email')
                ->where('id',$eid)
                ->get()->row_array();

            $attID = $this->db->select('id')
                ->from('email_attachment')
                ->where('eid',$eid)
                ->get()->result_array();
            $attIDs = array();
            foreach ($attID as $id){
                array_push($attIDs,$id['id']);
            }

            $res = array(
                'info' => $info,
                'attID'  => $attIDs
            );
            return $res;
        }else{
            return false;
        }

    }

    /**
     * 获取用户 阅读邮件 状态
     */
    public function get_read_state($eid){
        //查询 用户 是否 阅读
        $user_read = $this->db->select('eu.receiver_id as uid,eu.state,u.name')
            ->from('email_user as eu')
            ->join('user as u','u.id = eu.receiver_id')
            ->where('eu.email_id',$eid)
            ->get()->result_array();

        //查询 组是否有人阅读
        $group_read = $this->db->select('eu.receiver_gid as gid,eu.state,g.name')
            ->from('email_user as eu')
            ->join('group as g','g.id = eu.receiver_gid')
            ->where('eu.email_id',$eid)
            ->get()->result_array();

        $res = array(
            'user_read_state' => $user_read,
            'group_read_state' => $group_read
        );

        return $res;
    }

    /**
     * 获取未读 邮件 个数
     */
    public function get_unread_num(){
        $uid = $this->session->userdata('uid');
        $gid = $this->session->userdata('gid');
        $gid_arr = explode(',',$gid);
        $num = $this->db->select('id')
            ->from('email_user')
            ->where('state',0)
            ->group_start()
            ->where('receiver_id',$uid)
            ->or_where_in('receiver_gid',$gid_arr)
            ->group_end()
            ->get()->num_rows();
        return $num;
    }

    /**
     * @param $fid
     * @return bool
     * 云盘 文件下载
     */
    public function att_download($fid,$eid){
        //判断是否属于该邮件的文件
        $check = $this->db->select('id')->from('email_attachment')
            ->where('eid',$eid)
            ->where('id',$fid)
            ->get()->row_array();
        if(empty($check)){
            return false;
        }
        //查出该文件的信息
        $res = $this->db->select('loc,file_name as name')
            ->from('email_attachment')
            ->where('id',$fid)
            ->get()->row_array();
        return $res;
    }

    /**
     * 分页显示 发件箱 列表
     */
    public function get_send_emails_info($q){
        $uid = $this->session->userdata('uid');
        $res = array();
        $res['emails'] = $this->db->select('e.id,ea.eid as has_att,e.title,e.priority_level,e.time')
            ->from('email as e')
            ->join('email_attachment as ea','e.id = ea.eid','left')
            ->group_by('e.id')
            ->where('e.sender',$uid)
            ->group_start()
            ->like('e.title',$q['search'])
            ->or_like('e.priority_level',$q['search'])
            ->group_end()
            ->limit($q['length'],$q['start'])
            ->order_by('e.time','DESC')
            ->get()->result_array();


        $res['num'] = $this->db->select('e.id,e.title,e.priority_level,e.time')
            ->from('email as e')
            ->where('e.sender',$uid)
            ->group_start()
            ->like('e.title',$q['search'])
            ->or_like('e.priority_level',$q['search'])
            ->group_end()
            ->get()->num_rows();

        return $res;
    }

    /**
     * 分页显示 收件箱 列表
     * $q['state'] 已读 未读 状态
     */
    public function get_rec_emails_info($q){
        $uid  = $this->session->userdata('uid');
        $gids = $this->session->userdata('gid');
        $gid_arr = explode(',',$gids);
        $res = array();
        if($q['state'] == 'all'){
            $res['emails'] = $this->db->select('e.id,ea.id as has_att,e.title,u.name as sender_name,e.priority_level,e.time,eu.state')
                ->from('email as e')
                ->join('email_user as eu','eu.email_id = e.id')
                ->join('user as u','u.id = e.sender')
                ->join('email_attachment as ea','e.id = ea.eid','left')
                ->group_by('e.id')
                ->group_start()
                ->where('eu.receiver_id',$uid)
                ->or_where_in('eu.receiver_gid',$gid_arr)
                ->group_end()
                ->group_start()
                ->like('e.title',$q['search'])
                ->or_like('u.name',$q['search'])
                ->group_end()
                ->limit($q['length'],$q['start'])
                ->order_by('e.time','DESC')
                ->get()->result_array();


            $res['num'] = $this->db->select('e.id,e.title,u.name as sender_name,e.priority_level,e.time')
                ->from('email as e')
                ->join('email_user as eu','eu.email_id = e.id')
                ->join('user as u','u.id = e.sender')
                ->group_start()
                ->where('eu.receiver_id',$uid)
                ->or_where_in('eu.receiver_gid',$gid_arr)
                ->group_end()
                ->group_start()
                ->like('e.title',$q['search'])
                ->or_like('u.name',$q['search'])
                ->group_end()
                ->get()->num_rows();
        }else{
            $res['emails'] = $this->db->select('e.id,ea.id as has_att,e.title,u.name as sender_name,e.priority_level,e.time,eu.state')
                ->from('email as e')
                ->join('email_user as eu','eu.email_id = e.id')
                ->join('user as u','u.id = e.sender')
                ->join('email_attachment as ea','e.id = ea.eid','left')
                ->group_by('e.id')
                ->where('eu.state',$q['state'])
                ->group_start()
                ->where('eu.receiver_id',$uid)
                ->or_where_in('eu.receiver_gid',$gid_arr)
                ->group_end()
                ->group_start()
                ->like('e.title',$q['search'])
                ->or_like('u.name',$q['search'])
                ->group_end()
                ->limit($q['length'],$q['start'])
                ->order_by('e.time','DESC')
                ->get()->result_array();


            $res['num'] = $this->db->select('e.id,e.title,u.name as sender_name,e.priority_level,e.time')
                ->from('email as e')
                ->join('email_user as eu','eu.email_id = e.id')
                ->join('user as u','u.id = e.sender')
                ->where('eu.state',$q['state'])
                ->group_start()
                ->where('eu.receiver_id',$uid)
                ->or_where_in('eu.receiver_gid',$gid_arr)
                ->group_end()
                ->group_start()
                ->like('e.title',$q['search'])
                ->or_like('u.name',$q['search'])
                ->group_end()
                ->get()->num_rows();
        }


        return $res;
    }





}