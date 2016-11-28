<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/11/8
 * Time: 11:20
 */

class Admin_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    /**
     * 获取 信息类型 列表
     * @return array
     */
    public function get_info_type()
    {
        return $this->db->get("type")->result_array();
    }

    /**
     * @param $data
     */
    public function add_person($data){
        $userInfo = array(
            'username' => $data['username'],
            'password' => md5($data['password']),
            'name'     => $data['name'],
            'group_id' => $data['gid'],
            'sex'      => $data['sex'],
            'job'      => $data['job'],
            "avatar"   => "/img/avatar/avatar.png"
        );
        //开始事务
        $this->db->trans_begin();

        $this->db->insert('user',$userInfo);
        $uid = $this->db->insert_id();
        //$res2 = false;

        //插入权限表
        $pri = $data['privilege'];
        $pri_arr = explode(",",$pri);
        foreach ($pri_arr as $one){
            $insert_pri = array(
                'pid' => $one,
                'uid' => $uid
            );
            $this->db->insert('user_privilege',$insert_pri);
        }

        //插入组织结构
        $insert_data = array(
            'uid'   => $uid,
            'name'  => $data['name'],
            'parent_id' => $data['gid'],
            'type'  => 1

        );
        $this->insert_children_node($insert_data);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }


    }

    /**
     * @param $data
     * @return bool
     * 添加单位
     */
    public function add_group($data){
        $groupInfo = array(
            'name' => $data['groupname']
        );
        //插入组织表
        //开始事务
        $this->db->trans_begin();

        $this->db->insert('group',$groupInfo);
        $uid = $this->db->insert_id();

        //插入关系表(组织结构)
        $insert_data = array(
            'uid'   => $uid,
            'name'  => $data['groupname'],
            'parent_id' => $data['gid'],
            'type'  => 0

        );
        $this->insert_children_node($insert_data);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }

    }



    /**
     * @param $data
     * @return bool
     * 插入子节点
     */
    public function insert_children_node($data){
        $parent_id = $data['parent_id'] ? $data['parent_id'] : null;
        $uid   = $data['uid'];
        $name  = $data['name'];
        $type  = $data['type'];
        $res   = false;

        //找出待插入节点的左右值
        $parent_node_info = $this->db->select('lft,rgt')->from('relation')
            ->where('uid',$parent_id)
            ->where('type',0)
            ->get()->row_array();
        if(!empty($parent_node_info)){
            //父级节点左右值都加2
            $this->db->query("update yq_relation set lft=lft+2 where lft>=".$parent_node_info['rgt']);
            $this->db->query("update yq_relation set rgt=rgt+2 where rgt>=".$parent_node_info['rgt']);
            //新节点的左右值
            $new_node_lft = $parent_node_info['rgt'];
            $new_node_rgt = $new_node_lft + 1;
            $insert_info = array(
                'uid'   => $uid,
                'name'  => $name,
                'type'  => $type,
                'lft'   => $new_node_lft,
                'rgt'   => $new_node_rgt
            );

            $res = $this->db->insert('relation',$insert_info);
        }
        if($res){
            return true;
        }else{
            return false;
        }
    }

    public function get_group_info($uid){
        $res = $this->db->select('name')->from('group')
            ->where('id',$uid)
            ->get()->row_array();
        return $res;
    }

    public function get_user_info($uid){
        $res = $this->db->select('u.group_id,u.username,u.name,u.sex,up.pid,u.job')->from('user as u')
            ->join('user_privilege as up','up.uid ='.$uid,'left')
            ->where('u.id',$uid)
            ->get()->result_array();
        $arr = array(
            'group_id' => $res[0]['group_id'],
            'username' => $res[0]['username'],
            'name'     => $res[0]['name'],
            'sex'      => $res[0]['sex'],
            'job'      => $res[0]['job'],
            'pid'      => ""
        );
        foreach ($res as $one) {
            $arr['pid'] .= $one['pid'].",";
        }
        $arr['pid'] = substr($arr['pid'],0,strlen($arr['pid']) - 1);
        return $arr;
    }

    /**
     *更新用户信息
     *
     */
    public function update_user($data){
        $update_info = array(
            'id'      => $data['uid'],
            'password' => md5($data['password']),
            'sex'      => $data['sex'],
            'name'     => $data['name'],
            'job'      => $data['job'],
            'group_id' => $data['gid']
        );

        $pri_arr = explode(",",$data['privilege']);
        //开始事务
        $this->db->trans_begin();

        //更新user表
        $this->db->where('id',$data['uid']);
        $this->db->update('user',$update_info);

        //更新权限表
        //1、删除权限
        $this->db->delete('user_privilege',array('uid'=>$data['uid']));

        //2、添加权限
        foreach ($pri_arr as $one){
            $insert_pri = array(
                'pid' => $one,
                'uid' => $data['uid']
            );
            $this->db->insert('user_privilege',$insert_pri);
        }

        //更新关系表
        //先删除，再添加
        $this->db->delete('relation',array('uid'=>$data['uid'],'type' => 1));

        //插入组织结构
        $insert_data = array(
            'uid'   => $data['uid'],
            'name'  => $data['name'],
            'parent_id' => $data['gid'],
            'type'  => 1

        );
        $this->insert_children_node($insert_data);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }


    }

    /**
     * 更新单位信息
     */
    public function update_group($data){
        $update_info = array(
            'id'      => $data['uid'],
            'name'     => $data['name']
        );

        //更新group表
        $this->db->where('id',$data['uid']);
        $res1 = $this->db->update('group',$update_info);

        //更新关系表
        //先删除，再添加
        $this->db->delete('relation',array('uid'=>$data['uid'],'type' => 0));

        //插入组织结构
        $insert_data = array(
            'uid'   => $data['uid'],
            'name'  => $data['name'],
            'parent_id' => $data['new_group_id'],
            'type'  => 0
        );

        $res3 = $this->insert_children_node($insert_data);

        return $res1 && $res3;
    }


    /**
     * 删除节点
     */
    public function delete_node($id)
    {

        $node = $this->db->query("SELECT lft, rgt, rgt - lft + 1 AS width FROM yq_relation WHERE uid = $id")->first_row("array");
        $re1 = $this->db->query("DELETE FROM yq_relation WHERE lft BETWEEN ? AND ?", array($node['lft'], $node['rgt']));
        $re2 = $this->db->query("UPDATE yq_relation SET rgt = rgt - ? WHERE rgt > ?", array($node['width'], $node['rgt']));
        $re3 = $this->db->query("UPDATE yq_relation SET lft = lft - ? WHERE lft > ?", array($node['width'], $node['rgt']));

        return $re1&&$re2&&$re3;
    }


    /**获取用户登录日志
     * @param $pInfo
     * @return mixed
     */
    public function get_user_login_log($pInfo){
        $data['aaData'] = $this->db->select("log.id,u.name,g.name as group_name,log.ip,log.time")
            ->from('login_log AS log')
            ->join('user as u','u.id = log.uid')
            ->join('group as g','g.id = u.group_id')
            ->group_start()
            ->like('u.name',$pInfo['search'])
            ->or_like('g.name',$pInfo['search'])
            ->group_end()
            ->order_by('log.time',$pInfo['sort_type'])
            ->limit($pInfo['length'],$pInfo['start'])
            ->get()->result_array();

        $total = $this->db->select("log.id,u.name,g.name,log.ip,log.time")
            ->from('login_log AS log')
            ->join('user as u','u.id = log.uid')
            ->join('group as g','g.id = u.group_id')
            ->group_start()
            ->like('u.name',$pInfo['search'])
            ->or_like('g.name',$pInfo['search'])
            ->group_end()
            ->order_by('log.time',$pInfo['sort_type'])
            ->get()->num_rows();

        $data['sEcho'] = $pInfo['sEcho'];

        $data['iTotalDisplayRecords'] = $total;

        $data['iTotalRecords'] = $total;

        return $data;
    }

    /**
     * @param $uid
     * @param $username
     * @return bool
     * 用户名是否重复
     */
    public function username_is_repeat($uid,$username){
        $uid_row = $this->db->select('id')->from('user')
            ->where('username',$username)
            ->get()->row_array();
        if(empty($uid_row)){
            //没有重复
            return false;
        }else{
            $id = $uid_row['id'];
            if($uid != "" && $id == $uid){
                return false;
            }else{
                return true;
            }
        }
    }




}