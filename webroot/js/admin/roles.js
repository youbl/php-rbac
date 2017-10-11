
var apiurl = '../../admin/roles';
var dialogCtlId = '#editDialog';
// 避免网络导致，导致用户多次点击多次请求数据
var isNeting = false;
// 所有的用户和用户组数据，用于给角色绑定设置使用
var arrUsers = [];
var arrGroups = [];
var arrPerms = [];
$(document).ready(function () {
    loadLists();

    // 初始化新增对话框
    window.initDialog(dialogCtlId, saveitem);

    // 解决multiselect被浮层遮挡的bug
    $('.ui-multiselect-menu').css('z-index', 1000);

    loadUsersAndGroups();
});

/**
 * 加载所有的角色列表和用户组列表，供用户绑定
 */
function loadUsersAndGroups() {
    var url = '../../admin/users?noatt=1';
    ajaxLoadData(url, function (ret) {
        window.arrUsers = ret;
    });
    url = '../../admin/groups?noatt=1';
    ajaxLoadData(url, function (ret) {
        window.arrGroups = ret;
    });
    url = '../../admin/permissions?noatt=1';
    ajaxLoadData(url, function (ret) {
        window.arrPerms = ret;
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
    var name = $.trim(tr.find('td:eq(1)').text());
    var btnTxt = $(obj).text().replace(/[\[\]]/g, '');
    if (!confirm('确认要' + btnTxt + '：' + name + '吗？')) {
        return;
    }
    var para = {};
    para.id = $.trim(tr.find('td:eq(0)').text());
    var status = tr.find('td:eq(3)').attr('data');
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
    if (!obj) {
        // 新增
        edittxt = '新增角色';
        $('#divId').hide();
        $('#txtUid').val('');
    } else {
        // 编辑，从当前行获取要编辑的数据
        edittxt = '编辑角色';
        var tr = $(obj).parents('tr:eq(0)');
        $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));
        $('#txtName').val($.trim(tr.find('td:eq(1)').text()));
        $('#txtDesc').val($.trim(tr.find('td:eq(2)').text()));
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
function saveitem() {
    if ($('#hidType').length > 0) {
        return saveUserOrGroup();
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
function editUserOrGroup(obj) {
    var tmpTxt = $(obj).text();
    var isUser = tmpTxt.indexOf('用户') >= 0;
    var isGroup = tmpTxt.indexOf('分组') >= 0;
    var isRole = !isUser && !isGroup;

    $(dialogCtlId).html($('#tplItemAtt').html());

    var tr = $(obj).parents('tr:eq(0)');
    $('#txtUid').val($.trim(tr.find('td:eq(0)').text()));
    if (isUser) {
        $('#hidType').val('1');
        var target = $('#lstUsers');
        var data = window.arrUsers;
        var edittxt = '所属用户编辑...';
        var attId = 'u_id';
        var attName = 'u_name';
        var attStatus = 'u_status';
        var oldVal = ' ' + $.trim(tr.find('td:eq(4)').text());
    } else if (isGroup) {
        $('#hidType').val('0');
        var target = $('#lstGroups');
        var data = window.arrGroups;
        var edittxt = '所属用户组编辑...';
        var attId = 'g_id';
        var attName = 'g_name';
        var attStatus = 'g_status';
        var oldVal = ' ' + $.trim(tr.find('td:eq(5)').text());
    } else {
        $('#hidType').val('2');
        var target = $('#lstProms');
        var data = window.arrPerms;
        var edittxt = '拥有权限编辑...';
        var attId = 'p_id';
        var attName = 'p_desc';
        var attStatus = 'p_status';
        var oldVal = ' ' + $.trim(tr.find('td:eq(6)').text());
    }
    // 列表的2级父对象显示
    target.parent().parent().show();
    // 设置属性，用于防止无数据提交
    target.attr('oldVal', oldVal);
    for (var i = 0, j = data.length; i < j; i++) {
        var item = data[i];
        if (item[attStatus] !== 0) {
            continue; // 禁用的不允许
        }
        var selected = oldVal.indexOf(' ' + item[attId] + ':') >= 0 ? 'selected' : '';
        var txt = item[attId] + ':' + item[attName];
        var val = item[attId];

        // 角色特殊处理,用于复选父框，避免权限无法显示
        if (isRole && item['p_parentid'] !== 0) {
            val = item['p_parentid'] + '-' + val;
            txt = item['p_parentid'] + '-' + txt;
        }
        target.append('<option value="' + val + '" '
                + selected + '>' + txt + '</option>');
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
    if (isRole) {
        // 设置multiselect的点击事件：https://github.com/ehynds/jquery-ui-multiselect-widget/wiki/Events
        selectOption.click = checkParent;
    }
    target.multiselect(selectOption);

    $(dialogCtlId).dialog('option', 'title', edittxt);
    window.showDialog(dialogCtlId);
}

function checkParent(event, ui) {
    var lst = $('#lstProms');
    var arrOld = lst.val();
    if (!arrOld) {
        arrOld = [];
    }

    var val = ui.value;
    var arr = val.split('-');
    var isParent = arr.length !== 2;// 是否一级权限
    var pid = arr[0];
    if (!ui.checked) {
        if (isParent) {
            // 关闭父权限时，移除全部子权限
            for (var i = arrOld.length - 1; i >= 0; i--) {
                if (arrOld[i] === pid || arrOld[i].indexOf(pid + '-') === 0) {
                    arrOld.splice(i, 1);
                }
            }
            lst.val(arrOld).multiselect('refresh');
        }
        return;
    }

    // 选择子权限的同时，选中父权限
    if ($.inArray(pid, arrOld) >= 0) {
        // 已经包含父权限了
        return;
    }
    arrOld.push(pid);
    arrOld.push(ui.value);
    lst.val(arrOld).multiselect('refresh');
}

/**
 * 保存角色所属用户或分组
 */
function saveUserOrGroup() {
    var isUser = $('#hidType').val() === '1';
    var isGroup = $('#hidType').val() === '0';
    var target = isUser ? $('#lstUsers') : (isGroup ? $('#lstGroups') : $('#lstProms'));

    var para = {};
    para.id = $('#txtUid').val();
    var selectOptions = target.val();
    if (selectOptions) {
        para.list = selectOptions;
        if (!isUser && !isGroup) {
            // 角色编辑要移除value里的父id
            for (var i = para.list.length - 1; i >= 0; i--) {
                var idx = para.list[i].indexOf('-');
                if (idx > 0) {
                    para.list[i] = para.list[i].substring(idx + 1);
                }
            }
        }
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
    var flg = isUser ? 'roleuser' : (isGroup ? 'rolegroup' : 'roleperm');
    $.post(apiurl + '?flg=' + flg, para, function (response) {
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