// загрузка данных для показа в модальном окне для пополнения кошелька
$('.finance').on('click', '.but-payment', function() {
    $('#show_pay').modal('show');
    var balance = $(this).data('balance');
     app.modalAjax(
         '#show_pay',
         '/payment/pay.html'
    ).then(function(res) {
        $('#show_pay .modal-body').html(res);
        $('#balance').text(balance);
    });
});

// выбор способа оплаты
$('#show_pay').on('click', '.pay_img', function() {
    $(this).siblings('.pay_img').removeClass('pay_img_active');
    $(this).addClass('pay_img_active');
    var elemValue = $(this).attr('data-value');
    $('.pay_type').attr('value', elemValue);
    if ( $(this).closest('.form-group').hasClass('has-error') )
        $('#payment').yiiActiveForm('validateAttribute', 'payment-pay_type');
});

// показ поля ввода купонов
$('#show_pay').on('click', '#display_coupon', function () {
    $('#coupon_container').css('display', 'block');
    return false;
});

// обработка введенного купона
$('#show_pay').on('click', '#btn_coupon', function () {
    var coupon = $('#input_coupon').val();
    if ( !coupon ) {
        $.alert({
            type: 'red',
            title: 'Ошибка',
            content: 'Введите номер купона'
        });
        return false;
    }
    app.mainAjax("/payment/coupon-apply.html", {
        coupon: coupon
    }).then(function(result) {
        if ( result === 'error-length' ) {
            $.alert({
                type: 'red',
                title: 'Ошибка',
                content: 'Неверное количество символов в купоне'
            });
        }
        if ( result === 'error-coupon' ) {
            $.alert({
                type: 'red',
                title: 'Ошибка',
                content: 'Купон с таким номером не найден или был уже использован'
            });
        }
        if ( result === 'success' ) {
            $.confirm({
                type: 'green',
                title: 'Купон успешно применен',
                content: 'Вы успешно применили купон. Сумма купона зачислена на ваш счет',
                backgroundDismiss: function(){
                    return false;
                },
                buttons: {
                    Ok: function() {
                        location.reload();
                    }
                }
            });
        }
    });
});

//продление тарифа
$('#menu1').on('click', '#but-prolong', function() {
    var tariff = $(this).data('tariff');
    var cost_tariff = $(this).data('cost-tariff');
    console.log(tariff);
    $.confirm({
        title: 'Подтвердите активацию тарифа',
        content: 'Вы уверены, что хотите активировать тариф '+tariff.toUpperCase()+'? С Вашего счета спишется '+cost_tariff+' руб.',
        buttons: {
            Активировать: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/payment/tariff-prolongation.html", {
                    }).then(function(res) {
                        console.log(res);
                        switch (res) {
                            case 'error-money':
                                $.alert({
                                    type: 'red',
                                    title: 'Ошибка!',
                                    content: '<p class="text-danger">На Вашем счету не достаточно средств для активации тарифа!</p>'
                                });
                                return false;
                            case 'ok':
                                $.confirm({
                                    type: 'green',
                                    title: 'Ваш тариф активирован',
                                    content: 'Активация тарифа произведена успешно',
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