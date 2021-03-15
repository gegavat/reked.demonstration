$('.check_domain').each(function() {
	var self = this;
    new DG.OnOffSwitch({
        el: self,
        textOn: 'ВКЛ',
        textOff: 'ВЫКЛ',
        height:26,
        trackColorOn:'rgb(23, 165, 134)',
        trackColorOff:'rgb(134, 134, 134)',
        textColorOn: '#fff',
        textColorOff: '#fff',
        trackBorderColor:'#d4d4d4',
        listener: function() {
			var domain = $(self).parent('td').data('domain');
			var status = this.getValue() ? 'enable' : 'disable';
			var switchObj = this;
            app.mainAjax("/repl-activate/check-status.html", {
                domain: domain,
                status: status
            }).then(function(res){
                // console.log(res);
                if (res === 'deny') {
                    $.confirm({
                        type: 'red',
                        title: 'Ошибка активации',
                        content: 'Вы не можете активировать еще один домен. Необходимо сменить тариф',
                        buttons: {
                            Ok: function () {
                                switchObj.uncheck();
                            }
                        }
                    });
                }
            });
        }
    });
});

