<?php
/*
 * Уведомления для учеников и учителей.
 * Версия: 1.5 (Telegram + e-mail, логирование отправки в Telegram)
 */

if (!defined('ABSPATH')) exit;

// === Стили для уведомлений ===
add_action('wp_head', function(){
    ?>
    <style>
    .lc-notifications-list {
        max-width: 600px;
        margin: 32px auto;
        padding: 0;
        list-style: none;
    }
    .lc-notification {
        background: #fff;
        border: 1px solid #e2e6ed;
        border-radius: 12px;
        box-shadow: 0 1px 8px rgba(40, 60, 100, 0.04);
        margin-bottom: 18px;
        padding: 17px 22px 14px 22px;
        font-size: 1.07em;
        color: #24292f;
        transition: box-shadow .16s;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .lc-notification:last-child {
        margin-bottom: 0;
    }
    .lc-notif-time {
        color: #99a2b3;
        font-size: 0.97em;
        min-width: 110px;
        margin-right: 12px;
        flex-shrink: 0;
        text-align: right;
        font-family: monospace, monospace;
        line-height: 1.6;
    }
    .lc-notif-msg {
        flex: 1 1 auto;
        line-height: 1.5;
    }
    </style>
    <?php
});

// === Хелпер: отправка в Telegram ===
function lc_send_telegram($user_id, $message) {
    // Логируем попытку отправки (удалите после отладки!)
    file_put_contents(__DIR__.'/tg_debug.log', date('Y-m-d H:i:s')." Попытка для $user_id: $message\n", FILE_APPEND);

    $chat_id = get_user_meta($user_id, 'lc_telegram_id', true);
    $token = '8041510676:AAFq3wKCjT-jX-KCCT3yql38bhL0IpzVcx4'; // <-- свой токен!
    if (!$chat_id) {
        file_put_contents(__DIR__.'/tg_debug.log', date('Y-m-d H:i:s')." Нет chat_id для $user_id\n", FILE_APPEND);
        return;
    }
    $text = strip_tags($message);
    $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=".urlencode($text);
    $result = wp_remote_get($url);
    file_put_contents(__DIR__.'/tg_debug.log', date('Y-m-d H:i:s')." Telegram result: ".print_r($result,1)."\n", FILE_APPEND);
}

// === Хелпер: добавить уведомление и отправить ===
function lc_add_notification($user_id, $message) {
    // Добавляем уведомление в профиль
    $notifs = get_user_meta($user_id, 'lc_notifications', true);
    if (!is_array($notifs)) $notifs = [];
    $notifs[] = [
        'msg'  => $message,
        'date' => current_time('d.m.Y H:i')
    ];
    update_user_meta($user_id, 'lc_notifications', $notifs);

    // --- Отправить в Telegram ---
    lc_send_telegram($user_id, $message);

    // --- Отправить на e-mail ---
    $user = get_userdata($user_id);
    if ($user && !empty($user->user_email)) {
        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = 'Новое уведомление | ' . $site;
        $body = '<div style="font-family:sans-serif;font-size:1.05em;">'
              . esc_html($message)
              . '<br><br>'
              . '<small>Это автоматическое письмо с сайта ' . esc_html($site) . ' — не отвечайте на него.</small>'
              . '</div>';
        add_filter('wp_mail_content_type', function() { return 'text/html'; });
        wp_mail($user->user_email, $subject, $body);
        // Сбросить фильтр, чтобы не ломать другие письма сайта
        remove_filter('wp_mail_content_type', 'set_html_content_type');
    }
}

// === Универсальный рендер для уведомлений ===
function lc_render_notifications_box($notifs) {
    if (!is_array($notifs) || empty($notifs)) return '<p>Нет уведомлений.</p>';
    ob_start();
    echo '<ul class="lc-notifications-list">';
    foreach($notifs as $n) {
        echo '<li class="lc-notification">';
        echo    '<div class="lc-notif-time">'.esc_html($n['date']).'</div>';
        echo    '<div class="lc-notif-msg">'.esc_html($n['msg']).'</div>';
        echo '</li>';
    }
    echo '</ul>';
    return ob_get_clean();
}

// === Шорткод для учеников ===
add_shortcode('student_notifications', function(){
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    if (!in_array('um_custom_role_1', (array)$user->roles, true)) return '';
    $notifs = get_user_meta($user->ID, 'lc_notifications', true);
    return lc_render_notifications_box($notifs);
});

// === Шорткод для учителей ===
add_shortcode('teacher_notifications', function(){
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    if (!in_array('um_custom_role_2', (array)$user->roles, true)) return '';
    $notifs = get_user_meta($user->ID, 'lc_notifications', true);
    return lc_render_notifications_box($notifs);
});