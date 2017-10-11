<?php

/**
 * @name Controller_Admin
 * @author internal\youbeiliang01
 * @desc 权限管理控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class Controller_Admin extends Controller_Base {

    protected function init() {
        parent::init();
    }

    // <editor-fold defaultstate="collapsed" desc="action入口方法">

    /**
     * 根据指定的app和账号，返回权限列表
     * @param type $app
     * @param type $account
     */
    public function permissionsAction($app = '', $account = '') {
        if (!empty($account)) {
            $modelUser = new Model_Users();
            $users = $modelUser->getAllRights($account, $app);
            if (empty($users)) {
                jsonhelper::jsonError($account . ' has no permission found.');
            } else {
                jsonhelper::jsonOutput(true, $users);
            }
        }

        if ($this->isGet()) {
            // 返回列表数据
            return $this->showPerms($app);
        }

        if ($this->isPost()) {
            $flg = $this->getQuery('flg');
            switch ($flg) {
                case 'edit':
                    return $this->addOrUpdatePerm();
                case 'permrole':
                    return $this->mapEdit($flg);
                case 'disable':
                    return $this->disabledPerm();
                default :
                    return jsonhelper::jsonOutput(false, 'not valid operation');
            }
        }
    }

    /**
     * 用户相关操作统一入口
     * @param type $app
     * @return type
     */
    public function usersAction($app = '') {
        if ($this->isGet()) {
            $userid = $this->getQueryInt('uid');
            if (empty($userid)) {
                // 返回列表数据
                return $this->showUsers($app);
            }

            // 返回指定用户的权限列表数据
            return $this->showUserPerms($app, $userid);
        }

        if ($this->isPost()) {
            $flg = $this->getQuery('flg');
            switch ($flg) {
                case 'edit':
                    return $this->addOrUpdateUser();
                case 'role':
                case 'group':
                    return $this->mapEdit($flg);
                case 'disable':
                    return $this->disabledUser();
                default :
                    return jsonhelper::jsonOutput(false, 'not valid operation');
            }
        }
    }

    /**
     * 角色相关操作统一入口
     * @param type $app
     * @return type
     */
    public function rolesAction($app = '') {
        if ($this->isGet()) {
            // 返回列表数据
            return $this->showRoles($app);
        }

        if ($this->isPost()) {
            $flg = $this->getQuery('flg');
            switch ($flg) {
                case 'edit':
                    return $this->addOrUpdateRole();
                case 'roleuser':
                case 'rolegroup':
                case 'roleperm':
                    return $this->mapEdit($flg);
                case 'disable':
                    return $this->disabledRole();
                default :
                    return jsonhelper::jsonOutput(false, 'not valid operation');
            }
        }
    }

    /**
     * 用户组相关操作统一入口
     * @param type $app
     * @return type
     */
    public function groupsAction($app = '') {
        if ($this->isGet()) {
            $groupid = $this->getQueryInt('gid');
            if (empty($groupid)) {
                // 返回列表数据
                return $this->showGroups($app);
            }

            // 返回指定分组的权限列表数据
            return $this->showGroupPerms($app, $groupid);
        }

        if ($this->isPost()) {
            $flg = $this->getQuery('flg');
            switch ($flg) {
                case 'edit':
                    return $this->addOrUpdateGroup();
                case 'groupuser':
                case 'grouprole':
                    return $this->mapEdit($flg);
                case 'disable':
                    return $this->disabledGroup();
                default :
                    return jsonhelper::jsonOutput(false, 'not valid operation');
            }
        }
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="用户相关操作的私有方法">
    /**
     * 返回用户列表
     * @param type $app
     * @return type
     */
    private function showUsers($app = '') {
        // $app = $this->getQuery('app');

        $noAtt = $this->getQuery('noatt');
        $modelUser = new Model_Users();
        // 用于不需要填充属性的请求
        if (!empty($noAtt)) {
            $datas = $modelUser->getUsers($app);
            return jsonhelper::jsonOutput(true, $datas);
        }

        // 一次性取出数据和映射，避免多次查库，导致响应慢
        $datas = $modelUser->getUsersWithMap($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }
        $ret = array();
        foreach ($datas as $item) {
            $id = $item['u_id'];
            if (!isset($ret[$id])) {
                $ret[$id] = $item;
                $isStatusOk = $item['u_status'] === 0;
                $ret[$id]['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
                $ret[$id]['btnTxt'] = $isStatusOk ? '禁用' : '启用';
                $ret[$id]['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

                $ret[$id]['roles'] = array();
                $ret[$id]['groups'] = array();
            }
            if (!empty($item['rname'])) {
                $ret[$id]['roles'][$item['rname']] = $item['rname'];
            }
            if (!empty($item['gname'])) {
                $ret[$id]['groups'][$item['gname']] = $item['gname'];
            }
        }
        unset($user);
        unset($datas);
        return jsonhelper::jsonOutput(true, array_values($ret));
    }

    /**
     * 新增或更新用户
     */
    private function addOrUpdateUser() {
        $modelUser = new Model_Users();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['u_id'] = $this->getPostInt('id');
        $data['account'] = $this->getPost('account');
        $data['u_name'] = $this->getPost('name');
        $data['u_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['u_id'] === 0) {
            $ret = $modelUser->addUser($data);
        } else {
            $ret = $odelUser->updateUser($data);
        }
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 禁用或启用用户
     * @return type
     */
    private function disabledUser() {
        $modelUser = new Model_Users();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['u_id'] = $this->getPostInt('id');
        $data['u_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['u_id'] === 0) {
            return jsonhelper::jsonOutput(false, 'not valid uid');
        }
        $ret = $modelUser->updateUser($data);
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 显示指定用户的权限清单
     * @param type $app
     * @param type $userid
     */
    private function showUserPerms($app, $userid) {
        $model = new Model_Users();
        $ret = $model->getPermsByUid($userid, $app);
        $itemInfo = $model->getById($app, $userid);
        if (!empty($itemInfo)) {
            $itemInfo = $itemInfo[0];
        }
        jsonhelper::jsonOutput(true, $ret, $itemInfo);
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="用户组 相关操作的私有方法">
    /**
     * 返回用户组列表
     * @param type $app
     * @return type
     */
    private function showGroups($app = '') {

        $noAtt = $this->getQuery('noatt');
        $modelGroup = new Model_Groups();
        // 用于不需要填充属性的请求
        if (!empty($noAtt)) {
            $datas = $modelGroup->getGroups($app);
            return jsonhelper::jsonOutput(true, $datas);
        }
        // 一次性取出数据和映射，避免多次查库，导致响应慢
        $datas = $modelGroup->getGroupsWithMap($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }
        $ret = array();
        foreach ($datas as $item) {
            $id = $item['g_id'];
            if (!isset($ret[$id])) {
                $ret[$id] = $item;
                $isStatusOk = $item['g_status'] === 0;
                $ret[$id]['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
                $ret[$id]['btnTxt'] = $isStatusOk ? '禁用' : '启用';
                $ret[$id]['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

                $ret[$id]['users'] = array();
                $ret[$id]['roles'] = array();
            }
            if (!empty($item['uname'])) {
                $ret[$id]['users'][$item['uname']] = $item['uname'];
            }
            if (!empty($item['rname'])) {
                $ret[$id]['roles'][$item['rname']] = $item['rname'];
            }
        }
        unset($item);
        unset($datas);
        return jsonhelper::jsonOutput(true, array_values($ret));
    }

    /**
     * 禁用或启用用户组
     * @return type
     */
    private function disabledGroup() {
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['g_id'] = $this->getPostInt('id');
        $data['g_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['g_id'] === 0) {
            return jsonhelper::jsonOutput(false, 'not valid role id');
        }
        $model = new Model_Groups();
        $ret = $model->updateGroup($data);
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 新增或更新用户组
     */
    private function addOrUpdateGroup() {
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['g_id'] = $this->getPostInt('id');
        $data['g_name'] = $this->getPost('name');
        $data['g_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        $model = new Model_Groups();
        if ($data['g_id'] === 0) {
            $ret = $model->addGroup($data);
        } else {
            $ret = $model->updateGroup($data);
        }
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 显示指定用户组的权限清单
     * @param type $app
     * @param type $groupid
     */
    private function showGroupPerms($app, $groupid) {
        $model = new Model_Groups();
        $ret = $model->getPermsByGroup($groupid, $app);
        $groupInfo = $model->getGroupById($app, $groupid);
        if (!empty($groupInfo)) {
            $groupInfo = $groupInfo[0];
        }
        jsonhelper::jsonOutput(true, $ret, $groupInfo);
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="角色相关操作的私有方法">
    /**
     * 返回角色列表
     * @param type $app
     * @return type
     */
    private function showRoles($app = '') {
        // $app = $this->getQuery('app');

        $noAtt = $this->getQuery('noatt');
        $modelRole = new Model_Roles();
        // 用于不需要填充属性的请求
        if (!empty($noAtt)) {
            $datas = $modelRole->getRoles($app);
            return jsonhelper::jsonOutput(true, $datas);
        }
        // 一次性取出数据和映射，避免多次查库，导致响应慢
        $datas = $modelRole->getRolesWithMap($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }

        $ret = array();
        foreach ($datas as $item) {
            $id = $item['r_id'];
            if (!isset($ret[$id])) {
                $ret[$id] = $item;
                $isStatusOk = $item['r_status'] === 0;
                $ret[$id]['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
                $ret[$id]['btnTxt'] = $isStatusOk ? '禁用' : '启用';
                $ret[$id]['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

                $ret[$id]['users'] = array();
                $ret[$id]['groups'] = array();
                $ret[$id]['perms'] = array();
            }
            if (!empty($item['uname'])) {
                $ret[$id]['users'][$item['uname']] = $item['uname'];
            }
            if (!empty($item['gname'])) {
                $ret[$id]['groups'][$item['gname']] = $item['gname'];
            }
            if (!empty($item['pname'])) {
                $ret[$id]['perms'][$item['pname']] = $item['pname'];
            }
        }
        unset($item);
        unset($datas);
        return jsonhelper::jsonOutput(true, array_values($ret));
    }

    /**
     * 禁用或启用角色
     * @return type
     */
    private function disabledRole() {
        $model = new Model_Roles();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['r_id'] = $this->getPostInt('id');
        $data['r_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['r_id'] === 0) {
            return jsonhelper::jsonOutput(false, 'not valid role id');
        }
        $ret = $model->updateRole($data);
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 新增或更新角色
     */
    private function addOrUpdateRole() {
        $model = new Model_Roles();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['r_id'] = $this->getPostInt('id');
        $data['r_desc'] = $this->getPost('desc');
        $data['r_name'] = $this->getPost('name');
        $data['r_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['r_id'] === 0) {
            $ret = $model->addRole($data);
        } else {
            $ret = $model->updateRole($data);
        }
        jsonhelper::jsonOutput(true, $ret);
    }

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="权限相关操作的私有方法">
    /**
     * 返回权限列表
     * @param type $app
     * @return type
     */
    private function showPerms($app = '') {
        // $app = $this->getQuery('app');

        $noAtt = $this->getQuery('noatt');
        $model = new Model_Permissions();
        // 用于不需要填充属性的请求
        if (!empty($noAtt)) {
            $datas = $model->getPerms($app);
            return jsonhelper::jsonOutput(true, $datas);
        }
        // 一次性取出数据和映射，避免多次查库，导致响应慢
        $datas = $model->getPermsWithMap($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }

        $ret = array();
        foreach ($datas as $item) {
            $id = $item['p_id'];
            if (!isset($ret[$id])) {
                $ret[$id] = $item;
                $isStatusOk = $item['p_status'] === 0;
                $ret[$id]['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
                $ret[$id]['btnTxt'] = $isStatusOk ? '禁用' : '启用';
                $ret[$id]['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

                $ret[$id]['users'] = array();
                $ret[$id]['groups'] = array();
                $ret[$id]['roles'] = array();
            }
            if (!empty($item['uname'])) {
                $ret[$id]['users'][$item['uname']] = $item['uname'];
            }
            if (!empty($item['gname'])) {
                $ret[$id]['groups'][$item['gname']] = $item['gname'];
            }
            if (!empty($item['rname'])) {
                $ret[$id]['roles'][$item['rname']] = $item['rname'];
            }
        }
        unset($item);
        unset($datas);
        return jsonhelper::jsonOutput(true, array_values($ret));
    }

    /**
     * 禁用或启用权限
     * @return type
     */
    private function disabledPerm() {
        $model = new Model_Permissions();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['p_id'] = $this->getPostInt('id');
        $data['p_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['p_id'] === 0) {
            return jsonhelper::jsonOutput(false, 'not valid permission id');
        }
        $ret = $model->updatePerm($data);
        jsonhelper::jsonOutput(true, $ret);
    }

    /**
     * 新增或更新角色
     */
    private function addOrUpdatePerm() {
        $model = new Model_Permissions();
        $data = array();
        $data['app'] = $this->getPost('app');
        $data['p_id'] = $this->getPostInt('id');
        $data['p_parentid'] = $this->getPostInt('pid');
        $data['p_desc'] = $this->getPost('desc');
        $data['p_val'] = $this->getPost('name');
        $data['p_status'] = $this->getPostInt('status');
        $data['lastip'] = httphelper::GetClientIP();
        if ($data['p_id'] === 0) {
            $ret = $model->addPerm($data);
        } else {
            $ret = $model->updatePerm($data);
        }
        jsonhelper::jsonOutput(true, $ret);
    }

    // </editor-fold>

    /**
     * 设置用户、用户组与角色映射关系的统一方法
     * @return type
     */
    private function mapEdit($type) {
        $data = array();
        $row = array(
            'app' => $this->getPost('app'),
            'id1' => $this->getPostInt('id'),
            'lastip' => httphelper::GetClientIP());
        if ($row['id1'] === 0) {
            return jsonhelper::jsonOutput(false, 'not valid main id by flag:' . $type);
        }
        $arrIds = $this->getPost('list');
        if (is_array($arrIds)) {
            foreach ($arrIds as $itemid) {
                $row['id2'] = $itemid;
                $data[] = $row;
            }
        }
        switch ($type) {
            case 'role':
                // 用户与角色的映射
                $modelMap = new Model_MapUserRole();
                $keyField = 'u_id';
                $valField = 'r_id';
                break;
            case 'roleuser':
                // 用户与角色的映射
                $modelMap = new Model_MapUserRole();
                $keyField = 'r_id';
                $valField = 'u_id';
                break;
            case 'group':
                // 用户与用户组的映射
                $modelMap = new Model_MapUserGroup();
                $keyField = 'u_id';
                $valField = 'g_id';
                break;
            case 'groupuser':
                // 用户与用户组的映射
                $modelMap = new Model_MapUserGroup();
                $keyField = 'g_id';
                $valField = 'u_id';
                break;
            case 'rolegroup':
                // 用户组与角色的映射
                $modelMap = new Model_MapGroupRole();
                $keyField = 'r_id';
                $valField = 'g_id';
                break;
            case 'grouprole':
                // 用户组与角色的映射
                $modelMap = new Model_MapGroupRole();
                $keyField = 'g_id';
                $valField = 'r_id';
                break;
            case 'roleperm':
                // 权限与角色的映射
                $modelMap = new Model_MapRolePerm();
                $keyField = 'r_id';
                $valField = 'p_id';
                break;
            case 'permrole':
                // 权限与角色的映射
                $modelMap = new Model_MapRolePerm();
                $keyField = 'p_id';
                $valField = 'r_id';
                break;
            default :
                return jsonhelper::jsonOutput(false, 'not valid flag:' . $type);
        }
        // 先清除该key id的所有映射，再添加
        $ret = $modelMap->deleteById($keyField, $row['id1']);
        if ($ret >= 0 && !empty($data)) {
            $ret .= '/' . $modelMap->addMultiMap($data, $keyField, $valField);
        }
        jsonhelper::jsonOutput(true, $ret);
    }

}
