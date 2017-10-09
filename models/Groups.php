<?php

class Model_Groups extends Model_Base {

    /**
     * 返回指定应用的所有用户组
     * @param type $app
     * @return type
     */
    public function getGroups($app = '') {
        $sql = 'SELECT a.* FROM b_groups a WHERE a.`app`=?';
        $params = array($app);
        return $this->db->executeSql($sql, $params);
    }

    // <editor-fold defaultstate="collapsed" desc="获取权限列表的相关方法">

    /**
     * 返回指定用户组下的用户和所属角色列表,
     * 用于用户组编辑界面下拉选择列表 使用
     * @param type $gid
     * @param type $app
     * @return type
     */
    public function getUsersAndRolesByGid($gid = '', $app = '') {
        $sql1 = 'SELECT CONCAT(us.u_id, \':\',us.u_name) name,0 nametype FROM map_user_group mug, b_users us
            WHERE us.u_id=mug.u_id AND us.u_status=0 AND mug.map_status=0
              AND mug.g_id=? AND us.app=?';
        $sql2 = 'SELECT CONCAT(ro.r_id, \':\',ro.r_name) name,1 nametype FROM map_group_role mgr, b_roles ro
            WHERE ro.r_id=mgr.r_id AND ro.r_status=0 AND mgr.map_status=0
              AND mgr.g_id=? AND ro.app=?';
        $sql = '(' . $sql1 . ') union all (' . $sql2 . ')';
        $params = array($gid, $app, $gid, $app);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 返回指定用户组所属角色名列表
     * @param type $groupid
     * @param type $app
     * @return type
     */
    public function getRolesByGroup($groupid = '', $app = '') {
        $sql = 'SELECT a.r_name FROM b_roles a,map_group_role b
            WHERE a.r_id=b.r_id AND a.r_status=0 AND b.map_status=0
              AND b.g_id=? AND a.app=?';
        $params = array($groupid, $app);
        return $this->db->executeSql($sql, $params);
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="增删改相关方法">
    /**
     * 添加
     * @param type $data
     * @return int
     */
    public function addGroup($data) {
        $sql = 'INSERT INTO `b_groups` (`app`,`g_name`,`g_status`,`add_time`,`upd_time`,`lastip`)
VALUES(?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),?)';
        $params = array($data['app'], $data['g_name'], $data['g_status'], $data['lastip']);
        return $this->db->executeNoQuery($sql, $params);
    }

    /**
     * 更新用户组
     * @param type $data
     * @return int
     */
    public function updateGroup($data) {
        if (empty($data['g_id'])) {
            return false;
        }
        $sql = 'UPDATE `b_groups` SET ';
        $params = array();
        $sql .= sqlhelper::combineUpdateSql($data, $params);
        $sql .= ',upd_time=UNIX_TIMESTAMP() WHERE g_id=? AND app=?';
        $params[] = $data['g_id'];
        $params[] = $data['app'];

        return $this->db->executeNoQuery($sql, $params);
    }

    // </editor-fold>
}
