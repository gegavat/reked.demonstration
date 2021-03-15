// всплывающие подсказки при наведении на зн. вопроса в таблице тарифов
$('#table-tariffs i.fa-question-circle').tooltip();

// смена тарифа
$('#table-tariffs').on('click', '.but-tariff', function() {
    var change_tariff = $(this).data('change_tariff');
    $.confirm({
        title: 'Подтвердите смену тарифа',
        content: 'Вы уверены, что хотите перейти на выбранный тариф: '+change_tariff+'?<br><br>P.S. Если у вас остались не использованные дни на действующем тарифе, то будет произведен перерасчет средств',
        buttons: {
            Перейти: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/payment/change-tariff.html", {
                        change_tariff: change_tariff
                    }).then(function(res) {
                        console.log(res);
                        switch (res) {
                            case 'error-money':
                                $.alert({
                                    type: 'red',
                                    title: 'Ошибка!',
                                    content: '<p class="text-danger">На Вашем счету не достаточно средств для активации выбранного тарифа!</p>'
                                });
                                return false;
                            case 'del-d':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные домены для Мультилендинга<br>Пожалуйста, активируйте их заново</p>',
                                    buttons: {
                                        'Активировать домены': {
                                            btnClass: 'btn-orange',
                                            action: function () {
                                                location.href = '/repl-activate/index.html';
                                            }
                                        }
                                    }
                                });
                                return false;
                            case 'del-p':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные страницы для Геолендинга<br>Пожалуйста, активируйте их заново</p>',
                                    buttons: {
                                        'Активировать страницы': {
                                            btnClass: 'btn-orange',
                                            action: function () {
                                                location.href = '/geo-repl-activate/index.html';
                                            }
                                        }
                                    }
                                });
                                return false;
                            case 'del-b':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили все активные бид-менеджеры.<br>Пожалуйста, активируйте их заново</p>',
                                    buttons: {
                                        'Активировать бид-менеджеры': {
                                            btnClass: 'btn-orange',
                                            action: function () {
                                                location.href = '/bidder/index.html';
                                            }
                                        }
                                    }
                                });
                                return false;
                            case 'del-dp':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные домены для Мультилендинга и активированные страницы для Геолендинга<br>Пожалуйста, активируйте их заново</p>',
                                    buttons: {
                                        Ok: function(){ location.reload() }
                                    }
                                });
                                return false;
                            case 'del-db':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные домены для Мультилендинга и активные бид-менеджеры<br>Пожалуйста, активируйте их заново.</p>',
                                    buttons: {
                                        Ok: function(){ location.reload() }
                                    }
                                });
                                return false;
                            case 'del-pb':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные страницы для Геолендинга и активные бид-менеджеры<br>Пожалуйста, активируйте их заново.</p>',
                                    buttons: {
                                        Ok: function(){ location.reload() }
                                    }
                                });
                                return false;
                            case 'del-dpb':
                                $.confirm({
                                    type: 'orange',
                                    title: 'Ваш тариф изменен',
                                    content: '<p><span class="text-danger">Внимание!</span><br>Мы отключили активированные домены для Мультилендинга, активированные страницы для Геолендинга и активные бид-менеджеры<br>Пожалуйста, активируйте их заново.</p>',
                                    buttons: {
                                        Ok: function(){ location.reload() }
                                    }
                                });
                                return false;
                            case 'ok':
                                $.confirm({
                                    type: 'green',
                                    title: 'Ваш тариф изменен',
                                    content: 'Смена тарифа произведена успешно',
                                    buttons: {
                                        Ok: {
                                            btnClass: 'btn-green',
                                            action: function () {
                                                location.reload(true);
                                            }
                                        }
                                    }
                                });
                                return false;
                        }
                    });
                }
            },
            Отмена: {
                btnClass: 'btn-grey',
                action: function(){}
            }
        }
    });
});

// загрузка данных для показа в модальном окне для изменения пароля
$('#personal_area').on('click', '#but-password_change', function() {
    $('#show-password_change').modal('show');
    // var user_id = $(this).data('user_id');
    app.modalAjax(
        '#show-password_change',
        '/payment/password-change.html'
    ).then(function(res) {
        $('#show-password_change .modal-body').html(res);
    });
});

// вкл/выкл отображения видео-подсказок
$('input#video_tip').on('ifChecked ifUnchecked', function(){
    app.mainAjax("/payment/display-video-tip.html").then(function(res) {
        $.alert({
            type: 'green',
            title: 'Успешно',
            content: res ? 'Видеоподсказки включены' : 'Видеоподсказки выключены'
        });
    });
});

// вкл/выкл отправку сообщений и продление тарифа
$('input.console').on('ifChecked', function(){
    var status = 1;
    var operation = this.id;
    app.mainAjax("/payment/personal-operation.html", {
        status: status,
        operation: operation
    }).then(function() {
        if (operation == 'send_message') {
            title = 'Рассылка сообщений';
            content = 'Вы будете получать сообщения на электронную почту';
        } else {
            title = 'Продление тарифа';
            content = 'Включено автоматическое продление тарифа';
        }
        $.alert({
            type: 'green',
            title: title,
            content: content
        });
    });
});

$('input.console').on('ifUnchecked', function(){
    var status = 0;
    var operation = this.id;
    app.mainAjax("/payment/personal-operation.html", {
        status: status,
        operation: operation
    }).then(function() {
        if (operation == 'send_message') {
            title = 'Рассылка сообщений';
            content = 'Вы отписались от рассылки на электронную почту';
        } else {
            title = 'Продление тарифа';
            content = 'Отключено автоматическое продление тарифа';
        }
        $.alert({
            type: 'grey',
            title: title,
            content: content
        });
    });
});