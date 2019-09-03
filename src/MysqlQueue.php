<?php
/**
 * Created by PhpStorm.
 * User: CYM
 * Date: 19-9-1
 * Time: 下午12:54
 */
namespace MysqlQueue;
use EasySwoole\Component\Singleton;

class MysqlQueue
{
    use Singleton;
    private $table = 'queue_jobs';
    private $config;
    private $list = [];

    private function __construct(...$args)
    {
        $this->config = $args;
        echo "MysqlQueue \n";
    }

    public function setTable(string $talbe)
    {
        $this->table =  $table;
    }

    /*
     * 创建swoole 消息队列子进程， 根据config 创建多个消息队列进程消费
     * @param swoole_server
     */
    public function setSwooleProcessQueue(\swoole_server &$server){

        foreach ($this->config as $value){

            $key = key($value);
            $value = $value[$key];

            if(empty($value['num'])) $num = 1;
            else $num = abs(intval($value['num']));

            if(empty($value['timeout'])) $timeout = 1;
            else $timeout = floatval($value['timeout']);

            for($i = 0; $i < $num; $i++){
                $name = $key.'_'.$i;
                $this->list[$key][$i] = 0;

                $process = new \swoole_process(function (\swoole_process $process) use ($i, $num, $key, $name, $timeout){
                    $db = \EasySwoole\MysqliPool\Mysql::getInstance()->pool('mysql')::defer();
                    
                    /*
                    if($i == 0){
                        // 分配之前的数据 // queue = ''
                        $sql = 'select count(1) as count from '.$this->table.' where ';
                        for($i = 0; $i < $num; $i++){
                            $arr[] = ' name != "'.$key.'_'.$i.'" ';
                        }
                        $where = implode(' or ', $arr);
                        echo $sql.$where.PHP_EOL;
                        $count = $db->rawQuery($sql.$where);
                        
                        if(!empty($count[0]['count'])){
                            $count = ceil($count[0]['count'] / $num);

                            for($i = 0; $i < $num; $i++){
                                $sql = ' update '.$this->table.' set name = "'.$key.'_'.$i. '" where '.$where. ' limit '.$count;
                                echo $sql. PHP_EOL;
                                $db->rawQuery($sql);
                            } 
                        }
                        
                    }
                    */

                    if(PHP_OS != 'Darwin'){
                        $process->name($name);
                    }

                    $object = new $key;
                    
                    while(true){
                        while (true){
                            $time = time();
                            // 查询mysql queue 中的数据进行 参数 $name
                            $data = $db->where('name', $name)->where('reserved', 0, '=')->where('available_time', $time, '<=')->orderBy('available_time', 'asc')->getOne($this->table, 'id, job_data');

                            if(empty($data) || empty($data['job_data'])){
                                break;
                            }

                            $job_data = json_decode($data['job_data'], 1);
                            if(empty($job_data)){
                                break;
                            }
                            try{
                                // 修改queue 任务状态在执行
                                $db->where('id', $data['id'])->update($this->table, ['attempts' => $db->inc(1), 'reserved' => 1, 'reserve_time' => $time]);

                                $ret = $object->fire($job_data);// 成功已否，true 移除数据记录 false 重新入库
                                if($ret === true){
                                    $db->where('id', $data['id'])->delete($this->table,1);
                                }else{
                                    $release = $object->getRelease();
                                    // 入库
                                    $db->where('id', $data['id'])->update($this->table, ['reserved' => 0, 'reserve_time' => 0, 'available_time' => time() + ($release ? $release : 1)]);
                                    $object->setRelease(1);
                                }
                            }catch (\Throwable $throwable){
                                var_dump($throwable->getMessage());
                            }
                        }

                        \co::sleep($timeout);
                    }

                }, false, 0, true);
                $server->addProcess($process);
            }

        }
    }

    /*
     * 入库操作
     */
    public function push(string $jobHandlerClassName, $jobData): bool
    {

         return \EasySwoole\MysqliPool\Mysql::invoker('mysql',function (\EasySwoole\MysqliPool\Connection $db) use ($jobHandlerClassName, $jobData){

            $name = $jobHandlerClassName;
            asort($this->list[$name]);
            $key = key($this->list[$name]);
            $this->list[$name][$key]++;
            $queueName = $name.'_'.$key;

            $data = [
                'name'      => $queueName,
                'queue'     => $jobHandlerClassName,
                'job_data'  => is_array($jobData) ? json_encode($jobData) : $jobData,
                'attempts'  => 0,
                'reserved'  => 0,
                'available_time' => time(),
                'create_time' => time()
            ];

            return $db->insert($this->table, $data) ? true : false;
        });

    }

    public function pushQueue(array $data){

    }
}