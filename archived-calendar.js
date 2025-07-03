jQuery(function($){
  $('#archived-calendar').fullCalendar({
    locale     : 'ru',
    defaultView: 'month',
    header : { left:'prev,next', center:'title', right:'' },
    buttonText : { month:'Месяц', agendaWeek:'Неделя' },
    events     : function(start,end,timezone,callback){
      $.post(LC_AJAX.url, {
        action      : 'lc_get_archived',
        _ajax_nonce : LC_AJAX.nonce
      }, callback, 'json');
    },
    eventRender: function(event, element){
      element.find('.fc-content').empty();
      var t1 = moment(event.start).format('HH:mm'),
          t2 = moment(event.end).format('HH:mm'),
          card = $('<div class="event-card">')
                   .css('background', event.color)
                   .append('<div class="time">'+t1+'–'+t2+'</div>')
                   .append('<div class="title">'+event.title+'</div>');
      element.append(card);
    },
    eventClick: function(ev){
      $('#archived-title-arch').text(ev.start.format('D MMMM YYYY')+' — '+ev.title);
      $('#archived-description-arch').html(ev.description);
      [1,2,3].forEach(function(i){
        var a   = $('#archived-link-'+i+'-arch'),
            url = ev.materials['link'+i] || '';
        if(url){
          a.show().attr('href',url).text(['Презентация','Видео','Чек-лист'][i-1]);
        } else {
          a.hide();
        }
      });
      $('#lc-archived-modal-arch').addClass('open');
    }
  });

  // Закрыть попап
  $(document).on('click', '#lc-archived-modal-arch .lc-close-arch', function(){
    $('#lc-archived-modal-arch').removeClass('open');
  });
});