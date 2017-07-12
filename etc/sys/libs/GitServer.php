<?php
/**
 * Created by PhpStorm.
 * User: yancheng (cheng@love.xiaoyan.me)
 * Date: 14-6-24
 * Time: 上午10:55
 */
global $gitServer;
$gitServer = null;
class GitServer {
    private $inDebug = false;
    private $gitDir;
    private $project;
    public $execCMDS = array();

    public function GitServer($gitDir){
        $this->gitDir = $gitDir;
    }
    public function setDebug($debug = true){
        $this->inDebug = $debug;
    }

    public function selectProject($project){
        $this->existsProject();
        $this->project = $project;
    }

    public function tags(){
        $this->cmd($this->project);
    }

    public function tree($object,$dir = ''){
        if(!$object){
            throw new GitServerException("need object");
        }
        $files = array(
            'list' => array(),
            'readme' => null
        );
        $tree = $this->cmd("ls-tree {$object}".($dir ? ' '.$dir : ''));
        if($tree){
            foreach($tree as $index=>$line){
                preg_match('/(\d{6}?)([\S\s]+?)([a-z0-9]{40}?)\t(.*)/',$line,$ret);
                $fileName = $dir ? substr($ret[4],strlen($dir)) : $ret[4];
                $baseInfo = array(
                    'mode' => $ret[1],
                    'type' => trim($ret[2]),
                    'hash' => $ret[3],
                    'filename' => $fileName,
                );
                $commitInfo = $this->getFileLastCommit($object,$ret[4]);
                $files['list'][] = array_merge($baseInfo,$commitInfo);

                if(str_endwith('readme.md',trim(strtolower($ret[4])))){
                    $files['readme'] = $baseInfo;
                }
            }
            $files['list'] = $this->sortTree($files['list']);
            return $files;
        }
    }

    public function getFileInfo($object,$fileName){
        if(!$object){
            throw new GitServerException("need object");
        }
        $files = array(
            'list' => array(),
            'readme' => null
        );
        $tree = $this->cmd("ls-tree {$object} {$fileName}");
        if($tree && count($tree) == 1){
            preg_match('/(\d{6}?)([\S\s]+?)([a-z0-9]{40}?)\t(.*)/',$tree[0],$ret);
            $baseInfo = array(
                'mode' => $ret[1],
                'type' => trim($ret[2]),
                'hash' => $ret[3],
                'filename' => $ret[4],
            );
            $commitInfo = $this->getFileLastCommit($object,$ret[4]);
            return array_merge($baseInfo,$commitInfo);
        }
        return null;
    }

    private function sortTree($treeFiles){
        $dirs = $files = array();
        foreach($treeFiles as $f){
            if($f['type'] == 'blob'){
                $files[] = $f;
            }else{
                $dirs[] = $f;
            }
        }
        return array_merge($dirs,$files);
    }

    public function getFileLastCommit($reversion,$fileName){
        $logs = $this->getFileCommit($reversion,$fileName);
        return count($logs) > 0 ? $logs[0] : null;
    }

    public function getFileCommit($reversion,$fileName){
        $logs = $this->cmd("log -s -p $reversion -- $fileName");
        $logList = array();
        $tempLog = null;
        foreach($logs as $line){
            /**
             * commit 53409329aa675c9d90ef959bdc729eeaa9463f7f
            Author: callmeyan <yaclty2@gmail.com>
            Date:   Tue Jun 24 10:48:18 2014 +0800

            add branch server
             */
            if(preg_match('/commit(.*)([a-fA-F0-9]{40})/',$line,$ret)){ //commit hash
                if($tempLog){
                    $logList[] = $tempLog;
                }
                $tempLog = array();
                $tempLog['log_hash'] = $ret[2];
            }else if(preg_match('/Author:(.*)<(.*)>/',$line,$ret)){ //commit author
                $tempLog['author'] = array(
                    'name'=>trim($ret[1]),
                    'email'=>$ret[2]
                );
            }else if(preg_match('/Date:(.*)/',$line,$ret)){ //commit date
                $tempLog['date'] = strtotime(trim($ret[1]));
            }else{
                if(!isset($tempLog['log'])){
                    $tempLog['log'] = '';
                }
                $line = trim($line);
                $tempLog['log'] .= ($line != '' && $tempLog['log'] != '' ?"\n":'').$line;
            }
        }
        if($tempLog){
            $logList[] = $tempLog;
        }
        return $logList;
    }

    public function refList(){
        $refs = $this->cmd("for-each-ref");
        $retDatas = array(
            'list' => array(
                'branch'=>array(),
                'tag'=>array()
            ),
            'all' => array()
        );
        if($refs){
            foreach($refs as $index=>$line){
                //46513837a2043d9f31ae48bd3d57cd1a664fec3c commit	refs/heads/client
                preg_match('/([a-z0-9]{40})(.*)refs\/(.*)/',$line,$ret);
                $ret[2] = trim($ret[2]);
                if($ret[2] == 'commit'){
                    $retDatas['list']['branch'][] = array(
                        'hash'=>$ret[1],
                        'name'=>substr($ret[3],6)
                    );
                    $retDatas['all'][substr($ret[3],6)] = array(
                        'hash' => $ret[1],
                        'type' => 'branch'
                    );
                }else{
                    $retDatas['list']['tag'][] = array(
                        'hash'=>$ret[1],
                        'name'=>substr($ret[3],5)
                    );
                    $retDatas['all'][substr($ret[3],5)] = array(
                        'hash' => $ret[1],
                        'type' => 'tag'
                    );
                }
            }
            return $retDatas;
        }
    }

    public function getFileContent($fileInfo,$returnArray = false){
        if($fileInfo['type'] == "blob"){
            $ret = $this->cmd("cat-file blob ".$fileInfo['hash']);
            if($returnArray){
                return $ret;
            }
            return implode("\r\n",$ret);
        }
        return null;
    }

    public  function projects(){
        $dirs = getDirFiles($this->gitDir);
        $projects = array();
        foreach($dirs as $dir){

            if(is_dir($this->gitDir . $dir)){
                $projectDir = $this->gitDir . $dir;
                if(file_exists($projectDir.'/branches')
                    && file_exists($projectDir.'/refs')
                    && file_exists($projectDir.'/description')){
                    $projects[] = array(
                        'name' => $dir,
                        'description'=>file_get_contents($projectDir.'/description')
                    );
                }
            }
        }
        return $projects;
    }

    public function getProjectInfo(){
        $projectDir = $this->gitDir.$this->project;
        $project = array(
            'description' => file_get_contents($projectDir.'/description')
        );
        return $project;
    }

    private function existsProject(){
        $gitProjectDir = $this->gitDir.$this->project;
        if(!file_exists($gitProjectDir)){
            throw new GitServerException("the project not exists");
        }
    }

    public function cmd($params){
        if (!empty($params))
        {
            $projectDir = $this->gitDir.$this->project;

            $execCmd = "cd {$projectDir} && git {$params}";
            $this->execCMDS[] = $execCmd;
            if($this->inDebug){
                echo '<fieldset><legend>CMD</legend>'.$execCmd.'</fieldset>';
            }
            exec($execCmd,$ret,$code);

            if($code == 0){
                return $ret;
            }
        }
        return false;
    }
}

class GitServerException extends Exception{
    public function GitServerException($message,$code = 1){
        $this->message = $message;
        $this->code = $code;
    }
}