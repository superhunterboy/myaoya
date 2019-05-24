<?php

namespace Weiming\Controllers;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Weiming\Controllers\BaseController;
use \Weiming\Models\ReportArtificialDeposit;
use \Weiming\Models\ReportPayCompany;
use \Weiming\Models\ReportPayOnline;

class DataController extends BaseController
{
    private $getDatas    = array();
    private $dataTimeArr = array();
    private $dataNumArr  = array();
    private $aTimeIntval = array();

    public function __construct()
    {
        // BBIN 后台美东时间
        date_default_timezone_set('America/New_York');
    }
    // 需求：1.拉取每天所有支付种类成功数据，将成功支付总金额按时间划分（5分钟，15分钟，30分钟，1小时）
    // 需求：2.拉取7天的数据做数据对比 近2天对比 近3天对比 近1周数据对比 按时间划分（5分钟，15分钟，30分钟，1小时）

    /**
     * 获取数据
     */
    public function getChartData(Request $request, Response $response, $args)
    {
        $returnData = $request->getQueryParams();
        $days       = $returnData['days'];
        $mins       = $returnData['mins'];
        if (isset($days)) {
            $dayDataArr = $this->contrastData($days, $mins);
        } else {
            $dayDataArr = $this->contrastData(1, $mins);
        }
        return $response->withJson([
            'status' => 0,
            'msg'    => '查询成功',
            'data'   => $dayDataArr,
        ]);
    }

    /**
     * 获取对比数据
     */
    public function contrastData($days, $mins)
    {
        $s          = strtotime(date('Y-m-d 00:00:00'));
        $dayDataArr = array();
        if ($days != 1) {
            for ($i = 0; $i < $days; $i++) {
                $day                          = $s - (86400) * $i;
                $dayDataArr['day' . ($i + 1)] = $this->getAllData($day, $mins);
            }
        } else {
            $hourCount  = date("H");
            $count      = floor(date("i") / $mins);
            $hourCount  = $count + ($hourCount * (60 / $mins));
            $dayDataArr = $this->getDayData($s, $mins, $hourCount);
        }
        return $dayDataArr;
    }

    public function getDayData($time, $interval, $count)
    {
        $this->dataArr     = array();
        $this->aTimeIntval = array();
        $end               = $time + 86400;
        for ($i = $time; $i <= $end; $i = $i + (60 * $interval)) {
            $dateStr = date('H:i:s', $i);
            if (strlen($dateStr) > 7) {
                $this->aTimeIntval[$dateStr] = 0;
            }
        }
        $date                    = date('Y-m-d', $time);
        $reportPayOnline         = ReportPayOnline::where('time', 'like', $date . '%')->get()->toArray();
        $reportPayCompany        = ReportPayCompany::where('operation_datetime', 'like', $date . '%')->get()->toArray();
        $reportArtificialDeposit = ReportArtificialDeposit::where('time', 'like', $date . '%')->get()->toArray();

        foreach ($reportArtificialDeposit as $key => $value) {
            $min = substr($value['time'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['time'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        foreach ($reportPayCompany as $key => $value) {
            $min = substr($value['operation_datetime'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['operation_datetime'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        foreach ($reportPayOnline as $key => $value) {
            $min = substr($value['time'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['time'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        $keyArr            = array_keys($this->aTimeIntval);
        $valueArr          = array_values($this->aTimeIntval);
        $this->dataTimeArr = array();
        $this->dataNumArr  = array();

        if ($interval == 30) {
            $keyArr   = array_chunk($keyArr, 24);
            $valueArr = array_chunk($valueArr, 24);
            if (date("H") > 12) {
                foreach ($keyArr[1] as $key => $value) {
                    array_push($this->dataTimeArr, $value);
                }
                foreach ($valueArr[1] as $key => $value) {
                    array_push($this->dataNumArr, $value);
                }
            } else {
                foreach ($keyArr[0] as $key => $value) {
                    array_push($this->dataTimeArr, $value);
                }
                foreach ($valueArr[0] as $key => $value) {
                    array_push($this->dataNumArr, $value);
                }
            }
        } else {
            foreach ($keyArr as $key => $value) {
                array_push($this->dataTimeArr, $value);
            }

            foreach ($valueArr as $key => $value) {
                array_push($this->dataNumArr, $value);
            }
        }

        foreach ($keyArr as $key => $value) {
            array_push($this->dataTimeArr, $value);
        }

        foreach ($valueArr as $key => $value) {
            array_push($this->dataNumArr, $value);
        }

        $this->dataArr = [
            'time' => array_slice($this->dataTimeArr, 0, $count),
            'num'  => array_slice($this->dataNumArr, 0, $count),
        ];
        return $this->dataArr;
    }

    public function getAllData($time, $interval)
    {
        $this->dataArr     = array();
        $this->aTimeIntval = array();
        $end               = $time + 86400;
        for ($i = $time; $i < $end; $i = $i + (60 * $interval)) {
            $dateStr = date('H:i:s', $i);
            if (strlen($dateStr) > 7) {
                $this->aTimeIntval[$dateStr] = 0;
            }
        }
        $date                    = date('Y-m-d', $time);
        $reportPayOnline         = ReportPayOnline::where('time', 'like', $date . '%')->get()->toArray();
        $reportPayCompany        = ReportPayCompany::where('operation_datetime', 'like', $date . '%')->get()->toArray();
        $reportArtificialDeposit = ReportArtificialDeposit::where('time', 'like', $date . '%')->get()->toArray();

        foreach ($reportArtificialDeposit as $key => $value) {
            $min = substr($value['time'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['time'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        foreach ($reportPayCompany as $key => $value) {
            $min = substr($value['operation_datetime'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['operation_datetime'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        foreach ($reportPayOnline as $key => $value) {
            $min = substr($value['time'], 14, 2);
            $min = $interval * floor($min / $interval);
            if ($min == 0) {
                $min = '00';
            } elseif ($min == 5) {
                $min = '05';
            }
            $hour = substr($value['time'], 11, 2);
            $str  = $hour . ":" . $min . ":" . "00";
            $this->aTimeIntval[date($str)] += 1;
        }

        $keyArr            = array_keys($this->aTimeIntval);
        $valueArr          = array_values($this->aTimeIntval);
        $this->dataTimeArr = array();
        $this->dataNumArr  = array();

        if ($interval == 30) {
            $cnTime   = date("H");
            $keyArr   = array_chunk($keyArr, 24);
            $valueArr = array_chunk($valueArr, 24);
            if (24 > $cnTime && $cnTime > 11) {
                foreach ($keyArr[1] as $key => $value) {
                    array_push($this->dataTimeArr, $value);
                }
                foreach ($valueArr[1] as $key => $value) {
                    array_push($this->dataNumArr, $value);
                }
            } else {
                foreach ($keyArr[0] as $key => $value) {
                    array_push($this->dataTimeArr, $value);
                }
                foreach ($valueArr[0] as $key => $value) {
                    array_push($this->dataNumArr, $value);
                }
            }
        } else {
            foreach ($keyArr as $key => $value) {
                array_push($this->dataTimeArr, $value);
            }

            foreach ($valueArr as $key => $value) {
                array_push($this->dataNumArr, $value);
            }
        }

        $this->dataArr = [
            'day'  => $date,
            'time' => $this->dataTimeArr,
            'num'  => $this->dataNumArr,
        ];
        return $this->dataArr;
    }
}
