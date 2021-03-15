// анимация для обновления pjax
app.pjaxAnimation('#btn_pjax_campaign');

// загрузка кампаний из Я. Директа для показа в модальном окне
$('#pjax_campaign').on('click', '.get-ya-cmp', function() {
    $('#show-get-cmp').modal('show');
    var ya_account = $(this).data('ya_account');
    var login = $(this).data('login');
    $('#show-get-cmp').attr('data-ya_account', ya_account);
    app.modalAjax(
        '#show-get-cmp',
        '/campaign/get-ya-cmp.html',
        {
            ya_account: ya_account
        }
    ).then(function(res) {
        $('#show-get-cmp .modal-body').html(res);
    });
    $('#show-get-cmp H2 span').text(login);
});

// загрузка кампаний из G. Ads для показа в модальном окне
$('#pjax_campaign').on('click', '.get-g-cmp', function() {
    $('#show-get-cmp').modal('show');
    var g_account = $(this).data('g_account');
    var login = $(this).data('login');
    $('#show-get-cmp').attr('data-g_account', g_account);
    app.modalAjax(
        '#show-get-cmp',
        '/campaign/get-g-cmp.html',
        {
            g_account: g_account
        }
    ).then(function(res) {
        $('#show-get-cmp .modal-body').html(res);
    });
    $('#show-get-cmp H2 span').text( login + ' (' + g_account + ')' );
});

// яндекс. выбор кампаний и загрузка выбранных кампаний
$("#show-get-cmp").on('click', '#load-ya-cmp', function() {
    var checked = $("input:checkbox:checked");
    if ( checked.length === 0 ) {
        alert('Нужно выбрать хотя бы одну кампанию');
        return false;
    }
    var cmpArray = [];
    $(checked).each(function() {
        var bufObj = {};
        bufObj.cmp_id = $(this).data('id');
        bufObj.cmp_name = $(this).data('name');
        cmpArray.push(bufObj);
    });
    $('#show-get-cmp').modal('hide');
    app.mainAjax("/campaign/yandex-loading.html", {
        ya_account: $('#show-get-cmp').data('ya_account'),
        campaigns: JSON.stringify(cmpArray)
    }, true).then(function(result) {
        $.confirm({
            columnClass: 'large',
            title: 'Результат загрузки',
            content: result,
            buttons: {
                Ok: {
                    btnClass: 'btn-green',
                    action: function () {
                        $('#btn_pjax_campaign').click();
                    }
                }
            }
        });
    });
});

// google. выбор кампаний и загрузка выбранных кампаний
$("#show-get-cmp").on('click', '#load-g-cmp', function() {
    var checked = $("input:checkbox:checked");
    if ( checked.length === 0 ) {
        alert('Нужно выбрать хотя бы одну кампанию');
        return false;
    }
    var cmpArray = [];
    $(checked).each(function() {
        var bufObj = {};
        bufObj.cmp_id = $(this).data('id');
        bufObj.cmp_name = $(this).data('name');
        cmpArray.push(bufObj);
    });
    $('#show-get-cmp').modal('hide');
    app.mainAjax("/campaign/google-loading.html", {
        g_account: $('#show-get-cmp').data('g_account'),
        campaigns: JSON.stringify(cmpArray)
    }, true).then(function(result) {
        $.confirm({
            columnClass: 'large',
            title: 'Результат загрузки',
            content: result,
            buttons: {
                Ok: {
                    btnClass: 'btn-green',
                    action: function () {
                        $('#btn_pjax_campaign').click();
                    }
                }
            }
        });
    });
});

// обновление кампаний в Яндексе
$('#pjax_campaign').on('click', '.ya-cmp-update', function() {
    var ya_account = $(this).data('ya_account');
    var campaign_id = $(this).data('ya_cmp');

    app.mainAjax("/campaign-update/yandex.html", {
        ya_account: ya_account,
        campaign_id: campaign_id
    }).then(function(result) {
        $('#btn_pjax_campaign').click();
        $.alert({
            title: 'Завершение обновления',
            content: 'Кампания успешно обновлена'
        });
    });
});

// обновление кампаний в Google
$('#pjax_campaign').on('click', '.g-cmp-update', function() {
	var g_account = $(this).data('g_account');
	var campaign_id = $(this).data('g_cmp');
	
	app.mainAjax("/campaign-update/google.html", {
        g_account: g_account,
        campaign_id: campaign_id
    }).then(function(result) {
        $('#btn_pjax_campaign').click();
        $.alert({
            title: 'Завершение обновления',
            content: 'Кампания успешно обновлена'
        });
    });
});

// удаление кампаний в Яндексе
$('#pjax_campaign').on('click', '.ya-cmp-delete', function() {
    var campaign_id = $(this).data('ya_cmp');
    $.confirm({
        title: 'Удаление кампании',
        content: 'Вы уверены, что хотите удалить кампанию?',
        buttons: {
            Удалить: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/campaign/yandex-delete.html", {
                        campaign_id: campaign_id
                    }).then(function(result) {
                        console.log(result);
                        $('#btn_pjax_campaign').click();
                    });
                }
            },
            Отмена: {
                btnClass: 'btn-blue',
                action: function(){}
            }
        }
    });
});

// удаление кампаний в Google
$('#pjax_campaign').on('click', '.g-cmp-delete', function() {
    var campaign_id = $(this).data('g_cmp');
    $.confirm({
        title: 'Удаление кампании',
        content: 'Вы уверены, что хотите удалить кампанию?',
        buttons: {
            Удалить: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/campaign/google-delete.html", {
                        campaign_id: campaign_id
                    }).then(function(result) {
                        console.log(result);
                        $('#btn_pjax_campaign').click();
                    });
                }
            },
            Отмена: {
                btnClass: 'btn-blue',
                action: function(){}
            }
        }
    });
});