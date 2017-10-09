<?php

/**
 * 定义了zip相关的一些方法集
 *
 * @author youbeiliang01@baidu.com
 * @date 2014-07-31
 */
final class ziphelper {

    /**
     * 比较2个压缩文件内容是否一致，仅比较文件个数和文件名，不比较文件内容
     * @param type $file1
     * @param type $file2
     * @return boolean
     */
    public static function compare($file1, $file2) {
        $arr1 = self::getFileList($file1);
        if (empty($arr1)) {
            return false;
        }
        $arr2 = self::getFileList($file2);
        if (empty($arr2)) {
            return false;
        }

        $cnt = count($arr1);
        if ($cnt != count($arr2)) {
            return false;
        }

        sort($arr1);
        sort($arr2);
        //var_export($arr1);
        //var_export($arr2);
        for ($i = 0; $i < $cnt; $i++) {
            if ($arr1[$i] != $arr2[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取指定压缩包里的全部文件清单
     * @param type $zipfile
     * @param type $containSubDir 是否要包含子目录的文件
     * @param type $ignorePath 要忽略的路径起始值
     * @return boolean 返回false表示出错，返回数组表示文件清单
     */
    public static function getFileList($zipfile, $containSubDir = true, $ignorePath = 'META-INF/') {
        if (empty($zipfile) || !file_exists($zipfile)) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($zipfile) !== TRUE) {
//            $zip->close();
            return false;
        }
        $arr = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            // 不需要子目录时
            if (!$containSubDir && strpos($filename, '/') !== false) {
                continue;
            }

            // 最后一个字符是/时，表示目录，忽略
            if (substr($filename, -1) == '/') {
                continue;
            }

            if (!empty($ignorePath) && strpos($filename, $ignorePath) === 0) {
                // 不比较 META-INF/ 下的签名文件
                continue;
            }
            $arr[] = $filename;
        }
        $zip->close();
        unset($zip);
        return $arr;
    }

    /**
     * 返回zip包里指定路径的文件内容
     * @param type $zipfile
     * @param type $inZipFilename
     * @return boolean false表示返回失败
     */
    public static function getContent($zipfile, $inZipFilename) {
        if (empty($zipfile) || !file_exists($zipfile)) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($zipfile) !== TRUE) {
            $zip->close();
            return false;
        }
        // 读取内容失败时会返回false
        $ret = $zip->getFromName($inZipFilename);
        $zip->close();
        return $ret;
    }

    /**
     * 从zip文件中删除指定的目录前缀文件
     * @param type $zipfile
     * @param type $dirStart
     * @return string|int
     */
    public static function delFiles($zipFile, $dirStart = 'META-INF/') {
        if (empty($dirStart)) {
            return '要删除的目录不能为空';
        }
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            $zip->close();
            return '压缩包打开失败:' . $zipFile;
        }

        $cnt = 0;
        $totalFiles = $zip->numFiles;
        for ($i = 0; $i < $totalFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, $dirStart) === 0) {
                //echo $filename . "\n";
                //echo $zip->deleteName($filename) . "\n";
                if ($zip->deleteIndex($i) !== TRUE) {
                    $zip->close();
                    return $filename . ' 删除失败.' . $cnt;
                }
                $cnt++;
            }
        }
        $zip->close();
        return $cnt;
    }

}
