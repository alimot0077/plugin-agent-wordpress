<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('اطلاعات سیستم', 'aria-chatbot'); ?></h1>

    <table class="widefat striped">
        <tbody>
        <?php foreach ($system_info as $key => $value) : ?>
            <tr>
                <th><?php echo esc_html($key); ?></th>
                <td>
                    <?php
                    if (is_array($value)) {
                        echo esc_html(implode(', ', $value));
                    } else {
                        echo esc_html($value);
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
