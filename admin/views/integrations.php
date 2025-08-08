<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('یکپارچگی‌ها', 'aria-chatbot'); ?></h1>

    <table class="widefat striped">
        <thead>
        <tr>
            <th><?php _e('نام', 'aria-chatbot'); ?></th>
            <th><?php _e('وضعیت', 'aria-chatbot'); ?></th>
            <th><?php _e('توضیحات', 'aria-chatbot'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($available_integrations as $slug => $info) : ?>
            <tr>
                <td><?php echo esc_html($info['name']); ?></td>
                <td><?php echo $info['active'] ? __('فعال', 'aria-chatbot') : __('غیرفعال', 'aria-chatbot'); ?></td>
                <td><?php echo esc_html($info['description']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
