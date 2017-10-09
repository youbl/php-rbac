<?php

/**
 * 定义了http通用的静态方法集
 *
 * @author youbeiliang01@baidu.com
 * @date 2014-07-31
 */
final class httphelper {

    /**
     * 下载指定url的文件
     * @param type $url
     * @param type $filename
     */
    static function downfile($url, $filename = '') {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return false;
        }

        if (empty($filename)) {
            $filename = tempnam(self::get_tmp_dir(), 'filename');
        }

        $file = fopen($url, "rb");
        if (!$file) {
            return false;
        }
        $newf = fopen($filename, "wb");
        if (!$newf) {
            return false;
        }

        while (!feof($file)) {
            fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
        }
        fclose($newf);
        fclose($file);
        return $filename;
    }

    /**
     * 通过wget方法下载
     * @param type $url
     * @param type $filename
     * @return string
     */
    static function down_with_wget($url, $filename = '') {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return 'err url:' . $url;
        }

        if (empty($filename)) {
            $filename = tempnam(self::get_tmp_dir(), 'filename');
        }
        $cmd = 'wget "' . $url . '" -O "' . $filename . '" ';
        if (stripos($url, 'https') === 0) {
            $cmd .= ' --no-check-certificate';
        }
        $cmd .= ' 2>&1';
        $msg = $cmd . PHP_EOL . self::_exec($cmd);
        if (!file_exists($filename) || filesize($filename) <= 500) {
            //usleep(100);            exec($cmd);
            return $msg;
        }
        return $filename;
    }

    /**
     * 获取临时文件夹
     * @return type
     */
    static function get_tmp_dir() {
        $tmp_dir = ini_get('upload_tmp_dir');
        if ($tmp_dir) {
            if (is_dir($tmp_dir)) {
                return $tmp_dir;
            }
        }
        return sys_get_temp_dir();
    }

    /**
     * 访问指定url，并返回响应内容，返回数值型表示错误
     * @author youbeiliang01@baidu.com
     * @date 2014-07-31
     *
     * $url          string  要访问的url，可以带查询字符串
     * $request_type string  访问类型，GET或POST，默认GET
     * $param        array or string   要POST提交的参数
     * $header       array   要设置的HTTP头
     * $proxy        string  要使用的代理,格式http://10.79.1.1:80 或 10.79.1.1:80
     * $timeout      int     curl普通秒级超时，默认为0不设置
     * $follow       bool    为true时，会跟踪301或302递归
     * $getHead      bool    为true时，会返回响应的头
     * @return true false
     */
    static function curl($url, $request_type = 'GET', $param = null, $header = array(), $proxy = null, $timeout = 0, $follow = false, $getHead = false) {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return false;
        }

        $data = '';
        if (isset($param)) {
            if (is_string($param)) {
                $data = $param;
            } else if (is_array($param)) {
                $data = http_build_query($param);
            }
        }
        if (!empty($data) && $request_type != 'POST') {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= $data;
        }

        $ch = curl_init(); //初始化curl
        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        if ($timeout > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        $ishttps = (stripos($url, 'https:') === 0);
        if ($ishttps) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        }
        curl_setopt($ch, CURLOPT_URL, $url); //设置链接
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置HTTP头
        }

        if ($request_type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //POST数据
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, 0); //设置为GET方式
        }

        if ($follow) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 是否跟踪301或302递归
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // 是否跟踪301或302递归
        }
        if ($getHead) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        $response = curl_exec($ch); //接收返回信息
        // 获取http状态码，200，302等等
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $code = curl_errno($ch);
        if ($code) {//为0表示正常， 其它值表示出错
            return $code;
        }
        //if ($getHead) {
        //    $response = array($response);
        //    $response[] = curl_getinfo($ch);
        //}
        curl_close($ch); //关闭curl链接
        return $response;
    }

    /**
     * 返回指定url的http code，如200，404等
     * @param type $url
     * @param type $follow 为true时，会跟踪301或302递归
     * @return type
     */
    public static function getHttpCode($url, $follow = false) {
        $ch = self::_curlInit($url); //初始化curl
        if (empty($ch)) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        // 注意：如果不加这句，会导致整个url下载下来，非常慢，而且容易内存溢出
        curl_setopt($ch, CURLOPT_NOBODY, true);

        if ($follow) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 是否跟踪301或302递归
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // 是否跟踪301或302递归
        }
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$header = curl_getinfo($ch);
        //return $header['http_code'];
        curl_close($ch);
        return $httpCode;
    }

    /**
     * 返回指定url的头信息
     * @param type $url
     * @param type $key 为空返回全部
     * @return type
     */
    public static function getHeader($url, $key = '') {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return false;
        }

        $allkey = get_headers($url, 1);
        if (empty($key)) {
            return $allkey;
        }
        $ret = false;
        foreach ($allkey as $headkey => $headval) {
            if (strcasecmp($key, $headkey) === 0) {
                $ret = $headval;
                break;
            }
        }
        unset($allkey);
        $ret = trim($ret, '"');
        return $ret;
    }

    /**
     *
     * @param type $url
     * @param type $request_type
     * @param type $param
     * @param type $header
     * @param type $proxy
     * @param type $timeout
     * @return boolean
     */
    private static function _curlInit($url, $request_type = 'GET', $param = null, $header = array(), $proxy = null, $timeout = 0) {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return false;
        }

        $ch = curl_init(); //初始化curl
        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        if ($timeout > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        curl_setopt($ch, CURLOPT_URL, $url); //设置链接
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置HTTP头
        }

        if ($request_type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //POST数据
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, 0); //设置为GET方式
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64)');
        return $ch;
    }

    /**
     * 判断指定的url扩展名是否属于图片
     * @param type $url
     * @return boolean
     */
    public static function isImgUrl($url) {
        // 只允许http和https协议
        if (empty($url) || stripos($url, 'http') !== 0) {
            return false;
        }

        // 截取问号之前的url
        $idx = strpos($url, '?');
        if ($idx !== false) {
            $url = substr($url, 0, $idx);
        }
        $idx = strrpos($url, '.');
        if ($idx === false) {
            return false;
        }
        $ext = strtolower(substr($url, $idx));
        if ($ext === '.jpg' || $ext === '.jpeg' || $ext === '.gif' || $ext === '.bmp' || $ext === '.png') {
            return true;
        }
        return false;
    }

    /**
     * 获取当前访问的url，并返回
     *
     * @author youbeiliang01@baidu.com
     * @date 2014-08-06
     *
     * @return string
     */
    static function geturl($getQuery = true) {
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = self::isSSL() ? 'https://' : 'http://';
            $ret = $protocol . $_SERVER['HTTP_HOST'];
            if ($getQuery) {
                return $ret . $_SERVER['REQUEST_URI'];
            }
            $path = $_SERVER['REQUEST_URI'];

            // 截取问号之前的url
            $idx = strpos($path, '?');
            if ($idx !== false) {
                $path = substr($path, 0, $idx);
            }
            return $ret . $path;
        }

        if (isset($_SERVER['SCRIPT_NAME'])) {
            // 命令行下，无法获取url，可以获取文件路径
            return $_SERVER['SCRIPT_NAME'];
        }
        return '';
    }

    /*
     * 检测链接是否是SSL连接
     * @return bool
     */

    static function isSSL() {
        if (!isset($_SERVER['HTTPS'])) {
            return false;
        }
        if ($_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === '1') {  //Apache
            return true;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return true;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return true;
        }
        return false;
    }

    /**
     * 获取当前访问的客户端真实IP.
     * 注意：job环境下无法获取到
     *
     * @author youbeiliang01@baidu.com
     * @date 2014-09-29
     *
     * @return string
     */
    static function GetClientIP() {
        $cip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $cip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // 注意，客户端可以伪造 X-Forwarded-For: 192.168.156.45，从而导致我们得到假IP
            $cip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $cip = $_SERVER['REMOTE_ADDR'];
        }
        return $cip;
    }

    /**
     * 获取当前访问的客户端全部IP.
     * 注意：job环境下无法获取到
     *
     * @author youbeiliang01@baidu.com
     * @date 2014-09-29
     *
     * @return string
     */
    static function GetAllClientIP() {
        $cip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $cip .= $_SERVER['HTTP_CLIENT_IP'] . '-c;';
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // 注意，客户端可以伪造 X-Forwarded-For: 192.168.156.45，从而导致我们得到假IP
            $cip .= $_SERVER['HTTP_X_FORWARDED_FOR'] . '-x;';
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $cip .= $_SERVER['REMOTE_ADDR'] . '-r;';
        }
        return $cip;
    }

    /**
     * 获取当前服务器IP.
     * 注意：job环境下无法获取到
     *
     * @author youbeiliang01@baidu.com
     * @date 2014-10-09
     *
     * @return string
     */
    static function GetServerIP() {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
        if (isset($_ENV['HOSTNAME'])) {
            $MachineName = $_ENV['HOSTNAME'];
        } else if (isset($_ENV['COMPUTERNAME'])) {
            $MachineName = $_ENV['COMPUTERNAME'];
        } else {
            $MachineName = '';
        }
        $cip = gethostbyname($MachineName);
        return $cip;
    }

    /**
     * 向页面输出一段js的alert
     * @param type $msg
     * @param type $needEnd 输出js后，是否需要终止页面，默认终止
     */
    static function alert($msg, $needEnd = true) {
        $msg = str_replace('"', '\\"', $msg);
        $msg = str_replace("\r", '\\r', $msg);
        $msg = str_replace("\n", '\\n', $msg);
        echo '<html><head>'
        . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
        . '<script type="text/javascript">alert("' . $msg . '");</script>'
        . '</head></html>';
        if ($needEnd) {
            die();
        }
    }

    /**
     *
     * @param type $cmd
     * @return string
     */
    private static function _exec($cmd) {
        $ret = array();
        exec($cmd, $ret);
        $msg = '';
        foreach ($ret as $row) {
            $msg .= $row . "\n";
        }
        unset($ret);
        return $msg;
    }

}
