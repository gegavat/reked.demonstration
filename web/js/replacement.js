var protocol = window.location.protocol;
var hostname = window.location.hostname;
var pathname = window.location.pathname;

// после pjax-обновления вновь инициализируется bootstrap tooltip
$(document).ajaxComplete(function() {
    $("[data-toggle='tooltip']").tooltip();
});

// запись в куки не размеченной страницы и переход на разметку страницы
$('#pjax_replacement').on('click', '#link_to_mark', function(e) {
    e.preventDefault();
    var page = $('#replacement_page').val();
    app.setCookie('mark_page', page, {expires:3600, path:'/'});
    location.href = '/mark/index.html';
});

// всплывающий фрейм для показа всех объявлений группы
$('#pjax_replacement').on('click', '.data-ads', function(e) {
    e.preventDefault();
    var ads = JSON.parse( $(this).attr('data-ads') );
    var adFrame = $('.extra-ads');
    var adsHtml = "";
    ads.forEach(function(ad) {
		if (adsHtml !== "") adsHtml += "<hr>";
		var
			image = ad.bg_url ? "<div style='background-image: url(" + ad.bg_url + ");background-position: center center;min-height:60px'></div>" : "",
			header = ad.header ? "<div style='font-weight:600'>" + ad.header + "</div>" : "",
			header2 = ad.header2 ? "<div style='font-weight:600'>" + ad.header2 + "</div>" : "",
			description = ad.description ? "<div>" + ad.description + "</div>" : "",
			href = ad.href ? "<div><a href='" + ad.href + "' style='color: #334acc'>" + ad.href + "</a></div>" : "";

		adsHtml +=
		"<div>" +
			image + header + header2 + description + href +
		"</div>";
	});
	adFrame.html(adsHtml);
    adFrame.css({
		display: 'block',
		top: e.pageY + 10 + 'px',
		left: e.pageX + 6 + 'px'
	});
});

// скрыть всплывающий фрейм для показа всех об-ний группы
$(document).mouseup(function (e) {
    var adFrame = $('.extra-ads');
    if (adFrame.has(e.target).length === 0){
        adFrame.hide();
    }
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
        quills[i].repl_identity_id = $(txt_input).data('repl_identity_id');
        quills[i].mark_id = $(txt_input).data('mark_id');
        quills[i].numb_txt_repl = $(txt_input).data('numb_txt_repl');
        
        var delta = $(txt_input).data('delta');
        if (delta) quills[i].setContents(delta);
    }

    quills.forEach(function(quill, i) {
        quill.on('selection-change', function(range) {
            if (!range) {
                $.ajax({
                    url: '/replacement/save-txt.html',
                    data: {
                        repl_identity_id: quill.repl_identity_id,
                        mark_id: quill.mark_id,
                        delta: JSON.stringify( quill.getContents() )
                    },
                    type: 'POST',
                    success: function(res) {
                        console.log(res);

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

// получение типа кампании и id кампании для выбора кампаний и выбора страниц
function getCampaignParam(campaignInfo) {
    if ( campaignInfo[0] === 'ya_campaign' )
        return '?ya_campaign_id=' + campaignInfo[1];
    else if ( campaignInfo[0] === 'g_campaign' )
        return '?g_campaign_id=' + campaignInfo[1];
}

// выбор кампании
$('#pjax_replacement').on('change', '#replacement_campaign', function() {
    var campaignInfo = $(this).val().split('='),
        campaignParam = getCampaignParam(campaignInfo);
    var go_to_page = protocol + '//' + hostname + pathname + campaignParam;
    $('#btn_pjax_replacement').attr('href',go_to_page);
    $('#btn_pjax_replacement').click();
});

// выбор страницы
$('#pjax_replacement').on('change', '#replacement_page', function() {
    var campaignInfo = $('#replacement_campaign').val().split('='),
        campaignParam = getCampaignParam(campaignInfo),
        page = $(this).val();
    var go_to_page = protocol + '//' + hostname + pathname + campaignParam + '&repl_page=' + page;
    $('#btn_pjax_replacement').attr('href',go_to_page);
    $('#btn_pjax_replacement').click();
});

// анимация перезагрузки страницы
app.pjaxAnimation('#pjax_replacement', initQuill);

// привязываем Quill ко всем полям ввода
initQuill();

// по клику по полю ввода поднимаем это поле выше
var z_index = 10;
$('#pjax_replacement').on('click', '.txt-area', function() {
    z_index++;
    $(this).css('z-index', z_index);
});

// клик по любому изображению в таблице подмен
$('#pjax_replacement').on('click', '.repl-img', function() {
    $("#loading-image .modal-body").html('<br>');
    $('#loading-image').modal('show');
    var mode = $(this).attr('data-mode');
    $(this).attr('data-selected', 'true');
    var width = $(this).data('width');
    var height = $(this).data('height');
    var repl_identity_id = $(this).data('repl_identity_id');
    var mark_id = $(this).data('mark_id');

    // окно выбора изображений -actionUploadImage-
    function uploadImage() {
        app.modalAjax(
            '#loading-image',
            '/replacement/upload-image.html'
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
                    '/replacement/loaded-images.html',
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
                            '/replacement/loaded-images.html',
                            {
                                data: JSON.stringify({
                                    src: selectedImage.prop('src'),
                                    repl_identity_id: img.data('repl_identity_id'),
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
            '/replacement/update-image.html',
            {
                data: JSON.stringify({
                    repl_identity_id: repl_identity_id,
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
                    '/replacement/del-img.html',
                    {
                        data: JSON.stringify({
                            repl_identity_id: repl_identity_id,
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
        '/replacement/upload-image.html',
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
        '/replacement/crop-save-image.html',
        {
            data: JSON.stringify({
                src: $('#jcrop-img').prop('src'),
                repl_identity_id: img.data('repl_identity_id'),
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
        '/replacement/nocrop-save-image.html',
        {
            data: JSON.stringify({
                src: $('#jcrop-img').prop('src'),
                repl_identity_id: img.data('repl_identity_id'),
                mark_id: img.data('mark_id'),
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
        '/replacement/crop-cnc.html',
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

// эффект при наведении на иконки "посмотреть" и "удалить"
$("#pjax_replacement").on('mouseenter', '.repl-icon',
    function() { $(this).css('opacity', 1); }
);
$("#pjax_replacement").on('mouseleave', '.repl-icon',
    function() { $(this).css('opacity', 0.7); }
);

// клик по иконке "посмотреть подмену"
$("#pjax_replacement").on('click', '.repl-watch', function() {
    var replPage = $(this).data('repl_page');
    var replIdentityId = $(this).data('repl_identity_id');

    var replacementWindow = window.open ('/replacement/download-animation.html', '_blank');
    app.mainAjax('/replacement/get-replacement-by-display-identity.html', {
        replIdentityId: replIdentityId,
        replPage: replPage
    }).then(function(href) {
        replacementWindow.location.href = href;
    });

});

// клик по иконке "удалить подмену"
$("#pjax_replacement").on('click', '.repl-del', function() {
    var repl_identity_id = $(this).data('repl_identity_id');
    $.confirm({
        animateFromElement: false,
        animation: 'scaleY',
        columnClass: 'medium',
        title: 'Очистка настроенных подмен',
        content: 'Очистить настроенные подмены для этой группы объявлений?',
        buttons: {
            Да: function () {
                app.mainAjax('/replacement/del-rpl.html', {
                    repl_identity_id: repl_identity_id
                }).then(function (res) {
                    // очистка текстовых подмен в строке
                    $('div.txt-area').filter(function (i, elem) {
                        return $(elem).attr("data-repl_identity_id") == repl_identity_id;
                    }).each(function (i, elem) {
                        quills[$(elem).data('numb_txt_repl')].setText('');
                    });
                    // очистка графических подмен в строке
                    $('div.repl-img').filter(function (i, elem) {
                        return $(elem).attr("data-repl_identity_id") == repl_identity_id;
                    }).each(function (i, elem) {
                        $(elem).attr('data-mode', 'save');
                        $(elem).children('img').attr('src', '/web/images/no-image.png');
                    });
                });
            },
            Отмена: function () {
            }
        }
    });
});

// сохранение количества записей на странице в куки
$("#pjax_replacement").on('click', '.pagin_elem', function(e) {
    e.preventDefault();
    var pageNumber = $(this).data('page_numb');
    if ( $(this).hasClass('active') )
        return false;
    app.setCookie('page_number', pageNumber, {expires:3600});
    $('#btn_pjax_replacement').click();
});
