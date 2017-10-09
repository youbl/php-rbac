<?php

/**
 * 文件操作静态类库
 * @author hi_yijinkun01@baidu.com
 * @date 2015-03-27
 */
final class filehelper {

    /**
     * 返回指定文件的扩展名，默认不含小数点
     * @param type $filename
     * @param type $addPoint 是否要加前缀小数点
     * @return type
     */
    public static function getExt($filename, $addPoint = false) {
        $ret = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ret !== '') {
            $ret = strtolower($ret);
            if ($addPoint) {
                $ret = '.' . $ret;
            }
        }
        return $ret;
    }

    /**
     * 返回不含路径的文件名（带扩展名）
     * @param type $filename
     * @return type
     */
    public static function getName($filename) {
        //return pathinfo($filename, PATHINFO_BASENAME);
        return basename($filename); // 跟上面一行等效
    }

    /**
     * 返回指定文件的扩展名，不含小数点
     * @param type $filename
     * @return type
     */
    public static function getDir($filename) {
        //return pathinfo($filename, PATHINFO_DIRNAME);
        return dirname($filename); // 跟上面一行等效
    }

    /**
     * @breif 写CSV文件
     * @param array $arrHeader 文件标题数组
     * @param array $arrExportData 文件数据数组
     * @param string $filepath 文件路径
     * @return array ['code':0,'msg':''] code 成功1 失败0 msg 原因
     */
    static function writeCSVFile($arrHeader, $arrExportData, $filepath) {
        $result = array('code' => 0, 'msg' => '写文件失败');

        $strHeader = implode(',', $arrHeader) . "\r\n";    // 表格头部
        $strHeader = @iconv('utf-8', 'gbk', $strHeader);
        $strData = '';      // 表体数据

        foreach ($arrExportData as $value) {
            foreach ($value as $item) {
                $tempValue = str_replace('"', '""', $item);
                $tempValue = "\"" . $tempValue . "\""; //防止数据内换行或逗号、双引号等特殊字符
                $strData .= trim($tempValue) . ' ,';
            }
            $strData = trim($strData, ',') . " \r\n";
        }

        if (function_exists('mb_convert_encoding')) {
            $strData = mb_convert_encoding($strData, 'gbk', 'utf-8');
        } else {
            $strData = @iconv('utf-8', 'gbk', $strData);
        }

        $txt = $strHeader . $strData;
        //若文件存在先删除
        self::del_file($filepath);
        self::create_dir($filepath);

        if (!$fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
            $result['code'] = 0;
            $result['msg'] = '文件打开失败,文件地址：' . $filepath;
            return $result;
        }
        if (!file_exists($filepath)) {
            $result['code'] = 0;
            $result['msg'] = '文件创建失败,文件地址：' . $filepath;
            return $result;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $txt);
        flock($fp, LOCK_UN);
        fclose($fp);
        chmod($filepath, FILE_WRITE_MODE);
        $result['code'] = 1;
        $result['msg'] = '文件创建成功,文件地址：' . $filepath;
        return $result;
    }

    /**
     * 判断文件所在目录是否存在，不存在时创建目录
     * @param string $fileOrDir
     * @return boolean
     */
    public static function create_dir($fileOrDir, $isfile = true) {
        if ($isfile === true) {
            $dir = dirname($fileOrDir);
        } else {
            $dir = $fileOrDir;
        }
        if (is_dir($dir)) {
            return true;
        }

        // mkdir()函数指定的目录权限只能小于等于系统umask设定的默认权限，所以需要再chmod一次
        mkdir($dir, 0757, true);
        chmod($dir, 0757);
        return true;
    }

    /**
     * 温柔地删除文件
     * @param type $filePath
     * @return bool 文件不存在，返回true，否则返回删除成功与否
     */
    public static function del_file($filePath) {
        if (empty($filePath)) {
            return true;
        }
        clearstatcache();
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * 取得图像类型的文件后缀
     * @param type $imagetype IMAGETYPE_XXX 系列常量之一。枚举见http://php.net/manual/zh/function.exif-imagetype.php
     * @param type $include_dot 是否在后缀名前加一个点。默认是 TRUE。
     * @return 文件后缀名，图像类型为空或异常返回false
     */
    public static function image_type_to_ext($imagetype, $include_dot = true) {
        //参考PHP方法 image_type_to_extension
        //http://php.net/manual/zh/function.image-type-to-extension.php
        if (empty($imagetype))
            return false;
        $dot = $include_dot ? '.' : '';
        switch ($imagetype) {
            case IMAGETYPE_GIF : return $dot . 'gif';
            case IMAGETYPE_JPEG : return $dot . 'jpg';
            case IMAGETYPE_PNG : return $dot . 'png';
            case IMAGETYPE_SWF : return $dot . 'swf';
            case IMAGETYPE_PSD : return $dot . 'psd';
            case IMAGETYPE_BMP : return $dot . 'bmp';
            case IMAGETYPE_TIFF_II : return $dot . 'tif';
            case IMAGETYPE_TIFF_MM : return $dot . 'tif';
            case IMAGETYPE_JPC : return $dot . 'jpc';
            case IMAGETYPE_JP2 : return $dot . 'jp2';
            case IMAGETYPE_JPX : return $dot . 'jpf';
            case IMAGETYPE_JB2 : return $dot . 'jb2';
            case IMAGETYPE_SWC : return $dot . 'swc';
            case IMAGETYPE_IFF : return $dot . 'aiff';
            case IMAGETYPE_WBMP : return $dot . 'wbmp';
            case IMAGETYPE_XBM : return $dot . 'xbm';
            default : return false;
        }
    }

    /**
     * 在指定目录下查找不存在的文件名序列，并返回，
     * 用于添加新文件的文件命名，避免重复.
     * 举例：
     * 假设：$dir='/home/work';$filename='a.txt';
     * 如果/home/work/a.txt不存在，函数返回：/home/work/a.txt
     * 如果/home/work/a.txt存在，函数返回：/home/work/a-1.txt
     * 如果/home/work/a.txt存在，a-1.txt也存在，函数返回：/home/work/a-2.txt
     * 以此类推
     * @param type $dir
     * @param type $filename
     * @return type
     */
    public static function getNoExistFileName($dir, $filename) {
        $nameNoExt = pathinfo($filename, PATHINFO_FILENAME);
        $ext = self::getExt($filename, true);
        $idx = 0;

        $ret = $dir . '/' . $nameNoExt . $ext;
        while (file_exists($ret)) {
            $idx++;
            $ret = $dir . '/' . $nameNoExt . '-' . $idx . $ext;
        }
        return $ret;
    }

    /**
     * 获取文件大小描述
     * @param type $sizeOrFile
     * @return string
     */
    public static function getSizeDesc($sizeOrFile) {
        if (empty($sizeOrFile)) {
            return '0';
        }
        if (is_numeric($sizeOrFile)) {
            $size = floatval($sizeOrFile);
        } else if (is_file($sizeOrFile)) {
            clearstatcache();
            $size = filesize($sizeOrFile);
        } else {
            $size = floatval($sizeOrFile);
        }
        if (empty($size)) {
            return '0b';
        }
        $ret = _getSizeDesc($size, 1, 'b');
        if ($ret !== false) {
            return $ret;
        }
        $ret = _getSizeDesc($size, 1024, 'kb');
        if ($ret !== false) {
            return $ret;
        }
        $ret = _getSizeDesc($size, 1024 * 1024, 'mb');
        if ($ret !== false) {
            return $ret;
        }
        $ret = _getSizeDesc($size, 1024 * 1024 * 1024, 'gb');
        if ($ret !== false) {
            return $ret;
        }
        $ret = _getSizeDesc($size, 1024 * 1024 * 1024 * 1024, 'tb');
        if ($ret !== false) {
            return $ret;
        }
        return strval(ceil($size)) . 'b';
    }

    /**
     * getSizeDesc辅助函数
     * @param type $size
     * @param type $unit
     * @param type $unitname
     * @return boolean
     */
    private function _getSizeDesc($size, $unit, $unitname) {
        if ($size < $unit * 1024) {
            return strval(ceil($size / $unit)) . $unitname;
        }
        return false;
    }

}
