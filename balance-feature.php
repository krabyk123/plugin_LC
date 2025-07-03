<?php
/*
 * File: balance-feature.php
 * Description: Регистрирует два шорткода:
 *   1) [student_balance] — выводит "Баланс: N уроков" для текущего ученика (роль um_custom_role_1)
 *   2) [teacher_balance] — выводит таблицу балансов всех учеников с колонками "Доступно" и "Пройдено"
 */

if ( ! defined('ABSPATH') ) exit;

add_action('wp_enqueue_scripts', function(){
    if ( ! is_singular() ) return; 
    global $post;
    if ( ! has_shortcode( $post->post_content, 'teacher_balance' ) ) return;
    wp_enqueue_script(
      'lc-balance',
      plugins_url('assets/js/balance.js', __FILE__),
      ['jquery'],
      '1.5',
      true
    );
    wp_enqueue_style(
      'lc-balance',
      plugins_url('assets/css/balance.css', __FILE__),
      [],
      '1.5'
    );
});

// Регистрируем шорткоды
add_action('init', function(){
    add_shortcode('student_balance', 'render_student_balance');
    add_shortcode('teacher_balance', 'render_teacher_balance');
});

// Получить баланс ученика из user_meta
function get_student_balance($user_id) {
    $available = (int) get_user_meta($user_id, 'available_lessons', true);
    $completed = (int) get_user_meta($user_id, 'completed_lessons', true);
    return array('available' => $available, 'completed' => $completed);
}

// Шорткод [student_balance]
function render_student_balance() {
    if ( ! is_user_logged_in() ) return '';
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_1', (array) $user->roles, true) ) return '';
    $balance = get_student_balance($user->ID);
    return '<div class="student-balance">Баланс: ' . esc_html($balance['available']) . ' уроков</div>';
}

// Шорткод [teacher_balance]
function render_teacher_balance() {
    if ( ! is_user_logged_in() ) return '';
    $user = wp_get_current_user();
    if ( ! in_array('um_custom_role_2', (array) $user->roles, true) ) return '';

    // Получаем всех пользователей-учеников
    $students = get_users(array(
        'role__in' => array('um_custom_role_1'),
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ));

    ob_start();
    ?>
    <div id="teacher-balance">
      <div class="balance-header">
        <h2>Баланс</h2>
        <div class="balance-actions">
          <button id="balance-add-btn">Добавить</button>
          <button id="balance-remove-btn">Удалить</button>
        </div>
      </div>
      <table class="balance-table">
        <thead>
          <tr>
            <th>Ученики</th>
            <th>Доступно</th>
            <th>Пройдено</th>
            <th class="action-col"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($students as $stu):
            $bal = get_student_balance($stu->ID);
        ?>
          <tr data-user-id="<?php echo esc_attr($stu->ID); ?>">
            <td><?php echo esc_html($stu->display_name); ?></td>
            <td class="available"><?php echo esc_html($bal['available']); ?></td>
            <td class="completed"><?php echo esc_html($bal['completed']); ?></td>
            <td class="action-cell"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX-обработчик добавления/удаления доступных уроков
add_action('wp_ajax_balance_update', 'ajax_balance_update');
function ajax_balance_update() {
    if ( ! check_ajax_referer('lc_nonce', '_ajax_nonce', false) ) {
        wp_send_json_error('Неверный nonce');
    }
    $user_id = intval($_POST['user_id']);
    $delta   = intval($_POST['delta']);
    $type    = sanitize_text_field($_POST['type']); // 'add' или 'remove'

    // Текущее значение
    $current = (int) get_user_meta($user_id, 'available_lessons', true);
    if ( $type === 'remove' ) {
        $new = max(0, $current - $delta);
    } else {
        $new = $current + $delta;
    }
    update_user_meta($user_id, 'available_lessons', $new);
    wp_send_json_success(array('available' => $new));
}