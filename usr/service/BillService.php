<?php
/**
 * File: BillService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 11:08
 */

namespace Service;


use Models\BillData;
use Models\BillModel;
use Models\DataStatus;

class BillService
{
    /**
     * @var \Models\BillModel
     */
    private $billModel;

    public function __construct()
    {
        $this->billModel = new BillModel();
    }

    /**
     * @param $typeId
     * @return \Models\BillModel
     */
    public function getLastBillDataByType($typeId)
    {
        $pay_status = $this->billModel->pay_status;
        $condition = [
            'bill_type' => $typeId,
            'pay_status' => BillData::$STATUS_UN_PAY,
            'status' => DataStatus::NORMAL
        ];

        $data=  $this->billModel->find($condition, [0, 1], ['create_time DESC']);
        return $data;
    }

}