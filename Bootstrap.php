<?php

/**
 * @name Bootstrap
 * @author internal\youbeiliang01
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

    /**
     * 初始化配置
     */
    public function _initConfig() {
        //把配置保存起来
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);

        // 关闭自动加载模板,即在action后不自动加载view
        Yaf_Dispatcher::getInstance()->autoRender(false);
        // 不自动加载view时，需要手工加载
        // $this->display('hello'); // 加载 helloAction的模板
        // $this->getView()->display('test/world.phtml'); // 加载指定路径的模板
    }

    /**
     *
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        //注册一个插件
        $objSamplePlugin = new Plugin_Sample();
        $dispatcher->registerPlugin($objSamplePlugin);
    }

    /**
     *
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的路由协议,默认使用简单路由
    }

    /**
     *
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initView(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的view控制器，例如smarty,firekylin
    }

}
