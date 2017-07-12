<?php
/**
 * File: YCFTestor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016/4/10 22:33
 */
namespace Controller;

use Lib\WebController;
use Models\MemberCertification;
use Models\MemberModel;

class Home extends WebController{

    public function init()
    {
        //$this->addRoute('/','TestIndex');
//		$this->addRoute('/account/','login');
		$this->addRoute('/account/register.html','register');
		$this->addRoute('/account/user.logout','logout');
        $this->addRoute('/table.model.{name}','dbModel');
        $this->addRoute('/ses',function(){
            $_SESSION['s'] = $_COOKIE;
            if(isset($_SESSION['s'])){
                print_r($_SESSION);
            }else{
                $_SESSION['t'] = REQ_TIME;
            }
        });
    }
	public function login(){
		$this->render('login',array('login'=>true));
	}
	public function register(){
		$this->render('login');
	}
	public function logout(){
		\cEvent::logout();
	}
    public function dbModel($name)
    {
        $just_model = false;
        if(isset($_GET['js'])) $just_model = true;
        $sql = "show full columns from `{$name}`";
        $columns = DB()->fetchAll($sql);
        foreach($columns as $c){
            echo ($just_model ? $c['Field'].':\'\',':'public $'.$c['Field'].';')."\n";
        }
        if($just_model) die();
        echo '
public function __construct()
{
    $this->setPrimaryKey(\'id\');
    $this->setTableName(\''.$name.'\');
}';
    }
    public function before()
    {
        $this->setControllerRenderPath('test');
    }

    /**
     * @return \Models\MemberModel|null
     */
    protected function getLoginUser(){
        if(isset($_SESSION[USER_SES_KEY]) && $_SESSION[USER_SES_KEY]){
            $userInfo = new MemberModel();
            if(is_array($_SESSION[USER_SES_KEY])){
                $userInfo->setPropertyValue($_SESSION[USER_SES_KEY]);
            }else $userInfo = $_SESSION[USER_SES_KEY];
            $memberCert = new MemberCertification();
            $certData = $memberCert->findByPrimary($userInfo->id);
            if($certData != null) $certData = $memberCert->getCertificationData();
            $userInfo->extraData['Certification'] = $certData;
            return $userInfo;
        }
        return null;
    }

    public function TestIndex(){
        $userInfo = $this->getLoginUser();
        $this->assign('user_info',$this->getLoginUser());
        if($userInfo != null){
            if($userInfo->extraData['Certification'] && $userInfo->extraData['Certification']['certification_data']){
                $userInfo->extraData['Certification']['certification_data'] =
                    print_r($userInfo->extraData['Certification']['certification_data'],1);
            }
            $this->assign('user_info_array',print_r($userInfo->toArray(),1));
        }
        $this->render('index');
    }
}