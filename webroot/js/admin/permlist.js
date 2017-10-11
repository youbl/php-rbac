
var tbId = 'tabData';
$(document).ready(function () {
    var roleid = getQueryInt('rid');
    if (roleid > 0) {
        // 根据角色id加载权限列表
        return;
    }
    var userid = getQueryInt('uid');
    if (userid > 0) {
        // 根据用户id加载权限列表
        loadUserPerms(userid);
        return;
    }
    var groupid = getQueryInt('gid');
    if (groupid > 0) {
        // 根据用户组id加载权限列表
        loadGroupPerms(groupid);
        return;
    }

});

/**
 * 根据用户id，读取权限列表展示
 * @param {type} userid
 */
function loadUserPerms(userid) {
    var url = '../../admin/users?uid=' + userid;
    ajaxLoadResponse(url, function (response) {
        if (response.code !== 200) {
            alert(response.code + (response.message ? '-' + response.message : ''));
            return;
        }
        if (!response.result || response.result.length === 0) {
            $('#trNoData').show();
            return;
        }
        if (response.ext) {
            var title = '用户【' + response.ext['u_name'] + '】拥有的权限列表';
            $('.title').text(title);
        }
        bindTree(response.result);
    });
}


/**
 * 根据用户组id，读取权限列表展示
 * @param {type} groupid
 */
function loadGroupPerms(groupid) {
    var url = '../../admin/groups?gid=' + groupid;
    ajaxLoadResponse(url, function (response) {
        if (response.code !== 200) {
            alert(response.code + (response.message ? '-' + response.message : ''));
            return;
        }
        if (!response.result || response.result.length === 0) {
            $('#trNoData').show();
            return;
        }
        if (response.ext) {
            var title = '组【' + response.ext['g_name'] + '】拥有的权限列表';
            $('.title').text(title);
        }
        bindTree(response.result);
    });
}

/**
 * 根据返回的结果，绑定权限树
 * @param {type} result
 * @returns {undefined}
 */
function bindTree(result) {
    var tree = $('.accordion');
    for (var i = 0, j = result.length; i < j; i++) {
        var item = result[i];
        var parentid = item['p_parentid'];
        if (parentid === 0) {
            var html = '<li id="menuBase'
                    + item['p_id'] + '"><a  href="javascript:void(0)" target="right"><i class="icon20_index"></i>'
                    + item['p_id'] + ':' + item['p_desc'] + '<span></span></a><ul class="sub-menu"></ul></li>';
            tree.append(html);
        } else {
            var html = '<li class="sub-menu-li"><a href="javascript:void(0)" target="right">'
                    + item['p_id'] + ':' + item['p_desc'] + '</a></li>';
            tree.find('#menuBase' + parentid + ' ul').append(html);
        }
    }
}