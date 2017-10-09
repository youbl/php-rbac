<?php

/**
 * 定义了字符串通用的静态方法集
 *
 * @author youbeiliang01@baidu.com
 * @date 2014-07-28
 */
final class strhelper {

    /**
     * 返回字符串字节长度,一个汉字按长度2计算
     * @param type $str
     * @return int
     */
    static function strlen($str) {
        if (!isset($str)) {
            return 0;
        }
        if (!is_string($str)) {
            $str = (string) $str;
        }
        // 文件编码为utf8时，strlen会把一个汉字当3长度计算，所以这里除2
        /*
         * 需要注意的是如果文件编码是gb2312，strlen会把汉字当2长度计算，就不能用这个方法了
         * 举例字符串：'中文a字1符'
         * 文件保存为utf8时，strlen返回14，mb_strlen返回6
         * 文件保存为gb2312时，strlen返回10，mb_strlen返回7
         */
        return (strlen($str) + mb_strlen($str, 'UTF8')) / 2;
    }

    /**
     * 根据要求的字符串字节长度, 裁剪字符串返回，一个汉字按长度2计算
     * @param type $str
     * @param type $len
     * @return int
     */
    static function cutstr($str, $len) {
        if (!isset($str)) {
            return '';
        }
        if (!is_string($str)) {
            $str = (string) $str;
        }
        $realLen = self::strlen($str);
        while ($realLen > $len) {
            $str = substr($str, 0, strlen($str) - 1);
            $realLen = self::strlen($str);
        }
        return $str;
    }

    /**
     * 与百度接口对接通用的签名算法
     * @param type $params
     * @param type $secret
     * @param type $str 返回拼接后的字符串，用于记录日志
     * @return type
     */
    static function generate_sign($params, $secret = 'abcdefgh', &$str = '') {
        $str = '';  //签名字符串
        if (is_array($params)) {
            //先将参数以其参数名的字母升序进行排序
            ksort($params);
            //遍历排序后的参数数组中的每一个key/value对
            foreach ($params as $k => $v) {
                //为key/value对生成一个key=value格式的字符串，并拼接到签名字符串后面
                if ($k != 'sign') {
                    $str .= "$k=$v";
                }
            }
        } else {
            $str .= $params;
        }
        //将签名密钥拼接到签名字符串最后面
        $str .= $secret;
        //返回待签名字符串的md5签名值
        return md5($str);
    }

    /**
     * 判断参数是否 整数 或 整数字符串
     * @param type $str
     * @param type $canNegative 是否允许为负数
     * @return boolean
     */
    static function isint($str, $canNegative = false) {
        if (!isset($str)) {
            return false;
        }
        if (is_int($str)) {
            if ($canNegative) {
                return true;
            }
            return $str >= 0;
        }
        if (is_string($str)) {
            if ($canNegative) {
                return preg_match('/^[\+\-]?\d+$/', $str);
            }
            return preg_match('/^\d+$/', $str);
        }
        return false;
    }

    /**
     * 判断参数是否日期格式字符串, 只允许y-m-d格式
     * @param type $str
     */
    static function isdate($str) {
        if (!isset($str)) {
            return false;
        }
        if (is_string($str) && preg_match('/^\d{1,4}-\d{1,2}-\d{1,2}$/', $str)) {
            return true;
        }
        return false;
    }

    /**
     * @brief 中文 英文 以及混合字符 字符串截取(中文为utf8编码)
     * @param string $string  需要截取的字符串
     * @param int $start 截取的起始位置
     * @param int $length 截取的长度
     * @return string
     */
    static function mixed_substr($string, $start, $length) {
        if (strlen($string) > $length) {
            $str = null;
            $len = $start + $length;
            for ($i = $start; $i < $len; $i++) {
                if (ord(substr($string, $i, 1)) > 0xa0) {
                    $str .= substr($string, $i, 3);
                    $i += 2;
                } else {
                    $str .= substr($string, $i, 1);
                }
            }
            return $str . '···';
        } else {
            return $string;
        }
    }

    /**
     * 拼接not in的sql并返回
     * @param type $field
     * @param type $values 选项列表，为空时，返回 'field!=\'\''
     * @param type $not 为true时表示not in
     * @return string 返回格式参考 'field NOT IN(1,2)'
     */
    static function where_not_in($field, $values) {
        return self::where_in($field, $values, true);
    }

    /**
     * 拼接in的sql并返回
     * @param type $field
     * @param type $values 选项列表，为空时，返回 'field=\'\''
     * @param type $not 为true时表示not in
     * @return string 返回格式参考 'field IN(1,2)'
     */
    static function where_in($field, $values, $not = false) {
        if (!isset($field)) {
            $field = '';
        }
        if (!isset($values)) {
            if ($not) {
                return $field . '!=\'\'';
            } else {
                return $field . '=\'\'';
            }
        }
        if (!is_array($values)) {
            $values = array($values);
        }
        $in_str = '';
        foreach ($values as $value) {
            $val = $value;
            if (is_string($value)) {
                $val = '\'' . str_replace('\'', '\'\'', $value) . '\'';
            } else if (!is_int($value)) {
                continue;
            }
            if ($in_str !== '') {
                $in_str .= ',';
            }
            $in_str .= $val;
        }
        if ($in_str === '') {
            $in_str = '\'\'';
        }
        $ret = $field . ($not ? ' NOT IN(' : ' IN(') . $in_str . ')';
        return $ret;
    }

    /**
     * 拼接limit分类sql并返回
     * @param type $page
     * @param type $pageSize
     * @return type
     */
    static function limit($page, $pageSize = 20) {
        if (empty($page) || !self::isint($page)) {
            return ' limit 0,' . $pageSize;
        }
        $pageInt = (int) $page;
        if ($pageInt <= 0) {
            $pageInt = 1;
        }
        $offset = ( $pageInt - 1) * $pageSize;
        return ' limit ' . $offset . ',' . $pageSize;
    }

    /**
     * 获取随机数，通常用于验证码
     * @length 生成的随机字符串长度
     * @return 返回生成的随机字符串
     */
    public function getRandCode($length) {
        $chars = "2345678abcdefghgkmnpqrstuvwxyz";
        $code = "";
        $charlen = strlen($chars);
        for ($index = 0; $index < $length; ++$index) {
            $code .= $chars[rand(0, $charlen - 1)];
        }
        return $code;
    }

    /**
     * Generate a random string of specifified length
     * 目前应用创建产生的api key和secret key使用该算法，切记不能改动。
     *
     * @author wulin02(wulin02@baidu.com)
     * @param  int    $len    default 32
     * @param  string $seed
     * @return string
     */
    public function generate_rand_str($len = 32, $seed = '') {
        if (empty($seed)) {
            $seed = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ';
        }
        $seed_len = strlen($seed);
        $word = '';
        //随机种子更唯一
        mt_srand((double) microtime() * 1000000 * getmypid());
        for ($i = 0; $i < $len; ++$i) {
            $word .= $seed{mt_rand() % $seed_len};
        }
        return $word;
    }

    /**
     * 获取字符串的hash数值
     * @param type $str
     * @return string
     */
    static function hashCode($str) {
        if (empty($str)) {
            return '';
        }
        $mdv = md5($str);
        $mdv1 = substr($mdv, 0, 16);
        $mdv2 = substr($mdv, 16, 16);
        $crc1 = abs(crc32($mdv1));
        $crc2 = abs(crc32($mdv2));
        return bcmul($crc1, $crc2);
    }

    /**
     * php的trim会出现乱码问题，用这个代替
     * 注： 原来的 0xc2 和 0xa0已经包含在 \s里了
     * 参考： http://blog.csdn.net/mervyn1205/article/details/47291941
     * 问题代码及字符串:
     *  trim('平安普惠', chr(0xc2) . chr(0xa0)) // 这是去除nbsp这种空格
     *  trim('传动在线', "\xEF\xBB\xBF");       // 这是去除字符串里的bom头
     *  trim('暴走漫画', "\xEF\xBB\xBF");
     *  trim('研发、产品、', '、');
     * @param type $string
     * @param type $trim_chars
     * @return type
     */
    static function mb_trim($string, $trim_chars = '\s') {
        // u模式符表示 字符串被当成 UTF-8处理
        return preg_replace('/(^[' . $trim_chars . ']+)|([' . $trim_chars . ']+$)/u', '', $string);
    }

    /**
     * 判断字符串前3位是否bom，是就替换
     * @param type $str
     * @return type
     */
    static function replaceBom($str) {
        if (strlen($str) < 3) {
            return $str;
        }
        $ch1 = ord(substr($str, 0, 1));
        $ch2 = ord(substr($str, 1, 1));
        $ch3 = ord(substr($str, 2, 1));
        // 对应16进制就是 EF BB BF
        if ($ch1 === 239 && $ch2 === 187 && $ch3 === 191) {
            return substr($str, 3);
        } else {
            return $str;
        }
    }

}
