(function (Drupal) {
	"use strict";

  Drupal.behaviors.visaGuide = {
    attach: function(context) {

      const swiper = new Swiper('#node-visa-interactive-guide-form', {
        slidesPerView: 1,
        loop: false,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: ".swiper-pagination",
          clickable: true,
        },
      });
    }
  }

})(window.Drupal);
