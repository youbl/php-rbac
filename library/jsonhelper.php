<?php

/**
 * 定义了json相关的通用的静态方法集
 *
 * @author youbeiliang01@baidu.com
 * @date 2014-07-28
 */
final class jsonhelper {

    /**
     * 输出json格式的数据
     * @param type $success true或200，表示返回成功，其它值表示失败
     * @param type $data 成功或失败的响应数据
     * @return null
     */
    public static function jsonOutput($success, $data) {
        header('application/json;charset=utf-8');
        header("Cache-Control:no-cache");           // 禁止缓存

        $result = array();
        if ($success === true || $success === 200) {
            $result['code'] = 200;
            $result['result'] = $data;
        } else {
            $result['code'] = $success;
            $result['message'] = $data;
        }
        $ret = json_encode($result);
        // 增加jsonp回调机制支持, js调用参考：$.getJSON(url + '?callback=?', function(res) {});
        if (isset($_GET['callback'])) {
            $ret = $_GET['callback'] . '(' . $ret . ')';
        }
        echo $ret;
        exit;
    }

    /**
     *
     * @param type $err
     */
    public static function jsonError($err) {
        if (($err instanceof Exception)) {
            $err = $err->getMessage();
        }
        self::jsonOutput(500, $err);
    }

}
