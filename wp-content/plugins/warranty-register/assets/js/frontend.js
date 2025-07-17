(function ($) {
  $(document).ready(function () {
    const $form = $("#wr-form");

    // Initialize datepicker for purchase date field
    $("#wr_purchase_date").datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
      yearRange: "2000:+10",
    });

    // Trigger datepicker on calendar icon click
    $(".calendar-icon").on("click", function () {
      $(this).siblings("input").focus();
    });

    // On input: allow only numbers up to 4 digits
    $form.find('[name="warranty_number"]').on("input", function () {
      let value = $(this).val().replace(/\D/g, "");
      if (value.length > 4) value = value.slice(0, 4);
      $(this).val(value); // Set cleaned value
    });

    // On blur: pad to 4 digits with leading zeros
    $form.find('[name="warranty_number"]').on("blur", function () {
      let value = $(this).val();
      if (value.length > 0 && value.length <= 4) {
        $(this).val(value.padStart(4, "0"));
      }
    });

    // Show popup (success or error) from URL
    function showPopupFromURL() {
      const params = new URLSearchParams(window.location.search);
      const status = params.get("pwr_status");
      const message = params.get("pwr_message");

      if (status && message) {
        const decodedMsg = decodeURIComponent(message.replace(/\+/g, " "));
        const formattedMsg = decodedMsg.split("|").join("<br>");

        const $popup = $("#wr-popup");
        const $popupMsg = $("#wr-popup-message");

        $popup
          .removeClass("hidden success error")
          .addClass(status === "success" ? "success" : "error");
        $popupMsg.html(formattedMsg);

        setTimeout(() => {
          $popup.addClass("hidden");
          window.history.replaceState(
            {},
            document.title,
            window.location.pathname
          );
        }, 6000);
      }
    }

    // Delay popup display until after DOM load and scroll
    setTimeout(() => {
      showPopupFromURL();
    }, 300);

    // Manual popup close
    $(".wr-popup-close").on("click", function () {
      $("#wr-popup").addClass("hidden");
    });

    // Client-side form validation
    $form.on("submit", function (e) {
      e.preventDefault(); 
      grecaptcha.ready(function () {
        grecaptcha
          .execute("<?= esc_js($site_key); ?>", { action: "warranty_form" })
          .then(function (token) {
            $("#g-recaptcha-response").val(token); // set token in hidden field
            $form.off("submit"); // remove handler to avoid recursion
            $form.submit(); // resubmit now with captcha token
          });
      });

      let isValid = true;
      const requiredFields = [
        "first_name",
        "last_name",
        "email",
        "purchase_date",
        "country",
      ];
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Reset error classes
      $form.find(".wr-error").removeClass("wr-error");

      requiredFields.forEach(function (field) {
        const $field = $form.find('[name="' + field + '"]');
        if (!$field.val()) {
          $field.addClass("wr-error");
          isValid = false;
        }
        if (
          field === "email" &&
          $field.val() &&
          !emailRegex.test($field.val())
        ) {
          $field.addClass("wr-error");
          isValid = false;
        }
      });

      const $model = $form.find('select[name="product_model"]');
      if (!$model.val()) {
        $model.addClass("wr-error");
        isValid = false;
      }

      const $file = $form.find('input[type="file"]');
      if (!$file.val()) {
        $file.addClass("wr-error");
        isValid = false;
      }

      const $checkbox = $form.find('input[name="consent"]');
      if (!$checkbox.is(":checked")) {
        $checkbox.closest(".wr-checkbox-group").addClass("wr-error");
        isValid = false;
      }
      const $warranty = $form.find('[name="warranty_number"]');
      const warrantyVal = $warranty.val();
      if (
        !/^\d{4}$/.test(warrantyVal) ||
        parseInt(warrantyVal) < 1 ||
        parseInt(warrantyVal) > 1000
      ) {
        $warranty.addClass("wr-error");
        isValid = false;

        // Show popup error for invalid warranty number
        const $popup = $("#wr-popup");
        const $popupMsg = $("#wr-popup-message");

        $popup.removeClass("hidden success error").addClass("error");
        $popupMsg.html(
          "Warranty number must be a 4-digit number between 0001 and 1000."
        );

        setTimeout(() => {
          $popup.addClass("hidden");
          window.history.replaceState(
            {},
            document.title,
            window.location.pathname
          ); // clean URL
        }, 5000);
      }
    });

    // reCAPTCHA v3 execution
    if (typeof grecaptcha !== "undefined") {
      grecaptcha.ready(function () {
        grecaptcha
          .execute("<?php echo esc_js($site_key); ?>", {
            action: "warranty_form",
          })
          .then(function (token) {
            $("#g-recaptcha-response").val(token);
          });
      });
    }
  });
})(jQuery);
