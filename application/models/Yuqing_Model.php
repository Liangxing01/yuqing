<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2016/12/14
 * Time: 11:31
 * 链接 同步舆情的 mongodb数据库
 */

class Yuqing_Model extends CI_Model {
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->library(array("Mongo_db" => "mongo"));
    }

    public function test1(){
        $a = $this->mongo->get('rawdata');
        var_dump($a);
    }
}