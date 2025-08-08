<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_WooCommerce_Integration {
    /**
     * جستجوی محصولات ووکامرس
     */
    public function search_products($query) {
        if (!class_exists('WC_Product_Query')) {
            return array();
        }

        $products = wc_get_products(array(
            'limit' => 5,
            'status' => 'publish',
            'search' => $query,
        ));

        $results = array();
        foreach ($products as $product) {
            $results[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'url' => get_permalink($product->get_id())
            );
        }

        return $results;
    }

    /**
     * وضعیت سفارش
     */
    public function get_order_status($order_id) {
        if (!class_exists('WC_Order')) {
            return 'unknown';
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return 'unknown';
        }

        return $order->get_status();
    }
}
