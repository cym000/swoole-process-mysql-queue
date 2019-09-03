<?php
/**
 * Created by PhpStorm.
 * User: CYM
 * Date: 19-9-1
 * Time: 下午2:48
 */

namespace MysqlQueue\queue;
use MysqlQueue\Job;

class test extends Job
{
    /*
     * 消费进程 执行该方法
     * @param array|string
     * @return bool
     */
    function fire($data):bool
    {
        // TODO: Implement fire() method.
        // var_dump($data);
//        \co::sleep(10);
        $ret = $this->doJob($data);

        if($ret !== true){ // 失败了
            $this->setRelease(10); // 设置10秒后 重新执行
        }

        return $ret;
    }

    /*
     * @return bool
     */
    private function doJob($data){
        if(mt_rand(0,50) < 2) return false;
        return true;
        // 消费业务
        // 可使用easyswoole mysqli-pool
    }
}