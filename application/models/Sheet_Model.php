<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2017/1/10
 * Time: 11:47
 * 报表 模型
 */

class Sheet_Model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param $start 开始时间
     * @param $end   结束时间
     * 导出舆情类别表
     */
    public function export_type($start,$end){
        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();

        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("重庆巴南网信办")
            ->setLastModifiedBy("v6plus")
            ->setTitle("区委网信办网络台账类别表")
            ->setSubject("网络台账类别表")
            ->setDescription("网络台账类别表")
            ->setKeywords("网络台账类别表")
            ->setCategory("网络台账类别表");

        //拼接sql语句,判断有选择时间没有
        if(empty($start) || empty($end)){
            $where = "1=1";
        }else{
            $where = "info.time >= $start AND info.time <= $end";
        }

        $info_arr = $this->db->select("group.name AS 单位")
            ->select_sum("if(yq_info.type = 1, 1, 0)","交通类")
            ->select_sum("if(yq_info.type = 2, 1, 0)","城管类")
            ->select_sum("if(yq_info.type = 3, 1, 0)","环保类")
            ->select_sum("if(yq_info.type = 4, 1, 0)","医疗卫生类")
            ->select_sum("if(yq_info.type = 5, 1, 0)","征地拆迁类")
            ->select_sum("if(yq_info.type = 6, 1, 0)","官员作风类")
            ->select_sum("if(yq_info.type = 7, 1, 0)","小区环境类")
            ->select_sum("if(yq_info.type = 8, 1, 0)","违建类")
            ->select_sum("if(yq_info.type = 9, 1, 0)","房地产类")
            ->select_sum("if(yq_info.type = 10, 1, 0)","通讯信号类")
            ->select_sum("if(yq_info.type = 11, 1, 0)","涉法涉警类")
            ->select_sum("if(yq_info.type = 12, 1, 0)","教育类")
            ->select_sum("if(yq_info.type = 13, 1, 0)","社保类")
            ->select_sum("if(yq_info.type = 14, 1, 0)","安全事故类")
            ->select_sum("if(yq_info.type = 15, 1, 0)","重复帖")
            ->select_sum("if(yq_info.type = 16, 1, 0)","咨询类")
            ->select_sum("if(yq_info.type = 17, 1, 0)","建议类")
            ->select_sum("if(yq_info.type = 18, 1, 0)","讨薪类")
            ->select_sum("if(yq_info.type = 19, 1, 0)","消防安全类")
            ->select_sum("if(yq_info.type = 20, 1, 0)","水利类")
            ->select_sum("if(yq_info.type = 21, 1, 0)","突发类")
            ->select_sum("if(yq_info.type = 99, 1, 0)","其他")
            ->select_sum("if(yq_info.type = 100, 1, 0)","舆情平台")
            ->from('info')
            ->join('user_group','user_group.uid = info.publisher','left')
            ->join('group','group.id = user_group.gid','left')
            ->join('type','type.id = info.type','left')
            ->where('info.state',2)    //已确认信息
            ->where('info.duplicate',0)//不重复的信息
            ->where($where)
            ->group_by('group.id')
            ->get()->result_array();


        //设置 title
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '序号');
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $i = 66;
        foreach ($info_arr[0] as $k => $v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($i).'1', $k);
            $i++;
        }

        //填充内容
        for($i = 0; $i < count($info_arr); $i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($i+2), ($i+1));
            $a = 66;
            foreach ($info_arr[$i] as $k => $v){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr($a).($i+2), $v);
                $a++;
            }
        }
        $objPHPExcel->getActiveSheet()->setTitle('区委网信办网络台账类别表');


        //判断显示文件名
        if(!empty($start)){
            $time = date('Ymd',$start).'-'.date('Ymd',$end);
            header('Content-Disposition: attachment;filename="'.$time.'网络台账类别表'.'.xlsx"');
        }else{
            header('Content-Disposition: attachment;filename="'.'区委网信办网络台账类别表'.'.xlsx"');
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header("Content-Type: application/download");
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');
    }

    /**
     * @param $start
     * @param $end
     * 导出舆情台账
     */
    public function export_yuqing($start,$end){
        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();

        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("重庆巴南网信办")
            ->setLastModifiedBy("v6plus")
            ->setTitle("区委网信办网络舆情台账")
            ->setSubject("网络舆情台账")
            ->setDescription("网络舆情台账")
            ->setKeywords("网络舆情台账")
            ->setCategory("网络舆情台账");

        //拼接sql语句,判断有选择时间没有
        if(empty($start) || empty($end)){
            $where = "and 1=1";
        }else{
            $where = "and yq_event.start_time >= $start AND yq_event.start_time <= $end";
        }

        //设置 title
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '序号');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', '巡查单位');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', '发布时间');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', '发布平台');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', '标题');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', '链接');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G1', '责任单位');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H1', '涉及领域');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I1', '首接人');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J1', '交办时间');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K1', '首回时间');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L1', '办结时间');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M1', '关注量');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N1', '跟帖数');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O1', '原贴摘要');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P1', '办结回复摘要');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q1', '线上情况');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('R1', '线下情况');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('S1', '原文截图');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('T1', '备注');

        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);



        //查询数据库 获取 数据
        $info_arr = $this->db->query("select yq_event.id,yq_group.name as gname,yq_info.time as rep_time,yq_info.source,
yq_info.title,yq_info.url,yq_info.relate_scope, if((select yq_user.name from yq_user where yq_user.id = yq_event.`main_processor`)!= '',
(select yq_user.name from yq_user where yq_user.id = yq_event.`main_processor`),
(select yq_group.name from yq_group where yq_group.id = yq_event_designate.`group`)) as \"首接人\",
yq_event_designate.time as \"交办时间\",yq_event.first_reply,yq_event.`end_time`
from yq_event
left join yq_event_designate on yq_event.id = yq_event_designate.event_id 
left join yq_user_group on yq_user_group.`uid` = yq_event_designate.processor
left join yq_group on (yq_group.id = yq_user_group.`gid` or yq_group.id = yq_event_designate.`group`)
left join yq_event_info on yq_event_info.`event_id` = yq_event_designate.event_id
left join yq_info  on yq_event_info.`info_id` = yq_info.id
left join yq_user  on yq_user.id  = yq_event_designate.`processor`
where  yq_info.duplicate = 0 and ( yq_event.state = '已指派' or yq_event.state = '已完成' or yq_event.state = '待审核')
and (yq_event.main_processor = yq_event_designate.`processor` or yq_event.group = yq_event_designate.`group`)
" . $where)
        ->result_array();

        foreach ($info_arr as $k => $v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($k+2), $k+1);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($k+2), $v['gname']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($k+2), date('Y-m-d H:i:s',$v['rep_time']));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($k+2), $v['source']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($k+2), $v['title']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.($k+2), $v['url']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.($k+2), $v['首接人']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.($k+2), $v['relate_scope']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I'.($k+2), $v['首接人']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.($k+2), date('Y-m-d H:i:s',$v['交办时间']));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K'.($k+2), $v['first_reply'] ? date('Y-m-d H:i:s',$v['first_reply']) : '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L'.($k+2), $v['end_time'] ? date('Y-m-d H:i:s',$v['end_time']) :'');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('R'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('S'.($k+2), '');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('T'.($k+2), '');
        }

        $objPHPExcel->getActiveSheet()->setTitle('区委网信办网络舆情台账');


        //判断显示文件名
        if(!empty($start)){
            $time = date('Ymd',$start).'-'.date('Ymd',$end);
            header('Content-Disposition: attachment;filename="'.$time.'网络舆情台账'.'.xlsx"');
        }else{
            header('Content-Disposition: attachment;filename="'.'区委网信办网络舆情台账'.'.xlsx"');
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header("Content-Type: application/download");
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');

    }

    /**
     * 导出单一舆情 综合情况
     * 按勾选选项排序展示
     */
    public function export_one_event($data){
        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();

        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("重庆巴南网信办")
            ->setLastModifiedBy("v6plus")
            ->setTitle("网络舆情综合情况表")
            ->setSubject("网络舆情综合情况表")
            ->setDescription("网络舆情综合情况表")
            ->setKeywords("网络舆情综合情况表")
            ->setCategory("网络舆情综合情况表");

        $objPHPExcel->setActiveSheetIndex(0);

        // 所有单元格默认高度
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(45);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

        //设置默认字体 大小
        $objPHPExcel->getActiveSheet()->getStyle('A2:D14')->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->getStyle('A2:D14')->getFont()->setSize(12);
        $objPHPExcel->getActiveSheet()->getStyle('A2:D14')->getAlignment()->setWrapText(true);//自动换行

        //加边框
        $objPHPExcel->getActiveSheet()->getStyle("A2:D14")->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            )
        );

        //垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A2:D14')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:D14')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //设置部分空格 向左对齐
        $objPHPExcel->getActiveSheet()->getStyle('B9')->getAlignment()->setHorizontal();
        $objPHPExcel->getActiveSheet()->getStyle('B13')->getAlignment()->setHorizontal();
        $objPHPExcel->getActiveSheet()->getStyle('B7')->getAlignment()->setHorizontal();
        //合并单元格 填写标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '网络舆情综合情况表');
        //设置加粗 居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('宋体');
        $objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        /**
         * 根据 eid 查询数据库信息，然后填充表格
         */
        $event_info = $this->db->query("select yq_event.id,yq_event.title,yq_event.start_time as time,if(yq_event.main_processor != '',
        (select yq_user.name from yq_user where yq_user.id = yq_event.`main_processor`),
        (select yq_group.name from yq_group where yq_group.id = yq_event.`group`)) as host_person,
        if(yq_event.group != '',(select yq_group.name from yq_group where yq_group.id = yq_event.`group`),
        (select yq_group.name FROM yq_group join yq_event on yq_event.id = ".$data['eid']."
        join yq_user_group on yq_user_group.uid = yq_event.main_processor 
        where yq_group.id = yq_user_group.gid)) 
        as host_group,
        yq_event.first_reply,yq_event.end_time from yq_event
        where yq_event.id = ".$data['eid'])->row_array();


        $objPHPExcel->getActiveSheet()->setTitle($event_info['title']);
        // 填写舆情标题
        $objPHPExcel->getActiveSheet()->mergeCells('B2:D2');
        $objPHPExcel->getActiveSheet()->setCellValue('A2', '舆情标题');
        $objPHPExcel->getActiveSheet()->setCellValue('B2', $event_info['title']);

        //填写时间
        $objPHPExcel->getActiveSheet()->setCellValue('A3', '时间');
        $objPHPExcel->getActiveSheet()->setCellValue('B3', $event_info['time'] ? date("Y-m-d H:i:s",$event_info['time']) : '无');

        //填写地点
        $objPHPExcel->getActiveSheet()->setCellValue('C3', '地点');
        $objPHPExcel->getActiveSheet()->setCellValue('D3', '');

        //承办单位
        $objPHPExcel->getActiveSheet()->setCellValue('A4', '承办单位');
        $objPHPExcel->getActiveSheet()->setCellValue('B4', $event_info['host_group']);

        //承办人
        $objPHPExcel->getActiveSheet()->setCellValue('C4', '承办人');
        $objPHPExcel->getActiveSheet()->setCellValue('D4', $event_info['host_person']);

        //首回时间
        $objPHPExcel->getActiveSheet()->setCellValue('A5', '首回时间');
        $objPHPExcel->getActiveSheet()->setCellValue('B5', $event_info['first_reply'] ? date("Y-m-d H:i:s",$event_info['first_reply']) : '无');

        //办结时间
        $objPHPExcel->getActiveSheet()->setCellValue('C5', '办结时间');
        $objPHPExcel->getActiveSheet()->setCellValue('D5', $event_info['end_time'] ? date("Y-m-d H:i:s",$event_info['end_time']) : '无');

        //办结回复
        $objPHPExcel->getActiveSheet()->setCellValue('A6', '办结回复');
        $objPHPExcel->getActiveSheet()->setCellValue('B6', '');

        /**
         * 联表查询 一个事件对应的 所有上报信息
         */
        $info_list = $this->db->select("info.description,info.url")->from('event')
            ->join('event_info','event_info.event_id = event.id')
            ->join('info','info.id = event_info.info_id')
            ->where('event.id',$data['eid'])
            ->get()->result_array();

        //拼接摘要和网络链接
        $des = "";
        $url = "";
        foreach ($info_list as $k => $item){
            $des .= "$k:".$item['description']."\n";
            $url .= "$k:".$item['url']."\n";
        }

        //原贴摘要
        $objPHPExcel->getActiveSheet()->mergeCells('B7:D7');
        $objPHPExcel->getActiveSheet()->setCellValue('A7', '原贴摘要');
        $objPHPExcel->getActiveSheet()->setCellValue('B7', $des);

        //原文截图
        $objPHPExcel->getActiveSheet()->mergeCells('B8:D8');
        $objPHPExcel->getActiveSheet()->setCellValue('A8', '原文截图');
        $objPHPExcel->getActiveSheet()->setCellValue('B8', '');

        //始发网络链接
        $objPHPExcel->getActiveSheet()->setCellValue('A9', '始发网络链接');
        $objPHPExcel->getActiveSheet()->setCellValue('B9', $url);

        //舆情截图
        $objPHPExcel->getActiveSheet()->setCellValue('C9', '舆情截图');
        $objPHPExcel->getActiveSheet()->setCellValue('D9', '');

        //转发情况
        $objPHPExcel->getActiveSheet()->setCellValue('A10', '转发情况(含转发链接及时间)');
        $objPHPExcel->getActiveSheet()->setCellValue('B10', '');

        //转发数量统计
        $objPHPExcel->getActiveSheet()->setCellValue('C10', '转发数量统计');
        $objPHPExcel->getActiveSheet()->setCellValue('D10', '');

        //转发平台统计
        $objPHPExcel->getActiveSheet()->mergeCells('B11:D11');
        $objPHPExcel->getActiveSheet()->setCellValue('A11', '转发平台统计');
        $objPHPExcel->getActiveSheet()->setCellValue('B11', '');

        //评论数量及趋势
        $objPHPExcel->getActiveSheet()->mergeCells('B12:D12');
        $objPHPExcel->getActiveSheet()->setCellValue('A12', '评论数量及趋势');
        $objPHPExcel->getActiveSheet()->setCellValue('B12', '');

        /**
         * 检索查询 交互日志
         */
        $log = $this->db->select("l.description,l.pid,l.id,l.time,user.name,l.speaker")
            ->from('event_log AS l')
            ->where('l.event_id', $data['eid'])
            ->join('user','l.speaker = user.id')
            ->order_by('time', 'DESC')->get()
            ->result_array();
        //var_dump($log);
        //拼接日志 形成 "时间 某某某:XXXX"
        $format_log = "";
        foreach ($log as $item){
            $format_log .= date("Y-m-d",$item['time'])." ".$item['name'].":".$item['description']."\n";
        }
        //处置情况
        $objPHPExcel->getActiveSheet()->mergeCells('A13:A14');
        $objPHPExcel->getActiveSheet()->mergeCells('B13:D14');
        $objPHPExcel->getActiveSheet()->setCellValue('A13', '处置情况');
        $objPHPExcel->getActiveSheet()->setCellValue('B13', $format_log);



        header('Content-Disposition: attachment;filename="'.$event_info['title'].'--舆情综合情况表'.'.xlsx"');

        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header("Content-Type: application/download");
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');
    }
}