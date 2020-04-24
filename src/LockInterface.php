<?php
/**
 * Created by PhpStorm.
 * User: zhaoweijie
 * Date: 2020-04-24
 * Time: 09:35
 */

namespace Zwei\Sync;


use Zwei\Sync\Exception\LockFailException;
use Zwei\Sync\Exception\LockParamException;
use Zwei\Sync\Exception\LockTimeoutException;
use Zwei\Sync\Exception\UnLockTimeoutException;

interface LockInterface
{
    /**
     * @return string
     */
    public function getName();
    
    /**
     * @return integer Milliseconds
     */
    public function getExpired();
    
    /**
     * @return LockRepositoryInterface
     */
    public function getLockRepositoryInterface();
    
    /**
     * @return bool
     * @throws LockParamException|LockFailException|LockTimeoutException
     */
    public function lock();
    
    /**
     * @return integer
     * @throws UnLockTimeoutException
     */
    public function unlock();
    
    /**
     * @param callable $code
     * @return mixed
     * @throws LockParamException|LockFailException|LockTimeoutException
     * @throws UnLockTimeoutException
     * @throws \Exception
     * @throws \Throwable
     */
    public function synchronized(callable $code);
    
    /**
     * @return bool
     */
    public function checkLockTimeOut();
}