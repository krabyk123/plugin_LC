<?php
/*
Plugin Name: Lessons Calendar
Description: Shortcodes for student/teacher calendars and lesson management.
Version:     1.5
Author:      Ваше Имя
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// 1) CPT «lesson»
add_action('init', function(){
    register_post_type('lesson', [
        'label'    => 'Уроки',
        'public'   => false,
        'show_ui'  => true,
        'supports' => [],
    ]);
});

// 2) Все метаполя
add_action('init', function(){
    register_post_meta('lesson','lesson_title',       ['type'=>'string','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','lesson_description', ['type'=>'string','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','start',              ['type'=>'string','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','end',                ['type'=>'string','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','materials',          ['type'=>'object','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','teacher_id',         ['type'=>'integer','single'=>true,'show_in_rest'=>true]);
    register_post_meta('lesson','students',           ['type'=>'array','single'=>true,'show_in_rest'=>true,'default'=>[]]);
    register_post_meta('lesson','cancelled',          ['type'=>'boolean','single'=>true,'show_in_rest'=>true,'default'=>false]);
});

// 3) Метабокс для WYSIWYG-заголовка и описания
add_action('add_meta_boxes', function(){
    add_meta_box('lesson_meta','Параметры урока','render_lesson_meta','lesson','normal','high');
});
function render_lesson_meta($post){
    wp_nonce_field('lesson_meta_nonce','lesson_meta_nonce');
    $title = get_post_meta($post->ID,'lesson_title',true);
    $desc  = get_post_meta($post->ID,'lesson_description',true);
    echo '<p><strong>Заголовок урока:</strong></p>';
    wp_editor($title,'lesson_title',[
      'textarea_name'=>'lesson_title',
      'media_buttons'=>false,
      'textarea_rows'=>2
    ]);
    echo '<p><strong>Описание урока:</strong></p>';
    wp_editor($desc,'lesson_description',[
      'textarea_name'=>'lesson_description',
      'media_buttons'=>true,
      'textarea_rows'=>6
    ]);
}

// 4) Сохраняем WYSIWYG
add_action('save_post_lesson', function($post_id){
    if(
      !isset($_POST['lesson_meta_nonce']) ||
      !wp_verify_nonce($_POST['lesson_meta_nonce'],'lesson_meta_nonce') ||
      (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
      !current_user_can('edit_post',$post_id)
    ) return;
    if(isset($_POST['lesson_title']))
      update_post_meta($post_id,'lesson_title',wp_kses_post($_POST['lesson_title']));
    if(isset($_POST['lesson_description']))
      update_post_meta($post_id,'lesson_description',wp_kses_post($_POST['lesson_description']));
});

// 5) Подключаем FullCalendar + свои скрипты/стили
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_script('moment','https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js',['jquery'],null,true);
    wp_enqueue_script('fullcalendar','https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js',['jquery','moment'],null,true);
    wp_enqueue_style('fullcalendar','https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css');

    wp_enqueue_script('lc-calendar',plugins_url('assets/js/calendar.js',__FILE__),['jquery','fullcalendar'],'1.5',true);
    wp_enqueue_style(
      'lc-styles',
      plugins_url('assets/css/calendar.css', __FILE__),
      ['hello-elementor'],
      '1.5'
    );
    wp_localize_script('lc-calendar','LC_AJAX',[
      'url'   => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('lc_nonce'),
      'edit_page' => site_url('/edit-lesson/')
    ]);
}, 20);

// 6) Шорткод: календарь ученика
add_shortcode('student_calendar', function(){
    if ( ! is_user_logged_in() ) {
        return 'Только для авторизованных.';
    }
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_1', (array) $user->roles, true) ) {
        return 'Только для учеников.';
    }
    ob_start(); ?>
    <div id="student-calendar"></div>
    <div id="lc-modal">
      <div class="lc-content">
        <button class="lc-close">×</button>
        <h2 id="lc-title"></h2>
        <div class="lc-left">
          <div id="lc-description"></div>
        </div>
        <div class="lc-right">
          <a class="lc-btn" id="lc-link-1" href="#" target="_blank"></a>
          <a class="lc-btn" id="lc-link-2" href="#" target="_blank"></a>
          <a class="lc-btn" id="lc-link-3" href="#" target="_blank"></a>
        </div>
        <button id="lc-signup" style="display:none;">Записаться</button>
        <button id="lc-cancel-signup" style="display:none;">Отменить запись</button>
      </div>
    </div>
    <?php
    return ob_get_clean();
});

// 7) Шорткод: календарь учителя
add_shortcode('teacher_calendar', function(){
    if ( ! is_user_logged_in() ) {
        return 'Только для авторизованных.';
    }
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2', (array) $user->roles, true) ) {
        return 'Только для учителей.';
    }
    ob_start(); ?>
    <div id="teacher-calendar"></div>
    <div id="lc-modal-teacher">
      <div class="lc-content">
        <button class="lc-close">×</button>
        <h2 id="lc-t-title"></h2>
        <div class="lc-left">
          <div id="lc-t-description"></div>
        </div>
        <div class="lc-right">
          <button id="lc-cancel" style="display:none;">Отменить</button>
          <button id="lc-edit-redirect">Редактировать урок</button>
          <h3>Записавшиеся:</h3>
          <ul id="lc-students"></ul>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
});

// 8) Шорткод: форма создания урока (только для учителей um_custom_role_2)
add_shortcode('teacher_add_lesson', function(){
    if ( ! is_user_logged_in() ) {
        return '';
    }
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2', (array) $user->roles, true) ) {
        return '';
    }
    ob_start(); ?>
    <form id="lc-new-lesson">
      <p><label>Заголовок урока<br><textarea name="title" required></textarea></label></p>
      <p><label>Описание урока<br><textarea name="description"></textarea></label></p>
      <p><label>Дата начала<br><input type="datetime-local" name="start" required></label></p>
      <p><label>Дата окончания<br><input type="datetime-local" name="end" required></label></p>
      <p><label>Презентация<br><input type="url" name="mat[link1]"></label></p>
      <p><label>Видео<br><input type="url" name="mat[link2]"></label></p>
      <p><label>Чек-лист<br><input type="url" name="mat[link3]"></label></p>
      <p><button type="submit">Добавить урок</button></p>
    </form>
    <?php
    return ob_get_clean();
});

// === AJAX ===

// Получить уроки для студента
add_action('wp_ajax_nopriv_lc_get_student','lc_get_student');
add_action('wp_ajax_lc_get_student','lc_get_student');
function lc_get_student(){
    check_ajax_referer('lc_nonce');
    $user = get_current_user_id();
    $now_ts = current_time('timestamp');
    $lessons = get_posts([
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                ['key'=>'students','compare'=>'LIKE','value'=>$user],
                ['key'=>'start','compare'=>'>=','value'=>date('Y-m-d H:i:s', $now_ts)]
            ],
            ['key'=>'cancelled','value'=>false,'compare'=>'=']
        ],
    ]);
    $out = [];
    foreach($lessons as $L){
        $start      = get_post_meta($L->ID,'start',true);
        $end        = get_post_meta($L->ID,'end',true);

        $students_raw = get_post_meta($L->ID,'students',true);
        $students = [];
        if (is_array($students_raw)) {
            $students = array_filter($students_raw, function($v) { return !empty($v); });
        } elseif (!empty($students_raw)) {
            $students = [ $students_raw ];
        }
        if (count($students) > 0 && !in_array($user, $students)) {
            continue;
        }
        $registered = in_array($user,$students);
        $tend = strtotime($end);
        $color = ($tend < $now_ts)
            ? '#ccc'
            : ($registered ? '#257eff' : '#a0d3ff');
        $out[] = [
            'id'          => $L->ID,
            'title'       => get_post_meta($L->ID,'lesson_title',true),
            'start'       => $start,
            'end'         => $end,
            'color'       => $color,
            'description' => get_post_meta($L->ID,'lesson_description',true),
            'materials'   => get_post_meta($L->ID,'materials',true),
            'registered'  => $registered,
            'ended'       => ($tend < $now_ts)
        ];
    }
    wp_send_json($out);
}

// Получить уроки для учителя
add_action('wp_ajax_lc_get_teacher','lc_get_teacher');
function lc_get_teacher(){
    check_ajax_referer('lc_nonce');
    $t       = get_current_user_id();
    $now_ts  = current_time('timestamp');
    $lessons = get_posts([
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'meta_query'     => [
            ['key'=>'teacher_id', 'value'=>$t,    'compare'=>'='],
            ['key'=>'cancelled',  'value'=>false, 'compare'=>'='],
        ],
    ]);
    $out = [];
    foreach($lessons as $L){
        $start    = get_post_meta($L->ID,'start',true);
        $end      = get_post_meta($L->ID,'end',true);
        $tend     = strtotime($end);

        $students_raw = get_post_meta($L->ID,'students',true);
        $students = [];
        if (is_array($students_raw)) {
            $students = array_filter($students_raw, function($v) { return !empty($v); });
        } elseif (!empty($students_raw)) {
            $students = [ $students_raw ];
        }
        $count    = count($students);

        $color = ($tend < $now_ts)
            ? '#ccc'
            : ($count > 0 ? '#257eff' : '#a0d3ff');

        $out[] = [
            'id'          => $L->ID,
            'title'       => get_post_meta($L->ID,'lesson_title',true),
            'start'       => $start,
            'end'         => $end,
            'color'       => $color,
            'description' => get_post_meta($L->ID,'lesson_description',true),
            'materials'   => get_post_meta($L->ID,'materials',true),
            'ended'       => ($tend < $now_ts)
        ];
    }
    wp_send_json($out);
}

// Кто записался на урок
add_action('wp_ajax_lc_get_students','lc_get_students');
function lc_get_students(){
    check_ajax_referer('lc_nonce');
    $lesson       = intval($_POST['lesson']);
    $raw_students = get_post_meta($lesson,'students',true);
    $students = [];
    if (is_array($raw_students)) {
        $students = array_filter($raw_students, function($v) { return !empty($v); });
    } elseif (!empty($raw_students)) {
        $students = [ $raw_students ];
    }
    $out = [];
    foreach($students as $uid){
        if ( empty($uid) ) continue;
        $user = get_userdata(intval($uid));
        if ( ! $user ) continue;
        $out[] = $user->display_name;
    }
    wp_send_json_success($out);
}

// Записаться на урок
add_action('wp_ajax_lc_signup','lc_signup');
function lc_signup(){
    check_ajax_referer('lc_nonce');
    $lesson = intval($_POST['lesson']);
    $user   = get_current_user_id();
    $students = get_post_meta($lesson,'students',true);
    $students_clean = [];
    if (is_array($students)) {
        $students_clean = array_filter($students, function($v) { return !empty($v); });
    } elseif (!empty($students)) {
        $students_clean = [ $students ];
    }
    $already_registered = in_array($user,$students_clean);
    if(!$already_registered){
        $students_clean[] = $user;
        update_post_meta($lesson,'students',$students_clean);
        // Уведомления
        if (!function_exists('lc_add_notification')) require_once plugin_dir_path(__FILE__) . 'lc-notifications.php';
        $teacher_id = get_post_meta($lesson, 'teacher_id', true);
        $student = wp_get_current_user();
        $lesson_title = get_post_meta($lesson, 'lesson_title', true);
        lc_add_notification($user, "Вы записались на урок \"{$lesson_title}\" " . current_time('d.m.Y H:i'));
        if ($teacher_id) {
            lc_add_notification($teacher_id, "На урок \"{$lesson_title}\" (" . current_time('d.m.Y H:i') . ") записался ученик {$student->display_name}");
        }
    }
    wp_send_json_success();
}

// Отменить запись ученика на урок
add_action('wp_ajax_lc_cancel_signup','lc_cancel_signup');
function lc_cancel_signup(){
    check_ajax_referer('lc_nonce');
    $lesson = intval($_POST['lesson']);
    $user   = get_current_user_id();
    $students = get_post_meta($lesson, 'students', true);
    $students_clean = [];
    if (is_array($students)) {
        $students_clean = array_filter($students, function($v) { return !empty($v); });
    } elseif (!empty($students)) {
        $students_clean = [ $students ];
    }
    $new = array_diff($students_clean, [$user]);
    update_post_meta($lesson, 'students', $new);

    // Уведомления
    if (!function_exists('lc_add_notification')) require_once plugin_dir_path(__FILE__) . 'lc-notifications.php';
    $teacher_id = get_post_meta($lesson, 'teacher_id', true);
    $student = wp_get_current_user();
    $lesson_title = get_post_meta($lesson, 'lesson_title', true);
    lc_add_notification($user, "Вы отменили запись на урок \"{$lesson_title}\" " . current_time('d.m.Y H:i'));
    if ($teacher_id) {
        lc_add_notification($teacher_id, "Ученик {$student->display_name} отменил запись на урок \"{$lesson_title}\" (" . current_time('d.m.Y H:i') . ")");
    }

    wp_send_json_success();
}

// Создать урок
add_action('wp_ajax_lc_create','lc_create');
function lc_create(){
    check_ajax_referer('lc_nonce');
    $t = get_current_user_id();
    $id = wp_insert_post([
      'post_type'   => 'lesson',
      'post_status' => 'publish'
    ]);
    update_post_meta($id,'lesson_title',       wp_kses_post($_POST['title']));
    update_post_meta($id,'lesson_description', wp_kses_post($_POST['description']));
    update_post_meta($id,'start',              sanitize_text_field($_POST['start']));
    update_post_meta($id,'end',                sanitize_text_field($_POST['end']));
    update_post_meta($id,'materials', [
      'link1'=> esc_url_raw($_POST['mat']['link1']),
      'link2'=> esc_url_raw($_POST['mat']['link2']),
      'link3'=> esc_url_raw($_POST['mat']['link3']),
    ]);
    update_post_meta($id,'teacher_id',$t);
    update_post_meta($id,'cancelled',false);
    delete_post_meta($id,'students');
    wp_send_json_success();
}

// Отменить урок
add_action('wp_ajax_lc_cancel','lc_cancel');
function lc_cancel(){
    check_ajax_referer('lc_nonce');
    $lesson = intval($_POST['lesson']);
    update_post_meta($lesson,'cancelled',true);

    // Уведомления для всех студентов
    if (!function_exists('lc_add_notification')) require_once plugin_dir_path(__FILE__) . 'lc-notifications.php';
    $students = get_post_meta($lesson, 'students', true);
    $lesson_title = get_post_meta($lesson, 'lesson_title', true);
    if (is_array($students)) foreach ($students as $uid) {
        if (!empty($uid))
            lc_add_notification($uid, "Урок \"{$lesson_title}\" (" . current_time('d.m.Y H:i') . ") был отменён учителем");
    }
    wp_send_json_success();
}

// Пройденные уроки
add_shortcode('completed_lessons', function(){
    if( !is_user_logged_in() ) return '';
    $now_ts = current_time('timestamp');
    $lessons = get_posts([
        'post_type'=>'lesson',
        'numberposts'=>-1
    ]);
    ob_start();
    echo '<div class="lessons-grid">';
    foreach($lessons as $L){
        $end = get_post_meta($L->ID,'end',true);
        $cancelled = get_post_meta($L->ID,'cancelled',true);

        $end_ts = strtotime($end);
        if (
            $end_ts && 
            $end_ts < $now_ts && 
            ($cancelled === '0' || $cancelled === 0 || $cancelled === false || empty($cancelled))
        ) {
            $title = get_post_meta($L->ID,'lesson_title',true);
            $desc  = wp_strip_all_tags(get_post_meta($L->ID,'lesson_description',true));
            $desc  = mb_substr($desc,0,200);
            $teacher = get_userdata(get_post_meta($L->ID,'teacher_id',true))->display_name;
            echo '
              <div class="lesson-card">
                <h3>'. wp_kses_post($title) .'</h3>
                <p>'. esc_html($desc) .'…</p>
                <p><em>'.$teacher.'</em></p>
              </div>
            ';
        }
    }
    echo '</div>';
    return ob_get_clean();
});

// Отменённые уроки
add_shortcode('cancelled_lessons', function(){
    if( !is_user_logged_in() ) return '';
    $lessons = get_posts([
        'post_type'=>'lesson',
        'meta_query'=>[
            ['key'=>'cancelled','value'=>'1','compare'=>'=']
        ],
        'numberposts'=>-1
    ]);
    ob_start();
    echo '<div class="lessons-grid">';
    foreach($lessons as $L){
        $title = get_post_meta($L->ID,'lesson_title',true);
        $desc  = wp_strip_all_tags(get_post_meta($L->ID,'lesson_description',true));
        $desc  = mb_substr($desc,0,200);
        $teacher = get_userdata(get_post_meta($L->ID,'teacher_id',true))->display_name;
        echo '
          <div class="lesson-card cancelled">
            <h3>'. wp_kses_post($title) .'</h3>
            <p>'. esc_html($desc) .'…</p>
            <p><em>'.$teacher.'</em></p>
          </div>
        ';
    }
    echo '</div>';
    return ob_get_clean();
});

// Дополнительные модули (если есть)
require_once plugin_dir_path(__FILE__) . 'balance-feature.php';
require_once plugin_dir_path(__FILE__) . 'signup-toggle.php';
require_once plugin_dir_path(__FILE__) . 'editor-feature.php';
require_once plugin_dir_path(__FILE__) . 'archived-calendar-feature.php';
require_once plugin_dir_path(__FILE__).'editor-inline-feature.php';
require_once plugin_dir_path(__FILE__).'editor-redirect-feature.php';
require_once plugin_dir_path(__FILE__).'lc-notifications.php';
