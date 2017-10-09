<?php

/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author internal\youbeiliang01
 */
class Controller_Error extends Controller_Base {

    /**
     * 从2.1开始, errorAction支持直接通过参数获取异常
     * @param type $exception
     */
    public function errorAction($exception) {
        //1. assign to view engine
        $this->getView()->assign("exception", $exception);
        //5. render by Yaf
    }

}
