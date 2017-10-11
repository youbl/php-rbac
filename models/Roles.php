<?php

class Model_Roles extends Model_Base {

    /**
     * 返回指定应用的所有角色
     * @param type $app
     * @return type
     */
    public function getRoles($app = '') {
        $sql = 'SELECT ro.* FROM b_roles ro WHERE ro.`app`=?';
        $params = array($app);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 返回指定应用的所有角色,带map映射关系
     * @param type $app
     * @return type
     */
    public function getRolesWithMap($app = '') {
        // 角色与权限的映射关系
        $sql0 = 'SELECT mrp.r_id, pe.p_desc, pe.p_id FROM b_permissions pe, map_role_perm mrp
            WHERE pe.p_id=mrp.p_id AND mrp.map_status=0 AND pe.app=?';
        // 角色与用户的映射关系
        $sql1 = 'SELECT mur.r_id,us.u_id,us.u_name FROM map_user_role mur, b_users us
            WHERE us.u_id=mur.u_id AND us.u_status=0 AND mur.map_status=0 AND us.app=?';
        // 角色与用户组的映射关系
        $sql2 = 'SELECT mgr.r_id,gr.g_id,gr.g_name FROM map_group_role mgr, b_groups gr
            WHERE gr.g_id=mgr.g_id AND gr.g_status=0 AND mgr.map_status=0 AND gr.app=?';

        $sql = 'SELECT ro.*,
                CONCAT(sub0.p_id, \':\',sub0.p_desc) pname,
                CONCAT(sub1.u_id, \':\',sub1.u_name) uname,
                CONCAT(sub2.g_id, \':\',sub2.g_name) gname
             FROM b_roles ro
            LEFT JOIN (' . $sql0 . ') sub0 ON ro.r_id=sub0.r_id
            LEFT JOIN (' . $sql1 . ') sub1 ON ro.r_id=sub1.r_id
            LEFT JOIN (' . $sql2 . ') sub2 ON ro.r_id=sub2.r_id
            WHERE ro.`app`=?';
        $params = array($app, $app, $app, $app);
        return $this->db->executeSql($sql, $params);
    }

    // <editor-fold defaultstate="collapsed" desc="获取权限列表的相关方法">

    /**
     * 返回指定角色下的用户和用户组列表,
     * 用于角色编辑界面下拉选择列表 使用
     * @param type $rid
     * @param type $app
     * @return type
     */
    public function getUsersAndGroupsByRid($rid = '', $app = '') {
        $sql1 = 'SELECT CONCAT(us.u_id, \':\',us.u_name) name,0 nametype FROM b_users us,map_user_role mur
            WHERE us.u_id=mur.u_id AND us.u_status=0 AND mur.map_status=0
              AND mur.r_id=? AND us.app=?';
        $sql2 = 'SELECT CONCAT(gr.g_id, \':\',gr.g_name) name,1 nametype FROM map_group_role mgr, b_groups gr
            WHERE gr.g_id=mgr.g_id AND gr.g_status=0 AND mgr.map_status=0
              AND mgr.r_id=? AND gr.app=?';
        $sql = '(' . $sql1 . ') union all (' . $sql2 . ')';
        $params = array($rid, $app, $rid, $app);
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
    public function addRole($data) {
        $sql = 'INSERT INTO `b_roles` (`app`,`r_name`,`r_desc`,`r_status`,`add_time`,`upd_time`,`lastip`)
VALUES(?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),?)';
        $params = array($data['app'], $data['r_name'], $data['r_desc'], $data['r_status'], $data['lastip']);
        return $this->db->executeNoQuery($sql, $params);
    }

    /**
     * 更新
     * @param type $data
     * @return int
     */
    public function updateRole($data) {
        if (empty($data['r_id'])) {
            return false;
        }
        $sql = 'UPDATE `b_roles` SET ';
        $params = array();
        $sql .= sqlhelper::combineUpdateSql($data, $params);
        $sql .= ',upd_time=UNIX_TIMESTAMP() WHERE r_id=? AND app=?';
        $params[] = $data['r_id'];
        $params[] = $data['app'];

        return $this->db->executeNoQuery($sql, $params);
    }

    // </editor-fold>
}
