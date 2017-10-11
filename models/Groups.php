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

    /**
     * 返回指定应用的指定用户组
     * @param type $app
     * @param type $id
     * @return type
     */
    public function getGroupById($app, $id) {
        $sql = 'SELECT a.* FROM b_groups a WHERE a.`app`=? AND a.g_id=?';
        $params = array($app, $id);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 返回指定应用的所有用户组,带map映射关系
     * @param type $app
     * @return type
     */
    public function getGroupsWithMap($app = '') {
        $sql1 = 'SELECT mug.g_id,us.u_id,us.u_name FROM map_user_group mug, b_users us
            WHERE us.u_id=mug.u_id AND us.u_status=0 AND mug.map_status=0 AND us.app=?';
        $sql2 = 'SELECT mgr.g_id,ro.r_id,ro.r_name FROM map_group_role mgr, b_roles ro
            WHERE ro.r_id=mgr.r_id AND ro.r_status=0 AND mgr.map_status=0 AND ro.app=?';
        $sql = 'SELECT gr.*,
                CONCAT(sub1.u_id, \':\',sub1.u_name) uname,
                CONCAT(sub2.r_id, \':\',sub2.r_name) rname
             FROM b_groups gr
            LEFT JOIN (' . $sql1 . ') sub1 ON gr.g_id=sub1.g_id
            LEFT JOIN (' . $sql2 . ') sub2 ON gr.g_id=sub2.g_id
            WHERE gr.`app`=?';
        $params = array($app, $app, $app);
        return $this->db->executeSql($sql, $params);
    }

    // <editor-fold defaultstate="collapsed" desc="获取权限列表的相关方法">
    /**
     * 返回指定用户组拥有的权限列表
     * @param type $groupid
     * @param type $app
     * @return type
     */
    public function getPermsByGroup($groupid = '', $app = '') {
        $sql = 'SELECT DISTINCT pe.p_id,pe.p_val,pe.p_desc, pe.p_parentid
             FROM map_group_role mgr, map_role_perm mrp, b_permissions pe, b_roles ro
            WHERE mgr.r_id=mrp.r_id AND mgr.map_status=0 AND mrp.map_status=0
              AND pe.p_status=0 AND pe.p_id=mrp.p_id
              AND ro.r_status=0 AND ro.r_id=mgr.r_id AND ro.app=pe.app
              AND mgr.g_id=? AND pe.app=?
              ORDER BY CASE WHEN pe.p_parentid=0 THEN pe.p_id ELSE pe.p_parentid END,pe.p_parentid,pe.p_id DESC';
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
