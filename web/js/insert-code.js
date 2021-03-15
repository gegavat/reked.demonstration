// копирование кода для вставки в буфер
new ClipboardJS('#copy_emb_code');

$(document).ready(function () {
    $('.page_status').each(function(number, elem){
        var page = $(this).data('page');
        $.ajax({
            url: '/insert-code/check.html',
            data: {
                page: page
            },
            beforeSend: function () {
                $('.page_status').text('Идет проверка...');
            },
            success: function(res) {
                if ( res == 1 ) {
                    $('[data-page="' + page + '"]').text('Код установлен').attr('class', 'page_status text-success');
                }
                if ( res == 2 ) {
                    $('[data-page="' + page + '"]').text('Код не найден').attr('class', 'page_status text-danger');
                }
            },
            error: function() {
                $('.page_status').text('Ошибка запроса');
            }
        });
    });
});