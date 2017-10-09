<?php

//<editor-fold defaultstate="collapsed" desc="百度公司账号uuap登录相关常量定义和配置（即邮箱账号）">
// 移动开放平台记录的cookie名字
define('UUAP_COOKIE', 'uuap_name_mdev');
// 移动开放平台记录的cookie所属的domain，用baidu.com是为了方便 前后台 统一登录
define('UUAP_COOKIE_DOMAIN', 'baidu.com');
// 移动开放平台记录的cookie加解密用的密钥
define('UUAP_COOKIE_KEY', 'NPG4MRT9BC3XP7BX3IKXS1DDHU3XXHX7');
// uuap api接口的域名
define('UUAP_DOMAIN', 'uuap.baidu.com');

//</editor-fold>


class Service_Login {

    function __construct() {

    }

    //<editor-fold defaultstate="collapsed" desc="百度公司账号uuap登录相关方法（即邮箱账号）">

    /**
     * app.baidu.com前台退出UUAP
     * @return type
     */
    public function logoutUuapByApp($uid, $useruuap) {
        //cookie过期
        $this->_uuapSetCookie('', time() - 3600);
        //写退出日志
        $this->_loginLog($uid, $useruuap, true);
    }

    /**
     * 登录、退出写日志
     * @param type $uid
     * @param type $useruuap
     * @param type $isLogout
     */
    private function _loginLog($uid, $useruuap, $isLogout = false) {
        //记录是否写过登日志
        $ip = httphelper::GetClientIP();
        $key = 'uuap_login_' . $uid . '_' . $useruuap . '_' . $ip;
        $this->load->library('redishelper');
        $login = redishelper::get($key);

        $this->load->model('model_commonlog');
        if (empty($login)) {
            //20000:超级账号二次登陆日志
            $this->model_commonlog->insertLogUtil($uid, 20000, $useruuap . '成功二次登录');
            redishelper::set($key, 1);
        }
        if ($isLogout) {
            $this->model_commonlog->insertLogUtil($uid, 20000, $useruuap . '退出二次登录');
            redishelper::del($key);
        }
    }

    /**
     * mdev后台退出UUAP
     * @return type
     */
    public function logoutUuapByMdev($useruuap) {
        //cookie过期
        $this->_uuapSetCookie('', time() - 3600);
        LogUtil::logmsg('退出：' . $useruuap, 'uuapCookie/', false);
        header('Location: https://' . UUAP_DOMAIN . '/logout', TRUE, 302);
    }

    /**
     * 获取UUAP登录用户，没登录会跳转到登录页
     * @param type $service 登录成功后要跳转到的url
     * @return type
     */
    public function getLoginUuapName($service = '') {
        if (!empty($_COOKIE[UUAP_COOKIE])) {
            $uuapName = $this->encrypt->decode($_COOKIE[UUAP_COOKIE], UUAP_COOKIE_KEY);
            if ($uuapName !== false) {
                return $uuapName;
            }
            LogUtil::logmsg('cookie被篡改,要重新登录：' . $_COOKIE[UUAP_COOKIE], 'uuaplogin/errCookie');
        }

        if (empty($service)) {
            $service = httphelper::geturl(false);
        }
        $returnUrl = $service;
        // 避免指定的url没有登录权限

        if (ENVIRONMENT === 'production') {
            if (strpos($service, '/mdev.baidu.com/') !== false) {
                $returnUrl = 'http://mdev.baidu.com/'; // mdev强制跳转到首页，避免错误
            } else {
                if (strpos($service, '/app.baidu.com/') === false) {
                    $returnUrl = 'http://app.baidu.com/apps/';
                }
            }
        }

        // orp上会带上全路径，导致死循环
        $returnUrl = str_replace('/devapp/index.php', '', $returnUrl);

        $urlPara = 'service=' . urlencode($returnUrl);
        $loginurl = 'https://' . UUAP_DOMAIN . '/login?' . $urlPara;

        if (empty($_GET['ticket'])) {
            LogUtil::logmsg($loginurl . PHP_EOL . $service, 'uuaplogin/302-');
            header('Location: ' . $loginurl, TRUE, 302);
            die();
        }

        // uuap登录页返回的ticket
        $ticket = $_GET['ticket'];
        $uuapName = $this->_getUuapNameByTicket($ticket, $urlPara, $service);
        if (empty($uuapName)) {
            header('Location: ' . $loginurl, TRUE, 302);
            die();
        }
        return $uuapName;
    }

    private function _getUuapNameByTicket($ticket, $urlPara, $service = '') {
        $validurl = 'https://' . UUAP_DOMAIN . '/serviceValidate?' . $urlPara . '&ticket=' . $ticket;
        $response = httphelper::curl($validurl);
        //var_dump($response);
        //var_dump($response);
        /* 返回数据格式
          "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
          <cas:authenticationSuccess>
          <cas:user>youbeiliang01</cas:user>
          </cas:authenticationSuccess>
          </cas:serviceResponse>"
         */
        $match = array();
        preg_match('/<cas:user>([^<>]+)<\/cas:user>/', $response, $match);
        if (empty($match[1])) {
            LogUtil::logmsg($service . PHP_EOL . $validurl . PHP_EOL . $response, 'uuaplogin/302err');
            return false;
        }
        LogUtil::logmsg($service . PHP_EOL . $validurl . PHP_EOL . $response, 'uuaplogin/api');

        // 加密后，写入cookie,有效期到第2天24点
        $encryUuapName = $this->encrypt->encode($match[1], UUAP_COOKIE_KEY);
        $expire = strtotime(date('Y-m-d 00:00:00')) + 86400 * 2; // 86400
        $this->_uuapSetCookie($encryUuapName, $expire);

        return $match[1];
    }

    /**
     * 设置cookie封装的方法
     * @param type $value
     * @param type $expire
     * @param type $cookieName
     * @param type $domain
     * @return type
     */
    private function _uuapSetCookie($value, $expire, $cookieName = '', $domain = '') {
        if (empty($cookieName)) {
            $cookieName = UUAP_COOKIE;
        }
        if (empty($domain)) {
            $domain = UUAP_COOKIE_DOMAIN;
        }
        if ($this->_cookieSeted($cookieName)) {
            return;
        }
        setcookie($cookieName, $value, $expire, '/', $domain, false, true);
    }

    /**
     * 判断某个Cookie是否已经设置过
     * @param type $cookieName
     * @return boolean
     */
    private function _cookieSeted($cookieName) {
        if (empty($cookieName)) {
            return false;
        }
        $arr = headers_list();
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $line) {
            if (strpos($line, 'Set-Cookie: ' . $cookieName . '=') !== false) {
                return true;
            }
        }
        return false;
    }

    //</editor-fold>
}
