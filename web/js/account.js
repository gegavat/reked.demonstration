// анимация для обновления pjax
app.pjaxAnimation('#btn_pjax_account');

// добавление аккаунтов Яндекс
$('#but-ad-ya-acc').click(function() {
    var client_id = $(this).data('client_id');
    var yaAuth = window.open("https://oauth.yandex.ru/authorize?response_type=code&force_confirm=yes&client_id=" + client_id, 'Авторизация Яндекс', 'width=750, height=600, top='+((screen.height-680)/2)+',left='+((screen.width-860)/2)+', resizable=yes, scrollbars=yes, status=yes');

    var timerId = setInterval(function() {
        if (yaAuth.closed) {
            clearInterval(timerId);
            $('#btn_pjax_account').click();
        }
    }, 1000);
});

// добавление аккаунтов Google
$('#but-ad-go-acc').click(function() {
    var link = $(this).data('link');

    var yaAuth = window.open(link, 'Авторизация Google', 'width=750, height=600, top='+((screen.height-680)/2)+',left='+((screen.width-860)/2)+', resizable=yes, scrollbars=yes, status=yes');

    var timerId = setInterval(function() {
        if (yaAuth.closed) {
            clearInterval(timerId);
            $('#btn_pjax_account').click();
        }
    }, 1000);
});

// удаление аккаунтов Яндекс
$('#pjax_account').on('click', '.ya-account-remove-icons', function() {
    var ya_account = $(this).data('ya_account');
    $.confirm({
        title: 'Удаление аккаунта',
        content: 'Вы уверены, что хотите удалить аккаунт?',
        buttons: {
            Удалить: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/account/del-ya-account.html", {
                        ya_account: ya_account
                    }).then(function(result) {
                        console.log(result);
                        $('#btn_pjax_account').click();
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

// удаление аккаунтов Google
$('#pjax_account').on('click', '.g-account-remove-icons', function() {
    var g_account = $(this).data('g_account');
    $.confirm({
        title: 'Удаление аккаунта',
        content: 'Вы уверены, что хотите удалить аккаунт?',
        buttons: {
            Удалить: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/account/del-g-account.html", {
                        g_account: g_account
                    }).then(function(result) {
                        console.log(result);
                        $('#btn_pjax_account').click();
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