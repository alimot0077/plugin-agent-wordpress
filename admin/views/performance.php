<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('اطلاعات عملکرد', 'aria-chatbot'); ?></h1>

    <table class="widefat striped">
        <tbody>
        <?php foreach ($performance_data as $key => $value) : ?>
            <tr>
                <th><?php echo esc_html($key); ?></th>
                <td><?php echo esc_html($value); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
