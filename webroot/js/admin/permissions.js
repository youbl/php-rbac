
var apiurl = '../../admin/permissions';
var dialogCtlId = '#editDialog';
// 避免网络导致，导致用户多次点击多次请求数据
var isNeting = false;
// 所有的角色数据，用于给权限绑定设置使用
var arrRoles = [];
// 所有的权限数据，用于给权限绑定上级使用
var arrPerms = [];
$(document).ready(function () {
    loadLists();

    // 初始化新增对话框
    window.initDialog(dialogCtlId, saveitem);

    // 解决multiselect被浮层遮挡的bug
    $('.ui-multiselect-menu').css('z-index', 1000);

    loadRoles();
});

/**
 * 加载所有的角色列表，供权限绑定
 */
function loadRoles() {
    var url = '../../admin/roles?noatt=1';
    ajaxLoadData(url, function (ret) {
        window.arrRoles = ret;
    });
}

/**
 * 获取列表
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
            window.arrPerms = response.result;
            var html = window.render('tplRow', response.result);
            $(tbId).append(html);

            window.trColorChg(tbId);
        }
    });
}


/**
 * 禁用指定角色
 * @param {jquery} obj 点击对象
 * @return {undefined}
 */
function disableItem(obj) {
    var tr = $(obj).parents('tr:eq(0)');
    var name = $.trim(tr.find('td:eq(2)').text());
    var btnTxt = $(obj).text().replace(/[\[\]]/g, '');
    if (!confirm('确认要' + btnTxt + '：' + name + '吗？')) {
        return;
    }
    var para = {};
    para.id = $.trim(tr.find('td:eq(0)').text());
    var status = tr.find('td:eq(4)').attr('data');
    para.status = status === '1' ? 0 : 1;
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
 * 新增或修改
 * @param {jquery} obj 点击对象，为空表示新增，不为空表示编辑
 * @return {undefined}
 */
function editItem(obj) {
    $(dialogCtlId).html($('#tplItemInfo').html());
    // 获取当前
    var edittxt;
    var pid = '';
    if (!obj) {
        // 新增
        edittxt = '新增权限';
        $('#divId').hide();
        $('#txtUid').val('');
    } else {
        // 编辑，从当前行获取要编辑的数据
        edittxt = '编辑权限';
        var tr = $(obj).parents('tr:eq(0)');
        $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));
        $('#txtName').val($.trim(tr.find('td:eq(2)').text()));
        $('#txtDesc').val($.trim(tr.find('td:eq(3)').text()));
        var status = tr.find('td:eq(4)').attr('data');
        $('input[name="chkstatus"][value="' + status + '"]').prop('checked', 'checked');
        pid = $.trim(tr.find('td:eq(1)').text());
    }

    // 绑定上级权限列表
    for (var idx = 0, roleCnt = window.arrPerms.length; idx < roleCnt; idx++) {
        var item = window.arrPerms[idx];
        if (item['p_status'] !== 0 || item['p_parentid'] !== 0 || item['p_id'].toString() === $('#txtUid').val()) {
            continue;
        }
        var selected = item['p_id'].toString() === pid ? 'selected' : '';
        var html = '<option value="' + item['p_id'] + '" ' + selected + '>' + item['p_desc'] + '</option>';
        $('#lstParent').append(html);
    }

    $(dialogCtlId).dialog('option', 'title', edittxt);
    window.showDialog(dialogCtlId);
}


/**
 * 提交到api，保存topic
 * @return {undefined}
 */
function saveitem() {
    if ($('#hidType').length > 0) {
        return saveRoles();
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
            loadLists();
            alert('操作成功:' + response.result);
            window.hideDialog(dialogCtlId);
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
    para.desc = $.trim($('#txtDesc').val());
    para.name = $.trim($('#txtName').val());
    if (para.name.length <= 0) {
        alert('权限值不能为空');
        return false;
    }
    para.pid = $('#lstParent').val();

    var radStatus = $('input[name="chkstatus"]:checked');
    if (radStatus.length !== 1) {
        alert('请选择是否有效');
        return false;
    }
    para.status = radStatus.val();
    return para;
}

/**
 * 编辑有权限的角色
 * @param {type} obj 点击对象
 */
function editRoles(obj) {
    $(dialogCtlId).html($('#tplItemAtt').html());
    var tr = $(obj).parents('tr:eq(0)');
    $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));

    var target = $('#lstRoles');
    var data = window.arrRoles;
    var edittxt = '有权限的角色编辑...';
    var attId = 'r_id';
    var attName = 'r_name';
    var attStatus = 'r_status';
    var oldVal = ' ' + $.trim(tr.find('td:eq(5)').text());

    // 设置属性，用于防止无数据提交
    target.attr('oldVal', oldVal);
    for (var i = 0, j = data.length; i < j; i++) {
        var item = data[i];
        if (item[attStatus] !== 0) {
            continue; // 禁用的不允许
        }
        var selected = oldVal.indexOf(' ' + item[attId] + ':') >= 0 ? 'selected' : '';
        target.append('<option value="' + item[attId] + '" ' + selected + '>' + item[attId] + ':' + item[attName] + '</option>');
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
 * 保存角色所属用户或分组
 */
function saveRoles() {
    var target = $('#lstRoles');
    var para = {};
    para.id = $('#txtUid').val();
    var selectOptions = target.val();
    if (selectOptions) {
        para.list = selectOptions;
    } else {
        para.list = [];
    }
    if (para.list.length <= 0 && target.attr('oldVal').length <= 1) {
        alert('请至少选择一项');
        return;
    }
    if (isNeting) {
        alert('数据处理中，请稍候……');
        return;
    }
    isNeting = true;
    $.post(apiurl + '?flg=permrole', para, function (response) {
        isNeting = false;
        if (response.code !== 200) {
            alert('失败 ' + (response.message ? response.message : ''));
            return;
        }
        if (response.result) {
            loadLists();
            alert('操作成功:' + response.result);
            window.hideDialog(dialogCtlId);
        } else {
            alert('失败:其它错误');
        }
    }, 'json');
}