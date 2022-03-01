jQuery(function ($) {
  load_aba_payment();
  jQuery("body").on("updated_checkout", function () {
    load_aba_payment();
  });

  jQuery("form#order_review").submit(function (e) {
    if (
      jQuery("input[type=radio][name=payment_method]:checked").val() ==
      "aba_payway_aim"
    ) {
      var selected_payment_option = jQuery(
        "input[type=radio][name=payment_method]:checked"
      ).attr("real-value");
      if (selected_payment_option == "aba_payway_aim") {
        jQuery("#aba_main_modal [name='payment_option']").val("abapay");
      } else {
        jQuery("#aba_main_modal [name='payment_option']").val("cards");
      }
      AbaPayway.checkout();
      e.preventDefault();
    }
  });

  jQuery(document).on(
    "click",
    "input[type=submit][name=woocommerce_checkout_place_order],button[type=submit][name=woocommerce_checkout_place_order]",
    function (e) {
      jQuery(".woocommerce-error").remove();
      if (
        jQuery("input[type=radio][name=payment_method]:checked").val() ==
        "aba_payway_aim"
      ) {
        var first_name = jQuery("#billing_first_name").val();
        var last_name = jQuery("#billing_last_name").val();
        var phone = jQuery("#billing_phone").val();
        var email = jQuery("#billing_email").val();
        var aba_api_key = jQuery("#aba_api_key").val();
        var aba_api_url = jQuery("#aba_api_url").val();

        if (aba_api_key == "") {
          jQuery(
            '<div class="woocommerce-error">Payway Api key is missing.Please contact administrator to add api key.</div>'
          ).insertBefore("#customer_details");
          jQuery("html, body").animate(
            {
              scrollTop: jQuery(".woocommerce-error").offset().top - 100,
            },
            1000
          );
          return false;
        }
        if (aba_api_url == "") {
          jQuery(
            '<div class="woocommerce-error">Payway Api url is missing.Please contact Administrator to add api url.</div>'
          ).insertBefore("#customer_details");
          jQuery("html, body").animate(
            {
              scrollTop: jQuery(".woocommerce-error").offset().top - 100,
            },
            1000
          );
          return false;
        }

        //var transation_id = Math.floor(Math.random() * 1000000000);
        if (
          first_name != "" &&
          last_name != "" &&
          phone != "" &&
          email != "" &&
          jQuery(".woocommerce-error").length <= 0
        ) {
          jQuery("input[name=firstname]").val(first_name);
          jQuery("input[name=lastname]").val(last_name);
          jQuery("input[name=phone]").val(phone);
          jQuery("input[name=email]").val(email);
        }
        //---------Close Popup----------------------------
        jQuery(document).on("click touchstart", ".aba-close", function () {
          closePopup();
        });

        var closePopup = function () {
          var confirmClose = true;
          confirmClose = confirm("Do you want to leave?");
          if (confirmClose == true) {
            jQuery.post(
              frontend_ajax_object.ajaxurl,
              {
                _ajax_nonce: frontend_ajax_object.nonce,
                action: "aba_check_payment_cancel_order",
              },
              function (data) {
                location.href = encodeURI(atob(jQuery("#cancel_url").val()));
              }
            );

            if (jQuery("#aba_main_modal").hide()) {
              jQuery("#aba_webservice").attr("src", "");

              if (jQuery(window).width() < 500) {
                jQuery("html, body").css({
                  overflow: "auto",
                  height: "auto",
                });
                //Enable scrool in iOS
              }
            }
          }
        };
      } else {
        jQuery(
          "button[type=submit][name=woocommerce_checkout_place_order]"
        ).attr("onclick", "");
      }
    }
  );
});

function load_aba_payment() {
  if (jQuery("#aba_is_enable").val() != "no") {
    var aba_enable_payment = jQuery("#aba_enable_payment").val();
    var aba_pay_label = ""; //'Tap to pay with ABA Mobile';
    /*if (jQuery(window).width() > 768) {
            aba_pay_label = 'Scan to pay with ABA Mobile';
        }*/
    if (aba_enable_payment != "" && typeof aba_enable_payment != "undefined") {
      var aba_enable_payments = aba_enable_payment.split(",");
      if (jQuery.inArray("credit_debit", aba_enable_payments) != -1) {
        var html_payment = prepare_payment_elem(
          "aba_payway_aim_card",
          "Credit/Debit Card",
          "Pay with Credit/Debit Card",
          '<img class="payway_payment_logo" src="' +
            jQuery("#aba_logo_all_cards").val() +
            '" alt="ABA Pay">'
        );
        jQuery(
          ".wc_payment_methods.payment_methods.methods li.payment_method_aba_payway_aim:not(.new_element)"
        ).after(html_payment);
      }

      if (jQuery.inArray("aba_apy", aba_enable_payments) != -1) {
        var html_payment = prepare_payment_elem(
          "aba_payway_aim",
          "ABA PAY",
          "Pay with ABA PAY",
          '<img class="payway_payment_logo" src="' +
            jQuery("#aba_logo_aba").val() +
            '" alt="ABA Pay">',
          '<img height="20" src="' +
            jQuery("#aba_logo_aba").val() +
            '" alt="ABA Pay"'
        );
        jQuery(
          ".wc_payment_methods.payment_methods.methods li.payment_method_aba_payway_aim:not(.new_element)"
        ).after(html_payment);
      }

      jQuery("li.payment_method_aba_payway_aim:not(.new_element)").remove();
      jQuery("li.payment_method_aba_payway_aim").css("display", "block");

      var img_height = jQuery(
        "#payment .wc_payment_methods .payment_method_payway .aba-left img"
      ).height();
      var input_height = jQuery(
        "#payment .payment_methods>.payment_method_payway>input"
      ).outerHeight();
      img_height = img_height - input_height;
      var margin_top = img_height / 2;
      jQuery("#payment .payment_methods>.payment_method_payway>input").attr(
        "style",
        "margin-top: " + margin_top + "px !important;"
      );

      var left =
        jQuery(
          "#payment .wc_payment_methods .payment_method_payway .aba-left"
        ).outerWidth() + 40;
      jQuery(
        "#payment .wc_payment_methods .payment_method_payway .aba-right"
      ).css("width", "calc(100% - " + left + "px)");
    }
  }
}

function prepare_payment_elem(
  payment_id,
  payment_label,
  description,
  html_desc
) {
  var aba_choosen_method = jQuery("#aba_choosen_method").val();
  var aba_bg_color = jQuery("#aba_bg_color").val();
  var html_payment =
    '<li class="clearfix wc_payment_method new_element payment_method_payway payment_method_' +
    payment_id +
    " " +
    aba_bg_color +
    '">';
  html_payment +=
    "    <input " +
    (aba_choosen_method == payment_id ? "checked" : "") +
    ' id="payment_method_' +
    payment_id +
    '" type="radio" class="input-radio" name="payment_method" real-value="' +
    payment_id +
    '" value="aba_payway_aim" data-order_button_text="">';
  html_payment += '    <label for="payment_method_' + payment_id + '">';
  html_payment += "    " + payment_label + " " + html_desc + "</label>";
  html_payment += "</li>";
  /*
<li class="wc_payment_method payment_method_ppec_paypal">
        <input id="payment_method_ppec_paypal" type="radio" class="input-radio" name="payment_method" value="ppec_paypal" data-order_button_text="Continue to payment">

        <label for="payment_method_ppec_paypal">
        PayPal <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-small.png" alt="PayPal">	</label>
        <div class="payment_box payment_method_ppec_paypal" style="display:none;">
        <p>Pay via PayPal; you can pay with your credit card if you don’t have a PayPal account.</p>
    </div>
    </li>*/
  return html_payment;
}
