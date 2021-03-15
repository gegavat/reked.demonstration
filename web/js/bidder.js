// корректировка плагина iCheck
$('table input').off();
var keepOtherChecked = false;

$('.bulk_action tbody input').on('ifChecked', function () {
    $(this).closest('tr').addClass('selected');
    var x_panel = $(this).closest('.x_panel');
    x_panel.find('.column-title').hide();
    x_panel.find('.bulk-actions').show();
    if ( x_panel.find("input[name='table_records']:checked").length === x_panel.find("input[name='table_records']").length )
        x_panel.find('thead input').iCheck('check');
    keepOtherChecked = false;
});
$('.bulk_action tbody input').on('ifUnchecked', function () {
    $(this).closest('tr').removeClass('selected');
    $(this).closest('.x_panel').find("input[name='table_records']:checked").length !== 0
        ? keepOtherChecked = true
        : keepOtherChecked = false;
    $(this).closest('.x_panel').find('thead input:checked').iCheck('uncheck');
    if ( $(this).closest('.x_panel').find("input[name='table_records']:checked").length !== 0 ) {
        return false;
    }
    $(this).closest('.x_panel').find('.column-title').show();
    $(this).closest('.x_panel').find('.bulk-actions').hide();
});
$('.bulk_action thead input').on('ifChecked', function () {
    $(this).closest('.x_panel').find(".bulk_action input[name='table_records']").iCheck('check');
});
$('.bulk_action thead input').on('ifUnchecked', function (event) {
    if (keepOtherChecked) {
        keepOtherChecked = false;
        return false;
    }
    $(this).closest('.x_panel').find(".bulk_action input[name='table_records']").iCheck('uncheck');
});

// добавление аккаунтов Яндекс
$('#but-ad-ya-acc').click(function() {
    var client_id = $(this).data('client_id');
    var yaAuth = window.open("https://oauth.yandex.ru/authorize?response_type=code&force_confirm=yes&client_id=" + client_id, 'Авторизация Яндекс', 'width=750, height=600, top='+((screen.height-680)/2)+',left='+((screen.width-860)/2)+', resizable=yes, scrollbars=yes, status=yes');

    var timerId = setInterval(function() {
        if (yaAuth.closed) {
            clearInterval(timerId);
            location.reload();
        }
    }, 1000);
});

// удаление аккаунтов Яндекс
$('.ya-account-remove-icons').on('click', function() {
    var ya_account = $(this).data('ya_account');
    $.confirm({
        title: 'Удаление аккаунта',
        content: 'Вы уверены, что хотите удалить аккаунт?',
        buttons: {
            Удалить: {
                btnClass: 'btn-green',
                action: function () {
                    $.ajax({
                        url: '/account/del-ya-account.html',
                        data: {
                            ya_account: ya_account
                        },
                        success: function(res) {
                            console.log(res);
                            location.reload();
                        }
                    });
                }
            },
            Отмена: {
                btnClass: 'btn-default',
                action: function(){}
            }
        }
    });
});

// закрытие модального окна
$('#ya_bid_modal').on('click', '#ya-bid-cancel', function() {
    $('#ya_bid_modal').modal('hide');
});

// проверяет одинаковые ли состояния бид-менеджеров у выбранных кампаний
function compareBidderStatuses(cmpIds) {
    var bidderStatuses = [];
    cmpIds.forEach(function(id) {
        var currentBidderStatus = $('input[data-ya_cmp_id=' + id + ']').closest('tr').children('td.bidder-status').children('div').text();
        if ( bidderStatuses.indexOf(currentBidderStatus) === -1 )
            bidderStatuses.push(currentBidderStatus);
    });
    if ( bidderStatuses.length === 1 ) {
        return true;
    } else {
        $.confirm({
            type: 'red',
            title: 'Состояния бид-менеджеров отличаются',
            content: 'Нельзя группировать кампании, у которых отличаются состояния бид-менеджеров',
            buttons: {
                Ok: function() {}
            }
        });
        return false;
    }
}

// получает массив объектов {cmpIs, accId} или массив айдишников выбранных кампаний внутри указанного класса (.search_cmps, .network_cmps)
function getCheckedCmps(forClass, onlyIds) {
    var cmps = [];
    $(forClass).find('tbody input:checked').each(function (i, elem) {
        if (onlyIds) {
            cmps[i] = $(elem).data('ya_cmp_id');
        } else {
            cmps[i] = {
                cmpId: $(elem).data('ya_cmp_id'),
                accId: $(elem).data('ya_acc_id')
            };
        }
    });
    return cmps;
}

// настройка бид-менеджера для поиска
$('.ya_bid_mod_search_show').on('click', function() {
    var ya_cmp_ids = getCheckedCmps('.search_cmps', true);

    if ( !compareBidderStatuses(ya_cmp_ids) ) return false;

    $("#ya_bid_modal .modal-body").html('<br>');
    $('#ya_bid_modal').modal('show');

    app.modalAjax(
        '#ya_bid_modal',
        '/bidder/ya-show-search.html',
        {
            ya_cmp_ids: ya_cmp_ids
        },
        false,
        true
    ).then(function(res) {
        $('#ya_bid_modal .modal-body').html(res);

        // подключение плагина Touchspin (input-number.js) к цифровым полям ввода
        $(".bid-step").TouchSpin({
            initval: $(".bid-step").data('value') / app.payMultiplier || '',
            min: 0.10,
            max: 10.00,
            step: 0.10,
            decimals: 2,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
        $("#price").TouchSpin({
            initval: $("#price").data('value') / app.payMultiplier || '',
            min: 10.00,
            max: 1500.00,
            step: 0.10,
            decimals: 2,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
        $("#price-limit").TouchSpin({
            initval: $("#price-limit").data('value') / app.payMultiplier || '',
            min: 50,
            max: 2000,
            step: 10,
            decimals: 0,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
        $("#bid").TouchSpin({
            initval: $("#bid").data('value') / app.payMultiplier || '',
            min: 10.00,
            max: 1500.00,
            step: 0.10,
            decimals: 2,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
    });

    // переключение стратегий (изменение форм)
    $('#ya_bid_modal').on('change', '#strategy', function() {
        var strategy = $(this).val();
        strategyForm(strategy);
    });
    // функция, меняющая форму
    function strategyForm(strategy) {
        if ( strategy === 'max' ) {
            $('#stragegy_custom').css('display', 'none');
            $('#stragegy_max').css('display', 'block');
        }
        if ( strategy === 'custom' ) {
            $('#stragegy_max').css('display', 'none');
            $('#stragegy_custom').css('display', 'block');
        }
    }

});

// настройка бид-менеджера для РСЯ
$('.ya_bid_mod_network_show').on('click', function() {
    var ya_cmp_ids = getCheckedCmps('.network_cmps', true);

    if ( !compareBidderStatuses(ya_cmp_ids) ) return false;

    $("#ya_bid_modal .modal-body").html('<br>');
    $('#ya_bid_modal').modal('show');

    app.modalAjax(
        '#ya_bid_modal',
        '/bidder/ya-show-network.html',
        {
            ya_cmp_ids: ya_cmp_ids
        },
        false,
        true
    ).then(function(res) {
        $('#ya_bid_modal .modal-body').html(res);

        // подключение плагина Touchspin (input-number.js) к цифровым полям ввода
        $(".bid-step").TouchSpin({
            initval: $(".bid-step").data('value') / app.payMultiplier || '',
            min: 0.10,
            max: 10.00,
            step: 0.10,
            decimals: 2,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
        $("#bid").TouchSpin({
            initval: $("#bid").data('value') / app.payMultiplier || '',
            min: 3.00,
            max: 1500.00,
            step: 0.10,
            decimals: 2,
            boostat: 5,
            maxboostedstep: 10,
            buttondown_class: 'btn btn-default',
            buttonup_class: 'btn btn-default',
            postfix: 'руб.'
        });
    });
});

// по клику 'enter' с input снимается фокус
$('#ya_bid_modal').on('focus', 'input', function() {
    $(this).on('keydown', function(e) {
        if (e.which == 13) {
            $(this).blur();
        }
    });
});

// стилизация группы кнопок выбора объема трафика
$('#ya_bid_modal').on('click', '.traffic_volume .btn', function() {
    $(this).siblings('.btn').each(function(i, elem) {
        $(elem).removeClass('btn-primary');
        $(elem).addClass('btn-default');
    });
    $(this).removeClass('btn-default');
    $(this).addClass('btn-primary');
});

// поисковый биддер - сохранение, обновление, включение, выключение
$('#ya_bid_modal').on('click', '.ya_bid_search_change_status', function() {
	var mode = $(this).data('mode');
    var yaCmps = getCheckedCmps('.search_cmps');
	if ( mode === 'activate' || mode === 'update' ) {
		var strategy = $('#strategy').val();
		var step = strategy === 'max' ? $('#stragegy_max').find('.bid-step').val() : $('#stragegy_custom').find('.bid-step').val();
		var price = strategy === 'max' ? $('#price').val() : null;
		var price_limit = strategy === 'max' ? $('#price-limit').val() : null;
		var traffic_volume = strategy === 'custom' ? $('.traffic_volume').find('button.btn-primary').text() : null;
		var bid = strategy === 'custom' ? $('#bid').val() : null;
		if (!strategy) {
			alert ('Выберите стратегию бид-менеджера');
			return false;
		}
		if (strategy === 'max') {
			if ( !step || !price || !price_limit ) {
				alert ('Необходимо указать все настройки бид-менеджера');
				return false;
			}
			if ( Number(price) > Number(price_limit) ) {
                alert ('Ограничение ставки не должно быть меньше, чем списываемая цена');
                return false;
            }
		}
		if (strategy === 'custom') {
			if ( !step || !traffic_volume || !bid ) {
				alert ('Необходимо указать все настройки бид-менеджера');
				return false;
			}
		}
		app.mainAjax(
			'/bidder/ya-search-change-status.html',
			{
				data: JSON.stringify({
					mode: mode,
                    yaCmps: yaCmps,
					strategy: strategy,
					step: step,
					price: price,
					price_limit: price_limit,
					traffic_volume: traffic_volume,
					bid: bid
				})
			}, true
		).then(function (res) {
		    // console.log(res);
            afterChangeBidderStatus(res, yaCmps);
		});
	}
	if ( mode === 'disable' || mode === 'enable' ) {
        app.mainAjax(
            '/bidder/ya-search-change-status.html',
            {
                data: JSON.stringify({
                    mode: mode,
                    yaCmps: yaCmps
                })
            }, true
        ).then(function (res) {
            afterChangeBidderStatus(res, yaCmps);
        });
	}
});

// РСЯ биддер - сохранение, обновление, включение, выключение
$('#ya_bid_modal').on('click', '.ya_bid_network_change_status', function() {
    var mode = $(this).data('mode');
    var yaCmps = getCheckedCmps('.network_cmps');
    if ( mode === 'activate' || mode === 'update' ) {
        var strategy = $('#strategy').val();
        var step = $('#stragegy_max').find('.bid-step').val();
        var bid = $('#bid').val();
        if ( !step || !bid ) {
            alert ('Необходимо указать все настройки бид-менеджера');
            return false;
        }
        app.mainAjax(
            '/bidder/ya-network-change-status.html',
            {
                data: JSON.stringify({
                    mode: mode,
                    yaCmps: yaCmps,
                    strategy: strategy,
                    step: step,
                    bid: bid
                })
            }, true
        ).then(function (res) {
            afterChangeBidderStatus(res, yaCmps);
        });
    }

    if ( mode === 'disable' || mode === 'enable' ) {
        app.mainAjax(
            '/bidder/ya-network-change-status.html',
            {
                data: JSON.stringify({
                    mode: mode,
                    yaCmps: yaCmps
                })
            }, true
        ).then(function (res) {
            afterChangeBidderStatus(res, yaCmps);
        });
    }
});

// действия после сохранения, обновления, включения и выключения бид-менеджера
function afterChangeBidderStatus(res, cmps) {
    if (res === 'activated') {
        cmps.forEach(function(cmp) {
            var currentTd = $('input[data-ya_cmp_id=' + cmp.cmpId + ']').closest('tr').children('td.bidder-status');
            currentTd.html('<div class="text-success">Включен</div>');
        });
        $.alert({
            type: 'green',
            title: 'Активация прошла успешно',
            content: 'Количество кампаний, для которых был активирован бид-менеджер: ' +
            '<strong>' + cmps.length + '</strong>'
        });
    }
    if (res === 'updated') {
        $.alert({
            type: 'green',
            title: 'Обновление прошло успешно',
            content: 'Количество кампаний, для которых был обновлен бид-менеджер: ' +
            '<strong>' + cmps.length + '</strong>'
        });
    }
    if (res === 'enabled') {
        cmps.forEach(function(cmp) {
            var currentTd = $('input[data-ya_cmp_id=' + cmp.cmpId + ']').closest('tr').children('td.bidder-status');
            currentTd.html('<div class="text-success">Включен</div>');
        });
        $.alert({
            type: 'green',
            title: 'Включение прошло успешно',
            content: 'Количество кампаний, для которых был включен бид-менеджер: ' +
            '<strong>' + cmps.length + '</strong>'
        });
    }
    if (res === 'disabled') {
        cmps.forEach(function(cmp) {
            var currentTd = $('input[data-ya_cmp_id=' + cmp.cmpId + ']').closest('tr').children('td.bidder-status');
            currentTd.html('<div class="text-danger">Выключен</div>');
        });
        $.alert({
            type: 'green',
            title: 'Выключение прошло успешно',
            content: 'Количество кампаний, для которых был отключен бид-менеджер: ' +
            '<strong>' + cmps.length + '</strong>'
        });
    }
    if (res === 'saved') {
        cmps.forEach(function(cmp) {
            var currentTd = $('input[data-ya_cmp_id=' + cmp.cmpId + ']').closest('tr').children('td.bidder-status');
            currentTd.html('<div class="text-danger">Выключен</div>');
        });
        $.alert({
            type: 'red',
            title: 'Ошибка включения',
            content: 'Перейдите на другой тариф, чтобы увеличить количество активных бид-менеджеров'
        });
    }
    $('#ya_bid_modal').modal('hide');
}
