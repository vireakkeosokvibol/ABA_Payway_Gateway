var AbaPayway = (function (options) {
  "use strict";

  var _checkout = function () {
    $ = jQuery.noConflict();
    if (jQuery(window).width() < 500) {
      /* Disable scrool in iOS */
      jQuery(".aba-modal").height(jQuery(window).height() - 30);
      jQuery("html, body").css({
        overflow: "hidden",
        height: "0",
      });
      jQuery(".aba-modal-content").css({
        width: jQuery(window).width() - 25 + "px",
        "margin-left": "0",
      });
    }

    jQuery(".dropdown-menu").hide();

    jQuery("#aba_main_modal").append('<div class="aba-loader"></div>');

    jQuery(".aba-modal-content").append(
      '<iframe scrolling="yes" class="aba-iframe" src="" name="aba_webservice" id="aba_webservice"></iframe><span class="aba-close"><img src="https://payway-dev.ababank.com/fileadmin/templates/img/close.png"/></span>'
    );

    jQuery("#aba_main_modal").css("display", "block");

    jQuery("#aba_main_modal").click(function () {
      jQuery(this).focus();
    });

    if (jQuery("#aba_merchant_request, #respayment").submit()) {
      var form = this;
      jQuery(":submit", form).attr("disabled", false);

      setTimeout(function () {
        jQuery(".aba-loader").hide();
      }, 5000);

      jQuery("#aba_webservice").load(function () {
        jQuery(".aba-loader").hide();
      });
    }

    jQuery(document).on("click touchstart", ".aba-close", function () {
      closePopup();
    });
  };

  return {
    checkout: _checkout,
  };
})();
