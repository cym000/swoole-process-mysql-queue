<?php
/**
 * Created by PhpStorm.
 * User: CYM
 * Date: 19-9-1
 * Time: 下午2:33
 */
namespace MysqlQueue;

abstract class Job
{
    public $release = 1;

    public function setRelease(int $release){
        $this->release = $release;
    }

    public function getRelease(){
        return $this->release;
    }

    /*
     * 消费进程 执行该方法
     * @param array|string
     * @return bool|true 成功、false 失败
     */
    abstract function fire($data):bool;

}