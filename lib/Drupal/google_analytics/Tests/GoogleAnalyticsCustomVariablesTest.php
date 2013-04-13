<?php

/**
 * @file
 * Test file for Google Analytics module.
 */
/* @todo: upgrade to custom dimensions
class GoogleAnalyticsCustomVariablesTest extends DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => t('Google Analytics Custom Variables tests'),
      'description' => t('Test custom variables functionality of Google Analytics module.'),
      'group' => 'Google Analytics',
      'dependencies' => array('token'),
    );
  }

  function setUp() {
    parent::setUp('googleanalytics', 'token');

    $permissions = array(
      'access administration pages',
      'administer google analytics',
    );

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  function testGoogleAnalyticsCustomVariables() {
    $ua_code = 'UA-123456-3';
    variable_set('googleanalytics_account', $ua_code);

    // Basic test if the feature works.
    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Foo 1',
          'value' => 'Bar 1',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => 'Foo 2',
          'value' => 'Bar 2',
          'scope' => 2,
        ),
        3 => array(
          'slot' => 3,
          'name' => 'Foo 3',
          'value' => 'Bar 3',
          'scope' => 3,
        ),
        4 => array(
          'slot' => 4,
          'name' => 'Foo 4',
          'value' => 'Bar 4',
          'scope' => 2,
        ),
        5 => array(
          'slot' => 5,
          'name' => 'Foo 5',
          'value' => 'Bar 5',
          'scope' => 1,
        ),
      )
    );
    variable_set('googleanalytics_custom_var', $custom_vars);
    $this->drupalGet('');

    foreach ($custom_vars['slots'] as $slot) {
      $this->assertRaw("ga('set', 'customvar', " . $slot['slot'] . ", \"" . $slot['name'] . "\", \"" . $slot['value'] . "\", " . $slot['scope'] . ");", '[testGoogleAnalyticsCustomVariables]: _setCustomVar ' . $slot['slot'] . ' is shown.');
    }

    // Test whether tokens are replaced in custom variable names.
    $site_slogan = $this->randomName(16);
    variable_set('site_slogan', $site_slogan);

    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Name: [site:slogan]',
          'value' => 'Value: [site:slogan]',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => '',
          'value' => $this->randomName(16),
          'scope' => 1,
        ),
        3 => array(
          'slot' => 3,
          'name' => $this->randomName(16),
          'value' => '',
          'scope' => 2,
        ),
        4 => array(
          'slot' => 4,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
        5 => array(
          'slot' => 5,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
      )
    );
    variable_set('googleanalytics_custom_var', $custom_vars);
    $this->verbose('<pre>' . print_r($custom_vars, TRUE) . '</pre>');

    $this->drupalGet('');
    $this->assertRaw("ga('set', 'customvar', 1, \"Name: $site_slogan\", \"Value: $site_slogan\", 3);", '[testGoogleAnalyticsCustomVariables]: Tokens have been replaced in custom variable.');
    $this->assertNoRaw("ga('set', 'customvar', 2,", '[testGoogleAnalyticsCustomVariables]: Value with empty name is not shown.');
    $this->assertNoRaw("ga('set', 'customvar', 3,", '[testGoogleAnalyticsCustomVariables]: Name with empty value is not shown.');
    $this->assertNoRaw("ga('set', 'customvar', 4,", '[testGoogleAnalyticsCustomVariables]: Empty name and value is not shown.');
    $this->assertNoRaw("ga('set', 'customvar', 5,", '[testGoogleAnalyticsCustomVariables]: Empty name and value is not shown.');
  }
} */
