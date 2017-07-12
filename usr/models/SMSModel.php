<?php
/**
 * File: SMSModel.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-30 3:46
 */

namespace Models;


class SMSModel extends \Model
{
    public $id;
    public $sms_phone;
    public $sms_content;
    public $sms_type;
    public $send_time;
    /**
     * @var int 1:正常；2:已验证;-1:已删除
     */
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('yc_sms_list');
    }

    /**
     * @param null $condition
     * @param null $orderBy
     * @return \Models\SMSModel|null
     */
    public function findData($condition = null,$orderBy = null)
    {
        if(null == $condition) $condition = $this->getNotNullArray();
        $query = $this->getQuery()->where($condition);
        if($orderBy) $query->orderBy($orderBy);
        $data = $query->get($this->getTableName());
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        return $this;
    }
}