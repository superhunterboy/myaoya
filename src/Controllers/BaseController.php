<?php

namespace Weiming\Controllers;

use \Interop\Container\ContainerInterface;
use \PHPExcel;
use \PHPExcel_Cell_DataType;
use \PHPExcel_IOFactory;

class BaseController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;

    }

    public function __get($val)
    {

        return $this->container->{$val};

    }

    /**
     * 生成Excel表格
     * @param  array  $header 表头
     * @param  array  $datas  表数据
     */
    public function downExcel($header = [], $datas = [], $filename = '')
    {
        if (empty($filename)) {
            $filename = date("YmdHis") . '.xls';
        }
        $excel    = new PHPExcel();
        // 表头
        $letters = range('A', 'Z');
        foreach ($header as $key => $field) {
            $letter = array_shift($letters);
            $excel->getActiveSheet()->setCellValue($letter . '1', $field);
        }
        // 数据
        foreach ($datas as $key => $data) {
            $i       = $key + 2;
            $letters = range('A', 'Z');
            foreach ($data as $val) {
                $letter = array_shift($letters);
                $excel->getActiveSheet()->setCellValueExplicit($letter . $i, $val, PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        // 标签标题
        $excel->getActiveSheet()->setTitle(substr($filename, 0, -4));
        // 请求头
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        // 输出内容
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $objWriter->save('php://output');
    }
}
