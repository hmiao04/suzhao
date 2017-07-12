<?php
/**
 * File: BillModel.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-18 19:14
 */

namespace Models;


class BillModel extends \Model
{
    public $bill_sn;
    public $bill_type;
    public $member_id;
    public $bill_title;
    public $bill_data;
    public $pay_money;
    public $pay_status;
    public $pay_way;
    public $pay_way_sn;
    public $create_time;
    public $paid_time;
    public $remarks;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('bill_sn');
        $this->setTableName('sz_bills');
    }
}