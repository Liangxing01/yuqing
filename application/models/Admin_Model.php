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
     * 添加用户
     * @param $data
     */
    public function add_person($data){
        $userInfo = array(
            'username' => $data['username'],
            'password' => $data['password'] == '' ? md5('123456') : md5($data['password']),
            'name'     => $data['name'],
            'sex'      => $data['sex'],
            'job'      => $data['job'],
            "avatar"   => "/img/avatar/avatar.png",
            'is_exist' => 1
        );
        //开始事务
        $this->db->trans_begin();

        $this->db->insert('user',$userInfo);
        $uid = $this->db->insert_id();

        //插入 用户单位表，多单位
        $ug_query = array();
        foreach ($data['gid'] as $one){
            $one_query = array(
                'uid' => $uid,
                'gid' => $one,
                'is_exist' => 1
            );
            array_push($ug_query,$one_query);
        }
        $this->db->insert_batch('user_group',$ug_query);


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
        foreach ($data['gid'] as $gid){
            $insert_data = array(
                'uid'   => $uid,
                'name'  => $data['name'],
                'parent_id' => $gid,
                'type'  => 1

            );
            $this->insert_children_node($insert_data);
        }


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
            'type'  => $data['type']

        );
        $a = $this->insert_children_node($insert_data);

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
            ->group_start()
            ->where('type',0)
            //->or_where('type',2)
            ->group_end()
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

    /**
     * @param $uid
     * @return mixed
     * 获取组信息
     */
    public function get_group_info($uid){
        $res = $this->db->select('group.name,r.type')->from('group')
            ->where('group.id',$uid)
            ->join('relation as r','r.uid ='.$uid.' and type != 1')
            ->get()->row_array();
        return $res;
    }

    /**
     * @param $uid
     * @return array
     * 获取用户信息
     */
    public function get_user_info($uid){
        $res = $this->db->select('ug.gid as group_id, u.username, u.name, u.sex, u.job')->from('user as u')
            ->join('user_group as ug','ug.uid = '.$uid)
            ->where(array('u.id' => $uid, "ug.is_exist" => 1))
            ->get()->result_array();

        //获取组id
        $gids = array();
        foreach ($res as $one){
            array_push($gids,$one['group_id']);
        }
        //获取权限
        $privilege_res = $this->db->select("user.id , user_privilege.pid")
            ->join("user_privilege", "user_privilege.uid = user.id", "left")
            ->where("user.id", $uid)
            ->get("user")->result_array();
        $privileges = "";
        foreach ($privilege_res as $one) {
            $privileges .= $one['pid'].",";
        }
        $privileges = substr($privileges,0,strlen($privileges) - 1);

        $arr = array(
            'group_id' => $gids,
            'username' => $res[0]['username'],
            'name'     => $res[0]['name'],
            'sex'      => $res[0]['sex'],
            'job'      => $res[0]['job'],
            'pid'      => $privileges
        );
        return $arr;
    }

    /**
     * 算法：比较两个数组 返回 增加/删除的 字段
     */
    public function compare_array($arr1,$arr2){
        $add_arr = array_diff($arr1,$arr2);
        $del_arr = array_diff($arr2,$arr1);
        $res_arr = array(
            'add' => $add_arr,
            'del' => $del_arr
        );
        return $res_arr;
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
            'username' => $data['username']
            //'group_id' => $data['gid']
        );

        //如果密码为空，则保留原有密码
        if($data['password'] == ''){
            unset($update_info['password']);
        }

        $pri_arr = explode(",",$data['privilege']);
        //开始事务
        $this->db->trans_begin();

        //更新user表
        $this->db->where('id',$data['uid']);
        $this->db->update('user',$update_info);

        //更新用户单位表，先对比找出 不同的字段，进行更新
        $old_data = $this->db->select('gid, is_exist')
            ->from('user_group')
            ->where('uid',$data['uid'])
            ->get()->result_array();
        $old_gids =array();
        $is_exist = array(); //以前添加过的用户组
        foreach ($old_data as $old){
            if($old["is_exist"] == 1){
                array_push($old_gids,$old['gid']);
            }else{
                array_push($is_exist,$old['gid']);
            }
        }
        $diff_arr = $this->compare_array($data['gid'],$old_gids);

        //1、删除多余的 用户单位对应关系，更新 is_exist 字段为0
        if(!empty($diff_arr['del'])){
            foreach ($diff_arr['del'] as $del){
                $update_data = array(
                    'is_exist' => 0
                );
                $this->db->where('uid',$data['uid']);
                $this->db->where('gid',$del);
                $this->db->update('user_group',$update_data);
            }
        }

        //2、插入新的用户单位关系
        if(!empty($diff_arr['add'])){
            foreach ($diff_arr['add'] as $new){
                if(in_array($new, $is_exist)){
                    $this->db->where("gid", $new)->update("user_group", array("is_exist" => 1));
                }else{
                    $new_data = array(
                        'gid' => $new,
                        'uid' => $data['uid'],
                        'is_exist' => 1
                    );
                    $this->db->insert('user_group',$new_data);
                }
            }
        }


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
        foreach ($data['gid'] as $gid){
            $insert_data = array(
                'uid'   => $data['uid'],
                'name'  => $data['name'],
                'parent_id' => $gid,
                'type'  => 1

            );
            $this->insert_children_node($insert_data);
        }


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
            'name'    => $data['name']
        );

        $this->db->trans_begin();

        //更新group表
        $this->db->where('id',$data['uid']);
        $this->db->update('group',$update_info);

        //更新关系表
        $rel_update = array(
            'name'  => $data['name']
        );
        $this->db->where('id',$data['node_rel_id']);
        $this->db->update('relation',$rel_update);

        $this->load->model("Tree_Model", "tree");
        $this->tree->move_tree_node($data['parent_rel_id'], $data['node_rel_id']);



        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }

    /**
     * 删除用户
     * @param: uid
     */
    public function del_user($data){
        $uid = $data['uid'];

        //开始事务
        $this->db->trans_begin();

        //删除关系表数据
        $this->delete_node($data['rel_id']);

        //更新user_group表 is_exist 字段为0
        $up = array(
            'is_exist' => 0
        );
        $this->db->where('uid',$uid);
        $this->db->update('user_group',$up);

        //更新用户表 is_exist = 0
        $this->db->where('id',$uid);
        $this->db->update('user',$up);

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }

    }


    /**
     * 删除节点
     */
    public function delete_node($id)
    {

        $node = $this->db->query("SELECT lft, rgt, rgt - lft + 1 AS width FROM yq_relation WHERE id = $id AND `type` = 1")->first_row("array");
        $this->db->query("DELETE FROM yq_relation WHERE lft BETWEEN ? AND ?", array($node['lft'], $node['rgt']));
        $this->db->query("UPDATE yq_relation SET rgt = rgt - ? WHERE rgt > ?", array($node['width'], $node['rgt']));
        $this->db->query("UPDATE yq_relation SET lft = lft - ? WHERE lft > ?", array($node['width'], $node['rgt']));

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
            ->group_start()
            ->where('is_exist',1)
            ->or_where('is_exist is NULL')
            ->group_end()
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