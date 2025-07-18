<?php

use WpOrg\Requests\Response;

if (! defined('ABSPATH')) {
  exit;
}

class Warranty_Form
{
  public function __construct()
  {
    add_shortcode('warranty_form', [$this, 'render_form']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    add_action('admin_post_pwr_submit_form', [$this, 'process_form']);
    add_action('admin_post_nopriv_pwr_submit_form', [$this, 'process_form']);
  }

  public function enqueue_frontend_assets()
  {
    wp_enqueue_style('wr-frontend-css', WR_PLUGIN_URL . 'assets/css/frontend.css', [], '1.0');
    wp_enqueue_script('wr-frontend-js', WR_PLUGIN_URL . 'assets/js/frontend.js', ['jquery', 'jquery-ui-datepicker'], '1.0', true);
    wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
  }

  public function render_form()
  {
    $options = get_option('wr_settings', []);
    $field_labels = $options['field_labels'] ?? [];
    $colors = $options['colors'] ?? [];
    $warranty_limit = isset($options['warranty_limit']) ? (int)$options['warranty_limit'] : 1000;
    $success_message = $options['success_message'] ?? 'Warranty Registered Successfully!';
    $error_message = $options['error_message'] ?? 'There was an error submitting your form.';
    $models = isset($options['product_models']) ? explode(',', $options['product_models']) : [];

    ob_start();

    if (! class_exists('WooCommerce')) {
      echo '<div class="wr-error">WooCommerce is not active. Country list unavailable.</div>';
      return;
    }

    $options = get_option('wr_settings');
    $site_key = esc_attr($options['recaptcha_site_key']);
    ?>

    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_js($site_key); ?>"></script>
    <script>
      function onSubmit(token) {
        document.getElementById("demo-form").submit();
      }
    </script>

    <div class="wr-container" style="
      background-color: <?php echo esc_attr($colors['background_color'] ?? '#ffffff'); ?>;
      --wr-label-color: <?php echo esc_attr($colors['label_color'] ?? '#000000'); ?>;
      --wr-placeholder-color: <?php echo esc_attr($colors['placeholder_color'] ?? '#888888'); ?>;
      --wr-btn-bg: <?php echo esc_attr($colors['submit_btn_bg'] ?? '#0073aa'); ?>;
      --wr-btn-text: <?php echo esc_attr($colors['submit_btn_text'] ?? '#ffffff'); ?>;
    ">
      <h2 class="wr-form-title"><?php echo esc_html($field_labels['form_title'] ?? 'Register Your Warranty'); ?></h2>
      <form id="warrantyForm" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="pwr_submit_form" />
        <?php wp_nonce_field('pwr_form_nonce', 'pwr_nonce'); ?>

        <div class="wr-form-row">
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['first_name'] ?? 'First Name'); ?></label>
            <input type="text" name="first_name" placeholder="<?php echo esc_attr($field_labels['first_name'] ?? 'First Name'); ?>" />
          </div>
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['last_name'] ?? 'Last Name'); ?></label>
            <input type="text" name="last_name" placeholder="<?php echo esc_attr($field_labels['last_name'] ?? 'Last Name'); ?>" />
          </div>
        </div>

        <div class="wr-form-row">
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['email'] ?? 'Email'); ?></label>
            <input type="email" name="email" placeholder="<?php echo esc_attr($field_labels['email'] ?? 'Email'); ?>" />
          </div>
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['country'] ?? 'Country'); ?></label>
            <select name="country">
              <option value="">-- Select Country --</option>
              <?php foreach (WC()->countries->get_countries() as $code => $label): ?>
                <option value="<?php echo esc_attr($label); ?>"><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="wr-form-row">
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['purchase_date'] ?? 'Purchase Date'); ?></label>
            <div class="datepicker-icon-group">
              <input type="text" name="purchase_date" id="wr_purchase_date" placeholder="YYYY-MM-DD" />
              <span class="calendar-icon"></span>
            </div>
          </div>
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['warranty_number'] ?? 'Warranty Number'); ?></label>
            <input type="text" name="warranty_number" data-max="<?php echo esc_attr($warranty_limit); ?>" placeholder="0001" />
          </div>
        </div>

        <div class="wr-form-row">
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['product_model'] ?? 'Product Model'); ?></label>
            <select name="product_model">
              <option value="">-- Select Model --</option>
              <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr(trim($model)); ?>"><?php echo esc_html(trim($model)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="wr-form-row">
          <div class="wr-form-group">
            <label class="wr-label"><?php echo esc_html($field_labels['upload'] ?? 'Upload Invoice / Proof'); ?></label>
            <input type="file" name="invoice_file" />
          </div>
        </div>

        <div class="wr-checkbox-group">
          <label><input type="checkbox" name="consent" /> <?php echo esc_html($field_labels['consent'] ?? 'I agree to the terms and conditions'); ?></label>
        </div>
        
        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
        <button class="wr-submit-btn" type="submit">
          <?php echo esc_html($field_labels['submit'] ?? 'Register'); ?>
        </button>
      </form>
      <script>
        grecaptcha.ready(function(){
          grecaptcha.execute($site_key,{action: "submit"})
          .then(function(token){
            console.log(token);
          });
        });
      </script>

      <div class="wr-popup hidden" id="wr-popup">
        <div class="wr-popup-content">
          <span class="wr-popup-close">&times;</span>
          <div id="wr-popup-message" data-success="<?php echo esc_attr($success_message); ?>" data-error="<?php echo esc_attr($error_message); ?>"></div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  public function process_form()
  {

    $options = get_option('wr_settings');
    $site_key = esc_attr($options['recaptcha_site_key']);
    $secret_key = esc_attr($options['recaptcha_secret_key']); 


    if (! isset($_POST['pwr_nonce']) || ! wp_verify_nonce($_POST['pwr_nonce'], 'pwr_form_nonce')) {
      wp_die('Security check failed', 'Error', ['response' => 403]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_cards';
    $options = get_option('wr_settings', []);
    $max_warranty_limit = isset($options['warranty_max']) ? (int)$options['warranty_max'] : 1000;
    $success_message = sanitize_text_field($options['success_message'] ?? 'Thank you for registering.');
    $error_message = sanitize_text_field($options['error_message'] ?? 'There was a problem submitting the form.');

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
      'body' => [
        'secret'   => $secret_key,
        'response' => sanitize_text_field($_POST['g-recaptcha-response'] ?? ''),
      ],
    ]);

    $data = json_decode(wp_remote_retrieve_body($response), true);

    $required = [
      'first_name'       => 'First Name',
      'last_name'        => 'Last Name',
      'email'            => 'Email',
      'purchase_date'    => 'Purchase Date',
      'product_model'    => 'Product Model',
      'country'          => 'Country',
      'warranty_number'  => 'Warranty Number',
    ];

    $errors = [];
    $data = [];

    foreach ($required as $field => $name) {
      if (empty($_POST[$field])) {
        $errors[] = "$name is required";
      }
      $data[$field] = sanitize_text_field($_POST[$field] ?? '');
    }

    if (! is_email($data['email'])) {
      $errors[] = 'Please enter a valid email address';
    }

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['purchase_date'])) {
      $errors[] = 'Invalid purchase date format';
    }

    $file = $_FILES['invoice_file'] ?? null;
    if (! $file || $file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Warranty file is required';
    }

    if (empty($_POST['consent'])) {
      $errors[] = 'You must agree to the privacy policy';
    }

    $available_countries = WC()->countries->get_countries();
    if (!in_array($data['country'], $available_countries)) {
      $errors[] = 'Invalid country selected.';
    }

    // Warranty number
    $warranty_number = (int) ($_POST['warranty_number'] ?? 0);
    $formatted_number = str_pad($warranty_number, 4, '0', STR_PAD_LEFT);

    if ($warranty_number < 1 || $warranty_number > $max_warranty_limit) {
      $errors[] = "Warranty number must be between 0001 and " . str_pad($max_warranty_limit, 4, "0", STR_PAD_LEFT) . '.';
    } else {
      $data['warranty_number'] = $formatted_number;
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE warranty_number = %s",
        $formatted_number
      ));
      if ($existing > 0) {
        $errors[] = 'Warranty number already registered.';
      }
    }

    if (! empty($errors)) {
      wp_redirect(add_query_arg([
        'pwr_status'  => 'error',
        'pwr_message' => urlencode(implode('|', $errors)),
        'pwr_fields'  => urlencode(json_encode($_POST))
      ], wp_get_referer()));
      exit;
    }

    if (! function_exists('wp_handle_upload')) {
      require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploaded_file = wp_handle_upload($file, [
      'test_form' => false,
      'mimes'     => [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'pdf'  => 'application/pdf'
      ]
    ]);

    if (isset($uploaded_file['error'])) {
      wp_redirect(add_query_arg([
        'pwr_status'  => 'error',
        'pwr_message' => urlencode('File upload error: ' . $uploaded_file['error'])
      ], wp_get_referer()));
      exit;
    }

    $data['file_url'] = $uploaded_file['url'];

    $qr_content = sprintf(
      "Warranty|Number: %s|Name: %s %s",
      $data['warranty_number'],
      sanitize_text_field($data['first_name']),
      sanitize_text_field($data['last_name'])
    );

    $data['qr_code_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr_content);

    $result = $wpdb->insert($table_name, $data);

    if (! $result) {
      wp_redirect(add_query_arg([
        'pwr_status'  => 'error',
        'pwr_message' => urlencode($error_message)
      ], wp_get_referer()));
      exit;
    }

    Warranty_Admin::send_confirmation_email($data);

    wp_redirect(add_query_arg([
      'pwr_status'  => 'success',
      'pwr_message' => urlencode($success_message)
    ], wp_get_referer()));
    exit;
  }
}

new Warranty_Form();
