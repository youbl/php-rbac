
//模拟下拉菜单
$(document).ready(function () {
    loadAllMenu();

    // Store variables
    var accordion_head = $('.accordion > li > a');
    var accordion_body = $('.accordion li > .sub-menu');
    var accordion_tag = $('.accordion li > .sub-menu li');

    // Open the first tab on load
    accordion_head.first().addClass('active').next().slideDown('normal');

    // tab Click function
    accordion_head.on('click', function (event) {
        // Disable header links
        event.preventDefault();
        if ($(this).attr('class') !== 'active') {
            // hide all tabs
            accordion_body.slideUp('normal');
            accordion_head.removeClass('active');
            // show current tab
            $(this).next().stop(true, true).slideToggle('normal');
            $(this).addClass('active');
        }
    });

    // focus tab item
    accordion_tag.on('click', function (event) {
        accordion_tag.removeClass('active-tag');
        $(this).addClass('active-tag');
    });

    /**
     * bind menus
     */
    function loadAllMenu() {
        var url = '../admin/permissions';

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
                    alert('no permission found.');
                    return;
                }
                var tree = $('.accordion');
                for (var i = 0, j = response.result.length; i < j; i++) {
                    var item = response.result[i];
                    var parentid = item['p_parentid'];
                    var href = item['p_val'] ? item['p_val'] : 'javascript:void(0)';
                    if (parentid === 0) {
                        var html = '<li id="menuBase'
                                + item['p_id'] + '"><a href="'
                                + href + '"><i class="icon20_index"></i>'
                                + item['p_desc'] + '<span></span></a><ul class="sub-menu"></ul></li>';
                        tree.append(html);
                    } else {
                        var html = '<li class="sub-menu-li"><a href="'
                                + href + '" target="right">'
                                + item['p_desc'] + '</a></li>';
                        tree.find('#menuBase ul' + parentid).append(html);
                    }
                }
            }
        });
    }
});
