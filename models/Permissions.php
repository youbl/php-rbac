<?php

class Model_Permissions extends Model_Base {

    /**
     * 返回指定应用的所有权限
     * @param type $app
     * @return type
     */
    public function getPerms($app = '') {
        $sql = 'SELECT pe.* FROM b_permissions pe WHERE pe.`app`=?
            ORDER BY CASE WHEN pe.p_parentid=0 THEN pe.p_id ELSE pe.p_parentid END,pe.p_parentid,pe.p_id DESC';
        $params = array($app);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 返回指定应用的所有权限,带map映射关系
     * @param type $app
     * @return type
     */
    public function getPermsWithMap($app = '') {
        // 角色与权限的映射关系
        $sql0 = 'SELECT DISTINCT mrp.p_id,ro.r_id, ro.r_name,
                        sub1.u_id,sub1.u_name,
                        sub2.g_id,sub2.g_name
            FROM b_roles ro
           INNER JOIN map_role_perm mrp ON ro.r_id=mrp.r_id AND mrp.map_status=0 AND ro.app=?';
        // 角色与用户的映射关系
        $sql1 = 'SELECT mur.r_id,us.u_id,us.u_name FROM map_user_role mur, b_users us
            WHERE us.u_id=mur.u_id AND us.u_status=0 AND mur.map_status=0 AND us.app=?';
        // 角色与用户组的映射关系
        $sql2 = 'SELECT mgr.r_id,gr.g_id,gr.g_name FROM map_group_role mgr, b_groups gr
            WHERE gr.g_id=mgr.g_id AND gr.g_status=0 AND mgr.map_status=0 AND gr.app=?';

        $sqlallmap = $sql0 . '
            LEFT JOIN (' . $sql1 . ') sub1 ON ro.r_id=sub1.r_id
            LEFT JOIN (' . $sql2 . ') sub2 ON ro.r_id=sub2.r_id';

        $sql = 'SELECT pe.*,
                CONCAT(sub0.r_id, \':\',sub0.r_name) rname,
                CONCAT(sub0.u_id, \':\',sub0.u_name) uname,
                CONCAT(sub0.g_id, \':\',sub0.g_name) gname
             FROM b_permissions pe
            LEFT JOIN (' . $sqlallmap . ') sub0 ON pe.p_id=sub0.p_id
            WHERE pe.`app`=?
            ORDER BY CASE WHEN pe.p_parentid=0 THEN pe.p_id ELSE pe.p_parentid END,pe.p_parentid,pe.p_id DESC';
        $params = array($app, $app, $app, $app);
        return $this->db->executeSql($sql, $params);
    }

    // <editor-fold defaultstate="collapsed" desc="增删改相关方法">
    /**
     * 添加
     * @param type $data
     * @return int
     */
    public function addPerm($data) {
        $sql = 'INSERT INTO `b_permissions` (`app`,`p_val`,`p_desc`,`p_parentid`,`p_status`,`add_time`,`upd_time`,`lastip`)
VALUES(?,?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),?)';
        $params = array($data['app'], $data['p_val'], $data['p_desc'], $data['p_parentid'], $data['p_status'], $data['lastip']);
        return $this->db->executeNoQuery($sql, $params);
    }

    /**
     * 更新用户组
     * @param type $data
     * @return int
     */
    public function updatePerm($data) {
        if (empty($data['p_id'])) {
            return false;
        }
        $sql = 'UPDATE `b_permissions` SET ';
        $params = array();
        $sql .= sqlhelper::combineUpdateSql($data, $params);
        $sql .= ',upd_time=UNIX_TIMESTAMP() WHERE p_id=? AND app=?';
        $params[] = $data['p_id'];
        $params[] = $data['app'];
        return $this->db->executeNoQuery($sql, $params);
    }

    // </editor-fold>
}
