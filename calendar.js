jQuery(function($){
  // Русская локаль для moment.js
  moment.locale('ru');

  function initFC(sel, action, onClick){
    $(sel).fullCalendar({
      locale      : 'ru',
      defaultView: 'month',       // сразу открываем вид «месяц»
    header: {
      left:   'prev,next',       // обе стрелки слева
      center: 'title',           // название месяца по центру
      right:  ''                 // справа ничего
    },
      buttonText: {
        prev: '<',
        next: '>',
        month: 'Месяц',
        agendaWeek: 'Неделя',
        agendaDay: 'День'
      },
      navLinks    : false,
      editable    : false,
      eventLimit: 1,
      eventLimitText: function(n) {
        return n + ' уроков';
      },
      height             : 'auto',
      contentHeight      : 'auto',
      handleWindowResize : true,
      events: function(start, end, timezone, callback){
        $.post(LC_AJAX.url, {
          action      : action,
          _ajax_nonce : LC_AJAX.nonce
        }, callback, 'json');
      },
      eventRender: function(event, element){
        var t1 = moment(event.start).format('HH:mm'),
            t2 = moment(event.end  ).format('HH:mm'),
            card = $('<div class="event-card">')
                     .css('background', event.color)
                     .append('<div class="time">'  + t1 + '–' + t2 + '</div>')
                     .append('<div class="title">' + event.title + '</div>');
        element.find('.fc-content').empty().append(card);
      },
      eventClick: onClick
    });
  }

  // — STUDENT —
  initFC('#student-calendar','lc_get_student', function(ev){
    $('#lc-title').text(ev.start.format('D MMMM YYYY') + ' — ' + ev.title);
    $('#lc-description').html(ev.description);

    [1,2,3].forEach(function(i){
      var a   = $('#lc-link-' + i),
          url = ev.materials['link' + i] || '';
      if(url){
        a.show().attr('href', url)
         .text(['Презентация','Видео','Чек-лист'][i-1]);
      } else {
        a.hide();
      }
    });

    // Управляем кнопками записи и отмены
    if (ev.ended) {
      $('#lc-signup').hide();
      $('#lc-cancel-signup').hide();
    } else if (ev.registered) {
      $('#lc-signup').hide();
      $('#lc-cancel-signup').show();
    } else {
      $('#lc-signup').show();
      $('#lc-cancel-signup').hide();
    }

    $('#lc-signup').off('click').on('click', function(){
      $.post(LC_AJAX.url, {
        action      : 'lc_signup',
        lesson      : ev.id,
        _ajax_nonce : LC_AJAX.nonce
      }, function(){
        $('#student-calendar').fullCalendar('refetchEvents');
        $('#lc-modal').removeClass('open');
      });
    });

    $('#lc-cancel-signup').off('click').on('click', function(){
      $.post(LC_AJAX.url, {
        action      : 'lc_cancel_signup',
        lesson      : ev.id,
        _ajax_nonce : LC_AJAX.nonce
      }, function(){
        $('#student-calendar').fullCalendar('refetchEvents');
        $('#lc-modal').removeClass('open');
      });
    });

    $(document).trigger('lessonModalOpened', [ ev ]);
    $('#lc-modal').addClass('open');
  });

  // — TEACHER —
  initFC('#teacher-calendar','lc_get_teacher', function(ev){
    $('#lc-t-title').text(ev.start.format('D MMMM YYYY') + ' — ' + ev.title);
    $('#lc-t-description').html(ev.description);

    $.post(LC_AJAX.url, {
      action      : 'lc_get_students',
      lesson      : ev.id,
      _ajax_nonce : LC_AJAX.nonce
    }, function(res){
      if (!res.success) {
        return alert('Ошибка получения списка студентов');
      }
      var list = res.data,
          ul   = $('#lc-students').empty();
      if (list.length === 0) {
        ul.append('<li>— пока нет записавшихся —</li>');
      } else {
        list.forEach(function(name){
          ul.append('<li>' + name + '</li>');
        });
      }
    }, 'json');

    // Показывать/скрывать "Отменить" если урок не завершён
    if (ev.ended) {
      $('#lc-cancel').hide();
    } else {
      $('#lc-cancel').show();
      $('#lc-cancel').off('click').on('click', function(){
        $.post(LC_AJAX.url, {
          action      : 'lc_cancel',
          lesson      : ev.id,
          _ajax_nonce : LC_AJAX.nonce
        }, function(){
          $('#teacher-calendar').fullCalendar('refetchEvents');
          $('#lc-modal-teacher').removeClass('open');
        });
      });
    }

    // кнопка редактирования
    $('#lc-cancel').data('lesson', ev.id);
    $('#lc-edit-redirect')
      .data('lesson', ev.id)
      .off('click')
      .on('click', function(){
        var id  = $(this).data('lesson'),
            url = LC_AJAX.edit_page + '?lesson_id=' + id;
        window.location.href = url;
      });

    $('#lc-modal-teacher').addClass('open');
  });

  // — Создать новый урок —
  $('#lc-new-lesson').on('submit', function(e){
    e.preventDefault();
    var data = $(this).serialize() + '&action=lc_create&_ajax_nonce=' + LC_AJAX.nonce;
    $.post(LC_AJAX.url, data, function(){
      $('#teacher-calendar').fullCalendar('refetchEvents');
      $('#lc-new-lesson')[0].reset();
      alert('Урок создан');
    });
  });

  // — Закрыть попапы —
  $('.lc-close').on('click', function(){
    $(this).closest('#lc-modal, #lc-modal-teacher').removeClass('open');
  });

  // Автоматическое обновление календарей
  setInterval(function(){
    $('#student-calendar').fullCalendar('refetchEvents');
    $('#teacher-calendar').fullCalendar('refetchEvents');
  }, 10000);
});