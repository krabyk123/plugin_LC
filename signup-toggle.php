<?php
/*
 * signup-toggle.php
 * Feature: toggle lesson signup/cancel for students
 */

if ( ! defined('ABSPATH') ) exit;

// AJAX: отменить запись
add_action('wp_ajax_lc_unsubscribe','lc_unsubscribe');
function lc_unsubscribe(){
    check_ajax_referer('lc_nonce');
    $lesson   = intval($_POST['lesson']);
    $user     = get_current_user_id();
    $students = (array)get_post_meta($lesson,'students',true);
    if( in_array($user,$students) ){
        $students = array_diff($students, [$user]);
        update_post_meta($lesson,'students', array_values($students));
    }
    wp_send_json_success();
}
