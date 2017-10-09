<?php

class Model_Users extends Model_Base {

    /**
     * 返回指定应用的所有用户
     * @param type $app
     * @return type
     */
    public function getUsers($app = '') {
        $sql = 'SELECT a.* FROM b_users a WHERE a.`app`=?';
        $params = array($app);
        return $this->db->executeSql($sql, $params);
    }

    // <editor-fold defaultstate="collapsed" desc="获取权限列表的相关方法">

    /**
     * 返回指定用户所属角色名列表和用户组列表,
     * 用于用户编辑界面下拉选择列表 使用
     * @param type $uid
     * @param type $app
     * @return type
     */
    public function getRolesAndGroupsByUid($uid = '', $app = '') {
        $sql1 = 'SELECT CONCAT(ro.r_id, \':\',ro.r_name) name,0 nametype FROM b_roles ro,map_user_role mur
            WHERE ro.r_id=mur.r_id AND ro.r_status=0 AND mur.map_status=0
              AND mur.u_id=? AND ro.app=?';
        $sql2 = 'SELECT CONCAT(gr.g_id, \':\',gr.g_name),1 nametype FROM map_user_group mug, b_groups gr
            WHERE gr.g_id=mug.g_id AND gr.g_status=0 AND mug.map_status=0
              AND mug.u_id=? AND gr.app=?';
        $sql = '(' . $sql1 . ') union all (' . $sql2 . ')';
        $params = array($uid, $app, $uid, $app);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 返回指定用户所属用户组对应的角色名列表
     * @param type $uid
     * @param type $app
     * @return type
     */
    public function getGroupRolesByUid($uid = '', $app = '') {
        $sql = 'SELECT DISTINCT ro.r_name FROM b_roles ro,map_group_role mgr,map_user_group mug
            WHERE ro.r_id=mgr.r_id AND ro.r_status=0 AND mgr.map_status=0
              AND mug.map_status=0 AND mug.g_id=mgr.g_id
              AND mug.u_id=? AND ro.app=?';
        $params = array($uid, $app);
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 获取指定账号所属角色及分组的权限列表
     * @param type $account
     * @param type $app
     * @param type $permission
     * @return type
     */
    public function getAllRights($account, $app = '', $permission = null) {
        // 不允许数组调用，通常都是字符串
        if (is_array($account) || is_array($app) || is_array($permission)) {
            return false;
        }
        // 根据用户与角色的映射关系查找是否拥有权限, DISTINCT是避免多个角色拥有同一权限
        $sql1 = 'SELECT DISTINCT d.p_parentid, d.p_id, d.p_val,d.p_desc
 FROM b_users a, map_user_role b, map_role_perm c, b_permissions d
WHERE a.`u_id`=b.`u_id` AND b.`r_id`=c.`r_id` AND c.`p_id`=d.`p_id` AND a.app=d.app
AND a.`u_status`=0 AND b.`map_status`=0 AND c.`map_status`=0 AND d.`p_status`=0
AND a.`account`=? AND a.`app`=?';
        $params1 = array($account, $app);
        // 根据用户与分组的映射关系查找是否拥有权限
        $sql2 = 'SELECT DISTINCT e.p_parentid,e.p_id, e.p_val,e.p_desc
 FROM b_users a,map_user_group b,map_group_role c, map_role_perm d, b_permissions e
WHERE a.`u_id`=b.`u_id` AND b.`g_id`=c.`g_id` AND c.`r_id`=d.`r_id` AND d.`p_id`=e.`p_id` AND a.app=e.app
AND a.`u_status`=0 AND b.`map_status`=0 AND c.`map_status`=0 AND d.`map_status`=0 AND e.`p_status`=0
AND a.`account`=? AND a.`app`=?';
        $params2 = array($account, $app);
        if (isset($permission)) {
            $sql1 .= ' AND d.`p_val`=?';
            $sql2 .= ' AND e.`p_val`=?';
            $params1[] = $permission;
            $params2[] = $permission;
        }
        $params1 = array_merge($params1, $params2);
        $finalSql = '(' . $sql1 . ') UNION (' . $sql2 . ') ORDER BY p_parentid,p_id';
        return $this->db->executeSql($finalSql, $params1);
    }

    /**
     * 获取指定账号映射的角色的权限列表
     * @param type $account
     * @param type $app
     * @param type $permission
     * @return type
     */
    public function getAllUserRights($account, $app = '', $permission = null) {
        // 不允许数组调用，通常都是字符串
        if (is_array($account) || is_array($app) || is_array($permission)) {
            return false;
        }
        $sql = 'SELECT DISTINCT d.p_id, d.p_val
 FROM b_users a, map_user_role b, map_role_perm c, b_permissions d
WHERE a.`u_id`=b.`u_id` AND b.`r_id`=c.`r_id` AND c.`p_id`=d.`p_id` AND a.app=d.app
AND a.`u_status`=0 AND b.`map_status`=0 AND c.`map_status`=0 AND d.`p_status`=0
AND a.`account`=? AND a.`app`=?';
        $params = array($account, $app);
        if (isset($permission)) {
            $sql .= ' AND d.`p_val`=?';
            $params[] = $permission;
        }
        return $this->db->executeSql($sql, $params);
    }

    /**
     * 获取指定账号所属分组的权限列表
     * @param type $account
     * @param type $app
     * @param type $permission
     * @return type
     */
    public function getAllGroupRights($account, $app = '', $permission = null) {
        // 不允许数组调用，通常都是字符串
        if (is_array($account) || is_array($app) || is_array($permission)) {
            return false;
        }
        $sql = 'SELECT DISTINCT e.p_id, e.p_val
 FROM b_users a,map_user_group b,map_group_role c, map_role_perm d, b_permissions e
WHERE a.`u_id`=b.`u_id` AND b.`g_id`=c.`g_id` AND c.`r_id`=d.`r_id` AND d.`p_id`=e.`p_id` AND a.app=e.app
AND a.`u_status`=0 AND b.`map_status`=0 AND c.`map_status`=0 AND d.`map_status`=0 AND e.`p_status`=0
AND a.`account`=? AND a.`app`=?';
        $params = array($account, $app);
        if (isset($permission)) {
            $sql .= ' AND e.`p_val`=?';
            $params[] = $permission;
        }
        return $this->db->executeSql($sql, $params);
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="增删改相关方法">
    /**
     * 添加用户
     * @param type $data
     * @return int
     */
    public function addUser($data) {
        $sql = 'INSERT INTO `b_users` (`app`,`account`,`u_name`,`u_status`,`add_time`,`upd_time`,`lastip`)
VALUES(?,?,?,?,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),?)';
        $params = array($data['app'], $data['account'], $data['u_name'], $data['u_status'], $data['lastip']);
        return $this->db->executeNoQuery($sql, $params);
    }

    /**
     * 更新用户
     * @param type $data
     * @return int
     */
    public function updateUser($data) {
        if (empty($data['u_id'])) {
            return false;
        }
        $sql = 'UPDATE `b_users` SET ';
        $params = array();
        $sql .= sqlhelper::combineUpdateSql($data, $params);
        $sql .= ',upd_time=UNIX_TIMESTAMP() WHERE u_id=? AND app=?';
        $params[] = $data['u_id'];
        $params[] = $data['app'];

        return $this->db->executeNoQuery($sql, $params);
    }

    // </editor-fold>
}
