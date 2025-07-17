<?php
/**
 * Plugin Name: Warranty Register V.3
 * Description: Complete warranty registration system with QR codes and admin dashboard.
 * Version: 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants for path and URL.
define( 'WR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files.
require_once WR_PLUGIN_DIR . 'includes/class-warranty-activation.php';
require_once WR_PLUGIN_DIR . 'includes/class-warranty-form.php';
require_once WR_PLUGIN_DIR . 'includes/class-warranty-admin.php';
require_once WR_PLUGIN_DIR . 'includes/class-warranty-register.php';

// Instantiate the main plugin class (initializes forms/admin).
$warranty_register = new WarrantyRegister();

// Hook for plugin activation and deactivation.
register_activation_hook(__FILE__, ['Warranty_Activation', 'activate']);
register_deactivation_hook(__FILE__, ['Warranty_Activation', 'deactivate']);
