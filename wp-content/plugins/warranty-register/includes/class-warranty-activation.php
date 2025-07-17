<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Warranty_Activation {
    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'warranty_cards';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(150) NOT NULL,
            warranty_number varchar(10) NOT NULL UNIQUE,
            country varchar(100) NOT NULL,
            product_model varchar(100) NOT NULL,
            purchase_date date NOT NULL,
            file_url text NOT NULL,
            qr_code_url text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function deactivate() {
        // Optional: clean up
    }
}
