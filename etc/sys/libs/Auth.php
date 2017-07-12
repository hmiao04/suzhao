<?php
/**
 * File: Auth.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-4
 * Time: 下午3:06
 * @Description
 *
 * 以下为数据库
-- ----------------------------
-- Table structure for ycus_auth_group
-- ----------------------------
DROP TABLE IF EXISTS `ycus_auth_group`;
CREATE TABLE `ycus_auth_group` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`title` char(100) NOT NULL DEFAULT '' COMMENT '用户组中文名称',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '为1正常，为0禁用',
`rules` char(80) NOT NULL DEFAULT '' COMMENT '用户组拥有的规则id',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户组表';

-- ----------------------------
-- Table structure for ycus_auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `ycus_auth_group_access`;
CREATE TABLE `ycus_auth_group_access` (
`uid` mediumint(8) unsigned NOT NULL COMMENT '用户id',
`group_id` mediumint(8) unsigned NOT NULL COMMENT '用户组id',
UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
KEY `uid` (`uid`),
KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户组明细表';

-- ----------------------------
-- Table structure for ycus_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `ycus_auth_rule`;
CREATE TABLE `ycus_auth_rule` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`name` char(80) NOT NULL DEFAULT '' COMMENT '规则唯一标识',
`title` char(20) NOT NULL DEFAULT '' COMMENT '规则中文名称',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
`condition` char(100) NOT NULL DEFAULT '' COMMENT '为空表示存在就验证，不为空表示按照条件验证',
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='规则表';
 */
class AuthType{
    const SESSION = 1;
    const DATABASE = 2;
}

class RuleType {
    const URL = 1;
    const BUTTON = 2;
}
class Auth
{
    public $authType = AuthType::DATABASE;
    /**
     * @var bool 忽略大小写
     */
    public $ignoreCase = true;
    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  int           认证用户的id
     * @param string type        执行check的模式
     * @param string mode        执行check的模式
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        $authList = $this->getAuthList($uid,$type); //获取用户需要验证的所有有效规则列表
        if (is_string($name)) {
            if ($this->ignoreCase) $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名
        if ($mode=='url') {
            $REQUEST = unserialize( strtolower(serialize($_REQUEST)) );
        }

        $requestUrl = YCFCore::getInstance()->getPath();
        foreach ( $authList as $auth ) {
            if($this->ignoreCase) $auth = strtolower($auth);
            $query = preg_replace('/^.+\?/U','',$auth);
            if ($mode=='url' && $query != $auth ) {
                parse_str($query,$param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST,$param);
                $auth = preg_replace('/\?.*$/U','',$auth);
                if ( in_array($auth,$name) && $intersect==$param ) {  //如果节点相符且url参数满足
                    $list[] = $auth ;
                }
            }else if (in_array($auth , $name)){
                $list[] = $auth;
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }
    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  uid int     用户id
     * @return array       用户所属的用户组 array(
     *     array('uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid])){
            return $groups[$uid];
        }
        $user_groups = DB()
            ->field('uid,group_id,title,rules')
            ->table('auth_group_access a')
            ->join(
                array(
                    '[><]auth_group#g' => array('a.group_id'=>'g.id')
                )
            )
            ->where(array('a.uid'=>$uid))
            ->where(array('g.status'=>1))
            ->select();
        $groups[$uid]=$user_groups?:array();
        return $groups[$uid];
    }
    private function getAuthList($uid,$type){
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',',(array)$type);
        if (isset($_authList[$uid.$t])) {
            return $_authList[$uid.$t];
        }
        if($this->authType == AuthType::SESSION && isset($_SESSION['_AUTH_LIST_'.$uid.$t])){ //如果是session验证
            return $_SESSION['_AUTH_LIST_'.$uid.$t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();//保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid.$t] = array();
            return array();
        }

        $map=array(
            'id'=>$ids,
            'type'=>$type,
            'status'=>1,
        );
        //读取用户组所有权限规则
        $rules = DB()
            ->table('auth_rule')
            ->where($map)
            ->field('`condition`,`name`')
            ->select();
        //循环规则，判断结果。
        $authList = array();   //
        foreach ($rules as $rule) {
            if ($rule['condition']) { //根据condition进行验证
                $user = $this->getUserInfo($uid);//获取用户信息,一维数组

                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
//                var_dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = $rule['name'];
            }
        }
        $_authList[$uid.$t] = $authList;
        if($this->authType==AuthType::SESSION){
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_'.$uid.$t]=$authList;
        }
        return array_unique($authList);
    }
    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid) {
        static $userInfo=array();
        if(!isset($userInfo[$uid])){
            $userInfo[$uid]=DB()
                ->where(array('uid'=>$uid))
                ->table('auth_rule')->get();
        }
        return $userInfo[$uid];
    }
}