<?php

/**
 * @file
 * Contains \Drupal\google_analytics\Tests\GoogleAnalyticsPhpFilterTest.
 */

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

class GoogleAnalyticsPhpFilterTest extends DrupalWebTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('google_analytics', 'php');

  public static function getInfo() {
    return array(
      'name' => 'Google Analytics PhpFilter tests',
      'description' => 'Test PhpFilter functionality of Google Analytics module.',
      'group' => 'Google Analytics',
    );
  }

  function setUp() {
    parent::setUp();

    // Administrator with all permissions.
    $permissions_admin_user = array(
      'access administration pages',
      'administer google analytics',
      'use PHP for tracking visibility',
    );
    $this->admin_user = $this->drupalCreateUser($permissions_admin_user);

    // Administrator who cannot configure tracking visibility with PHP.
    $permissions_delegated_admin_user = array(
      'access administration pages',
      'administer google analytics',
    );
    $this->delegated_admin_user = $this->drupalCreateUser($permissions_delegated_admin_user);
  }

  function testGoogleAnalyticsPhpFilter() {
    $ua_code = 'UA-123456-1';
    $this->drupalLogin($this->admin_user);

    $edit = array();
    $edit['googleanalytics_account'] = $ua_code;
    $edit['googleanalytics_visibility_pages'] = 2;
    $edit['googleanalytics_pages'] = '<?php return 0; ?>';
    $this->drupalPost('admin/config/system/google-analytics', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $googleanalytics_pages = variable_get('googleanalytics_pages', $this->randomName(8));
    $this->assertEqual('<?php return 0; ?>', $googleanalytics_pages, '[testGoogleAnalyticsPhpFilter]: PHP code snippet is intact.');

    // Check tracking code visibility.
    variable_set('googleanalytics_pages', '<?php return TRUE; ?>');
    $this->drupalGet('');
    $this->assertRaw('//www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is displayed on frontpage page.');
    $this->drupalGet('admin');
    $this->assertRaw('//www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is displayed on admin page.');

    variable_set('googleanalytics_pages', '<?php return FALSE; ?>');
    $this->drupalGet('');
    $this->assertNoRaw('//www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is not displayed on frontpage page.');

    // Test administration form.
    variable_set('googleanalytics_pages', '<?php return TRUE; ?>');
    $this->drupalGet('admin/config/system/google-analytics');
    $this->assertRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testGoogleAnalyticsPhpFilter]: Permission to administer PHP for tracking visibility.');
    $this->assertRaw(check_plain('<?php return TRUE; ?>'), '[testGoogleAnalyticsPhpFilter]: PHP code snippted is displayed.');

    // Login the delegated user and check if fields are visible.
    $this->drupalLogin($this->delegated_admin_user);
    $this->drupalGet('admin/config/system/google-analytics');
    $this->assertNoRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testGoogleAnalyticsPhpFilter]: No permission to administer PHP for tracking visibility.');
    $this->assertNoRaw(check_plain('<?php return TRUE; ?>'), '[testGoogleAnalyticsPhpFilter]: No permission to view PHP code snippted.');

    // Set a different value and verify that this is still the same after the post.
    variable_set('googleanalytics_pages', '<?php return 0; ?>');

    $edit = array();
    $edit['googleanalytics_account'] = $ua_code;
    $this->drupalPost('admin/config/system/google-analytics', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $googleanalytics_visibility_pages = variable_get('googleanalytics_visibility_pages', 0);
    $googleanalytics_pages = variable_get('googleanalytics_pages', $this->randomName(8));
    $this->assertEqual(2, $googleanalytics_visibility_pages, '[testGoogleAnalyticsPhpFilter]: Pages on which this PHP code returns TRUE is selected.');
    $this->assertEqual('<?php return 0; ?>', $googleanalytics_pages, '[testGoogleAnalyticsPhpFilter]: PHP code snippet is intact.');
  }

}
