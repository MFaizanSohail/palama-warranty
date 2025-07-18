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

    // On input: allow only numbers
    $form.find('[name="warranty_number"]').on("input", function () {
      let value = $(this).val().replace(/\D/g, "");
      const max = $(this).data("max") || "1000";
      const totalLength = max.length;

      if (value.length > totalLength) {
        value = value.slice(0, totalLength);
      }

      $(this).val(value);
    });

    // On blur: pad with leading zeros to match max length
    $form.find('[name="warranty_number"]').on("blur", function () {
      const max = $(this).data("max") || "1000";
      const totalLength = max.length;
      let value = $(this).val().replace(/\D/g, "");

      if (value.length > 0) {
        $(this).val(value.padStart(totalLength, "0"));
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
          window.history.replaceState({}, document.title, window.location.pathname);
        }, 6000);
      }
    }

    setTimeout(() => {
      showPopupFromURL();
    }, 300);

    // Manual popup close
    $(".wr-popup-close").on("click", function () {
      $("#wr-popup").addClass("hidden");
    });

    // Client-side form validation
    $form.on("submit", function (e) {
      let isValid = true;
      const requiredFields = [
        "first_name",
        "last_name",
        "email",
        "purchase_date",
        "country",
      ];
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Reset error states
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
      const max = $warranty.data("max") || "1000";
      const totalLength = max.length;
      const warrantyVal = $warranty.val();

      if (
        !/^\d+$/.test(warrantyVal) ||
        warrantyVal.length !== totalLength ||
        parseInt(warrantyVal) < 1 ||
        parseInt(warrantyVal) > parseInt(max)
      ) {
        $warranty.addClass("wr-error");
        isValid = false;

        const $popup = $("#wr-popup");
        const $popupMsg = $("#wr-popup-message");

        $popup.removeClass("hidden success error").addClass("error");
        $popupMsg.html(
          `Warranty number must be a ${totalLength}-digit number between ${"0".repeat(totalLength - 1)}1 and ${max}.`
        );

        setTimeout(() => {
          $popup.addClass("hidden");
          window.history.replaceState({}, document.title, window.location.pathname);
        }, 5000);
      }

      if (!isValid) {
        e.preventDefault();
      }
    });
  });
})(jQuery);
