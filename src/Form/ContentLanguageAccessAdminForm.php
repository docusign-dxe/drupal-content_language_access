<?php

/**
 * @file
 * Contains \Drupal\content_language_access\Form\ContentLanguageAccessAdminForm.
 */

namespace Drupal\content_language_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Administration form for content language access module.
 */
class ContentLanguageAccessAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_language_access_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('content_language_access.settings');

    foreach (Element::children($form['content_language_access']) as $group) {
      foreach (Element::children($form['content_language_access'][$group]) as $variable) {
        $config->set($variable, (bool) $form_state->getValue($variable));
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_language_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state = NULL) {
    $form = [];

    $config = $this->config('content_language_access.settings');

    $form['content_language_access'] = [
      '#type' => 'details',
      '#title' => t('Permissions'),
      '#open' => TRUE,
    ];

    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $language) {
      if (!$language->isLocked()) {
        $form['content_language_access'][$language->getId()] = [
          '#type' => 'details',
          '#title' => t('Drupal language: @language', [
            '@language' => $language->getName(),
          ]),
          '#open' => TRUE,
        ];
        foreach ($languages as $language_perm) {
          if (!$language_perm->isLocked()) {
            $form['content_language_access'][$language->getId()][$language->getId() . '_' . $language_perm->getId()] = [
              '#type' => 'checkbox',
              '#title' => t('Content language: @language', [
                '@language' => $language_perm->getName(),
              ]),
              '#default_value' => (bool) $config->get($language->getId() . '_' . $language_perm->getId()),
            ];

            // Only shows the same language for better visualization.
            if ($language->getId() == $language_perm->getId()) {
              $form['content_language_access'][$language->getId()][$language->getId() . '_' . $language_perm->getId()]['#disabled'] = TRUE;
              $form['content_language_access'][$language->getId()][$language->getId() . '_' . $language_perm->getId()]['#value'] = TRUE;
            }
          }
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

}
