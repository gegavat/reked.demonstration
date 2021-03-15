// общие свойства и методы приложения
var app = {
    payMultiplier: 1000000,
    markDescendants: 4,
    // расширенный jquery ajax для модальных окон
    modalAjax: function(modSelector, url, data, isFile, isPost) {
        var ajaxParam = {
            url: url,
            data: data || false,
            type: isPost ? 'POST' : 'GET',
            beforeSend: function () {
                $(modSelector + ' .modal-body').html('<img id="loadImg" src="/web/images/ajax-loader-modal.svg">');
            },
            error: function () {
                alert('Ошибка загрузки');
            },
            complete: function () {
                $("#loadImg").hide();
            }
        };
        if (isFile) {
            ajaxParam.processData = false;
            ajaxParam.contentType = false;
        }
        return new Promise(function(resolve) {
            ajaxParam.success = function (res) {
                resolve(res);
            };
            $.ajax(ajaxParam);
        });
    },
    // расширенный jquery ajax для основных страниц
    mainAjax: function(url, data, isPost) {
        return new Promise(function(resolve) {
            $.ajax({
                url: url,
                data: data || false,
                type: isPost ? 'POST' : 'GET',
                beforeSend: function () {
                    $("#bckgr_dark").css('display', 'block');
                    $("#bckgr_dark").animate({opacity: 0.7}, 600);
                },
                success: function(res) {
                    resolve(res);
                },
                error: function() {
                    alert ("Ошибка запроса");
                },
                complete: function () {
                    $("#bckgr_dark").animate({opacity: 0}, 500, function () {
                        $("#bckgr_dark").css('display', 'none');
                    });
                }
            });
        });
    },
    // анимация при отправке pjax-запроса
    pjaxAnimation: function(pjaxContainer, onLoadFunc) {
        $(pjaxContainer).on('pjax:send', function() {
            $("#bckgr_dark").css('display', 'block');
            $("#bckgr_dark").animate({opacity: 0.7}, 300);
        });
        $(pjaxContainer).on('pjax:complete', function() {
            $("#bckgr_dark").animate({opacity: 0}, 300, function() {
                $("#bckgr_dark").css('display', 'none');
            });
            if (onLoadFunc) onLoadFunc();
        });
    },

    // возвращает cookie с именем name, если есть, если нет, то undefined
    getCookie: function(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    },
    // запись cookie
    // name - название cookie, value - значение cookie (строка)
    // options - объект с дополнительными свойствами для установки cookie:
    // expires: 3600 – кука на час
    // path - путь для cookie.
    // domain - домен для cookie.
    // secure - если true, то пересылать cookie только по защищенному соединению.
    setCookie: function (name, value, options) {
        options = options || {};
        var expires = options.expires;
        if (typeof expires == "number" && expires) {
            var d = new Date();
            d.setTime(d.getTime() + expires * 1000);
            expires = options.expires = d;
        }
        if (expires && expires.toUTCString) {
            options.expires = expires.toUTCString();
        }
        value = encodeURIComponent(value);
        var updatedCookie = name + "=" + value;
        for (var propName in options) {
            updatedCookie += "; " + propName;
            var propValue = options[propName];
            if (propValue !== true) {
                updatedCookie += "=" + propValue;
            }
        }
        document.cookie = updatedCookie;
    }

};

$(document).ready(function() {
    // сворачивание блоков; работает и после pjax-обновления
	$('.collapse-link').off();
    $('.right_col').on('click', '.collapse-link', function() {
        var $BOX_PANEL = $(this).closest('.x_panel'),
            $ICON = $(this).find('i'),
            $BOX_CONTENT = $BOX_PANEL.find('.x_content');
        if ($BOX_PANEL.attr('style')) {
            $BOX_CONTENT.slideToggle(200, function(){
                $BOX_PANEL.removeAttr('style');
            });
        } else {
            $BOX_CONTENT.slideToggle(200);
            $BOX_PANEL.css('height', 'auto');
        }
        $ICON.toggleClass('fa-chevron-up fa-chevron-down');
    });

    // всегда развернутый пункт меню "Менеджер подмен"
    // $('#sidebar-menu').find('a').off();
    // var replMenu = $('.repl_menu_item ul.nav.child_menu');
    // replMenu.css('display', 'block');
    // $('a#menu_toggle').on('click', function() {
    //     if ( $('body').hasClass('nav-sm') ) {
    //         replMenu.css('display', 'none');
    //     } else {
    //         replMenu.css('display', 'block');
    //     }
    // });

    // выделение пунктов меню при наведении мышью
    // $('#sidebar-menu .side-menu').children('li').on('mouseover', function() {
    //     $(this).addClass('active');
    // });
    // $('#sidebar-menu .side-menu').children('li').on('mouseleave', function() {
    //     $(this).removeClass('active');
    // });

    // скрытие видеоподсказок
    $('.video_tip_remove').on('click', function() {
        $.confirm({
            title: 'Подтверждение действия',
            content: 'Вы уверены, что хотите скрыть видеоподсказки?<br>Вы всегда сможете включить их в личном профиле.',
            buttons: {
                Ok: function() {
                    app.mainAjax("/payment/display-video-tip.html").then(function() {
                        $('.video_container').css('display', 'none');
                    });
                },
                Отмена: function() {}
            }
        });
    });

    // отображение, запуск видеоподсказки
    $('.video_container').on('click', function(e) {
        if ( $(e.target).hasClass('video_tip_remove') )
            return false;
        $('#show_video_tips').modal('show');
        var videoUrl = $(this).attr('data-video_url');
        $('#show_video_tips .modal-body').html('<iframe width="560" height="315" src="' + videoUrl + '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
    });

    // всплывающая подсказка при наведении на кнопку "заказать настройку"
    $('.btn-order-set button').tooltip();
    $('.btn-order-set button').on('click', function() {
        $.confirm({
            type: 'green',
            title: 'Подтверждение действия',
            content: 'Заказать настройку сервиса? С баланса будет списано ' + $(this).data('cost') + ' рублей',
            buttons: {
                Заказать: {
                    btnClass: 'btn-green',
                    action: function () {
                        app.mainAjax("/payment/order-set.html").then(function(res) {
                            if ( res === 'error-money' ) {
                                $.alert({
                                    type: 'red',
                                    title: 'Ошибка заказа',
                                    content: 'В Вашем кошельке не достаточно денег. Сначала, пополните баланс'
                                });
                            }
                            if ( res === 'ok' ) {
                                $.confirm({
                                    type: 'green',
                                    title: 'Результат заказа',
                                    content: 'Отлично, услуга заказана! Сейчас мы свяжемся с Вами для уточнения подробностей',
                                    buttons: {
                                        Ok: function() {
                                            location.reload();
                                        }
                                    }
                                });
                            }
                        });
                    }
                },
                Отмена: function() {}
            }
        });
    });
});


