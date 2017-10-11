/**
 * @file 通用方法
 */

/**
 *
 * 限制对象只能输入数字或逗号
 * @param {type} obj 要限制的对象
 * @param {type} enableComma 是否允许输入半角逗号
 */
function digiLimit(obj, enableComma) {
    if (enableComma) {
        var reg = /[^\d,]/g;
    } else {
        var reg = /[^\d]/g;
    }
    $(obj).val($(obj).val().replace(reg, ''));
}

/**
 * 初始化对话框
 * @param {string|jQuery} id 对象id
 * @param {method} confirmMethod 点确认时的调用方法
 * @return {undefined}
 */
function initDialog(id, confirmMethod) {
    // https://jqueryui.com/dialog/#modal-message
    $(id).dialog({
        autoOpen: false,
        height: 400,
        width: 650,
        modal: true, // 模态开启
        draggable: true, // 是否可拖拽
        minWidth: 300, // 最小宽度
        hide: {effect: 'explode', duration: 500}, // 隐藏效果
        buttons: {
            '确认': confirmMethod,
            '取消': function () {
                hideDialog(id);
            }
        }
    });
}

/**
 * 显示对话框
 * @param {string|jQuery} id 对象id
 * @return {undefined}
 */
function showDialog(id) {
    $(id).dialog('open');
}

/**
 * 隐藏对话框
 * @param {string|jQuery} id 对象id
 * @return {undefined}
 */
function hideDialog(id) {
    $(id).dialog('close');
}

/**
 * 根据指定模板，进行数据展开
 * @param {string} template 模板名
 * @param {Array|Object} data 数据
 * @return {string|render.temp|jQuery}
 */
function render(template, data) {
    if (data instanceof Array) {
        var result = [];
        for (var index in data) {
            var temp = render(template, data[index]);
            temp = temp.replace(/{\$index}/g, index);
            result.push(temp);
        }
        return result.join('');
    }
    var temp = $('#' + template).html();
    for (var i in data) {
        var regex = new RegExp('{' + i + '}', 'g');
        temp = temp.replace(regex, toStr(data[i]));
    }
    return temp;
}

/**
 * 把数组或对象的属性，通过分号分隔拼接
 * @param {type} obj 传入的数组或对象
 * @return {String}
 */
function toStr(obj) {
    if (obj === null || obj === undefined) {
        return '';
    }
    if (obj instanceof Object) {
        var ret = '';
        var idx = 0;
        for (var i in obj) {
            if (idx !== 0) {
                ret += '; ';
            }
            idx++;
            ret += obj[i];
        }
        return ret;
    }
    return obj.toString();
}

/**
 * 获取url里的变量值
 * @param {string} name 变量名
 * @return {string}
 */
function getQueryString(name) {
    if (typeof (name) !== 'string') {
        return '';
    }
    name = $.trim(name);
    if (name.length === 0) {
        return '';
    }
    var idx = location.search.indexOf(name);
    if (idx < 0) {
        return '';
    }
    var tmp = location.search.substr(idx + name.length);
    if (tmp.charAt(0) !== '=') {
        return '';
    }
    idx = tmp.indexOf('&');
    var ret;
    if (idx < 0) {
        ret = tmp.substr(1);
    } else {
        ret = tmp.substr(1, idx - 1);
    }
    return $.trim(ret);
}

/**
 * 获取url里的变量数字
 * @param {string} name 变量名
 * @return {number}
 */
function getQueryInt(name) {
    var tmp = getQueryString(name);
    if ((/^-?\d+$/).test(tmp)) {
        return parseInt(tmp, 10);
    }
    return 0;
}

/**
 * 对指定的table进行隔行变色
 * @param {string} id table的id
 * @return {undefined}
 */
function trColorChg(id) {
    // 延时处理，避免dom没加载完成
    setTimeout(function () {
        // table鼠标移动变色
        $(id).find('tr:gt(0)').each(function () {
            $(this).bind('mouseover', function () {
                window.onRowOver(this);
            }).bind('mouseout', function () {
                window.onRowOut(this);
            }).bind('click', function () {
                window.onRowClick(this);
            });
        });
    }, 500);
}

/**
 * 通过ajax加载数据
 * @param {type} url
 * @param {type} callback
 * @returns {undefined}
 */
function ajaxLoadResponse(url, callback) {
    $.ajax({
        type: 'GET',
        url: url,
        cache: false,
        dataType: 'json',
        success: function (response) {
            callback(response);
        }
    });
}


/**
 * 通过ajax加载数据
 * @param {type} url
 * @param {type} callback
 * @returns {undefined}
 */
function ajaxLoadData(url, callback) {
    ajaxLoadResponse(url, function (response) {
        if (response.code !== 200) {
            alert(response.code + (response.message ? '-' + response.message : ''));
            return;
        }
        if (!response.result || response.result.length === 0) {
            return;
        }
        callback(response.result);
    });
}