// очистка добавляемого url от протокола
$('#geo_page_url').on('focusout', function() {
    var url = $(this).val();
    url = decodeURIComponent(url);
    url = url.replace(/\?.*/, "");
    url = url.replace(/#.*/, "");
    url = url.replace(/\/$/, "");
    if ( url.match(/^https?:\/\//g) ) {
        if ( url.indexOf("https://") !== -1 ) {
            $('#geo_page_protocol').val("https://");
        } else {
            $('#geo_page_protocol').val("http://");
        }
        url = url.replace(/^https?:\/\//, "");
    }
    $(this).val(url);
});

// добавление новой страницы
$('#geo_page_add').on('click', function() {
    var protocol = $('#geo_page_protocol').val();
    var url = $('#geo_page_url').val();
    if (!url) {
        $.alert({
            type: 'red',
            title: 'Ошибка добавления страницы',
            content: 'Укажите ссылку на страницу'
        });
        return false;
    }
    app.mainAjax(
        "/geo-page/new-page.html",
        {
            data: JSON.stringify({
                url: protocol+url
            })
        },
        true
    ).then(function(result) {
        if ( result === 'error-url' ) {
            $.alert({
                type: 'red',
                title: 'Ошибка добавления страницы',
                content: 'Некорректный url-адрес страницы'
            });
        } else if ( result === 'error-alreadyexist' ) {
            $.alert({
                type: 'red',
                title: 'Ошибка добавления страницы',
                content: 'Страница уже была добавлена'
            });
        } else {
            $('#geo_page_url').val('');
            $('#btn_pjax_geo_page').click();
        }
    });
});

// удаление добавленных страниц
$('#pjax_geo_page').on('click', '.geo_page_del', function() {
    var pageId = $(this).data('page_id');
    $.confirm({
        title: 'Подтверждение удаления',
        content: 'Вы действительно хотите удалить эту страницу?',
        buttons: {
            Да: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax(
                        "/geo-page/del-page.html",
                        {
                            page_id: pageId
                        }
                    ).then(function(result) {
                        console.log (result);
                        if ( result === 'error' ) {
                            $.alert({
                                type: 'red',
                                title: 'Ошибка удаления страницы',
                                content: 'Пожалуйста, обновите страницу и повторите попытку'
                            });
                        } else {
                            $('#btn_pjax_geo_page').click();
                        }
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