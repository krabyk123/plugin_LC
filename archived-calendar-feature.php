<?php
/*
 * Архивный календарь: показывает только отменённые или прошедшие уроки
 * Шорткод: [archived_calendar]
 */
if (!defined('ABSPATH')) exit;

add_action('init', function(){
    add_shortcode('archived_calendar','render_archived_calendar');
});

// === Обычный архивный календарь ===
function render_archived_calendar(){
    if (!is_user_logged_in()) return '';
    ob_start(); ?>
    <div id="archived-calendar"></div>
    <div id="lc-archived-modal-arch" class="archived-modal">
      <div class="lc-content">
        <button class="lc-close-arch">×</button>
        <h2 id="archived-title-arch"></h2>
        <div class="lc-left">
          <div id="archived-description-arch"></div>
        </div>
        <div class="lc-right">
          <a class="lc-btn" id="archived-link-1-arch" href="#" target="_blank"></a>
          <a class="lc-btn" id="archived-link-2-arch" href="#" target="_blank"></a>
          <a class="lc-btn" id="archived-link-3-arch" href="#" target="_blank"></a>
        </div>
      </div>
    </div>
    <script>
    jQuery(function($){
      $('#archived-calendar').fullCalendar({
        locale     : 'ru',
        header     : { left:'prev,next', center:'title', right:'month,agendaWeek' },
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
      // Закрыть попап для архивного календаря
      $(document).on('click', '#lc-archived-modal-arch .lc-close-arch', function(){
        $('#lc-archived-modal-arch').removeClass('open');
      });
    });
    </script>
    <?php
    return ob_get_clean();
}

// === Новый success календарь (только успешно завершённые) ===
add_action('init', function(){
    add_shortcode('success_calendar','render_success_calendar');
});

function render_success_calendar(){
    if (!is_user_logged_in()) return '';
    ob_start(); ?>
    <div id="success-calendar"></div>
    <div id="lc-archived-modal-success" class="archived-modal">
      <div class="lc-content">
        <button class="lc-close-success">×</button>
        <h2 id="archived-title-success"></h2>
        <div class="lc-left">
          <div id="archived-description-success"></div>
        </div>
        <div class="lc-right">
          <a class="lc-btn" id="archived-link-1-success" href="#" target="_blank"></a>
          <a class="lc-btn" id="archived-link-2-success" href="#" target="_blank"></a>
          <a class="lc-btn" id="archived-link-3-success" href="#" target="_blank"></a>
        </div>
      </div>
    </div>
    <script>
    jQuery(function($){
      $('#success-calendar').fullCalendar({
        locale     : 'ru',
        header     : { left:'prev,next', center:'title', right:'month,agendaWeek' },
        buttonText : { month:'Месяц', agendaWeek:'Неделя' },
        events     : function(start,end,timezone,callback){
          $.post(LC_AJAX.url, {
            action      : 'lc_get_success',
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
          $('#archived-title-success').text(ev.start.format('D MMMM YYYY')+' — '+ev.title);
          $('#archived-description-success').html(ev.description);
          [1,2,3].forEach(function(i){
            var a   = $('#archived-link-'+i+'-success'),
                url = ev.materials['link'+i] || '';
            if(url){
              a.show().attr('href',url).text(['Презентация','Видео','Чек-лист'][i-1]);
            } else {
              a.hide();
            }
          });
          $('#lc-archived-modal-success').addClass('open');
        }
      });
      // Закрыть попап для success календаря
      $(document).on('click', '#lc-archived-modal-success .lc-close-success', function(){
        $('#lc-archived-modal-success').removeClass('open');
      });
    });
    </script>
    <?php
    return ob_get_clean();
}

// === AJAX: получить архивные уроки (отменённые + все прошедшие) ===
add_action('wp_ajax_nopriv_lc_get_archived','lc_get_archived');
add_action('wp_ajax_lc_get_archived','lc_get_archived');
function lc_get_archived(){
    check_ajax_referer('lc_nonce');
    $now_ts = current_time('timestamp');
    $lessons = get_posts([
        'post_type'   => 'lesson',
        'numberposts' => -1,
    ]);
    $out = [];
    foreach($lessons as $L){
        $cancelled = get_post_meta($L->ID,'cancelled',true);
        $end = get_post_meta($L->ID,'end',true);
        $end_ts = strtotime($end);
        // В архив идут либо отменённые, либо уже завершённые
        if ( $cancelled === '1' || $cancelled === 1 || $cancelled === true || ($end_ts && $end_ts < $now_ts) ) {
            $out[] = [
                'id'          => $L->ID,
                'title'       => get_post_meta($L->ID,'lesson_title',true),
                'start'       => get_post_meta($L->ID,'start',true),
                'end'         => $end,
                'color'       => '#ccc',
                'description' => get_post_meta($L->ID,'lesson_description',true),
                'materials'   => get_post_meta($L->ID,'materials',true),
            ];
        }
    }
    wp_send_json($out);
}

// === AJAX: только успешно завершённые (НЕ отменённые, завершённые по дате) ===
add_action('wp_ajax_nopriv_lc_get_success','lc_get_success');
add_action('wp_ajax_lc_get_success','lc_get_success');
function lc_get_success(){
    check_ajax_referer('lc_nonce');
    $now_ts = current_time('timestamp');
    $lessons = get_posts([
        'post_type'   => 'lesson',
        'numberposts' => -1,
    ]);
    $out = [];
    foreach($lessons as $L){
        $cancelled = get_post_meta($L->ID,'cancelled',true);
        $end = get_post_meta($L->ID,'end',true);
        $end_ts = strtotime($end);

        // Явно отменён только если cancelled 1, '1', true (ВСЁ остальное — не отменён)
        $is_cancelled = ($cancelled === '1' || $cancelled === 1 || $cancelled === true);

        if ( !$is_cancelled && $end_ts && $end_ts < $now_ts ) {
            $out[] = [
                'id'          => $L->ID,
                'title'       => get_post_meta($L->ID,'lesson_title',true),
                'start'       => get_post_meta($L->ID,'start',true),
                'end'         => $end,
                'color'       => '#ccc',
                'description' => get_post_meta($L->ID,'lesson_description',true),
                'materials'   => get_post_meta($L->ID,'materials',true),
            ];
        }
    }
    wp_send_json($out);
}



