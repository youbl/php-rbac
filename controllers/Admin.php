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
        $modelUser = new Model_Users();
        $users = $modelUser->getAllRights($account, $app);
        if (empty($users)) {
            jsonhelper::jsonError($account . ' has no permission found.');
        } else {
            jsonhelper::jsonOutput(true, $users);
        }
    }

    /**
     * 用户相关操作统一入口
     * @param type $app
     * @return type
     */
    public function usersAction($app = '') {
        if ($this->isGet()) {
            // 返回列表数据
            return $this->showUsers($app);
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
                    return $this->mapEdit($flg);
                case 'disable':
                    return $this->disabledRole();
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
    public function groupsAction($app = '') {
        if ($this->isGet()) {
            // 返回列表数据
            return $this->showGroups($app);
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
        $modelUser = new Model_Users();
        $users = $modelUser->getUsers($app);
        if (empty($users)) {
            return jsonhelper::jsonOutput(true, array());
        }

        $noAtt = $this->getQuery('noatt');
        foreach ($users as &$user) {
            $isStatusOk = $user['u_status'] === 0;
            $user['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
            $user['btnTxt'] = $isStatusOk ? '禁用' : '启用';
            $user['add_time'] = date('Y-m-d H:i:s', $user['add_time']);

            // 用于不需要填充属性的请求
            if (!empty($noAtt)) {
                continue;
            }

            $user['roles'] = array();
            $user['groups'] = array();
            $arrData = $modelUser->getRolesAndGroupsByUid($user['u_id'], $app); // 用户所属角色和用户组列表
            foreach ($arrData as $item) {
                if ($item['nametype'] === 0) {
                    $user['roles'][] = $item['name'];
                } else {
                    $user['groups'][] = $item['name'];
                }
            }
        }
        unset($user);
        return jsonhelper::jsonOutput(true, $users);
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

    // </editor-fold>
    //
    // <editor-fold defaultstate="collapsed" desc="用户组 相关操作的私有方法">
    /**
     * 返回用户组列表
     * @param type $app
     * @return type
     */
    private function showGroups($app = '') {
        // $app = $this->getQuery('app');
        $modelGroup = new Model_Groups();
        $datas = $modelGroup->getGroups($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }

        $noAtt = $this->getQuery('noatt');
        foreach ($datas as &$item) {
            $isStatusOk = $item['g_status'] === 0;
            $item['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
            $item['btnTxt'] = $isStatusOk ? '禁用' : '启用';
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

            // 用于不需要填充属性的请求
            if (!empty($noAtt)) {
                continue;
            }

            $item['users'] = array();
            $item['roles'] = array();
            $arrData = $modelGroup->getUsersAndRolesByGid($item['g_id'], $app); // 角色下用户和用户组列表
            foreach ($arrData as $att) {
                if ($att['nametype'] === 0) {
                    $item['users'][] = $att['name'];
                } else {
                    $item['roles'][] = $att['name'];
                }
            }
        }
        unset($item);
        return jsonhelper::jsonOutput(true, $datas);
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
        if ($data['r_id'] === 0) {
            $ret = $model->addGroup($data);
        } else {
            $ret = $model->updateGroup($data);
        }
        jsonhelper::jsonOutput(true, $ret);
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
        $modelRole = new Model_Roles();
        $datas = $modelRole->getRoles($app);
        if (empty($datas)) {
            return jsonhelper::jsonOutput(true, array());
        }

        $noAtt = $this->getQuery('noatt');
        foreach ($datas as &$item) {
            $isStatusOk = $item['r_status'] === 0;
            $item['status'] = $isStatusOk ? '启用' : '<span class="must">禁用</span>';
            $item['btnTxt'] = $isStatusOk ? '禁用' : '启用';
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);

            // 用于不需要填充属性的请求
            if (!empty($noAtt)) {
                continue;
            }

            $item['users'] = array();
            $item['groups'] = array();
            $arrData = $modelRole->getUsersAndGroupsByRid($item['r_id'], $app); // 角色下用户和用户组列表
            foreach ($arrData as $att) {
                if ($att['nametype'] === 0) {
                    $item['users'][] = $att['name'];
                } else {
                    $item['groups'][] = $att['name'];
                }
            }
        }
        unset($item);
        return jsonhelper::jsonOutput(true, $datas);
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

    /**
     * 设置用户、用户组与角色映射关系
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
