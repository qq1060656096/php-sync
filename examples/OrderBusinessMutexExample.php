<?php
/**
 * Created by PhpStorm.
 * User: zhaoweijie
 * Date: 2020-04-24
 * Time: 16:32
 */
namespace Zwei\Sync\Examples;

use Zwei\Sync\Exception\LockFailException;
use Zwei\Sync\Exception\LockTimeoutException;
use Zwei\Sync\Exception\UnLockTimeoutException;
use Zwei\Sync\Helper\Helper;
use Zwei\Sync\Mutex\OrderBusinessMutex;
use Zwei\Sync\Repository\RedisLockRepository;

class OrderBusinessMutexExample
{
    
    /**
     * 订单审核
     *
     * @throws \Zwei\Sync\Exception\LockParamException
     * @throws \Zwei\Sync\Exception\NoLockUnLockFailException
     */
    public function orderCheck()
    {
        try {
            $host = '199.199.199.199';
            $post = 16379;
            $password = '000000';
            $redis = new \Redis();
            $redis->connect($host, $post);
            $redis->auth($password);
            
            $redisRepository = new RedisLockRepository($redis);
            $expired = Helper::secondsToMilliseconds(30);
            $orderId = 1;
            $orderDemoBusinessMutex = new OrderBusinessMutex($redisRepository, $expired, $orderId);
            $orderDemoBusinessMutex->lock();
            // todo
            $orderDemoBusinessMutex->unlock();
        } catch (LockParamException $exception) {
            // 参数错误
        }  catch (LockFailException $exception) {
            // 其他人正在操作, 请稍后在试
        } catch (LockTimeoutException $exception) {
            // 加锁超时
        } catch (UnLockTimeoutException $exception) {
            // 解锁超时
        } catch (NoLockUnLockFailException $exception) {
            // 没有加锁时，解锁
        }
    }
    
    
    /**
     * 订单审核
     */
    public function orderCheck2()
    {
        try {
            $host = '199.199.199.199';
            $post = 16379;
            $password = '000000';
            $redis = new \Redis();
            $redis->connect($host, $post);
            $redis->auth($password);
    
            $redisRepository = new RedisLockRepository($redis);
            $expired = Helper::secondsToMilliseconds(30);
            $orderId = 1;
            $orderBusinessMutex = new OrderBusinessMutex($redisRepository, $expired, $orderId);
            $orderBusinessMutex->synchronized(function(){
                // todo
            });
        } catch (LockParamException $exception) {
            // 参数错误
        }  catch (LockFailException $exception) {
            // 其他人正在操作, 请稍后在试
        } catch (LockTimeoutException $exception) {
            // 加锁超时
        } catch (UnLockTimeoutException $exception) {
            // 解锁超时
        } catch (NoLockUnLockFailException $exception) {
            // 没有加锁时，解锁
        }
    }
}
