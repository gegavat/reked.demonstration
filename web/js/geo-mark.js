var save_border = false;
var mark_move_id = false;

// подсчет количества потомков
function getDescendants(node, accum) {
    var i;
    accum = accum || [];
    for (i = 0; i < node.children.length; i++) {
        accum.push(node.children[i])
        getDescendants(node.children[i], accum);
    }
    return accum.length;
}

// смена фрейма по клику по выбранной странице в списке
$('#geo_mark_select').change(function() {
    var page = $(this).val();
    app.setCookie('geo_mark_page', page, {expires:3600, path:'/'});
    location.reload(true);
});

// после загрузки фрейма навешиваем на него обработчики
$('iframe').on("load", function () {
    // обрамление элементов фрейма при наведении мыши
    var iframe = $('iframe').contents().find("body");
    iframe.mouseover(function(event) {
        if  ( $(event.target).css('border-top-width') === '3px' ) return false; // отменяем смену стиля при просмотре подмены
        // if (event.target.children.length != 0) return false;
        if ( getDescendants(event.target) > app.markDescendants) return false;

        $(event.target).css({
            border: "2px outset blue",
            cursor: "pointer"
        });
    });
    iframe.mouseout(function(event) {
        // задержка удаления рамки при просмотре на 2 сек.
        if  ( $(event.target).css('border-top-width') === '3px' ) {
            setTimeout(function() {
                if (save_border) {
                    save_border = false;
                    return false;
                }
                $(event.target).css({
                    border: "none"
                });
            }, 2000);
            return false;
        }
        // обычный режим работы
        if (save_border) {
            save_border = false;
            return false;
        }
        $(event.target).css({
            border: "none"
        });
    });

    // действие по клику по элементу фрейма
    iframe.click(function(event) {
        event.preventDefault();
        // if (event.target.children.length != 0) return false;
        if ( getDescendants(event.target) > app.markDescendants) return false;

        save_border = true;
        // проверка режима: добавление или перемещение
        if (!mark_move_id) {
            $.confirm({
                animation: 'scaleY',
                columnClass: 'small',
                title: 'Подтверждение действия',
                content: 'Сделать этот элемент подменяемым?',
                buttons: {
                    Да: function () {
                        var self = event;
                        var selector = requestSelector(self);
                        app.mainAjax("/geo-mark/save-mark.html", {
                            selector: JSON.stringify(selector)
                        }, true).then(function(res) {
                            console.log(res);
                            switch (res) {
                                case 'error-type':
                                    $.alert({
                                        type: 'red',
                                        title: 'Результат добавления',
                                        content: '<p class="text-danger">Не удается определить тип элемента</p>'
                                    });
                                    return false;
                                case 'error-alreadyexist':
                                    $.alert({
                                        type: 'red',
                                        title: 'Результат добавления',
                                        content: '<p class="text-danger">Элемент уже подменяется</p>'
                                    });
                                    return false;
                            }
                            $.confirm({
                                title: 'Результат добавления',
                                content: 'Подмена успешно добавлена!',
                                buttons: {
                                    Ok: function () {
                                        $('html, body').animate({ scrollTop: $('#pjax_geo_mark').offset().top-40 }, 500);
                                        $('#btn_pjax_geo_mark').click();
                                    }
                                }
                            });
                        });
                        $(event.target).mouseout();
                    },
                    Отмена: function () {
                        $(event.target).mouseout();
                    }
                }
            });
        } else {
            $.confirm({
                animation: 'scaleY',
                columnClass: 'small',
                title: 'Подтверждение действия',
                content: 'Переместить сюда подмену?',
                buttons: {
                    Да: function () {
                        var self = event;
                        var selector = requestSelector(self);
                        selector.mark_id = mark_move_id;
                        app.mainAjax("/geo-mark/move-mark.html", {
                            selector: JSON.stringify(selector)
                        }, true).then(function(res) {
                            console.log(res);
                            switch (res) {
                                case 'error-type-undefined':
                                    $.alert({
                                        type: 'red',
                                        title: 'Результат перемещения',
                                        content: '<p class="text-danger">Не удается определить тип элемента</p>',
                                    });
                                    return false;
                                case 'error-type-mismatch':
                                    $.alert({
                                        type: 'red',
                                        columnClass: 'medium',
                                        title: 'Результат перемещения',
                                        content: '<p class="text-danger">Несоответствие типов подменяемых элементов</p>',
                                    });
                                    return false;
                                case 'error_same':
                                    $.alert({
                                        type: 'red',
                                        columnClass: 'medium',
                                        title: 'Результат перемещения',
                                        content: '<p class="text-danger">Невозможно переместить в тот же самый элемент</p>',
                                    });
                                    return false;
                                case 'error-alreadyexist':
                                    $.alert({
                                        type: 'red',
                                        title: 'Результат перемещения',
                                        content: '<p class="text-danger">Этот элемент уже подменяется</p>',
                                    });
                                    return false;
                            }
                            $.confirm({
                                title: 'Результат перемещения',
                                content: 'Подмена успешно перемещена!',
                                buttons: {
                                    Ok: function () {
                                        mark_move_id = false;
                                        $('#bckgr_unlock').click();
                                        $('#btn_pjax_geo_mark').click();
                                    }
                                }
                            });
                        });
                        $(event.target).mouseout();
                    },
                    Отмена: function () {
                        $(event.target).mouseout();
                    }
                }
            });
        }
    });
});

// получение селектора элемента
function requestSelector(event) {
    var result = {};
    result.url = $('#geo_mark_select').val();
    var evtar = event.target;
    if (evtar.nodeName == 'IMG') result.type = 'img';
    else if (evtar.textContent) result.type = 'txt';
    else result.type = 'undefined';
    // получение пути к DOM-элементу
    var stack = [];
    while ( evtar.parentNode != null ) {
        var sibCount = 0;
        var sibIndex = 0;
        for ( var i = 0; i < evtar.parentNode.childNodes.length; i++ ) {
            var sib = evtar.parentNode.childNodes[i];
            if ( sib.nodeName == evtar.nodeName ) {
                if ( sib === evtar ) {
                    sibIndex = sibCount;
                }
                sibCount++;
            }
        }
        if ( evtar.hasAttribute('id') && evtar.id != '' ) {
            stack.unshift(evtar.nodeName.toLowerCase() + '#' + evtar.id);
        } else if ( sibCount > 1 ) {
            sibIndex++;
            stack.unshift(evtar.nodeName.toLowerCase() + ':nth-of-type(' + sibIndex + ')');
        } else {
            stack.unshift(evtar.nodeName.toLowerCase());
        }
        evtar = evtar.parentNode;
    }
    // если в последнем дочернем элементе есть id, оставляем только этот id элемента
    var lastElem = stack[stack.length - 1];
    var lastElemPosId = lastElem.indexOf('#');
    if ( lastElemPosId != -1 ) {
        result.path = lastElem.substr(lastElemPosId);
    } else {
        result.path = stack.slice(1).join(' > ');
    }

    if (result.type === 'img') {
        result.width = event.target.naturalWidth;
        result.height = event.target.naturalHeight;
    }
    return result;
}

// переименование подменяемых элементов
$('#pjax_geo_mark').on('change', '.geo_mark_name', function() {
    var id = $(this).data('id');
    var name = $(this).val();
    $.ajax({
        url: '/geo-mark/rename.html',
        data: {
            id: id,
            name: name
        },
        success: function(res) {
            console.log(res);
        },
        error: function() {
            alert('Ошибка переименования');
        }
    });
});

// удаление выбранной подмены
$('#pjax_geo_mark').on('click', '.geo_mark_del', function() {
    var id = $(this).data('id');

    $.confirm({
        title: 'Подтверждение удаления',
        content: 'Вы действительно хотите удалить эту подмену?',
        buttons: {
            Да: {
                btnClass: 'btn-green',
                action: function () {
                    app.mainAjax("/geo-mark/del-mark.html", {
                        id: id
                    }).then(function(res) {
                        console.log(res);
                        $('#btn_pjax_geo_mark').click();
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

// перемещение выбранной подмены
$('#pjax_geo_mark').on('click', '.geo_mark_move', function() {
    var id = $(this).data('id');
    mark_move_id = id;
    var marks = document.getElementsByClassName('geo_mark_move');
    for (var i = 0; i < marks.length; i++) {
        var current_id = $(marks[i]).data('id');
        if ( current_id === id ) $(document.getElementsByClassName('geo_mark_watch')[i]).click();
    }
    $('#bckgr_lock, #bckgr_lock_message').css('display', 'block');
    $('#insert_frame').css('z-index', '10001');
});

// кнопка отмены перемещения
$('#bckgr_unlock').click(function() {
    mark_move_id = false;
    $('#bckgr_lock, #bckgr_lock_message').css('display', 'none');
    $('#insert_frame').css('z-index', '1');
});

// просмотр подмены
$('#pjax_geo_mark').on('click', '.geo_mark_watch', function() {
    try {
        var path = $(this).data('selector_path');
        var to_f_elem = window.frames[0].document.querySelector(path);
        if (!to_f_elem) throw new SyntaxError("Не получен параметр selector.path");
        var f_body = window.frames[0].window.document.body;
        $('html, body').animate({scrollTop: $('#insert_frame').offset().top - 40}, 500);
        $(f_body).animate({scrollTop: $(to_f_elem).offset().top - 90}, 500);
        $(to_f_elem).css('border', '3px outset green');
    } catch (e) {
        console.log (e.name + ' - ' + e.message);
        $.confirm({
            animation: 'scaleY',
            type: 'red',
            title: 'Подмена не найдена',
            content: 'Возможно подменяемый элемент был удален с вашего сайта <br>' +
            'Пожалуйста, переместите эту подмену на новое место',
            buttons: {
                Ok: function() {}
            }
        });
    }
});