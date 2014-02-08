<?php

/**
 * @file
 * This files creates an admin page for this module
 */

/**
 * Implements hook_menu().
 */
function content_language_access_admin_form($form, $form_state = NULL) {
  $form = array();

  $config = _content_language_access_get_config();

  $form['content_language_access'] = array(
    '#type' => 'fieldset',
    '#title' => t('Permissions'),
    '#collapsible' => FALSE,
    '#tree' => TRUE,
  );

  $languages = language_list();
  foreach ($languages as $language) {
    if ($language->enabled) {
      $form['content_language_access'][$language->language] = array(
        '#type' => 'fieldset',
        '#title' => t('Drupal language: @language', array('@language' => $language->name)),
        '#collapsible' => TRUE,
        '#tree' => TRUE,
      );
      foreach ($languages as $language_perm) {
        if ($language_perm->enabled) {
          $form['content_language_access'][$language->language][$language_perm->language] = array(
            '#type' => 'checkbox',
            '#title' => t('Content language: @language', array('@language' => $language_perm->name)),
            '#default_value' => (isset($config[$language->language][$language_perm->language])) ? $config[$language->language][$language_perm->language] : FALSE,
          );

          // Only shows the same language for better visualization.
          if ($language->language == $language_perm->language) {
            $form['content_language_access'][$language->language][$language_perm->language]['#disabled'] = TRUE;
            $form['content_language_access'][$language->language][$language_perm->language]['#value'] = TRUE;
          }
        }
      }
    }
  }

  return system_settings_form($form);
}