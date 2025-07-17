<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WarrantyRegister {
    public function __construct() {
        // Initialize core plugin components
        new Warranty_Form();   // Handles the frontend form
        new Warranty_Admin();  // Handles admin dashboard
    }
}
