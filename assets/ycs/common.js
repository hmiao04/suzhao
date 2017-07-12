(function(u){
    var a = u.indexOf("Opera") > -1,b = u.indexOf("compatible") > -1 && u.indexOf("MSIE") > -1 && !a,
        e = 'https://www.baidu.com/s?wd=google%E6%B5%8F%E8%A7%88%E5%99%A8',m='你使用的浏览器已过时!\n为了能够正常使用本系统请下载最新的浏览器';
    if (b )
    {
        var c = new RegExp("MSIE (\\d+\\.\\d+);");
        c.test(u);
        var d = parseFloat(RegExp["$1"]);
        if(d <= 10)
        { if(alert(m)){location.href=e;}}
    }
})(navigator.userAgent);
if(typeof(console) == "undefined"){    var console = {        log:function(){}    }}
var ycs = {
    api_base:'',
    processToLogin:function(){
        alert("您的登录信息不存在或者已经过期了,请刷新网页或者重新登录!");
    },
    /**
     * ajax数据是否正确
     * @param data
     * @returns {boolean}
     */
    ajaxIsSuccess:function(data){
        var code = data['code'];
        return code == 0 ;
    },
    processAjaxSuccess:function(data){
        if(ycs.ajaxIsSuccess(data)){return true;}
        alert(data['message']);
        return false;
    },
    ajax : function(opt){
        var errorHandler = function(){};
        opt = $.extend({ dataType:'json',error:errorHandler,success:function(){}},opt);
        var callback = opt.success,_callback = function(ret){
            if(opt.dataType == 'json' && ret['code'] == 403){ycs.processToLogin();
                return;
            }
            callback.call(this,ret);
        };

        return $.ajax(opt);
    },
    isFunction:function(data){
        return typeof(data) == 'function';
    },
    get : function(url,data,func,type){
        if(this.isFunction(data)){
            type = func;
            func = data;
            data = {};
        }
        return this.ajax({
            url:url,
            type:'get',
            data:data,
            dataType:type,
            success:func
        });
    },
    form:{
        processAjax:function(ret,frm){
            if(typeof(ret) == "object"){
                if(ret['code'] == 0){return true;}
                if(typeof(ret['message']) == "string"){alert(ret['message']);return false;}
                if(typeof(ret['message']) == "object"){
                    frm = $(frm);
                    for(var key in ret['message']){
                        // id="name-error"  for="name"
                        var err_label = frm.find('label#'+key+'-error');
                        if(err_label.length == 0){
                            err_label = $('<label class="error" style="display: none;"></label>')
                                .attr('id',key+'-error').attr('for',key).insertAfter(frm.find('[name='+key+']'));
                        }
                        err_label.show().html(ret['message'][key]);
                    }
                }
            }
        }
    },
    post : function(url,data,func,type){
        if(this.isFunction(data)){
            type = func;
            func = data;
            data = {};
        }
        return this.ajax({
            url:url,
            type:'post',
            data:data,
            dataType:type,
            success:func
        });
    },
    pager:{
        get:function(key,url){
            var reg = new RegExp("(^|&)" + key + "=([^&]*)(&|$)", "i");
            if(typeof(url) == "undefined" || url == null || url.length == 0){
                url = window.location.search;
            }
            var r = url.substr(url.indexOf('?')+1).match(reg);
            //if(url && this.hasQuery(url)) r = url.substr(url.indexOf('?')+1).match(reg);
            if (r != null && this.hasQuery(url)){
                return decodeURIComponent(r[2]);
            }
            return null;
        },
        hasQuery:function(url){
            return url.indexOf('?') != -1;
        },
        set:function(key,value,url){
            if(!url) url = location.href;
            value = encodeURIComponent(value);
            if(url && key && value){
                if(this.get(key,url) == null){
                    var sp = '?'
                    if(this.hasQuery(url)){
                        sp = '&';
                    }
                    url +=sp + key + "=" + value;
                }else{
                    var reg = new RegExp(key + "=(.*)+(&|$)", "i");
                    url = url.replace(reg,key+'='+value);
                }
            }
            return url;
        }
    },
    createLoadingText:function(callback,count,interval){
        var dot = '.',str = dot;
        if(typeof(count) != "number" || count < 2) count = 3;
        if(typeof(interval) != "number" || interval < 100) interval = 300;
        var timer = null;
        var ret = {
            stop:function(){
                if(timer){
                    callback.call(this,'',ret);
                    clearInterval(timer);
                }
            },
            start:function(){
                timer = setInterval(process,interval);
            }
        },process = function(){
            if(str.length < count){str += dot;}
            else str = dot;
            callback.call(this,str,ret);
        };
        timer = setInterval(process,interval);
        return ret;
    },
    initVue:function(model,ele){
        if(!ele || !model || typeof(model['res']) == 'undefined') return;
        var _initialModel = $.extend({},model['res']);
        var eleObject = $(ele);
        var editApp = new Vue({
            el: ele,
            data: model,
            methods: {
                saveData: function () {
                    var frm = eleObject.find('form');
                    if (frm.valid()) {
                        var btn = frm.find('[type=submit]');
                        var ld = ycs.createLoadingText(function(str,l){btn.html('保存' + str);});
                        var postData = new FormData(frm[0]);
                        if($('[type=file]').length > 0){
                            $('[type=file]').each(function(){
                                var fs = this['files'],file_name = $(this).attr('data-name');
                                $(fs).each(function(){ // 可以多文件上传
                                    postData.append(file_name,this);// add file to formData
                                });
                            });
                        }
                        var ajaxDataProcess = {
                            url:frm.attr('action'),
                            type: 'POST',
                            data: postData,
                            processData: false,
                            contentType: false,
                            dataType:'json',
                            error:function(){
                                ld.stop();
                                alert('操作发生了致命的错误');
                            },
                            success:function(ret){
                                ld.stop();
                                if(ret['code'] == 0){
                                    var url = frm.attr('data-success-url');
                                    if(url){
                                        location.href=url;
                                    }else{
                                        location.reload();
                                    }
                                }else{
                                    alert(ret['message']);
                                }
                            }
                        };
                        $.ajax(ajaxDataProcess);
                    }
                    return false;
                }
            }
        });
        if(eleObject.length > 0 && eleObject.hasClass('modal')){
            eleObject.on('show.bs.modal',function(e){
                var modal = $(this);
                var link = $(e.relatedTarget),mode = link.attr('data-mode');
                modal.find('label.error').html('');
                modal.find('[type=file]').removeClass('required');
                if(mode == 'edit'){
                    $.get(link.attr('data-url'),{},function(ret){
                        if(ret['code'] == 0){
                            model.res = ret['data'];
                        }else{
                            modal.modal('hide');
                            alert(ret['message']);
                        }
                    },'json');
                }else{
                    modal.find('form').get(0).reset();
                    model.res = _initialModel;
                }
            });
        }
    }
};
var ycj = function (ms) {
    if (this == window) {
        return new ycj(ms);
    }
    return this.init(ms);
}
var __ms_list = {};
ycj.define = function (name, module) {
    __ms_list[name] = module;
};
ycj.require = function(name){
    var args = Array.prototype.slice.call(arguments);
    args.shift();
    return __ms_list[name].apply(this,args);
};
ycj.parseJSON = function(str){
    var ret = null;
    if(JSON && typeof(JSON['parse']) == "function"){
        try{ret = JSON.parse(str);}catch(e){}
    }
    if(null == ret){
        try{ret = eval('['+str+']')[0];}catch(e){}
    }
    return null == ret ? str : ret;
};
ycj.prototype = {
    _init_ms: null,
    init: function (ms) {
        if (typeof(__ms_list[ms]) != "function") {
            throw Error('not found module named ' + ms);
            return false;
        }
        this._init_ms = __ms_list[ms];
        return this;
    },
    delayRun: function (ms) {
        var __ = this,args = Array.prototype.slice.call(arguments);
        args.shift();
        $(function () {
            setTimeout(function(){
                __.run.apply(__,args);
            }, ms)
        });
        return this;
    },
    run: function () {
        var args = Array.prototype.slice.call(arguments),__ = this;
        $(function(){
            if(args && args.length > 0){
                __._init_ms.apply(this,args)
            }else{
                __._init_ms.call()
            }
        });
        return this;
    }
};


String.prototype.setUrl = function(key,value){
    return ycs.pager.set(key,value,this);
};
var ajaxPostHandler = function () {
    var updateAlert = function(msg,r){
        if(typeof(r) != "function") r= function(){};
        //layer.msg(msg,{end:r,time:300});
        alert(msg);
        r.call();
        return false;
    };
    var target, query, form;
    var target_form = $(this).attr('target-form');
    if(typeof(target_form) == "undefined") target_form = 'no_data';
    if($(this).data('before-post') && typeof($(this).data('before-post')) == "function"){
        if($(this).data('before-post').call(this) === false) return false;
    }
    var that = this;
    var need_confirm = false;
    var null_msg = $(this).attr('data-null') ? $(this).attr('data-null'): '请选择要操作数据';
    if (
        ($(this).attr('type') == 'submit') ||
        (target = $(this).attr('href')) ||
        (target = $(this).attr('url'))
    ) {
        form = $('.' + target_form);
        if ($(this).attr('hide-data') === 'true') {//无数据时也可以使用的功能
            form = $('.hide-data');
            query = form.serialize();
        } else if (target_form != 'no_data' && form.get(0) == undefined ) {
            updateAlert(null_msg);
            return false;
        } else if (target_form != 'no_data' && form.get(0).nodeName == 'FORM') {
            if ($(this).hasClass('confirm')) {
                var confirm_info = $(that).attr('data-confirm') ? $(that).attr('data-confirm') : $(that).attr('confirm-info');
                confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                if (!confirm(confirm_info)) {
                    return false;
                }
            }
            if ($(this).attr('url') !== undefined) {
                target = $(this).attr('url');
            } else {
                target = form.get(0).action;
            }
            query = form.serialize();
        } else if (target_form != 'no_data' && (form.get(0).nodeName == 'INPUT' || form.get(0).nodeName == 'SELECT' || form.get(0).nodeName == 'TEXTAREA')) {
            form.each(function (k, v) {
                if (v.type == 'checkbox' && v.checked == true) {
                    need_confirm = true;
                }
            })
            if (need_confirm && $(this).hasClass('confirm')) {
                var confirm_info = $(that).attr('data-confirm') ? $(that).attr('data-confirm') : $(that).attr('confirm-info');
                confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                if (!confirm(confirm_info)) {
                    return false;
                }
            }
            query = form.serialize();
        } else {
            if ($(this).hasClass('confirm')) {
                var confirm_info = $(that).attr('data-confirm') ? $(that).attr('data-confirm') : $(that).attr('confirm-info');
                confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                if (!confirm(confirm_info)) {
                    return false;
                }
            }
            query = form.find('input,select,textarea').serialize();
        }
        if(query=='' && target_form != 'no_data'){
            updateAlert(null_msg,'danger');
            return false;
        }
        $(that).addClass('disabled').attr('autocomplete', 'off').prop('disabled', true);
        $.post(target, query,function (data) {
            $(that).removeClass('disabled').attr('autocomplete', 'on').prop('disabled', false);
            if (ycs.processAjaxSuccess(data)) {
                if($(that).parents('[load-url]').length > 0 || $(that).parents('.ajax-data-wrapper').length > 0){
                    var ajaxWrapper = $(that).parents('[load-url]').length > 0 ? $(that).parents('[load-url]'):$(that).parents('.ajax-data-wrapper');
                    var ajaxPager = ajaxWrapper.eq(0);

                    if(ajaxPager.attr('load-url')){
                        ajaxPager.ajaxLoadByPage(ajaxPager.attr('load-url'));
                        return false;
                    }
                }
                var successMessage = '操作成功';
                if (data['data'] && data['data']['message']) {
                    successMessage = data['data']['message'];
                }
                if (data['data'] && data['data']['url']) {
                    updateAlert(successMessage,function(){
                        location.href = data['data']['url'];
                    });
                } else {
                    if($(that).parents('#modal-ajax').length > 0){
                        var modal = $(that).parents('#modal-ajax');
                        if(modal.attr('data-url')){
                            modal.trigger('show.bs.modal');return false;
                        }
                    }
                    updateAlert(successMessage, function(){
                        location.reload();
                    });
                }
            }
        },'json').error(function(){
            //layer.closeAll('loading');
            $(that).removeClass('disabled').attr('autocomplete', 'on').prop('disabled', false);
            updateAlert('发送请求失败');
        });
    }
    return false;
};

$.fn.ajaxPost = function(){
    this.each(function(){
        var me = $(this);
        if(me.data('bind-ajax')) return false;
        me.on('click',ajaxPostHandler);
        me.data('bind-ajax',true);
    });
};


$.fn.ajaxLoadByPage = function(ajaxUrl,fn,before){
    var loadHtml = function(url,me){
        me.html('<p style="line-height: 40px;text-align: center;">加载中...</p>');
        me.attr('load-url',url);
        $.get(url,function(source){
                try{if(typeof(before) == "function"){before.call(me)}}catch (e){}
                location.hash = '#!/' + url;
                me.html(source).trigger('load-done');
                try{if(typeof(fn) == "function"){fn.call(me)}}catch (e){}
                if(me.data('load-done') && typeof(me.data('load-done')) == "function"){
                    try{me.data('load-done').call(me)}catch (e){}
                }
            },'text')
            .error(function(){
                me.html('');
                var p = $('<p style="color:#f00;line-height: 40px;text-align: center;"></p>')
                    .appendTo(me);
                var retry = $('<a href="#"></a>').html('加载失败,请点击重试').appendTo(p);
                retry.on('click',function(){
                    loadHtml(url,me);
                    return false;
                });
                return false;
            });
        if(!me.data('bind-event')){
            me.data('bind-event',true).on('refresh',function(){
                loadHtml(url,me);
            });

        }
        return false;
    };
    var loadUrl = function(me){
        loadHtml($(this).attr('href'),me);
    };
    if(ajaxUrl){
        loadHtml(ajaxUrl,$(this));
        return;
    }
};

$.showLoadingText = function (count,interval) {
    if(typeof(count) != "number" || count < 2) count = 3;
    if(typeof(interval) != "number" || interval < 300) interval = 400;
    var _ = this,_text = '',
        isButton = _.is('button'),
        originText = isButton? _.html(): _.val(),
        ele = isButton?$('<span class="loading-text"></span>').appendTo(_):_;
    _.attr('disabled','disabled');
    var timer = setInterval(function(){
        if(_text.length >= count){
            _text = '';
        }
        _text += '.';
        isButton ? ele.html(_text): ele.val(_text);
    },interval);
    var ret = {
        close:function(){
            isButton?ele.remove():ele.val(originText);
            clearInterval(timer);
            _.removeAttr('disabled');
        }
    };
    return ret;
};