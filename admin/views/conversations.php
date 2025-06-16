<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('لیست مکالمات', 'aria-chatbot'); ?></h1>

    <?php if (!empty($conversations)) : ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php _e('شناسه', 'aria-chatbot'); ?></th>
                <th><?php _e('کاربر', 'aria-chatbot'); ?></th>
                <th><?php _e('پیام', 'aria-chatbot'); ?></th>
                <th><?php _e('پاسخ', 'aria-chatbot'); ?></th>
                <th><?php _e('زمان', 'aria-chatbot'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($conversations as $conv) : ?>
                <tr>
                    <td><?php echo intval($conv->id); ?></td>
                    <td><?php echo esc_html($conv->user_id); ?></td>
                    <td><?php echo esc_html(wp_trim_words($conv->message, 10)); ?></td>
                    <td><?php echo esc_html(wp_trim_words($conv->response, 10)); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conv->timestamp))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        echo paginate_links(array(
            'current' => $page,
            'total'   => $total_pages,
        ));
        ?>
    <?php else : ?>
        <p><?php _e('هیچ مکالمه‌ای یافت نشد.', 'aria-chatbot'); ?></p>
    <?php endif; ?>
</div>
