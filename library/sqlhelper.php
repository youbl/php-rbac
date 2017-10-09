<?php

/**
 * mysql数据库操作辅助类
 */
class sqlhelper {

    /**
     * 收集所有数据库连接单例
     * @var type
     */
    private static $arrInstance = array();

    /**
     * 根据ini配置文件节点配置，返回对应的数据库实例
     * @param string $configName
     * @return type
     */
    public static function getConnByConfig($configName = 'mysql') {
        // 读取ini配置文件里的数据
        // $config = Yaf_Application::app()->getConfig();
        $config = Yaf_Registry::get('config'); // 在Bootstrap里缓存的

        if (empty($configName)) {
            $configName = 'mysql';
        }
        if (empty($config[$configName])) {
            return null;
        }
        return self::getInstance($config[$configName]);
    }

    /**
     * 返回单例化实例
     * @param type $dbconfig 数据库连接信息：
     *      为string时，格式参考：10.1.2.3:3306,databaseName,root,password
     *      为 array时，格式参考：array('hostname'=>'ip:port','database'=>'db名','username'=>'登录名','password'=>'密码');
     * @return \sqlhelper
     */
    public static function getInstance($dbconfig = null) {
        if (empty($dbconfig)) {
            // 已经有数据库实例存在时，返回第一个实例
            foreach (self::$arrInstance as $instance) {
                return $instance;
            }
            throw new Exception('尚未初始化数据库');
        }
        $md5 = md5(json_encode($dbconfig));
        if (!isset(self::$arrInstance[$md5])) {
            self::$arrInstance[$md5] = new sqlhelper($dbconfig);
        }
        return self::$arrInstance[$md5];
    }

    /**
     * 根据字段清单，组合update的set语句并返回
     * @param type $data
     * @param type $params
     * @param type $arrFields
     * @return string
     */
    public static function combineUpdateSql($data, &$params, $arrFields = null) {
        if (!isset($arrFields)) {
            $arrFields = array_keys($data);
        }
        $sql = '';
        foreach ($arrFields as $field) {
            if (isset($data[$field])) {
                if (!empty($params)) {
                    $sql .= ',';
                }
                $sql .= '`' . $field . '`=?';
                $params[] = $data[$field];
            }
        }
        return $sql;
    }

    // <editor-fold defaultstate="collapsed" desc="实例属性">
    /**
     * 数据库服务器IP或name
     * @var type
     */
    private $host;

    /**
     * 数据库服务端口，默认3306
     * @var type
     */
    private $port = 3306;

    /**
     * 数据库登录用户，默认root
     * @var type
     */
    private $user = 'root';

    /**
     * 数据库登录密码，默认空
     * @var type
     */
    private $pwd = '';

    /**
     * 要连接的数据库实例名
     * @var type
     */
    private $dbname;

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="构造函数和析构函数">
    /**
     * 构造函数
     * @throws Exception
     */
    private function __construct() {
        $num = func_num_args();   //获得参数个数
        if ($num <= 0) {
            throw new Exception('未提供数据库连接信息，格式参考：10.1.2.3:3306,databaseName,root,password');
        }
        $args = func_get_args();   //获得参数列表数组
        if (empty($args[0])) {
            throw new Exception('数据库连接信息为空，格式参考：10.1.2.3:3306,databaseName,root,password');
        }
        if (is_string($args[0])) {
            // 兼容分号和逗号
            $tmp = str_replace(';', ',', $args[0]);
            $arrConstr = explode(',', $tmp);
        } else if (isset($args[0]['hostname'])) { // !is_array($args[0])
            $arrConstr = array($args[0]['hostname'], $args[0]['database'], $args[0]['username'], $args[0]['password']);
        } else {
            throw new Exception('数据库连接信息为空，格式参考：10.1.2.3:3306,databaseName,root,password');
        }
        $this->setVar($arrConstr);
        if (empty($this->host) || $this->port <= 0) {
            throw new Exception('数据库IP或端口有误:' . $this->host . ':' . $this->port);
        }
        if (empty($this->user) || empty($this->dbname)) {
            throw new Exception('数据库登录名或数据库名不允许为空');
        }
    }

    /**
     * 设置数据库变量
     * @param type $arrConstr
     * @throws Exception
     */
    private function setVar($arrConstr) {
        // 设置ip与端口
        if (empty($arrConstr[0]) || empty($arrConstr[1])) {
            return;
        }
        $dbAndPort = explode(':', $arrConstr[0]);
        $this->host = trim($dbAndPort[0]);
        if (isset($dbAndPort[1])) {
            $tmp = trim($dbAndPort[1]);
            if (is_numeric($tmp)) {
                $this->port = (int) $tmp;
            }
        }
        // 设置数据库实例名
        $this->dbname = trim($arrConstr[1]);
        // 设置数据库登录名
        if (isset($arrConstr[2])) {
            $this->user = trim($arrConstr[2]);
        }
        // 设置数据库密码
        if (isset($arrConstr[3])) {
            $this->pwd = trim($arrConstr[3]);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct() {

    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="实例方法，数据库相关的辅助函数">

    /**
     * 执行分号分隔的多个sql
     * @param type $sql
     * @return boolean
     */
    function executeMultiSql($sql) {
        $db = $this->createDbLink();
        if (empty($db)) {
            return false;
        }
        if (mysqli_multi_query($db, $sql)) {
            $ret = $db->affected_rows;
            $db->close();
            return $ret;
            /*
              do {
              // 存储第一个结果集
              if ($result = mysqli_store_result($con)) {
              while ($row = mysqli_fetch_row($result)) {
              printf("%sn", $row[0]);
              }
              mysqli_free_result($result);
              }
              } while (mysqli_next_result($con));
             *
             */
        }
        $db->close();
        return false;
    }

    /**
     * 执行Insert、Update之类的sql
     * @param type $sql 要执行的sql
     * @param type $params 参数
     * @param type $id 返回最近一次插入的id
     * @return false 或 受影响的行数
     */
    public function executeNoQuery($sql, $params = null, &$id = 0) {
        $db = $this->createDbLink();
        $stmt = $this->executeDo($db, $sql, $params);
        if (empty($stmt)) {
            return false;
        }
        $ret = $stmt->affected_rows;
        if ($ret > 0) {
            $id = $stmt->insert_id;
        }
        $stmt->close();
        $db->close();
        return $ret;
    }

    /**
     * 执行查询相关的sql，修改数据的请勿使用
     * @param type $sql
     * @param type $params
     * @return boolean|array
     */
    public function executeSql($sql, $params = null) {
        $db = $this->createDbLink();
        $stmt = $this->executeDo($db, $sql, $params);
        if (empty($stmt)) {
            return false;
        }

        // 全部读取，不写这一句，会逐行读取
        $stmt->store_result();

        // 收集所有字段列表，并收集字段数组的引用，传递给bind_result方法
        $out = array();
        $refFields = array();
        $fieldsInfo = $stmt->result_metadata()->fetch_fields();
        foreach ($fieldsInfo as $field) {
            $refFields[] = &$out[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $refFields);
        unset($fields);

        $ret = array();
        while ($stmt->fetch()) {
            $newrow = array();
            foreach ($out as $key => $val) {
                $newrow[$key] = $val;
            }
            $ret[] = $newrow;
        }
        // 如果要记录行数： $stmt->num_rows;
        $stmt->close();
        $db->close();
        return $ret;
    }

    /**
     * 实际调用的执行sql的方法
     * @param type $db
     * @param type $sql
     * @param type $params
     * @return boolean
     */
    private function executeDo($db, $sql, $params = null) {
        if (empty($db)) {
            return false;
        }
        $stmt = $db->prepare($sql);
        if (empty($stmt)) {
            $this->checkIsDbError($db);
            return false;
        }

        // 有参数时，初始化sql参数
        if (isset($params)) {
            // 只有一个参数时，处理成数组，方便后面统一处理
            if (is_string($params) || is_int($params)) {
                $params = array($params);
            }
            if (is_array($params) && count($params) > 0) {
                // 数组第0位放置参数类型，就是bind_param的第一个参数
                $newParams = array('');
                foreach ($params as $val) {
                    $newParams[] = $val;
                    $newParams[0] .= (is_int($val) ? 'd' : 's');
                }

                $refFields = array();
                foreach ($newParams as $key => $val) {
                    $refFields[] = &$newParams[$key];
                }

                //$stmt->bind_param('ds', $p1, $p2);
                call_user_func_array(array($stmt, 'bind_param'), $refFields);
                unset($refFields);
                unset($newParams);
            }
            unset($params);
        }

        $stmt->execute();
        $this->checkIsDbError($db, $stmt);
        return $stmt;
    }

    /**
     * 创建数据库连接并返回
     * @return type
     */
    private function createDbLink() {
        $host = $this->host;
        $user = $this->user;
        $pwd = $this->pwd;
        $dbname = $this->dbname;
        $port = $this->port;
        $db = new mysqli($host, $user, $pwd, $dbname, $port);
        if (mysqli_connect_errno()) {
            if (!empty($db)) {
                $db->close();
            }
            throw new Exception('Unable to connect!' . mysqli_connect_error());
        }
        $db->set_charset('utf8');
        return $db;
    }

    /**
     * 检查db是否有异常，有则抛出
     * @param type $db
     * @param type $stmt
     * @throws Exception
     */
    private function checkIsDbError($db, $stmt = null) {
        $errno = $db->errno;
        if ($errno > 0) {
            $msg = $db->error;
            if (!empty($stmt)) {
                $stmt->close();
            }
            $db->close();
            throw new Exception($msg, $errno);
        }
    }

// </editor-fold>
}
