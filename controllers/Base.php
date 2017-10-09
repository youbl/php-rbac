<?php

/**
 * @name Controller_Base
 * @author internal\youbeiliang01
 * @desc api控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
abstract class Controller_Base extends Yaf_Controller_Abstract {

    /**
     * Yaf_Request_Http实例,
     * 参考: http://php.net/manual/zh/class.yaf-request-http.php
     *       http://yaf.laruence.com/manual/yaf.class.request.html
     * @var type
     */
    protected $request;

    /**
     * Yaf_Controller_Abstract的构造函数 __construct
     * 不允许重载，但是yaf提供魔术方法init，只要Controller里定义了init方法，
     * Controller被实例化时，会自动调用
     */
    protected function init() {
        $this->request = $this->getRequest();
    }

    /**
     * 当前请求是否GET
     * @return type
     */
    protected function isGet() {
        return $this->request->isGet();
    }

    /**
     * 当前请求是否POST
     * @return type
     */
    protected function isPost() {
        return $this->request->isPost();
    }

    // <editor-fold defaultstate="collapsed" desc="获取querystring或post、cookie相关方法">
    /**
     * 获取post 整型数据
     * @param type $name
     * @param type $default
     * @return type
     */
    protected function getPostInt($name, $default = 0) {
        $tmp = $this->request->getPost($name, '');
        if (!is_numeric($tmp)) {
            return $default;
        }
        return intval($tmp);
    }

    /**
     * 获取query 整型数据
     * @param type $name
     * @param type $default
     * @return type
     */
    protected function getQueryInt($name, $default = 0) {
        $tmp = $this->request->getQuery($name, '');
        if (!is_numeric($tmp)) {
            return $default;
        }
        return intval($tmp);
    }

    /**
     * 获取post数据
     * @param type $name
     * @param type $default
     * @return type
     */
    protected function getPost($name, $default = '') {
        return $this->request->getPost($name, $default);
    }

    /**
     * 获取query数据
     * @param type $name
     * @param type $default
     * @return type
     */
    protected function getQuery($name, $default = '') {
        return $this->request->getQuery($name, $default);
    }

    /**
     * 获取cookie数据
     * @param type $name
     * @param type $default
     * @return type
     */
    protected function getCookie($name, $default = '') {
        return $this->request->getCookie($name, $default);
    }

    // </editor-fold >
}
