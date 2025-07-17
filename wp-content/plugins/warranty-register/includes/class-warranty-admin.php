<?php
if (! defined('ABSPATH')) {
  exit;
}

class Warranty_Admin
{

  public function __construct()
  {
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    add_action('admin_post_pwr_export_csv', [$this, 'export_to_csv']);
    add_action('admin_post_pwr_delete_entry', [$this, 'delete_entry']);
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);
  }

  public function add_admin_menu()
  {
    add_menu_page(
      'Warranty Registrations',
      'Warranty Cards',
      'manage_options',
      'warranty',
      [$this, 'render_admin_page'],
      'dashicons-clipboard'
    );
  }

  public function enqueue_admin_assets($hook)
  {
    // Only enqueue for our plugin page.
    if ('toplevel_page_warranty' === $hook) {
      wp_enqueue_style('wr-admin-css', WR_PLUGIN_URL . 'assets/css/admin.css', [], '1.0');
    }
  }

  public function render_admin_page()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_cards';

    // Handle search
    $search_term = sanitize_text_field($_GET['s'] ?? '');
    $search_sql = '';
    $search_args = [];

    if (!empty($search_term)) {
      $search_sql = "WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR warranty_number LIKE %s";
      $like = '%' . $wpdb->esc_like($search_term) . '%';
      $search_args = [$like, $like, $like, $like];
    }

    $per_page     = 20;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset       = ($current_page - 1) * $per_page;

    // Count total
    if (!empty($search_sql)) {
      $total_items = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM $table_name $search_sql", ...$search_args)
      );
    } else {
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    if (!empty($search_sql)) {
      $query = "SELECT * FROM $table_name $search_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
      $results_args = array_merge($search_args, [$per_page, $offset]);
      $registrations = $wpdb->get_results($wpdb->prepare($query, ...$results_args));
    } else {
      $query = "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d";
      $registrations = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
    }
?>

    <div class="wrap wr-admin-wrap">
      <?php if (isset($_GET['delete'])): ?>
        <div class="notice <?php echo $_GET['delete'] === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
          <p><?php echo $_GET['delete'] === 'success' ? 'Entry deleted successfully.' : 'Failed to delete entry.'; ?></p>
        </div>
      <?php endif; ?>

      <div class="wr-admin-header">
        <h1 class="wp-heading-inline">Warranty Registrations</h1>

        <form method="get" action="" style="display:inline-block; margin-left: 20px;">
          <input type="hidden" name="page" value="warranty">
          <input type="search" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="Search" />
          <input type="submit" class="button" value="Search">
        </form>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="wr-export-form" style="float:right;">
          <input type="hidden" name="action" value="pwr_export_csv">
          <?php wp_nonce_field('pwr_export_nonce', 'pwr_export_nonce'); ?>
          <button type="submit" class="button button-primary">Export to CSV</button>
        </form>
      </div>

      <div class="tablenav top">
        <div class="tablenav-pages">
          <?php
          echo paginate_links([
            'base'      => add_query_arg(['paged' => '%#%', 's' => $search_term]),
            'format'    => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total'     => ceil($total_items / $per_page),
            'current'   => $current_page,
          ]);
          ?>
        </div>
      </div>

      <div class="wr-admin-table">
        <table class="wp-list-table widefat striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Warranty No.</th>
              <th>Model</th>
              <th>Purchase Date</th>
              <th>Registered</th>
              <th>Documents</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($registrations)) : ?>
              <tr>
                <td colspan="8">No warranty registrations found</td>
              </tr>
            <?php else : ?>
              <?php foreach ($registrations as $reg) : ?>
                <tr>
                  <td><?php echo $reg->id; ?></td>
                  <td><?php echo esc_html($reg->first_name . ' ' . $reg->last_name); ?></td>
                  <td><?php echo esc_html($reg->email); ?></td>
                  <td><?php echo esc_html($reg->warranty_number); ?></td>
                  <td><?php echo esc_html($reg->product_model); ?></td>
                  <td><?php echo esc_html($reg->purchase_date); ?></td>
                  <td><?php echo date('M j, Y', strtotime($reg->created_at)); ?></td>
                  <td>
                    <a href="<?php echo esc_url($reg->file_url); ?>" target="_blank" class="button">View Document</a>
                    <a href="<?php echo esc_url($reg->qr_code_url); ?>" target="_blank" class="button">View QR Code</a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                      <?php wp_nonce_field('pwr_delete_nonce_' . $reg->id); ?>
                      <input type="hidden" name="action" value="pwr_delete_entry">
                      <input type="hidden" name="entry_id" value="<?php echo esc_attr($reg->id); ?>">
                      <button type="submit" class="button button-link-delete">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php
  }



  public function export_to_csv()
  {
    if (! isset($_POST['pwr_export_nonce']) || ! wp_verify_nonce($_POST['pwr_export_nonce'], 'pwr_export_nonce')) {
      wp_die('Security check failed');
    }
    if (! current_user_can('manage_options')) {
      wp_die('Unauthorized access');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_cards';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=warranty-registrations-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
      'ID',
      'First Name',
      'Last Name',
      'Email',
      'Warranty Number',
      'Country',
      'Product Model',
      'Purchase Date',
      'File URL',
      'QR Code URL',
      'Registration Date'
    ]);
    $registrations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
    foreach ($registrations as $reg) {
      fputcsv($output, [
        $reg['id'],
        $reg['first_name'],
        $reg['last_name'],
        $reg['email'],
        $reg['warranty_number'],
        $reg['country'],
        $reg['product_model'],
        $reg['purchase_date'],
        $reg['file_url'],
        $reg['qr_code_url'],
        $reg['created_at']
      ]);
    }
    fclose($output);
    exit;
  }

  // Helper to send confirmation email. We can make it static for ease of use.
  public static function send_confirmation_email($data)
  {
    $to      = $data['email'];
    $subject = 'Your Warranty Registration';
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    ob_start();
  ?>
    <!DOCTYPE html>
    <html>

    <head>
      <style>
        /* Email styles */
        body {
          font-family: Arial, sans-serif;
          line-height: 1.6;
        }

        .container {
          max-width: 600px;
          margin: 0 auto;
          padding: 20px;
        }

        .header {
          background: #000;
          color: #fff;
          padding: 20px;
          text-align: center;
        }

        .content {
          padding: 20px;
          border: 1px solid #ddd;
        }

        .footer {
          text-align: center;
          font-size: 12px;
          color: #777;
        }

        table {
          width: 100%;
          border-collapse: collapse;
        }

        th,
        td {
          padding: 10px;
          border-bottom: 1px solid #ddd;
          text-align: left;
        }
      </style>
    </head>

    <body>
      <div class="container">
        <div class="header">
          <h2>Warranty Registration</h2>
        </div>
        <div class="content">
          <p>Dear <?php echo esc_html($data['first_name']); ?>,</p>
          <p>Thank you for registering your warranty. Here are your details:</p>
          <table>
            <tr>
              <th>Warranty Number:</th>
              <td><?php echo esc_html($data['warranty_number']); ?></td>
            </tr>
            <tr>
              <th>Name:</th>
              <td><?php echo esc_html($data['first_name'] . ' ' . $data['last_name']); ?></td>
            </tr>
            <tr>
              <th>Product Model:</th>
              <td><?php echo esc_html($data['product_model']); ?></td>
            </tr>
            <tr>
              <th>Purchase Date:</th>
              <td><?php echo esc_html($data['purchase_date']); ?></td>
            </tr>
          </table>
          <div style="text-align: center; margin: 20px 0;">
            <p>Your warranty QR code:</p>
            <img src="<?php echo esc_url($data['qr_code_url']); ?>" alt="Warranty QR Code" style="max-width: 200px;">
          </div>
          <p>Please keep this email for your records.</p>
        </div>
        <div class="footer">
          <p>&copy; <?php echo date('Y'); ?> Watches. All rights reserved.</p>
        </div>
      </div>
    </body>

    </html>
  <?php
    $message = ob_get_clean();
    wp_mail($to, $subject, $message, $headers);
  }

  public function delete_entry()
  {
    if (! current_user_can('manage_options')) {
      wp_die('Unauthorized request');
    }

    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

    if (! $entry_id || ! check_admin_referer('pwr_delete_nonce_' . $entry_id)) {
      wp_redirect(admin_url('admin.php?page=warranty&delete=fail'));
      exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_cards';

    $deleted = $wpdb->delete($table_name, ['id' => $entry_id], ['%d']);

    if ($deleted !== false) {
      wp_redirect(admin_url('admin.php?page=warranty&delete=success'));
    } else {
      wp_redirect(admin_url('admin.php?page=warranty&delete=fail'));
    }
    exit;
  }

  public function add_settings_page()
  {
    add_submenu_page(
      'warranty',
      'Warranty Settings',
      'Settings',
      'manage_options',
      'warranty-settings',
      [$this, 'render_settings_page']
    );
  }

  public function render_settings_page()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $options = get_option('wr_settings', []);

    if (isset($_POST['wr_settings_submit'])) {
      check_admin_referer('wr_settings_update');
      $options = $_POST['wr_settings'] ?? [];
      update_option('wr_settings', $options);
      echo '<div class="updated"><p>Settings saved.</p></div>';
    }
  ?>
    <div class="wrap">
      <h1>Warranty Form Settings</h1>
      <form method="post" action="options.php">
        <?php settings_fields('wr_settings_group'); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">reCAPTCHA Site Key</th>
            <td><input type="text" name="wr_settings[recaptcha_site_key]" value="<?php echo esc_attr($options['recaptcha_site_key'] ?? ''); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">reCAPTCHA Secret Key</th>
            <td><input type="text" name="wr_settings[recaptcha_secret_key]" value="<?php echo esc_attr($options['recaptcha_secret_key'] ?? ''); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Warranty Number Limit</th>
            <td><input type="number" name="wr_settings[warranty_limit]" value="<?php echo esc_attr($options['warranty_limit'] ?? 1000); ?>" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Success Message</th>
            <td><input type="text" name="wr_settings[success_message]" value="<?php echo esc_attr($options['success_message'] ?? 'Warranty Registered Successfully!'); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Error Message</th>
            <td><input type="text" name="wr_settings[error_message]" value="<?php echo esc_attr($options['error_message'] ?? 'There was an error submitting your form.'); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Product Models (comma separated)</th>
            <td>
              <textarea name="wr_settings[product_models]" class="large-text" rows="4"><?php echo esc_textarea($options['product_models'] ?? 'Model A,Model B'); ?></textarea>
              <p class="description">Enter model names separated by commas (e.g. Model A, Model B, Model C)</p>
            </td>
          </tr>
        </table>

        <h2>Theme Colors</h2>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">Background Color</th>
            <td><input type="text" name="wr_settings[colors][background_color]" value="<?php echo esc_attr($options['colors']['background_color'] ?? '#ffffff'); ?>" class="wr-color-picker" data-default-color="#ffffff" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Form Label Color</th>
            <td><input type="text" name="wr_settings[colors][label_color]" value="<?php echo esc_attr($options['colors']['label_color'] ?? '#000000'); ?>" class="wr-color-picker" data-default-color="#000000" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Placeholder Color</th>
            <td><input type="text" name="wr_settings[colors][placeholder_color]" value="<?php echo esc_attr($options['colors']['placeholder_color'] ?? '#888888'); ?>" class="wr-color-picker" data-default-color="#888888" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Submit Button Background</th>
            <td><input type="text" name="wr_settings[colors][submit_btn_bg]" value="<?php echo esc_attr($options['colors']['submit_btn_bg'] ?? '#0073aa'); ?>" class="wr-color-picker" data-default-color="#0073aa" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">Submit Button Text Color</th>
            <td><input type="text" name="wr_settings[colors][submit_btn_text]" value="<?php echo esc_attr($options['colors']['submit_btn_text'] ?? '#ffffff'); ?>" class="wr-color-picker" data-default-color="#ffffff" /></td>
          </tr>
        </table>

        <?php submit_button(); ?>
      </form>
    </div>
    <script>
      jQuery(document).ready(function($) {
        $('.wr-color-picker').wpColorPicker();
      });
    </script>
<?php
  }

  public function register_settings()
  {
    register_setting('wr_settings_group', 'wr_settings');
  }
}
