(function ($, Drupal) {

  Drupal.behaviors.socialCourseNav = {
    attach: function attach(context) {
      var toggleNav = $('.course_nav-toggle');

      toggleNav.each(function () {
        var el = $(this);
        var icon = el.find('svg use');
        var content = el.parents('.course__navigation').find('.card__block--list');

        el.on('click', function (e) {
          e.preventDefault();

          if (el.hasClass('is-active')) {
            el.removeClass('is-active');
            content.slideUp(500);
            icon.attr('xlink:href', '#icon-arrow_drop_down');
          }
          else {
            el.addClass('is-active');
            content.slideDown(500);
            icon.attr('xlink:href', '#icon-arrow_drop_up');
          }
        });
      });
    }
  };

})(jQuery, Drupal);
