<?php

class Model_MapUserGroup extends Model_Base {

    // <editor-fold defaultstate="collapsed" desc="增删改相关方法">
    /**
     * 添加多条记录：用户与角色的映射
     * @param type $data
     * @param type $keyfield
     * @param type $valfield
     * @return int
     */
    public function addMultiMap($data, $keyfield, $valfield) {
        if (empty($data)) {
            return false;
        }
        $sql = 'INSERT INTO `map_user_group` (`' . $keyfield . '`,`' . $valfield
                . '`,`map_status`,`add_time`,`lastip`)VALUES';
        $sqlval = '';
        foreach ($data as $item) {
            if ($sqlval !== '') {
                $sqlval .= ',';
            }
            $keyid = intval($item['id1']);
            $valid = intval($item['id2']);
            $ip = str_replace('\'', '', $item['lastip']);
            $sqlval .= '(' . $keyid . ',' . $valid . ',0,UNIX_TIMESTAMP(),\'' . $ip . '\')';
        }
        return $this->db->executeNoQuery($sql . $sqlval);
    }

    /**
     * 删除指定用户到用户组的映射
     * @param type $keyfield
     * @param type $id
     * @return int
     */
    public function deleteById($keyfield, $id) {
        if (empty($id)) {
            return false;
        }
        $sql = 'DELETE FROM `map_user_group` WHERE ' . $keyfield . '=?';
        return $this->db->executeNoQuery($sql, $id);
    }

    // </editor-fold>
}
