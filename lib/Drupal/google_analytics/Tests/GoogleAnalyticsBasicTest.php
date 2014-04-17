<?php

/**
 * @file
 * Contains \Drupal\google_analytics\Tests\GoogleAnalyticsBasicTest.
 */

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

class GoogleAnalyticsBasicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('google_analytics');

  public static function getInfo() {
    return array(
      'name' => 'Google Analytics basic tests',
      'description' => 'Test basic functionality of Google Analytics module.',
      'group' => 'Google Analytics',
    );
  }

  function setUp() {
    parent::setUp();

    $permissions = array(
      'access administration pages',
      'administer google analytics',
    );

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  function testGoogleAnalyticsConfiguration() {
    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/google-analytics');
    $this->assertRaw(t('Web Property ID'), '[testGoogleAnalyticsConfiguration]: Settings page displayed.');

    // Check for account code validation.
    $edit['google_analytics_account'] = $this->randomName(2);
    $this->drupalPostForm('admin/config/system/google-analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'), '[testGoogleAnalyticsConfiguration]: Invalid Web Property ID number validated.');
  }

  function testGoogleAnalyticsPageVisibility() {
    $ua_code = 'UA-123456-1';
    \Drupal::config('google_analytics.settings')->set('account', $ua_code)->save();

    // Show tracking on "every page except the listed pages".
    \Drupal::config('google_analytics.settings')->set('visibility.pages_enable', 0)->save();
    // Disable tracking one "admin*" pages only.
    \Drupal::config('google_analytics.settings')->set('visibility.pages', "admin\nadmin/*")->save();
    // Enable tracking only for authenticated users only.
    \Drupal::config('google_analytics.settings')->set('visibility.roles', array(DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID))->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($ua_code, '[testGoogleAnalyticsPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw($ua_code, '[testGoogleAnalyticsPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/google-analytics');
    // Checking for tracking code URI here, as $ua_code is displayed in the form.
    $this->assertNoRaw('google-analytics.com/analytics.js', '[testGoogleAnalyticsPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    \Drupal::config('google_analytics.settings')->set('visibility.pages_enabled', 1)->save();
    $this->drupalGet('admin');
    $this->assertRaw($ua_code, '[testGoogleAnalyticsPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/google-analytics');
    // Checking for tracking code URI here, as $ua_code is displayed in the form.
    $this->assertRaw('google-analytics.com/analytics.js', '[testGoogleAnalyticsPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw($ua_code, '[testGoogleAnalyticsPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw($ua_code, '[testGoogleAnalyticsPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    \Drupal::config('google_analytics.settings')->set('visibility.pages_enabled', 0)->save();
    // Enable tracking code for all user roles.
    \Drupal::config('google_analytics.settings')->set('visibility.roles', array())->save();

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertRaw('/403.html', '[testGoogleAnalyticsPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomName(64));
    $this->assertRaw('/404.html', '[testGoogleAnalyticsPageVisibility]: 404 Not Found tracking code shown on non-existent page.');

    // DNT Tests:
    // Enable caching of pages for anonymous users.
    \Drupal::config('google_analytics.settings')->set('cache', 1)->save();
    // Test whether DNT headers will fail to disable embedding of tracking code.
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertRaw('ga("send", "pageview");', '[testGoogleAnalyticsDNTVisibility]: DNT header send from client, but page caching is enabled and tracker cannot removed.');
    // DNT works only with caching of pages for anonymous users disabled.
    \Drupal::config('google_analytics.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertRaw('ga("send", "pageview");', '[testGoogleAnalyticsDNTVisibility]: Tracking is enabled without DNT header.');
    // Test whether DNT header is able to remove the tracking code.
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertNoRaw('ga("send", "pageview");', '[testGoogleAnalyticsDNTVisibility]: DNT header received from client. Tracking has been disabled by browser.');
    // Disable DNT feature and see if tracker is still embedded.
    \Drupal::config('google_analytics.settings')->set('privacy.donottrack', 0)->save();
    $this->drupalGet('', array(), array('DNT: 1'));
    $this->assertRaw('ga("send", "pageview");', '[testGoogleAnalyticsDNTVisibility]: DNT feature is disabled, DNT header from browser has been ignored.');
  }

  function testGoogleAnalyticsTrackingCode() {
    $ua_code = 'UA-123456-2';
    \Drupal::config('google_analytics.settings')->set('account', $ua_code)->save();

    // Show tracking code on every page except the listed pages.
    \Drupal::config('google_analytics.settings')->set('visibility.pages_enabled', 0)->save();
    // Enable tracking code for all user roles.
    \Drupal::config('google_analytics.settings')->set('visibility.roles', array())->save();

    /* Sample JS code as added to page:
    <script type="text/javascript" src="/sites/all/modules/google_analytics/google_analytics.js?w"></script>
    <script>
    (function(i,s,o,g,r,a,m){
    i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,"script","//www.google-analytics.com/analytics.js","ga");
    ga('create', 'UA-123456-7');
    ga('send', 'pageview');
    </script>
    <!-- End Google Analytics -->
    */

    // Test whether tracking code uses latest JS.
    \Drupal::config('google_analytics.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertRaw('google-analytics.com/analytics.js', '[testGoogleAnalyticsTrackingCode]: Latest tracking code used.');

    // Test whether anonymize visitors IP address feature has been enabled.
    $this->drupalGet('');
    $this->assertNoRaw('ga("set", "anonymizeIp", 1);', '[testGoogleAnalyticsTrackingCode]: Anonymize visitors IP address not found on frontpage.');
    // Enable anonymizing of IP addresses.
    \Drupal::config('google_analytics.settings')->set('privacy.anonymizeip', 1)->save();
    $this->drupalGet('');
    $this->assertRaw('ga("set", "anonymizeIp", 1);', '[testGoogleAnalyticsTrackingCode]: Anonymize visitors IP address found on frontpage.');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertNoRaw('ga("set", "cookieDomain",', '[testGoogleAnalyticsTrackingCode]: Single domain tracking is active.');

    // Enable "One domain with multiple subdomains".
    \Drupal::config('google_analytics.settings')->set('domain_mode', 1)->save();
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    // TODO: Workaround to run tests successfully. This feature cannot tested reliable.
    global $cookie_domain;
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $this->assertRaw('ga("set", "cookieDomain",', '[testGoogleAnalyticsTrackingCode]: One domain with multiple subdomains is active on real host.');
    }
    else {
      // Special cases, Localhost and IP addresses don't show '_setDomainName'.
      $this->assertNoRaw('ga("set", "cookieDomain",', '[testGoogleAnalyticsTrackingCode]: One domain with multiple subdomains may be active on localhost (test result is not reliable).');
    }

    // Enable "Multiple top-level domains" tracking.
    \Drupal::config('google_analytics.settings')->set('domain_mode', 2)->save();
    \Drupal::config('google_analytics.settings')->set('cross_domains', "www.example.com\nwww.example.net")->save();
    $this->drupalGet('');
    $this->assertRaw('ga("set", "cookieDomain", "none");', '[testGoogleAnalyticsTrackingCode]: _setDomainName: "none" found. Cross domain tracking is active.');
    $this->assertRaw('ga("set", "allowLinker", true);', '[testGoogleAnalyticsTrackingCode]: _setAllowLinker: true found. Cross domain tracking is active.');
    $this->assertRaw('"trackCrossDomains":["www.example.com","www.example.net"]', '[testGoogleAnalyticsTrackingCode]: Cross domain tracking with www.example.com and www.example.net is active.');

    // Test whether the BEFORE and AFTER code is added to the tracker.
    // @todo: review detectFlash once API docs are available.
    \Drupal::config('google_analytics.settings')->set('codesnippet.before', 'ga("set", "detectFlash", false);')->save();
    \Drupal::config('google_analytics.settings')->set('codesnippet.after', 'ga("create", "UA-123456-3", {name: "newTracker"});ga("newTracker.send", "pageview");')->save();
    $this->drupalGet('');
    $this->assertRaw('ga("set", "detectFlash", false);', '[testGoogleAnalyticsTrackingCode]: Before codesnippet has been found with "Flash" detection disabled.');
    $this->assertRaw('ga("create", "UA-123456-3", {name: "newTracker"});', '[testGoogleAnalyticsTrackingCode]: After codesnippet with "newTracker" tracker has been found.');
  }
}
