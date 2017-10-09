
var apiurl = '../../admin/users';
var dialogCtlId = '#userDialog';
// 避免网络导致，导致用户多次点击多次请求数据
var isNeting = false;
// 所有的角色和用户组数据，用于给用户绑定设置使用
var arrRoles = [];
var arrGroups = [];
$(document).ready(function () {
    loadLists();

    // 初始化新增对话框
    window.initDialog(dialogCtlId, saveuser);

    // 解决multiselect被浮层遮挡的bug
    $('.ui-multiselect-menu').css('z-index', 1000);
});

/**
 * 加载所有的角色列表和用户组列表，供用户绑定
 */
function loadRolesAndGroups() {
    var url = '../../admin/roles?noatt=1';
    $.ajax({
        type: 'GET',
        url: url,
        cache: false,
        dataType: 'json',
        success: function (response) {
            if (response.code !== 200) {
                alert(response.code + (response.message ? '-' + response.message : ''));
                return;
            }
            if (!response.result || response.result.length === 0) {
                return;
            }
            window.arrRoles = response.result;
        }
    });
    url = '../../admin/groups?noatt=1';
    $.ajax({
        type: 'GET',
        url: url,
        cache: false,
        dataType: 'json',
        success: function (response) {
            if (response.code !== 200) {
                alert(response.code + (response.message ? '-' + response.message : ''));
                return;
            }
            if (!response.result || response.result.length === 0) {
                return;
            }
            window.arrGroups = response.result;
        }
    });
}

/**
 * 获取用户列表
 */
function loadLists() {
    var tbId = '#tabData';
    $(tbId + ' tr:gt(1)').remove();
    $('#trNoData').hide();

    var url = apiurl;
    $.ajax({
        type: 'GET',
        url: url,
        cache: false,
        dataType: 'json',
        success: function (response) {
            if (response.code !== 200) {
                alert(response.code + (response.message ? '-' + response.message : ''));
                return;
            }
            if (!response.result || response.result.length === 0) {
                $('#trNoData').show();
                return;
            }
            var html = window.render('tplRow', response.result);
            $(tbId).append(html);

            window.trColorChg(tbId);
        }
    });
}


/**
 * 禁用指定用户
 * @param {jquery} obj 点击对象
 * @return {undefined}
 */
function disableUser(obj) {
    var tr = $(obj).parents('tr:eq(0)');
    var account = $.trim(tr.find('td:eq(1)').text());
    var btnTxt = $(obj).text().replace(/[\[\]]/g, '');
    if (!confirm('确认要' + btnTxt + '：' + account + '吗？')) {
        return;
    }
    var para = {};
    para.id = $.trim(tr.find('td:eq(0)').text());
    var status = tr.find('td:eq(3)').attr('data');
    para.status = status ? 0 : 1;
    $.post(apiurl + '?flg=disable', para, function (response) {
        if (response.code !== 200) {
            alert('操作失败 ' + (response.message ? response.message : ''));
            return;
        }
        if (response.result > 0) {
            alert('操作成功');
            loadLists();
        } else {
            alert('操作失败:' + (response.result ? response.result : '其它错误'));
        }
    }, 'json');
}

/**
 * 新增或修改用户
 * @param {jquery} obj 点击对象，为空表示新增，不为空表示编辑
 * @return {undefined}
 */
function editUser(obj) {
    $(dialogCtlId).html($('#tplUserInfo').html());
    // 获取当前
    var edittxt;
    if (!obj) {
        // 新增
        edittxt = '新增用户';
        $('#txtUid').val('');
    } else {
        // 编辑，从当前行获取要编辑的数据
        edittxt = '编辑用户';
        var tr = $(obj).parents('tr:eq(0)');
        $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));
        $('#txtAccount').val($.trim(tr.find('td:eq(1)').text()));
        $('#txtName').val($.trim(tr.find('td:eq(2)').text()));
        var status = tr.find('td:eq(3)').attr('data');
        $('input[name="chkstatus"][value="' + status + '"]').prop('checked', 'checked');
    }
    $(dialogCtlId).dialog('option', 'title', edittxt);
    window.showDialog(dialogCtlId);
}


/**
 * 提交到api，保存topic
 * @return {undefined}
 */
function saveuser() {
    if ($('#hidType').length > 0) {
        return saveRoleOrGroup();
    }
    var para = getParams();
    if (!para) {
        return;
    }
    if (isNeting) {
        alert('数据处理中，请稍候……');
        return;
    }
    isNeting = true;
    $.post(apiurl + '?flg=edit', para, function (response) {
        isNeting = false;
        if (response.code !== 200) {
            alert('失败 ' + (response.message ? response.message : ''));
            return;
        }
        if (response.result) {
            alert('操作成功:' + response.result);
            window.hideDialog(dialogCtlId);
            loadLists();
        } else {
            alert('失败:' + (response.result ? response.result : '其它错误'));
        }
    }, 'json');
}

/**
 * 收集所有要提交的数据
 * @return {Object} 收集到的参数清单
 */
function getParams() {
    var para = {};
    para.id = $('#txtUid').val();
    para.account = $.trim($('#txtAccount').val());
    if (para.account.length <= 0) {
        alert('账号不能为空');
        return false;
    }

    para.name = $.trim($('#txtName').val());
    if (para.name.length <= 0) {
        alert('名称不能为空');
        return false;
    }

    var radStatus = $('input[name="chkstatus"]:checked');
    if (radStatus.length !== 1) {
        alert('请选择是否有效');
        return false;
    }
    para.status = radStatus.val();
    return para;
}

/**
 * 编辑用户所属角色或分组
 * @param {type} obj 点击对象
 */
function editRoleOrGroup(obj) {
    var isRole = $(obj).text().indexOf('角色') >= 0;

    $(dialogCtlId).html($('#tplUserAtt').html());
    $('#hidType').val(isRole ? '1' : '0');

    var tr = $(obj).parents('tr:eq(0)');
    $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));
    if (isRole) {
        var target = $('#lstRoles');
        var data = window.arrRoles;
        var edittxt = '所属角色编辑...';
        var attId = 'r_id';
        var attName = 'r_name';
    } else {
        var target = $('#lstGroups');
        var data = window.arrGroups;
        var edittxt = '所属用户组编辑...';
        var attId = 'g_id';
        var attName = 'g_name';
    }
    target.parent().parent().show();
    for (var i = 0, j = data.length; i < j; i++) {
        var item = data[i];
        target.append('<option value="' + item[attId] + '">' + item[attName] + '</option>');
    }

    var selectOption = {
        selectedList: 4,
        selectedText: '#项已选',
        height: 200,
        noneSelectedText: '请选择',
        header: true,
        checkAllText: '全选',
        uncheckAllText: '取消选择',
    };
    target.multiselect(selectOption);

    $(dialogCtlId).dialog('option', 'title', edittxt);
    window.showDialog(dialogCtlId);
}

/**
 * 保存用户所属角色或分组
 * @param {type} obj 点击对象
 */
function saveRoleOrGroup() {
    var isRole = $('#hidType').val() === '1';
    var para = {};
    para.id = $('#txtUid').val();
    var selectOptions = isRole ? $('#lstRoles').val() : $('#lstGroups').val();
    if (selectOptions) {
        para.list = selectOptions;
    } else {
        para.list = [];
    }
    if (isNeting) {
        alert('数据处理中，请稍候……');
        return;
    }
    isNeting = true;
    var flg = isRole ? 'role' : 'group';
    $.post(apiurl + '?flg=' + flg, para, function (response) {
        isNeting = false;
        if (response.code !== 200) {
            alert('失败 ' + (response.message ? response.message : ''));
            return;
        }
        if (response.result) {
            alert('操作成功:' + response.result);
            window.hideDialog(dialogCtlId);
            loadLists();
        } else {
            alert('失败:其它错误');
        }
    }, 'json');
}