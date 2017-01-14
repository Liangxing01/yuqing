<?php
/**
 * Created by PhpStorm.
 * User: maskerj
 * Date: 2017/1/9
 * Time: 16:46
 * 报表功能
 */

class Report_sheet extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Sheet_Model','sheet');
    }

    public function test(){
        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();

        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("转弯的阳光")
            ->setLastModifiedBy("转弯的阳光")
            ->setTitle("数据EXCEL导出")
            ->setSubject("数据EXCEL导出")
            ->setDescription("备份数据")
            ->setKeywords("excel")
            ->setCategory("result file");

        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        $objPHPExcel->setActiveSheetIndex(0)
                //Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A1', 'h哈哈哈哈阿士大夫撒旦法士大夫撒旦法')
                ->setCellValue('B1', '1231')
                ->setCellValue('C1', '123');

        $objPHPExcel->getActiveSheet()->setTitle('User');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.'test'.'.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Type: application/download");
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');

    }

    /**
     * 展示 报表导出功能页面
     */
    public function export_sheet(){
        $this->assign("active_title", "export_sheet");
        $this->assign("active_parent", "sheet_parent");
        $this->all_display("sheet/export_sheet.html");
    }

    /**
     * 导出 网络台账类别表
     */
    public function export_types(){
        $start = $this->input->get('start');
        $end   = $this->input->get('end');
        if(!empty($start) || !empty($end)){
            if(!is_numeric($start) || !is_numeric($end)){
                echo 'error';
                return;
            }
        }
        //导出报表
        $this->sheet->export_type((int)$start,(int)$end);
    }

    /**
     * 导出网络舆情台账
     */
    public function export_yuqing(){
        $start = $this->input->get('start');
        $end   = $this->input->get('end');
        if(!empty($start) || !empty($end)){
            if(!is_numeric($start) || !is_numeric($end)){
                echo 'error';
                return;
            }
        }
        //导出报表
        $this->sheet->export_yuqing((int)$start,(int)$end);
    }
}