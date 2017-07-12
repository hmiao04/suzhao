/**
 * Created by home on 2017-03-24.
 */

var common_config = {
    ajax: {
        process: function (ret, frm) {
            if (ret['code'] == 0) {
                modalLoading.hide();
                return true;
            }
            else {
                if (typeof(ret['message']) == "string") {
                    modalLoading.msg(ret['message'], 1500);
                } else {
                    if (frm) {
                        ycs.form.processAjax(ret, frm);
                    }
                    modalLoading.hide();
                }
            }
        },
        error: function () {
            modalLoading.msg('发送请求异常', 1500);
        }
    }
};
(function (w, $) {
    'use strict';
    function initLoading() {
        $("body").append("<div class='modal' id='xxpc_jb_loading_0324' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' data-backdrop='static'>" +
            "<div class='modal-dialog modal-sm' role='document' style='margin-top: 15%;'>" +
            "<div class='modal-content'>" +
            "<div class='modal-header' style='display: none;'>" +
            "<h4 class='modal-title loadingTitle'>提示</h4>" +
            "</div>" +
            "<div class='modal-body' style='font-size: 16px;'>" +
            "<span class='glyphicon glyphicon-refresh' aria-hidden='true'></span> &nbsp;" +
            "<span class='loadingText'>处理中，请稍候...</span>" +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>"
        );
        return $('#xxpc_jb_loading_0324');
    }

    var loading = initLoading(), text = loading.find(".loadingText"), bd = loading.find('.modal-body');

    function showLoading(str) {
        if (loading.find('.glyphicon-refresh').length == 0) {
            bd.html("<span class='glyphicon glyphicon-refresh' aria-hidden='true'></span> &nbsp;" +
                "<span class='loadingText'>处理中，请稍候...</span>");
            text = loading.find(".loadingText");
        }
        if (str) {
            text.html(str);
        }
        loading.modal("show");
    }

    function hideLoading(callback) {
        loading.removeClass('fade');
        loading.modal("hide");
        setTimeout(function () {
            loading.addClass('fade');
            typeof(callback) == "function" && callback.call();
        }, 20);
    }

    w.modalLoading = {
        show: showLoading, hide: hideLoading, text: function (str) {
            if (str) {
                bd.text(str);
            }
        }, msg: function (msg, t, callback) {
            if (msg) {
                showLoading();
                bd.html(msg);
                var _t = 3000;
                if (typeof(t) == "number") _t = t;
                setTimeout(function () {
                    hideLoading(callback);
                }, _t);
            }
        }
    };
    $.fn.onAjax = function (callback) {
        this.each(function () {
            var me = $(this);
            if (me.data('bind-ajax')) return false;
            me.on('click', function () {
                modalLoading.show('数据加载中...');
                $.post(me.attr('href'), {}, function (ret) {
                    if (common_config.ajax.process(ret)) {
                        callback.call(me, ret);
                    }
                }, 'json').error(common_config.ajax.error);
                return false;
            });
            me.data('bind-ajax', true);
        });
    };
    $.fn.formOnAjax = function (callback) {
        this.each(function () {
            var me = $(this);
            if (!me.is('form')) return false;
            if (me.data('bind-ajax')) return false;
            me.on('submit', function () {
                me.trigger('form.post.before');
                if (me.hasClass('validate') && !me.valid()) {
                    return false;
                }
                modalLoading.show('数据提交中...');
                $.post(me.attr('action'), me.serializeArray(), function (ret) {
                    if (common_config.ajax.process(ret, me)) {
                        callback.call(me, ret);
                    }
                }, 'json').error(common_config.ajax.error);
                return false;
            });
            me.data('bind-ajax', true);
        });
    };
})(window, jQuery);

$(function () {
    $('.need-number').on('keypress', function (e) {
        var key = e['key'];
        if (key && key.toLowerCase() == 'backspace') return true;
        var keyCode = window.event ? e.keyCode : e.which;
        var intTest = /\d/, floatTest = /[\d\.]/;
        var t = $(this).hasClass('data-float') ? floatTest : intTest;
        if (!String.fromCharCode(keyCode).match(t)) {
            return false;
        }
    });
    $('.need-number').on('change', function (e) {
        var v = this.value;
        if (v.length == 0 || isNaN(v)) v = '0';
        this.value = $(this).hasClass('data-float') ? parseFloat(v) : parseInt(v);
    });
});

ycj.define('home-page', function () {

    //$('img').addClass('xkl_lazyLoad');

    $('.xkl_lazyLoad').each(function () {
        var img = $(this), src = img.attr('data-src');
        if (!src)return false;
        img.on('load', function () {
            img.fadeIn(500);
        }).attr('src', src).hide();
    });

    var sharingDiv = $('.sharing-picture'), wrapper = sharingDiv.find('.panel-body>.row');
    sharingDiv.find('.addition-bar>a').on('click', function () {
        if ($(this).hasClass('selected')) return false;
        wrapper.html('<div class="loading-text">加载中</div>')
            .load($(this).attr('href'));
        sharingDiv.find('.addition-bar>a').removeClass('selected');
        $(this).addClass('selected');
        return false;
    });
});
ycj.define('home-task', function () {

});
ycj.define('member-init', function () {
    $(document).on('click', 'a.ajax-post', function () {
        var ele = $(this), url = ele.attr('href');
        if (ele.hasClass('confirm')) {
            var msg = '是否继续操作？';
            if (ele.attr('data-message-confirm')) {
                msg = ele.attr('data-message-confirm');
            }
            if (!confirm(msg)) return false;
        }
        modalLoading.show('发送请求中...');
        $.post(url, {}, function (ret) {
            if (common_config.ajax.process(ret)) {
                location.reload();
            }
        }, 'json').error(common_config.ajax.error)
        return false;
    });
});
ycj.define('goods-list', function () {
    var wrapper = $('#goods-list-wrapper'),
        pageState = {title: document.title, url: 'list.html'};

    var loadGoods = function (search, value) {
        if(search){
            pageState.url = ycs.pager.set(search, value, pageState.url);
        }
        wrapper.append('<div class="loading-goods"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>加载中...</div>');
        $.get(pageState.url)
            .done(function(s){
                wrapper.html(s);
                if ('pushState' in history) {
                    history.pushState(pageState, pageState.title, pageState.url);
                }
            })
            .fail(function(){
                wrapper.html('<div class="loaded-error">加载材料数据失败,点击 <a href="javascript:loadGoodsList();">重试</a>...</div>');
            });
    };
    window.loadGoodsList = loadGoods;
    $(document).on('click','.color-item>a',function(){
        var link = $(this);
        $('.color-item').removeClass('active');
        link.parent().addClass('active');
        loadGoods('color',link.attr('data-color'));
        return false;
    }).on('click','.category-link',function(){
        var link = $(this);
        $('.category-link-wrapper').removeClass('active');
        link.parent().addClass('active');
        loadGoods('category',link.attr('data-cate-id'));
        return false;
    }).on('click','.main-category',function(){
        var link = $(this),subCate = link.find('.sub-category'),
            offset = subCate.find('ul.sub-category-list');
        if(link.hasClass('active')) return;
        $('.main-category').removeClass('active');
        link.addClass('active');

        if(link.hasClass('has-child')){
            link.find('a.category-link').eq(0).trigger('click');
        }else{
            loadGoods('category','all');
        }
        setTimeout(function(){
            subCate.css('height',offset.height());
        },10);
        //loadGoods('category',link.attr('data-cate-id'));
        return false;
    });
    //
});