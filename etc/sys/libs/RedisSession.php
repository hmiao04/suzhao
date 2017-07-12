<?php

/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/24
 * Time: 下午4:05
 */
class RedisSession implements SessionHandlerInterface
{
    /**
     * @var Redis
     */
    private $redis = null;
    /**
     * 保存session的数据库表的信息
     */
    private $_options = array(
        'handler' => null, //连接句柄
        'host' => null,
        'port' => null,
        'lifeTime' => 30,
    );
    public function __construct($options = array())
    {
        if(!class_exists("redis", false)){
            die("必须安装redis扩展");
        }
        if(!isset($options['lifeTime']) || $options['lifeTime'] <= 0){
            $options['lifeTime'] = 30;
        }
        $this->_options = array_merge($this->_options, $options);
    }
    /**
     * 开始使用该驱动的session
     */
    public function begin(){
        if($this->_options['host'] === null ||
            $this->_options['port'] === null ||
            $this->_options['lifeTime'] === null
        ){
            return false;
        }

        //设置session处理函数
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close()
    {
        $this->redis->close();
        $this->redis = null;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id)
    {
        if(!$session_id)return true;
        return $this->redis->delete($session_id) >= 1 ? true : false;
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $session_id The session id.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $session_id)
    {
        if($this->redis && is_resource($this->redis)) return true;
        //连接redis
        $redisHandle = new Redis();
        $redisHandle->connect($this->_options['host'], $this->_options['port']);
        if(!$redisHandle){
            return false;
        }
        $this->_options['handler'] = $redisHandle;
        $this->redis = $redisHandle;
        $this->gc(null);
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id)
    {
        $this->redis->get($session_id);
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data)
    {
        $this->redis->setex($session_id, $this->_options['lifeTime'], $session_data);
    }
}