<?php

/**
 * File: SessionHandler.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-09 2:14
 */
class YCSessionHandler extends YC implements SessionHandlerInterface
{
    /**
     * @var int 失效时间
     */
    private $expire_time = 30;
    private $table_name = 'yc_session';
    private $session_id = '';
    private $table_exists = false;

    /**
     * @var \DBCore
     */
    private $db = null;

    public function __construct($table_name = 'yc_session', $expire_time = 30)
    {
        $this->db = DB();
        $this->table_exists = $this->db->table_exists($table_name);
        if(false == $this->table_exists) {
            throw new AppException("Session save table '$table_name' not exists!");
        }
        $this->expire_time = $expire_time;
        $this->table_name = $table_name;
        Logger::getLogger()->debug('yc session inited');
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
        return true;
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
        $this->db->delete($this->table_name, array('session_id' => $session_id));
        return true;
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
        $this->db->delete($this->table_name, array('last_access[<]' => REQ_TIME - $this->expire_time * 60));
        return true;
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
        $this->session_id = $session_id;
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
        $sessionData = null;
        try {
            $sessionData = $this->db->get($this->table_name,null, '*',array(
                'AND ' => array(
                    'session_id' => $session_id,
                    'last_access[>=]' => REQ_TIME - $this->expire_time * 60
                )
            ));
            if ($sessionData) {
                $this->db->update($this->table_name, array('last_access' => REQ_TIME), array('session_id' => $session_id));
                $sessionData['user_data'] = $sessionData['user_data']?base64_decode($sessionData['user_data']):null;
                $sessionData = $sessionData['user_data'] ? unserialize($sessionData['user_data']) : null;
            }
        } catch (Exception $e) {
            throw new AppException($e);
        }
        return $sessionData;
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
        try{
//            file_put_contents('tmp.txt',print_r($session_data,1),FILE_APPEND);
            Logger::getLogger()->debug('yc session write');
            $session_data = array(
                'session_id' => $session_id,
                'last_access' => REQ_TIME,
                'user_data' => base64_encode(serialize($session_data)),
                'ip' => getClientIP(),
                'browser' => $_SERVER['HTTP_USER_AGENT']
            );
            $sessionData = $this->db->get($this->table_name,null, '*', array('session_id' => $session_id));
            if ($sessionData) {
                unset($session_data['session_id']);
                $this->db->update($this->table_name, $session_data, array('session_id' => $session_id));
            } else {
                $this->db->insert($this->table_name, $session_data);
            }
        } catch (Exception $e) {
//            print_r(debug_backtrace());
        }
        return true;
    }
}