/**
 * @file
 * E learn Theme behaviors.
 */
(function (Drupal, $) {

  'use strict';

  Drupal.behaviors.eLearnTheme = {
    attach (context, settings) {
      $('a.print-certificate', context).on('click', function (e) {
        window.print();
        console.log('working');
      });
    }
  };

} (Drupal, jQuery));
