<?php
/**
 * File: Model.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016/4/10 21:47
 */

class ModelInfo extends YC{
    static $PRIMARY_KEY = 1;
}
abstract class Model extends YC
{
    private $primaryKey = null;
    private $tableName = null;
    private $_arrayData = null;
    private $ignore = array(
        'lastInsertId','ignore','extraData'
    );
    public $lastInsertId = 0;
    public $extraData = array();

    public function __construct(){
        $this->getTableName();
    }

    public function __toString()
    {

        return get_class($this).'@'.md5(serialize($this));
    }

    /**
     * @var \Model
     */
    private static $model_instance = null;
    public static function TableName($model)
    {
        if(Cache::getInstance()->exists($model) == false){
            $ref = new ReflectionClass($model);
            Cache::getInstance()->set($model,$ref->newInstance());
        }
        self::$model_instance = Cache::getInstance()->get($model);
        return self::$model_instance->tableName;
    }

    public function getTableName($model = null){
        if(null != $model){
            return self::TableName($model);
        }
        if(null == $this->tableName){
            $this->tableName = get_class($this);
            if(strpos($this->tableName,'\\')){
                $classInfo = explode('\\',$this->tableName);
                $this->tableName = $classInfo[count($classInfo)-1];
            }
        }
        return $this->tableName;
    }

    protected function setPrimaryKey($key){
        $this->primaryKey = $key;
    }
    protected function setTableName($name){
        $this->tableName = $name;
    }

    /**
     * 新增数据
     * @param bool|false $savePrimary
     * @return $this
     * @throws AppException
     */
    public function insert($savePrimary = false)
    {
        $saveData =$this->getNotNullArray();
        if($this->primaryKey && false == $savePrimary){
            unset($saveData[$this->primaryKey]);
        }
        $data = DB()->insert($this->getTableName(),$saveData);
        $this->lastInsertId = $data;
        return $this;
    }

    /**
     * 更新数据
     * @param array $updateWhere
     * @param array $saveData
     * @return $this|bool
     */
    public function update($updateWhere = null,$saveData = null)
    {
        if($saveData == null) $saveData = $this->getNotNullArray();
        if(!$this->primaryKey){
            return false;
        }
        if($updateWhere == null){
            $updateWhere = array();
            $updateWhere[$this->primaryKey] = $saveData[$this->primaryKey];
        }
        if(isset($saveData[$this->primaryKey])){
            unset($saveData[$this->primaryKey]);
        }
        DB()->update($this->tableName,$saveData,$updateWhere);
        return $this;
    }

    /**
     * @return DBCore
     */
    public function createQuery(){
        return DB()->table($this->getTableName());
    }
    public function queryInfo(){
        return DB()->all_queryItem();
    }

    /**
     * 删除数据
     * @param array $condition
     * @return mixed
     * @throws AppException
     */
    public function delete($condition = null)
    {
        if(null == $condition){
            $condition = $this->getNotNullArray();
        }
        return DB()->delete($this->getTableName(),$condition);
    }
    /**
     * 查询符合条件的所有数据
     */
    public function findConditionAll()
    {
        $dataArray = DB()->where($this->getNotNullArray())->select($this->getTableName());
        return $dataArray;
    }

    /**
     * @var DBCore
     */
    private $queryObject = null;

    public function condition($condition){
        $this->initQueryObject();
        $this->queryObject->where($condition);
        return $this;
    }
    public function orderBy($orderBy){
        $this->initQueryObject();
        $this->queryObject->orderBy($orderBy);
        return $this;
    }
    public function limit($li){
        $this->initQueryObject();
        call_user_func_array(array($this->queryObject,'limit'),func_get_args());
        return $this;
    }
    public function exists($condition = null){
        if(null == $condition) $condition = $this->getNotNullArray();
        $this->initQueryObject();
        $this->queryObject->where($condition);
        $this->queryObject->table($this->getTableName());
        $data = $this->queryObject->get();
        $this->queryObject = null;
        return $data !== false;
    }
    public function select(){
        $this->initQueryObject();
        $this->queryObject->where($this->getNotNullArray());
        $this->queryObject->table($this->getTableName());
        $dataArray = $this->queryObject->select();
        $this->queryObject = null;
        return $dataArray;
    }
    public function getOne(){
        $this->initQueryObject();
        $this->queryObject->where($this->getNotNullArray());
        $this->queryObject->table($this->getTableName());
        $data = $this->queryObject->get();
        $this->queryObject = null;
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        return $this;
    }
    private function initQueryObject(){
        if($this->queryObject == null){
            $this->queryObject = DB();
        }
    }
    /**
     * 查询所有数据
     */
    public function findAll()
    {
        $dataArray = DB()->select($this->getTableName());
        return $dataArray;
    }

    /**
     * 查询数据条数
     * @param array $condition
     * @return int
     */
    public function count($condition = null){
        if(null == $condition) $condition = $this->getNotNullArray();
        $query = $this->getQuery()->where($condition);
        return $query->field('1')->count($this->getQueryTableName($query));
    }

    /**
     * @param \DBCore $query
     * @return null|string
     */
    public function getQueryTableName($query){
        $tableName = $query->getTableName();
        return $tableName ? $tableName : $this->getTableName();
    }

    public function getQuery(){
        return DB()->table($this->getTableName());
    }
    /**
     * 根据条件查询列表数据
     * @param array $condition
     * @param null $limit
     * @param null $orderBy
     * @return array|bool
     */
    public function findByCondition($condition = null,$limit = null,$orderBy = null){
        if(null == $condition) $condition = $this->getNotNullArray();
        $query = $this->getQuery()->where($condition);
        if($limit) $query->limit($limit);
        if($orderBy) $query->orderBy($orderBy);
        return $query->select($this->getQueryTableName($query));
    }

    /**
     * 查询单个对象
     * @param array $condition
     * @return $this|null
     * @throws AppException
     */
    public function find($condition = null)
    {
        if(null == $condition) $condition = $this->getNotNullArray();
        $data = DB()->where($condition)->get($this->getTableName());
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        return $this;
    }

    private function getPropValue($propertyName){
        $varValue = get_object_vars($this);
        if(isset($varValue[$propertyName])){
            return $varValue[$propertyName];
        }
        return null;
    }

    /**
     * @param null $objectId
     * @return $this|null
     * @throws AppException
     */
    public function findByPrimary($objectId = null){
        if(!$this->primaryKey) return null;
        $where = array();
        if($objectId) {
            $where[$this->primaryKey] = $objectId;
        }
        else {
            $where[$this->primaryKey] = $this->getPropValue($this->primaryKey);
        }
        $data = DB()->where($where)->get($this->getTableName());
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        return $this;
    }

    /**
     * 根据SQL语句查询对象
     * @param $querySQL
     * @return $this|null
     */
    public function findByQuery($querySQL){
        $data = DB()->fetch($querySQL);
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        return $this;
    }
    /**
     * 根据SQL语句查询对象
     * @param $querySQL
     * @return array|null
     */
    public function findListByQuery($querySQL){
        return DB()->fetchAll($querySQL);
    }

    public function setPropertyValue($k,$v){
        $obj = new \ReflectionObject($this);
        $p = $obj->getProperty($k);
        if($p)$p->setValue($this,$v);
        return $this;
    }
    public function setProperty(array $dataArray){
        $obj = new \ReflectionObject($this);

        foreach($dataArray as $k=>$v){
            if(!$k || !is_string($k)) continue;
            if($obj->hasProperty($k)){
                $obj->getProperty($k)->setValue($this,$v);
            }
        }
    }

    public function getNotNullArray(){
        $data = array();
        foreach($this->toArray() as $k=>$v){
            if(in_array($k,$this->ignore)) continue;
            if($v || $v === 0 || $v === '0'){
                if(strtolower(substr($v,0,4)) == '[!=]'){
                    $data[$k.'[!]'] = substr($v,4);
                }else{
                    $data[$k] = $v;
                }
            }
        }
        return $data;
    }

    public function toArray(){
//        if(null != $this->_arrayData) return $this->_arrayData;
        $obj = new \ReflectionObject($this);

        $varValue = get_object_vars($this);
        $vars = $obj->getProperties();
        foreach($vars as $p){
            $this->_arrayData[$p->getName()] = $varValue[$p->getName()];
        }
        return $this->_arrayData;
    }
}