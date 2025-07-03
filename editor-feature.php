<?php
/*
 * editor-feature.php
 * Feature: front-end WYSIWYG для формы создания урока
 */

if ( ! defined('ABSPATH') ) exit;

// Переопределяем шорткод teacher_add_lesson
add_action('init', function(){
    remove_shortcode('teacher_add_lesson');
    add_shortcode('teacher_add_lesson','render_teacher_add_lesson_wysiwyg');
});

// Подключаем нужные скрипты редактора
add_action('wp_enqueue_scripts', function(){
    if ( function_exists('wp_enqueue_editor') ) {
        wp_enqueue_editor();
    }
    // --- Подключаем собственные стили попапа редактора урока ---
    wp_enqueue_style(
       'lc-editor-popup',
       plugins_url('assets/css/editor-popup.css', __FILE__),
       [],       // нет зависимостей
       '1.0'
    );
});

function render_teacher_add_lesson_wysiwyg(){
    if ( ! is_user_logged_in() ) return '';
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2', (array)$user->roles, true) ) return '';

    ob_start(); ?>
    <form id="lc-new-lesson">
      <p><strong>Заголовок урока</strong><br>
        <?php wp_editor('', 'new_lesson_title', [
            'textarea_name' => 'title',
            'textarea_rows' => 2,
            'media_buttons'=> false,
            'teeny'=> true
        ]); ?>
      </p>
      <p><strong>Описание урока</strong><br>
        <?php wp_editor('', 'new_lesson_description', [
            'textarea_name' => 'description',
            'textarea_rows' => 6,
            'media_buttons'=> true,
            'teeny'=> false
        ]); ?>
      </p>
      <p><label>Дата начала<br><input type="datetime-local" name="start" required></label></p>
      <p><label>Дата окончания<br><input type="datetime-local" name="end" required></label></p>
      <p><label>Презентация<br><input type="url" name="mat[link1]"></label></p>
      <p><label>Видео<br><input type="url" name="mat[link2]"></label></p>
      <p><label>Чек-лист<br><input type="url" name="mat[link3]"></label></p>
      <p><button type="submit">Добавить урок</button></p>
    </form>
    <?php
    return ob_get_clean();
}
