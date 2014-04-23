<?php

/**
 * @file
 * Contains \Drupal\google_analytics\Tests\GoogleAnalyticsUninstallTest.
 */

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

class GoogleAnalyticsUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('google_analytics');

  public static function getInfo() {
    return array(
      'name' => 'Google Analytics uninstall tests',
      'description' => 'Test uninstall functionality of Google Analytics module.',
      'group' => 'Google Analytics',
    );
  }

  function setUp() {
    parent::setUp();

    $permissions = array(
      'access administration pages',
      'administer google analytics',
      'administer modules',
    );

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  function testGoogleAnalyticsUninstall() {
    $cache_path = 'public://google_analytics';
    $ua_code = 'UA-123456-1';

    // Show tracker in pages.
    \Drupal::config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable local caching of analytics.js
    \Drupal::config('google_analytics.settings')->set('cache', 1)->save();

    // Load page to get the analytics.js downloaded into local cache.
    $this->drupalGet('');

    // Test if the directory and analytics.js exists.
    $this->assertTrue(file_prepare_directory($cache_path), 'Cache directory "public://google_analytics" has been found.');
    $this->assertTrue(file_exists($cache_path . '/analytics.js'), 'Cached analytics.js tracking file has been found.');

    // Uninstall the module.
    $edit = array();
    $edit['uninstall[google_analytics]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Test if the directory and all files have been removed.
    $this->assertFalse(file_scan_directory($cache_path, '/.*/'), 'Cached JavaScript files have been removed.');
    $this->assertFalse(file_prepare_directory($cache_path), 'Cache directory "public://google_analytics" has been removed.');
  }

}
