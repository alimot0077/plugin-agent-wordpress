<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('پایگاه دانش', 'aria-chatbot'); ?></h1>

    <?php if (!empty($knowledge_items)) : ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php _e('سؤال', 'aria-chatbot'); ?></th>
                <th><?php _e('پاسخ', 'aria-chatbot'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($knowledge_items as $item) : ?>
                <tr>
                    <td><?php echo esc_html($item->question); ?></td>
                    <td><?php echo esc_html(wp_trim_words($item->answer, 15)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('موردی ثبت نشده است.', 'aria-chatbot'); ?></p>
    <?php endif; ?>
</div>
