$(function () {
    var showMsg = function(msg,text){
        msg.html(text);
        //setTimeout(function(){
        //    msg.html('');
        //},3000);
    }
    var form1v = {
            submitHandler: function (from1) {
                var btn = $(from1).find('[type=submit]'),msg = $(from1).find('.message-wrapper');
                btn.button('loading');
                msg.html('');
                modalLoading.show('登录中...');
                $.ajax({
                    url: from1.action, //后台处理程序
                    type: from1.method,//数据发送方式
                    dataType: 'json',           //接受数据格式
                    data: $(from1).serializeArray(),
                    success: function (data) {
                        if (data.code == 0) {
                            //alert('登录成功');
                            var url = ycs.pager.get('callback');
                            location.replace(url?url:"../");
                        }
                        else{
                            modalLoading.hide();
                            btn.button('reset');
                            showMsg(msg,data.message);
                        }
                    },
                    error: function (data) {
                        modalLoading.hide();
                        btn.button('reset');
                        showMsg(msg,'发送登录请求失败');
                    }
                })
            }
        },
        form2v = {
            submitHandler: function (from1) {
                var btn = $(from1).find('[type=submit]'),msg = $(from1).find('.message-wrapper');
                btn.button('loading');
                modalLoading.show('注册中...');
                msg.html('');
                $.ajax({
                    url: from1.action, //后台处理程序
                    type: from1.method,//数据发送方式
                    dataType: 'json',           //接受数据格式
                    data: $(from1).serializeArray(),
                    success: function (data) {
                        if (data.code == 0) {
                            modalLoading.msg("注册成功",1500,function(){
                                from1.reset();
                                location.hash = 'login';
                            });

                        }
                        else{
                            modalLoading.hide();
                            btn.button('reset');
                            showMsg(msg,data.message);
                        }
                    },
                    error: function (data) {
                        modalLoading.hide();
                        btn.button('reset');
                        showMsg(msg,'发送登录请求失败');
                    }
                })
            }
        };
    $("#login_form").validate(form1v);
    $("#register_form").validate(form2v);
    $("#register_btn").click(function () {
        var elm = $("#register_form");
        elm.find('label.error').hide();
    });
    $("#back_btn").click(function () {
        var elm = $("#login_form");
        elm.find('label.error').hide();
    });
    var lblSend = $('button.btn-send-sms'),smsLabel = $('#phone_code-error'),timer = null;
    var countDown = function(time,callback){
        if(timer) clearInterval(timer);
        timer = setInterval(function(){
            if(time < 0){
                clearInterval(timer);timer = null;
            }else{
                time -- ;
            }
            callback.call(this,time);
        },1000);
    };
    lblSend.on('click',function(){
        var phone_value = $('#send_sms_phone').val();
        if(phone_value.length == 0){
            smsLabel.text('请先输入手机号码');
            return false;
        }
        smsLabel.text('发送中...');
        lblSend.attr('disabled','disabled');
        $.post(lblSend.attr('data-url'),{phone:phone_value},function(ret){
            if(ret['code'] == 0){
                lblSend.attr('disabled','disabled');
                countDown(60,function(c){
                    if(c > 0){
                        lblSend.html('重新获取(' + c + 's)');
                    }else{
                        lblSend.removeAttr('disabled');
                        lblSend.html('重新获取');
                    }
                });
            }else{
                lblSend.removeAttr('disabled');
            }
            smsLabel.text(ret['message']).show();
        }).fail(function(){
            lblSend.removeAttr('disabled');
            smsLabel.text('发送短信验证异常').show();
        });
        return false;
    });
    var loadView = function(){
        var view = location.hash ? location.hash.substr(1):'login';
        document.title = view == 'login' ? '用户登录':'账号注册';
        $('.form-wrapper').attr('class','form-wrapper ' + view);
    };
    $(window).on('hashchange',loadView);
    loadView();
}); 