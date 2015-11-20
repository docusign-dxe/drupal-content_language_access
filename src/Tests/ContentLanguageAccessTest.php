<?php

/**
 * @file
 * Test suite for content language access module.
 */

namespace Drupal\content_language_access\Tests;

use Drupal;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test the features of content_language_access module.
 *
 * @group content_language_access
 */
class ContentLanguageAccessTest extends WebTestBase {

  /**
   * Drupal installation profile to use.
   *
   * @var string $profile
   */
  protected $profile = 'standard';

  /**
   * Modules to install.
   *
   * @var array $modules
   */
  public static $modules = ['node', 'locale', 'content_language_access'];

  /**
   * A simple user with 'access content' permission.
   *
   * @var \Drupal\user\Entity\User $adminUser
   */
  private $adminUser;

  /**
   * A simple user with 'access content' permission.
   *
   * @var \Drupal\user\Entity\User $visitor
   */
  private $visitor;

  /**
   * Content type created for tests.
   *
   * @var \Drupal\node\Entity\NodeType $contentType
   */
  private $contentType;

  /**
   * Contents created.
   *
   * @var \Drupal\node\NodeInterface $nodes
   */
  private $nodes;

  /**
   * List of current languages.
   *
   * @var LanguageInterface[] $languages
   */
  private $languages;

  /**
   * Implements setUp().
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'administer site configuration',
      'access administration pages',
      'administer content types',
      'administer nodes',
      'administer users',
    ]);
    $this->visitor = $this->drupalCreateUser(['access content']);

    /** @var LanguageInterface[] $languages */
    $this->languages = Drupal::languageManager()->getLanguages();

    $this->configureLanguages();

    $this->createContentType();
    $this->createContents();
  }

  /**
   * Creates the languages for the test execution.
   */
  protected function configureLanguages() {
    $this->drupalLogin($this->adminUser);

    $this->addLanguage('aaa');
    $this->addLanguage('bbb');
  }

  /**
   * Creates a random content type for test execution.
   */
  protected function createContentType() {
    $this->contentType = $this->drupalCreateContentType();
  }

  /**
   * Creates a content for each language for the tests.
   */
  protected function createContents() {
    $this->drupalLogin($this->adminUser);

    /** @var LanguageInterface[] $languages */
    $languages = $this->getLanguageList();

    foreach ($languages as $language_key => $language) {
      $settings = [];
      $settings['title'] = 'Test ' . $language->getName();
      $settings['body'][$language_key][0] = [];
      $settings['language'] = $language_key;

      $this->nodes[$language_key] = $this->drupalCreateNode($settings);
    }
  }

  /**
   * Returns the list of languages available.
   *
   * @param bool $with_neutral_language
   *   Optional, specifies if the function needs to return also the neutral
   *   language.
   *
   * @return LanguageInterface[]
   *   With all the languages available (plus the neutral language)
   */
  protected function getLanguageList($with_neutral_language = TRUE) {
    /** @var LanguageInterface[] $languages */
    // Problems with cache of Drupal::languageManager()->getLanguages()
    $languages = $this->languages;

    if ($with_neutral_language) {
      $languages[Language::LANGCODE_NOT_SPECIFIED] = new Language([
        'id' => Language::LANGCODE_NOT_SPECIFIED,
        'name' => 'Language Neutral',
      ]);
    }

    return $languages;
  }

  /**
   * Enables the specified language if it has not been already.
   *
   * @param string $language_code
   *   The language code to enable.
   */
  protected function addLanguage($language_code) {
    // Check to make sure that language has not already been installed.
    $this->drupalGet('admin/config/regional/language');

    if (strpos($this->getTextContent(), 'edit-languages-' . $language_code) === FALSE) {
      // Doesn't have language installed so add it.
      $edit = [];
      $edit['predefined_langcode'] = 'custom';
      $edit['langcode'] = $language_code;
      $edit['label'] = $language_code;
      $edit['direction'] = LanguageInterface::DIRECTION_LTR;
      $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

      $this->languages[$language_code] = new Language([
        'id' => $language_code,
        'name' => $language_code,
      ]);
    }
  }

  /**
   * Tests each content in each language.
   */
  public function testContentLanguageAccess() {
    $this->drupalLogin($this->visitor);

    /** @var \Drupal\Core\Language\LanguageInterface[] $languages */
    $languages = $this->getLanguageList(FALSE);

    $this->verbose(print_r($languages, 1));
    foreach ($this->nodes as $node) {
      foreach ($languages as $language) {
        // English is the default language and does not have prefix.
        if ($language->getId() != Drupal::languageManager()
            ->getDefaultLanguage()
            ->getId()
        ) {
          $prefix = $language->getId() . '/';
        }
        else {
          $prefix = '';
        }

        $this->drupalGet($prefix . 'node/' . $node->id());

        $node_languages = $node->getTranslationLanguages();

        if (!isset($node_languages[Language::LANGCODE_NOT_SPECIFIED]) ||
          !isset($node_languages[Language::LANGCODE_NOT_APPLICABLE]) ||
          isset($node_languages[$language->getId()])
        ) {
          $this->assertResponse(200);
        }
        else {
          $this->assertResponse(403);
        }
      }
    }
  }

}
