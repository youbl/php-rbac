<?php

class Model_Roles extends Model_Base {

    public function getRoles($app = '') {
        $sql = 'SELECT ro.* FROM b_roles ro WHERE ro.`app`=?';
        $params = array($app);
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
