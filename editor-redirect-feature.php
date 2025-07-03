<?php
/**
 * Front-end: форма редактирования урока
 * Шорткод: [edit_lesson_form]
 */
if ( ! defined('ABSPATH') ) exit;

// Обрабатываем POST перед выводом формы
add_action('init', function(){
    if ( isset($_POST['edit_lesson_nonce']) ) {
        if ( ! wp_verify_nonce($_POST['edit_lesson_nonce'],'edit_lesson') )
            wp_die('Неправильный nonce');
        $id = intval($_POST['lesson_id']);
        // Обновляем
        update_post_meta($id,'lesson_title',       wp_kses_post($_POST['title']));
        update_post_meta($id,'lesson_description', wp_kses_post($_POST['description']));
        update_post_meta($id,'start',              sanitize_text_field($_POST['start']));
        update_post_meta($id,'end',                sanitize_text_field($_POST['end']));
        update_post_meta($id,'materials', [
          'link1'=> esc_url_raw($_POST['mat']['link1']),
          'link2'=> esc_url_raw($_POST['mat']['link2']),
          'link3'=> esc_url_raw($_POST['mat']['link3']),
        ]);
        // редирект обратно
        $back = !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : site_url();
        wp_safe_redirect( $back );
        exit;
    }
});

// Регистрируем шорткод
add_shortcode('edit_lesson_form','render_edit_lesson_form');
function render_edit_lesson_form(){
    if ( ! is_user_logged_in() ) return 'Только для авторизованных.';
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2',(array)$user->roles,true) )
        return 'Только для учителей.';

    $lesson_id = intval($_GET['lesson_id'] ?? 0);
    if ( ! $lesson_id ) return 'Не указан урок.';

    // Предзаполняем данные
    $title       = get_post_meta($lesson_id,'lesson_title',true);
    $description = get_post_meta($lesson_id,'lesson_description',true);
    $start       = get_post_meta($lesson_id,'start',true);
    $end         = get_post_meta($lesson_id,'end',true);
    $mat         = get_post_meta($lesson_id,'materials',true);

    ob_start(); ?>
    <form method="post" id="edit-lesson-form">
      <?php wp_nonce_field('edit_lesson','edit_lesson_nonce'); ?>
      <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
      <input type="hidden" name="redirect_to" value="<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>">

      <p><strong>Заголовок урока</strong><br>
        <?php wp_editor($title,'edit_title_editor',[
          'textarea_name'=>'title',
          'teeny'=>true,
          'media_buttons'=>false,
          'textarea_rows'=>2
        ]); ?>
      </p>

      <p><strong>Описание урока</strong><br>
        <?php wp_editor($description,'edit_desc_editor',[
          'textarea_name'=>'description',
          'teeny'=>false,
          'media_buttons'=>true,
          'textarea_rows'=>6
        ]); ?>
      </p>

      <p><label>Дата начала<br>
        <input type="datetime-local" name="start" value="<?php echo esc_attr(str_replace(' ','T',$start)); ?>" required>
      </label></p>

      <p><label>Дата окончания<br>
        <input type="datetime-local" name="end" value="<?php echo esc_attr(str_replace(' ','T',$end)); ?>" required>
      </label></p>

      <p><label>Презентация<br>
        <input type="url" name="mat[link1]" value="<?php echo esc_attr($mat['link1'] ?? ''); ?>">
      </label></p>

      <p><label>Видео<br>
        <input type="url" name="mat[link2]" value="<?php echo esc_attr($mat['link2'] ?? ''); ?>">
      </label></p>

      <p><label>Чек-лист<br>
        <input type="url" name="mat[link3]" value="<?php echo esc_attr($mat['link3'] ?? ''); ?>">
      </label></p>

      <p><button type="submit">Обновить урок</button></p>
    </form>
    <?php
    return ob_get_clean();
}
