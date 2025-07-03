<?php
// editor-inline-feature.php
if ( ! defined('ABSPATH') ) exit;

/**
 * Шорткод [teacher_edit_lesson] — форма редактирования урока
 */
add_shortcode('teacher_edit_lesson', function(){
    if ( ! is_user_logged_in() ) {
        return '<p>Только для авторизованных.</p>';
    }
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2', (array)$user->roles, true) ) {
        return '<p>Только для учителей.</p>';
    }

  // вставляем вот эту строку:
    $now = current_time('mysql');

    // Получим список предстоящих уроков этого учителя
    $lessons = get_posts([
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'meta_query'     => [
            ['key'=>'teacher_id','value'=>$user->ID,'compare'=>'='],
            ['key'=>'cancelled','value'=>'0','compare'=>'='],
            ['key'=>'start','value'=>$now,'compare'=>'>='],
        ],
        'orderby'        => 'meta_value',
        'meta_key'       => 'start',
        'order'          => 'ASC',
    ]);

    // HTML формы
    ob_start(); ?>
    <form id="lc-update-lesson-form">
      <p>
        <label>Выберите урок:<br>
          <select id="lc-lesson-select" name="lesson_id" required>
            <option value="">— выберите —</option>
            <?php foreach($lessons as $L):
              $date = date_i18n('d.m.Y H:i', strtotime(get_post_meta($L->ID,'start',true)));
              $title = get_post_meta($L->ID,'lesson_title',true);
            ?>
              <option value="<?php echo $L->ID; ?>">
                <?php echo esc_html( trim($title).' — '.$date ); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </p>

      <p><strong>Заголовок урока:</strong><br>
        <?php wp_editor('', 'edit_lesson_title_editor', [
          'textarea_name' => 'lesson_title',
          'textarea_rows' => 2,
          'media_buttons' => false,
          'teeny'         => true,
        ]); ?>
      </p>

      <p><strong>Описание урока:</strong><br>
        <?php wp_editor('', 'edit_lesson_description_editor', [
          'textarea_name' => 'lesson_description',
          'textarea_rows' => 6,
          'media_buttons' => true,
          'teeny'         => false,
        ]); ?>
      </p>

      <p>
        <label>Дата начала<br>
          <input type="datetime-local" id="edit_start" name="start" required>
        </label>
      </p>

      <p>
        <label>Дата окончания<br>
          <input type="datetime-local" id="edit_end" name="end" required>
        </label>
      </p>

      <p><strong>Ссылки на материалы:</strong></p>
      <p><input type="url" id="edit_link1" name="mat[link1]" placeholder="Презентация"></p>
      <p><input type="url" id="edit_link2" name="mat[link2]" placeholder="Видео"></p>
      <p><input type="url" id="edit_link3" name="mat[link3]" placeholder="Чек-лист"></p>

      <p><button type="submit">Обновить урок</button></p>
    </form>
    <?php
    return ob_get_clean();
});


/**
 * Подключаем скрипт и стили для редактора
 */
add_action('wp_enqueue_scripts', function(){
    if ( ! is_user_logged_in() || ! current_user_can('edit_posts') ) return;

    wp_enqueue_script(
      'lc-lesson-editor',
      plugins_url('assets/js/lesson-editor.js', __FILE__),
      ['jquery','wp-editor'],
      '1.0',
      true
    );
    wp_localize_script('lc-lesson-editor','LC_Editor',[
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('lc_editor'),
    ]);
});

/**
 * AJAX: получить детали урока
 */
add_action('wp_ajax_lc_get_lesson_details','lc_get_lesson_details');
function lc_get_lesson_details(){
    check_ajax_referer('lc_editor','_ajax_nonce');
    $id = intval($_POST['lesson_id']);
    if(!$id) wp_send_json_error('Не указан ID урока.');

    $data = [
      'lesson_title'       => get_post_meta($id,'lesson_title',true),
      'lesson_description' => get_post_meta($id,'lesson_description',true),
      'start'              => get_post_meta($id,'start',true),
      'end'                => get_post_meta($id,'end',true),
      'materials'          => get_post_meta($id,'materials',true),
    ];
    wp_send_json_success($data);
}

/**
 * AJAX: обновить урок
 */
add_action('wp_ajax_lc_update_lesson','lc_update_lesson');
function lc_inline_update_lesson(){
    check_ajax_referer('lc_editor','_ajax_nonce');
    $id   = intval($_POST['lesson_id']);
    if(!$id) wp_send_json_error('Нет ID урока.');

    // Сохраняем метаполя
    update_post_meta($id,'lesson_title',       wp_kses_post($_POST['lesson_title']));
    update_post_meta($id,'lesson_description', wp_kses_post($_POST['lesson_description']));
    update_post_meta($id,'start',              sanitize_text_field($_POST['start']));
    update_post_meta($id,'end',                sanitize_text_field($_POST['end']));
    update_post_meta($id,'materials', [
      'link1'=> esc_url_raw($_POST['mat']['link1']),
      'link2'=> esc_url_raw($_POST['mat']['link2']),
      'link3'=> esc_url_raw($_POST['mat']['link3']),
    ]);

    wp_send_json_success([
      'lesson_title' => get_post_meta($id,'lesson_title',true),
      'start'        => get_post_meta($id,'start',true),
    ]);
}
