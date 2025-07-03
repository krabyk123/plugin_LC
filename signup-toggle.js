jQuery(function($){
  // В calendar.js после открытия попапа нужно триггерить событие:
  // $(document).trigger('lessonModalOpened', [ev]);
  // Наш код слушает его и подменяет кнопку

  // Подмена кнопки при открытии попапа
  $(document).on('lessonModalOpened', function(e, ev){
    var $btn = $('#lc-signup');
    if ( ev.registered ) {
      $btn.text('Отменить запись').attr('id','lc-cancel-signup');
    } else {
      $btn.text('Записаться').attr('id','lc-signup');
    }
    $btn.data('lesson', ev.id);
  });

  // Делегируем клик и для «записаться», и для «отменить»
  $('#student-calendar, body').on('click', '#lc-signup, #lc-cancel-signup', function(){
    var $btn   = $(this),
        lesson = $btn.data('lesson'),
        action = $btn.is('#lc-signup') ? 'lc_signup' : 'lc_unsubscribe';

    $.post(LC_AJAX.url, {
      action      : action,
      lesson      : lesson,
      _ajax_nonce : LC_AJAX.nonce
    }, function(){
      var isNowRegistered = (action === 'lc_signup');
      // Переключаем текст и ID
      $btn.text(isNowRegistered ? 'Отменить запись' : 'Записаться')
          .attr('id', isNowRegistered ? 'lc-cancel-signup' : 'lc-signup');
      // Обновляем календарь
      $('#student-calendar').fullCalendar('refetchEvents');
    });
  });
});
