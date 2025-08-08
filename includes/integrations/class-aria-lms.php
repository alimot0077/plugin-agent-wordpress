<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_LMS_Integration {
    /**
     * لیست دوره‌های موجود در سیستم آموزش
     */
    public function list_courses() {
        if (!post_type_exists('sfwd-courses')) {
            return array();
        }

        $posts = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));

        $courses = array();
        foreach ($posts as $post_id) {
            $courses[] = array(
                'id' => $post_id,
                'title' => get_the_title($post_id)
            );
        }

        return $courses;
    }
}
