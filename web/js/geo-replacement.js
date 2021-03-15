var protocol = window.location.protocol;
var hostname = window.location.hostname;
var pathname = window.location.pathname;

// после pjax-обновления вновь инициализируется bootstrap tooltip
$(document).ajaxComplete(function() {
    $("[data-toggle='tooltip']").tooltip();
});

// запись в куки не размеченной страницы и переход на разметку страницы
$('#pjax_geo_replacement').on('click', '#link_to_mark', function(e) {
    e.preventDefault();
    var page = $.trim( $("#geo_replacement_page option:selected").text() );
    app.setCookie('geo_mark_page', page, {expires:3600, path:'/'});
    location.href = '/geo-mark/index.html';
});

var quills = [];

var fontSizeStyle = Quill.import('attributors/style/size');
fontSizeStyle.whitelist = ['8px', '10px', '12px', '14px', '16px', '18px', '20px', '22px', '24px', '48px'];
Quill.register(fontSizeStyle, true);

function initQuill() {
    var txt_inputs = document.getElementsByClassName('txt-area');
    for (var i = 0; i < txt_inputs.length; i++) {
        var txt_input = txt_inputs[i];

        quills[i] = new Quill(txt_input, {
            bounds: '.container',
            modules: {
                toolbar: '.editor-tbr-' + i
            },
            theme: 'bubble'
        });
        quills[i].location_id = $(txt_input).data('location_id');
        quills[i].mark_id = $(txt_input).data('mark_id');
        quills[i].numb_txt_repl = $(txt_input).data('numb_txt_repl');

        var delta = $(txt_input).data('delta');
        if (delta) quills[i].setContents(delta);
    }
    quills.forEach(function(quill, i) {
        quill.on('selection-change', function(range) {
            if (!range) {
                $.ajax({
                    url: '/geo-replacement/save-txt.html',
                    data: {
                        page_id: $('#geo_replacement_page').val(),
                        mark_id: quill.mark_id,
                        location_id: quill.location_id,
                        delta: JSON.stringify( quill.getContents() )
                    },
                    type: 'POST',
                    success: function(res) {
                        // console.log(res);
                        var save_icon = $('span').filter('[data-numb_txt_repl="' + quill.numb_txt_repl +  '"]');
                        save_icon.css('display', 'block');
                        setTimeout(function(selector) {
                            selector.css('display', 'none');
                        }, 1000, save_icon);
                    },
                    error: function() {
                        alert('Ошибка сохранения. Не удается соединиться с сервером');
                    }
                });
            }
        });
    });
}

// анимация перезагрузки страницы
app.pjaxAnimation('#pjax_geo_replacement', initQuill);

// привязываем Quill ко всем полям ввода
initQuill();

// по клику по полю ввода поднимаем это поле выше
var z_index = 10;
$('#pjax_geo_replacement').on('click', '.txt-area', function() {
    z_index++;
    $(this).css('z-index', z_index);
});

// выбор страницы
$('#pjax_geo_replacement').on('change', '#geo_replacement_page', function() {
    var pageId = $(this).val();
    var go_to_page = protocol + '//' + hostname + pathname + '?repl_page_id=' + pageId;
    // console.log (go_to_page);
    $('#btn_pjax_geo_replacement').attr('href',go_to_page);
    $('#btn_pjax_geo_replacement').click();
});

// кнопка Добавить Локацию
$('#pjax_geo_replacement').on('click', '.location-add', function() {
    $('#geo-location-modal').modal('show');
    $('input[type=radio][name=sg-country]').prop('checked', false);
    $('.sg-region-list, .sg-city-list').empty();
});

// подгрузка регионов sypexgeo
$('#geo-location-modal').on('change', 'input[type=radio][name=sg-country]', function() {
    $('.sg-region-list, .sg-city-list').empty();
    var countryIso = $(this).val();
    $.ajax({
        url: '/geo-replacement/get-sg-regions.html',
        data: {country_iso: countryIso},
        success: function(res) {
            $('.sg-region-list').html(res);
        }
    });
});

// подгрузка городов sypexgeo
$('#geo-location-modal').on('change', 'input[type=radio][name=sg-region]', function() {
    $('.sg-city-list').empty();
    var regionId = $(this).val();
    $.ajax({
        url: '/geo-replacement/get-sg-cities.html',
        data: {region_id: regionId},
        success: function(res) {
            $('.sg-city-list').html(res);
        }
    });
});

// закрыть окно добавления локации
$('#geo-location-modal').on('click', '.geo_loc_mod_close', function() {
    $('#geo-location-modal').modal('hide');
});

// сохранение новой локации
$('#geo-location-modal').on('click', '.geo_loc_mod_add', function() {
    var country = $('input[type=radio][name=sg-country]:checked').val();
    var region = $('input[type=radio][name=sg-region]:checked').val();
    var city = $('input[type=radio][name=sg-city]:checked').val();
    if ( !country ) {
        alert ('Укажите локацию');
    }
    var geo_type, geo_id;
    // страна
    if ( country && !region && !city ) {
        geo_type = 'country';
        geo_id = $('input[type=radio][name=sg-country]:checked').data('country_id');
    }
    // регион
    if ( country && region && !city ) {
        geo_type = 'region';
        geo_id = region;
    }
    // город
    if ( country && region && city ) {
        geo_type = 'city';
        geo_id = city;
    }
    $.ajax({
        url: '/geo-replacement/save-location.html',
        data: {
            page_id: $('#geo_replacement_page').val(),
            geo_type: geo_type,
            geo_id: geo_id
        },
        success: function(res) {
            // console.log (res);
            if ( res === 'error-alreadyexist' ) {
                $.alert({
                    type: 'red',
                    title: 'Ошибка',
                    content: 'Данная локация уже добавлена'
                });
                return false;
            }
            if ( res === 'error-alreadyexist' ) {
                $.alert({
                    type: 'red',
                    title: 'Неизвестная ошибка',
                    content: 'Пожалуйста, попробуйте позже'
                });
                return false;
            }
            if ( res === 'success' ) {
                $('#geo-location-modal').modal('hide');
                $('#btn_pjax_geo_replacement').click();
            }
        }
    });
});

// эффект при наведении на иконки "посмотреть" и "удалить"
$("#pjax_geo_replacement").on('mouseenter', '.repl-icon',
    function() { $(this).css('opacity', 1); }
);
$("#pjax_geo_replacement").on('mouseleave', '.repl-icon',
    function() { $(this).css('opacity', 0.7); }
);

// клик по иконке "посмотреть подмену"
$("#pjax_geo_replacement").on('click', '.repl-watch', function() {
    var locationId = $(this).data('location_id');
    var replPage = $.trim( $("#geo_replacement_page option:selected").text() );
    var replacementWindow = window.open ('/geo-replacement/download-animation.html', '_blank');
    app.mainAjax('/geo-replacement/get-replacement-by-display-identity.html', {
        locationId: locationId,
        replPage: replPage
    }).then(function(href) {
        replacementWindow.location.href = href;
    });
});

// клик по иконке "удалить локацию"
$("#pjax_geo_replacement").on('click', '.repl-del', function() {
    var locationId = $(this).data('location_id');
    $.confirm({
        animateFromElement: false,
        animation: 'scaleY',
        title: 'Удаление локации',
        content: 'Удалить выбранную локацию?',
        buttons: {
            Да: function () {
                $.ajax({
                    url: '/geo-replacement/del-loc.html',
                    data: { location_id: locationId },
                    success: function(res) {
                        $('#btn_pjax_geo_replacement').click();
                    }
                });
            },
            Отмена: function () {
            }
        }
    });
});

// клик по любому изображению в таблице подмен
$('#pjax_geo_replacement').on('click', '.repl-img', function() {
    $("#loading-image .modal-body").html('<br>');
    $('#loading-image').modal('show');
    var mode = $(this).attr('data-mode');
    $(this).attr('data-selected', 'true');
    var width = $(this).data('width');
    var height = $(this).data('height');
    var location_id = $(this).data('location_id');
    var mark_id = $(this).data('mark_id');

    // окно выбора изображений -actionUploadImage-
    function uploadImage() {
        app.modalAjax(
            '#loading-image',
            '/geo-replacement/upload-image.html'
        ).then(function(res) {
            $("#loading-image .modal-body").html(res);
            $('#img-sizes').html('<strong>' + width + 'x' + height + 'px' +'</strong>');

            // три кнопки, полученные функцией uploadImage
            // "отмена"
            $('#loading-image .modal-body').on('click', '#cnc-file', function() {
                $('#loading-image').modal('hide');
            });
            // "выбрать файл" (input type=file) (превращен в обычную кнопку далее в коде)
            // ----------
            // "выбрать из загруженных"
            $("#loading-image").off( "click", "#choose-loaded-file" );
            $('#loading-image').on('click', '#choose-loaded-file', function() {
                // если изображение обновляется, то исключить из выбора это изображение
                var updateSrc = false;
                if (mode==="update") {
                    updateSrc = $('.repl-img')
                        .filter('[data-selected="true"]')
                        .children('img')
                        .prop('src');
                }
                // запрос загруженных изображений, прошедших фильтрацию на текущее изображение
                app.modalAjax(
                    '#loading-image',
                    '/geo-replacement/loaded-images.html',
                    updateSrc ? {
                        src: updateSrc
                    } : false
                ).then(function(res) {
                    $("#loading-image .modal-body").html(res);
                    // имитация чекбоксов на загруженных изображениях
                    $('.loaded-image img').click(function() {
                        var img_sel = $('.loaded-image img').filter('[data-selected="true"]');
                        if ( img_sel.length != 0 ) {
                            img_sel.attr('data-selected', 'false');
                            img_sel.css('border', 'none');
                        }

                        $(this).attr('data-selected', 'true');
                        $(this).css('border', 'blue 3px solid');
                    });
                    // сохранение уже загруженного изображения
                    $('#btn-choose-loadimg').click(function() {
                        var selectedImage = $('.loaded-image img').filter('[data-selected="true"]');
                        if ( selectedImage.length === 0 ) {
                            alert ('Выберите изображение!');
                            return false;
                        }
                        var img = $('.repl-img').filter('[data-selected="true"]');
                        app.modalAjax(
                            '#loading-image',
                            '/geo-replacement/loaded-images.html',
                            {
                                data: JSON.stringify({
                                    src: selectedImage.prop('src'),
                                    page_id: $('#geo_replacement_page').val(),
                                    location_id: img.data('location_id'),
                                    mark_id: img.data('mark_id')
                                })
                            },
                            false,
                            true
                        ).then(function(res) {
                            console.log(res);
                            $('#loading-image').modal('hide');
                            img.children('img').prop('src', res);
                            img.attr('data-mode', 'update');
                        });
                    });
                    $('#btn-cnc-loadimg').click(function() {
                        $('#loading-image').modal('hide');
                    });
                });
            });

            // при закрытия мод. окна снимаем атрибут выделения с ячейки с картинкой
            $('#loading-image').on('hidden.bs.modal', function() {
                $('.repl-img').filter('[data-selected="true"]').attr('data-selected', 'false');
            });
        });
    }

    // если изображение еще не прикреплено, сохраняем его (save), если прикреплено - обновляем (update)
    if ( mode === 'save' ) uploadImage();
    if ( mode === 'update' ) {
        // окно выбора изображений -actionUpdateImage-
        app.modalAjax(
            '#loading-image',
            '/geo-replacement/update-image.html',
            {
                data: JSON.stringify({
                    page_id: $('#geo_replacement_page').val(),
                    location_id: location_id,
                    mark_id: mark_id
                })
            },
            false,
            true
        ).then(function(res) {
            // отображаем картинку и три кнопки
            $("#loading-image .modal-body").html(res);
            // "обновить" - загружает стандартную форму загрузки изображений (прописана ранее)
            $('#btn-upd-img').click(function() {
                uploadImage();
            });
            // "удалить" - удаляет из БД эту подмену -actionDelImg-
            $('#btn-del-img').click(function() {
                app.modalAjax(
                    '#loading-image',
                    '/geo-replacement/del-img.html',
                    {
                        data: JSON.stringify({
                            page_id: $('#geo_replacement_page').val(),
                            location_id: location_id,
                            mark_id: mark_id
                        })
                    },
                    false,
                    true
                ).then(function(res) {
                    console.log(res);
                    $('#loading-image').modal('hide');
                    var img = $('.repl-img').filter('[data-selected="true"]');
                    img.children('img').prop('src', '/web/images/no-image.png');
                    img.attr('data-mode', 'save');
                });
            });
            // "отмена" - просто сворачивает модальное окно
            $('#btn-cnc-img').click(function() {
                $('#loading-image').modal('hide');
            });
        });
    }
});

// объект координат для кадрирования плагином Jcrop
var coords = {};

// при выборе файла происходит отправка формы
$('#loading-image').on('change', 'input[type=file]', function() {
    $('#form-image').submit();
});

// действия при отправке формы
$('#loading-image').on('submit', '#form-image', function(e) {
    e.preventDefault();
    // собираем данные формы в formdata и отправляем их используя ajax
    var form = $('#form-image');
    var file = $('input[type=file]')[0].files[0];
    var formData = new FormData(form[0]);
    formData.append('file', file);

    // при POST запросе обрабатываем данные стандартной формы и выдаем форму кадрирования изображения
    // запрос изображения для кадрирования -actionUploadImage-
    app.modalAjax(
        '#loading-image',
        '/geo-replacement/upload-image.html',
        formData,
        true,
        true
    ).then(function(res) {
        $("#loading-image .modal-body").html(res);
        // выводим изображение с кадрированием
        // и две кнопки "обрезать и сохранить", "сохранить без обрезки" - обработчики далее
        var img = $('.repl-img').filter('[data-selected="true"]');
        var width = img.data('width');
        var height = img.data('height');
        // цепляем к полученному изображению плагин Jcrop
        $('#jcrop-img').Jcrop({
            aspectRatio: width / height,
            onSelect: function(c) {
                coords.x = c.x;
                coords.y = c.y;
                coords.w = c.w;
                coords.h = c.h;
            },
            setSelect: [0, 0, width, height],
            boxWidth: 600,
            boxHeight: 600
        });
    });
});

// обработка кнопок при кадрировании изображений
// кнопка "Обрезать и Сохранить" (кадрирование изображений)
$('#loading-image').on('click', '.apply-img-crop', function() {
    var img = $('.repl-img').filter('[data-selected="true"]');
    app.modalAjax(
        '#loading-image',
        '/geo-replacement/crop-save-image.html',
        {
            data: JSON.stringify({
                src: $('#jcrop-img').prop('src'),
                page_id: $('#geo_replacement_page').val(),
                location_id: img.data('location_id'),
                mark_id: img.data('mark_id'),
                coords: coords
            })
        },
        false,
        true
    ).then(function(res) {
        console.log(res);
        $('#loading-image').modal('hide');
        // после сохранения в БД обновляем картинку в таблице подмен
        img.children('img').prop('src', res);
        img.attr('data-mode', 'update');
    });
});

// кнопка "Сохранить без обрезки" (кадрирование изображений)
$('#loading-image').on('click', '.apply-img-nocrop', function() {
    var img = $('.repl-img').filter('[data-selected="true"]');
    app.modalAjax(
        '#loading-image',
        '/geo-replacement/nocrop-save-image.html',
        {
            data: JSON.stringify({
                src: $('#jcrop-img').prop('src'),
                page_id: $('#geo_replacement_page').val(),
                location_id: img.data('location_id'),
                mark_id: img.data('mark_id')
            })
        },
        false,
        true
    ).then(function(res) {
        console.log(res);
        $('#loading-image').modal('hide');
        // после сохранения в БД обновляем картинку в таблице подмен
        img.children('img').prop('src', res);
        img.attr('data-mode', 'update');
    });
});

// кнопка "Отмена загрузки" (кадрирование изображений)
$('#loading-image').on('click', '.apply-img-cnc', function() {
    var src = $('#jcrop-img').prop('src');
    app.modalAjax(
        '#loading-image',
        '/geo-replacement/crop-cnc.html',
        {
            src: src
        }
    ).then(function(res) {
        $('#loading-image').modal('hide');
    });
});

// стилизация кнопки загрузки изображений
$('#loading-image').on('click', '#choose-file', function() {
    $('#btn-file-img').trigger('click');
});