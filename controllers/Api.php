<?php

/**
 * @name Controller_Api
 * @author internal\youbeiliang01
 * @desc api控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class Controller_Api extends Controller_Base {

    public function rightsAction() {
        $account = $this->getQuery('bb', 'a');
        var_dump($account);
        $users = new Model_Users();
        var_dump($users);

        $servicelogin = new Service_Login();
        var_dump($servicelogin);
    }

    /**
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/webroot/api/demo/name/internadddl/param2/haha 的时候, 你就会发现不同
     * @param type $name
     * @param type $param2
     */
    public function demoAction($name = "Stranger", $param2 = '第二个参数') {
        //2. fetch model
        // $model = new Model_Sample();
        //3. assign
        $view = $this->getView();
        $view->assign("content", 'addd');
        $view->assign("name", $name);
        $view->assign("param2", $param2);
        $view->display('index/index.phtml');
    }

}
