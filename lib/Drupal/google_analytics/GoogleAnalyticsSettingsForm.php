<?php
/**
 * @file
 * Contains \Drupal\google_analytics\Google_AnalyticsSettingsForm.
 */

namespace Drupal\google_analytics;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure Google_Analytics settings for this site.
 */
class GoogleAnalyticsSettingsForm extends SystemConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'google_analytics_admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('google_analytics.settings');
    $settings = $config->get();

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
    );

    $form['general']['google_analytics_account'] = array(
      '#title' => t('Web Property ID'),
      '#type' => 'textfield',
      '#default_value' => $settings['account'],
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#description' => t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href="@analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href="@webpropertyid">Find more information in the documentation</a>.', array('@analytics' => 'http://www.google.com/analytics/', '@webpropertyid' => url('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', array('fragment' => 'webProperty')))),
    );

    // Visibility settings.
    $form['tracking_scope'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Tracking scope'),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'google_analytics') . '/google_analytics.admin.js'),
      ),
      //'#tree' => TRUE,
    );

    $form['tracking']['domain_tracking'] = array(
      '#type' => 'details',
      '#title' => t('Domains'),
      '#group' => 'tracking_scope',
    );

    global $cookie_domain;
    $multiple_sub_domains = array();
    foreach (array('www', 'app', 'shop') as $subdomain) {
      if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
        $multiple_sub_domains[] = $subdomain . $cookie_domain;
      }
      // IP addresses or localhost.
      else {
        $multiple_sub_domains[] = $subdomain . '.example.com';
      }
    }

    $multiple_toplevel_domains = array();
    foreach (array('.com', '.net', '.org') as $tldomain) {
      $host = $_SERVER['HTTP_HOST'];
      $domain = substr($host, 0, strrpos($host, '.'));
      if (count(explode('.', $host)) > 2 && !is_numeric(str_replace('.', '', $host))) {
        $multiple_toplevel_domains[] = $domain . $tldomain;
      }
      // IP addresses or localhost
      else {
        $multiple_toplevel_domains[] = 'www.example' . $tldomain;
      }
    }

    $form['tracking']['domain_tracking']['google_analytics_domain_mode'] = array(
      '#type' => 'radios',
      '#title' => t('What are you tracking?'),
      '#options' => array(
        0 => t('A single domain (default)') . '<div class="description">' . t('Domain: @domain', array('@domain' => $_SERVER['HTTP_HOST'])) . '</div>',
        1 => t('One domain with multiple subdomains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_sub_domains))) . '</div>',
        2 => t('Multiple top-level domains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_toplevel_domains))) . '</div>',
      ),
      '#default_value' => $settings['domain_mode'],
    );
    $form['tracking']['domain_tracking']['google_analytics_cross_domains'] = array(
      '#title' => t('List of top-level domains'),
      '#type' => 'textarea',
      '#default_value' => $settings['cross_domains'],
      '#description' => t('If you selected "Multiple top-level domains" above, enter all related top-level domains. Add one domain per line. By default, the data in your reports only includes the path and name of the page, and not the domain name. For more information see section <em>Show separate domain names</em> in <a href="@url">Tracking Multiple Domains</a>.', array('@url' => url('http://support.google.com/analytics/bin/answer.py', array('query' => array('answer' => '1034342'))))),
    );

    // Page specific visibility configurations.
    $visibility = $settings['visibility'];
    $php_access = user_access('use PHP for tracking visibility');

    $form['tracking']['page_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Pages'),
      '#group' => 'tracking_scope',
    );

    if ($settings['visibility']['pages_enabled'] == 2 && !$php_access) {
      $form['tracking']['page_vis_settings'] = array();
      $form['tracking']['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
      $form['tracking']['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $settings['visibility']['pages']);
    }
    else {
      // @TODO: see BlockBase.php for upgrade
      $options = array(
        t('Every page except the listed pages'),
        t('The listed pages only'),
      );
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

      if (module_exists('php') && $php_access) {
        $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }
      $form['tracking']['page_vis_settings']['google_analytics_visibility_pages'] = array(
        '#type' => 'radios',
        '#title' => t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $settings['visibility']['pages_enabled'],
      );
      $form['tracking']['page_vis_settings']['google_analytics_pages'] = array(
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => !empty($settings['visibility']['pages']) ? $settings['visibility']['pages'] : '',
        '#description' => $description,
        '#rows' => 10,
      );
    }

    // Render the role overview.
    $form['tracking']['role_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Roles'),
      '#group' => 'tracking_scope',
    );

    $form['tracking']['role_vis_settings']['google_analytics_visibility_roles'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking for specific roles'),
      '#options' => array(
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ),
      '#default_value' => $settings['visibility']['roles_enabled'], // @FIXME rename variable
    );

    $role_options = array_map('check_plain', user_role_names());
    $form['tracking']['role_vis_settings']['google_analytics_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => !empty($settings['visibility']['roles']) ? $settings['visibility']['roles'] : array(),
      '#options' => $role_options,
      '#description' => t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    );

    // Standard tracking configurations.
    $form['tracking']['user_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Users'),
      '#group' => 'tracking_scope',
    );
    $t_permission = array('%permission' => t('opt-in or out of tracking'));
    $form['tracking']['user_vis_settings']['google_analytics_custom'] = array(
      '#type' => 'radios',
      '#title' => t('Allow users to customize tracking on their account page'),
      '#options' => array(
        t('No customization allowed'),
        t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ),
      '#default_value' => !empty($settings['visibility']['custom']) ? $settings['visibility']['custom'] : 0,
    );

    // Link specific configurations.
    $track = $config->get('track');
    $form['tracking']['linktracking'] = array(
      '#type' => 'details',
      '#title' => t('Links and downloads'),
      '#group' => 'tracking_scope',
    );
    $form['tracking']['linktracking']['google_analytics_trackoutbound'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track clicks on outbound links'),
      '#default_value' => $settings['track']['outbound'],
    );
    $form['tracking']['linktracking']['google_analytics_trackmailto'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track clicks on mailto links'),
      '#default_value' => $settings['track']['mailto'],
    );
    $form['tracking']['linktracking']['google_analytics_trackfiles'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track downloads (clicks on file links) for the following extensions'),
      '#default_value' => $settings['track']['files'],
    );
    $form['tracking']['linktracking']['google_analytics_trackfiles_extensions'] = array(
      '#title' => t('List of download file extensions'),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#default_value' => $settings['track']['files_extensions'],
      '#description' => t('A file extension list separated by the | character that will be tracked as download when clicked. Regular expressions are supported. For example: !extensions', array('!extensions' => GOOGLEANALYTICS_TRACKFILES_EXTENSIONS)),
      '#maxlength' => 255,
    );

    // Message specific configurations.
    $form['tracking']['messagetracking'] = array(
      '#type' => 'details',
      '#title' => t('Messages'),
      '#group' => 'tracking_scope',
    );
    $form['tracking']['messagetracking']['google_analytics_trackmessages'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Track messages of type'),
      '#default_value' => !empty($settings['track']['messages']) ? $settings['track']['messages'] : array(),
      '#description' => t('This will track the selected message types shown to users. Tracking of form validation errors may help you identifying usability issues in your site. For each visit (user session), a maximum of approximately 500 combined GATC requests (both events and page views) can be tracked. Every message is tracked as one individual event. Note that - as the number of events in a session approaches the limit - additional events might not be tracked. Messages from excluded pages cannot tracked.'),
      '#options' => array(
        'status' => t('Status message'),
        'warning' => t('Warning message'),
        'error' => t('Error message'),
      ),
    );

    // Google already have many translations, if not - they display a note to change the language.
    global $language;
    $form['tracking']['search_and_advertising'] = array(
      '#type' => 'details',
      '#title' => t('Search and Advertising'),
      '#group' => 'tracking_scope',
    );

    $site_search_dependencies = '<div class="admin-requirements">';
    $site_search_dependencies .= t('Requires: !module-list', array('!module-list' => (module_exists('search') ? t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => 'Search')) : t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => 'Search')))));
    $site_search_dependencies .= '</div>';

    $form['tracking']['search_and_advertising']['google_analytics_site_search'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track internal search'),
      '#description' => t('If checked, internal search keywords are tracked. You must configure your Google account to use the internal query parameter <strong>search</strong>. For more information see <a href="@url">Setting Up Site Search for a Profile</a>.', array('@url' => url('http://support.google.com/analytics/bin/answer.py', array('query' => array('answer' => '1012264'))))) . $site_search_dependencies,
      '#default_value' => $settings['track']['site_search'],
      '#disabled' => (module_exists('search') ? FALSE : TRUE),
    );
    /* @todo: not supported, https://support.google.com/analytics/bin/answer.py?hl=en&hlrm=de&answer=2795983
     $form['tracking']['search_and_advertising']['google_analytics_trackadsense'] = array(
       '#type' => 'checkbox',
       '#title' => t('Track AdSense ads'),
       '#description' => t('If checked, your AdSense ads will be tracked in your Google Analytics account.'),
       '#default_value' => $settings['track']['adsense'],
    );
    $form['tracking']['search_and_advertising']['google_analytics_trackdoubleclick'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track DoubleClick data'),
      '#description' => t('If checked, the alternative Google <a href="@doubleclick">DoubleClick data tracking</a> is used to enable AdWords remarketing features. If you choose this option you will need to <a href="@privacy">update your privacy policy</a>.', array('@doubleclick' => url('http://support.google.com/analytics/bin/answer.py', array('query' => array('answer' => '2444872'))), '@privacy' => url('http://support.google.com/analytics/bin/answer.py', array('query' => array('answer' => '2636405'))))),
      '#default_value' => $settings['track']['doubleclick'],
    ); */

    // Privacy specific configurations.
    $form['tracking']['privacy'] = array(
      '#type' => 'details',
      '#title' => t('Privacy'),
      '#group' => 'tracking_scope',
    );
    $form['tracking']['privacy']['google_analytics_tracker_anonymizeip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Anonymize visitors IP address'),
      '#description' => t('Tell Google Analytics to anonymize the information sent by the tracker objects by removing the last octet of the IP address prior to its storage. Note that this will slightly reduce the accuracy of geographic reporting. In some countries it is not allowed to collect personally identifying information for privacy reasons and this setting may help you to comply with the local laws.'),
      '#default_value' => $settings['privacy']['anonymizeip'],
    );
    $form['tracking']['privacy']['google_analytics_privacy_donottrack'] = array(
      '#type' => 'checkbox',
      '#title' => t('Universal web tracking opt-out'),
      '#description' => t('If enabled and your server receives the <a href="@donottrack">Do-Not-Track</a> header from the client browser, the Google Analytics module will not embed any tracking code into your site. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision. This feature is currently limited to logged in users and disabled page caching.', array('@donottrack' => 'http://donottrack.us/')),
      '#default_value' => $settings['privacy']['donottrack'],
    );

    // Custom variables.
    /* @todo: Update to custom dimensions.
     $form['google_analytics_custom_var'] = array(
       '#collapsed' => TRUE,
       '#collapsible' => TRUE,
       '#description' => t('You can add Google Analytics <a href="@custom_var_documentation">Custom Variables</a> here. These will be added to every page that Google Analytics tracking code appears on. Google Analytics will only accept custom variables if the <em>name</em> and <em>value</em> combined are less than 128 bytes after URL encoding. Keep the names as short as possible and expect long values to get trimmed. You may use tokens in custom variable names and values. Global and user tokens are always available; on node pages, node tokens are also available.', array('@custom_var_documentation' => 'https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingCustomVariables')),
       '#theme' => 'googleanalytics_admin_custom_var_table',
       '#title' => t('Custom variables'),
       '#tree' => TRUE,
       '#type' => 'fieldset',
     );

    $googleanalytics_custom_vars = $config->get('custom_var');

    // Google Analytics supports up to 5 custom variables.
    for ($i = 1; $i < 6; $i++) {
      $form['google_analytics_custom_var']['slots'][$i]['slot'] = array(
        '#default_value' => $i,
        '#description' => t('Slot number'),
        '#disabled' => TRUE,
        '#size' => 1,
        '#title' => t('Custom variable slot #@slot', array('@slot' => $i)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      );
      $form['google_analytics_custom_var']['slots'][$i]['name'] = array(
        '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['name']) ? $googleanalytics_custom_vars['slots'][$i]['name'] : '',
        '#description' => t('The custom variable name.'),
        '#maxlength' => 255,
        '#size' => 20,
        '#title' => t('Custom variable name #@slot', array('@slot' => $i)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => array('googleanalytics_token_element_validate'),
        '#token_types' => array('node'),
      );
      $form['google_analytics_custom_var']['slots'][$i]['value'] = array(
        '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['value']) ? $googleanalytics_custom_vars['slots'][$i]['value'] : '',
        '#description' => t('The custom variable value.'),
        '#maxlength' => 255,
        '#title' => t('Custom variable value #@slot', array('@slot' => $i)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => array('googleanalytics_token_element_validate'),
        '#token_types' => array('node'),
      );
      if (module_exists('token')) {
        $form['google_analytics_custom_var']['slots'][$i]['name']['#element_validate'][] = 'token_element_validate';
        $form['google_analytics_custom_var']['slots'][$i]['value']['#element_validate'][] = 'token_element_validate';
      }
      $form['google_analytics_custom_var']['slots'][$i]['scope'] = array(
        '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['scope']) ? $googleanalytics_custom_vars['slots'][$i]['scope'] : 3,
        '#description' => t('The scope for the custom variable.'),
        '#title' => t('Custom variable slot #@slot', array('@slot' => $i)),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => array(
          1 => t('Visitor'),
          2 => t('Session'),
          3 => t('Page'),
        ),
      );
    }

    $form['google_analytics_custom_var']['google_analytics_custom_var_description'] = array(
      '#type' => 'item',
      '#description' => t('You can supplement Google Analytics\' basic IP address tracking of visitors by segmenting users based on custom variables. Section 7 of the <a href="@ga_tos">Google Analytics terms of service</a> requires that You will not (and will not allow any third party to) use the Service to track, collect or upload any data that personally identifies an individual (such as a name, email address or billing information), or other data which can be reasonably linked to such information by Google. You will have and abide by an appropriate Privacy Policy and will comply with all applicable laws and regulations relating to the collection of information from Visitors. You must post a Privacy Policy and that Privacy Policy must provide notice of Your use of cookies that are used to collect traffic data, and You must not circumvent any privacy features (e.g., an opt-out) that are part of the Service.', array('@ga_tos' => 'http://www.google.com/analytics/terms/gb.html')),
    );
    $form['google_analytics_custom_var']['google_analytics_custom_var_token_tree'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('node'),
      '#dialog' => TRUE,
    ); */


    // Advanced feature configurations.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['advanced']['google_analytics_cache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Locally cache tracking code file'),
      '#description' => t("If checked, the tracking code file is retrieved from Google Analytics and cached locally. It is updated daily from Google's servers to ensure updates to tracking code are reflected in the local copy. Do not activate this until after Google Analytics has confirmed that site tracking is working!"),
      '#default_value' => $settings['cache'],
    );

    // Allow for tracking of the originating node when viewing translation sets.
    if (module_exists('translation')) {
      $form['advanced']['google_analytics_translation_set'] = array(
        '#type' => 'checkbox',
        '#title' => t('Track translation sets as one unit'),
        '#description' => t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
        '#default_value' => $settings['translation_set'],
      );
    }

    // @todo: Update urls once they are available.
    $form['advanced']['codesnippet'] = array(
      '#type' => 'details',
      '#title' => t('Custom JavaScript code'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t('You can add custom Google Analytics <a href="@snippets">code snippets</a> here. These will be added every time tracking is in effect. Before you add your custom code, you should read the <a href="@ga_concepts_overview">Google Analytics Tracking Code - Functional Overview</a> and the <a href="@ga_js_api">Google Analytics Tracking API</a> documentation. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://drupal.org/node/248699', '@ga_concepts_overview' => 'https://developers.google.com/analytics/resources/concepts/gaConceptsTrackingOverview', '@ga_js_api' => 'https://developers.google.com/analytics/devguides/collection/gajs/methods/')),
    );
    $form['advanced']['codesnippet']['google_analytics_codesnippet_before'] = array(
      '#type' => 'textarea',
      '#title' => t('Code snippet (before)'),
      '#default_value' => $settings['codesnippet']['before'],
      '#rows' => 5,
      '#description' => t("Code in this textarea will be added <strong>before</strong> <code>ga('send', 'pageview');</code>."),
    );
    $form['advanced']['codesnippet']['google_analytics_codesnippet_after'] = array(
      '#type' => 'textarea',
      '#title' => t('Code snippet (after)'),
      '#default_value' => $settings['codesnippet']['after'],
      '#rows' => 5,
      '#description' => t("Code in this textarea will be added <strong>after</strong> <code>ga('send', 'pageview');</code>. This is useful if you'd like to track a site in two accounts."),
    );

    $form['advanced']['google_analytics_js_scope'] = array(
      '#type' => 'select',
      '#title' => t('JavaScript scope'),
      '#description' => t('Google recommends adding the external JavaScript files to the header for performance reasons. If <em>Multiple top-level domains</em> has been selected, this setting will be forced to header.'),
      '#options' => array(
        'footer' => t('Footer'),
        'header' => t('Header'),
      ),
      '#default_value' => $settings['js_scope'],
      '#disabled' => ($settings['domain_mode'] == 2) ? TRUE : FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    // Custom variables validation.
    /* @todo: upgrade to custom dimensions
    foreach ($form_state['values']['google_analytics_custom_var']['slots'] as $custom_var) {
      $form_state['values']['google_analytics_custom_var']['slots'][$custom_var['slot']]['name'] = trim($custom_var['name']);
      $form_state['values']['google_analytics_custom_var']['slots'][$custom_var['slot']]['value'] = trim($custom_var['value']);

      // Validate empty names/values.
      if (empty($custom_var['name']) && !empty($custom_var['value'])) {
        form_set_error("googleanalytics_custom_var][slots][" . $custom_var['slot'] . "][name", t('The custom variable @slot-number requires a <em>Name</em> if a <em>Value</em> has been provided.', array('@slot-number' =>  $custom_var['slot'])));
      }
      elseif (!empty($custom_var['name']) && empty($custom_var['value'])) {
        form_set_error("googleanalytics_custom_var][slots][" . $custom_var['slot'] . "][value", t('The custom variable @slot-number requires a <em>Value</em> if a <em>Name</em> has been provided.', array('@slot-number' =>  $custom_var['slot'])));
      }
    } */

    // Trim some text values.
    $form_state['values']['google_analytics_account'] = trim($form_state['values']['google_analytics_account']);
    $form_state['values']['google_analytics_pages'] = trim($form_state['values']['google_analytics_pages']);
    $form_state['values']['google_analytics_cross_domains'] = trim($form_state['values']['google_analytics_cross_domains']);
    $form_state['values']['google_analytics_codesnippet_before'] = trim($form_state['values']['google_analytics_codesnippet_before']);
    $form_state['values']['google_analytics_codesnippet_after'] = trim($form_state['values']['google_analytics_codesnippet_after']);
    $form_state['values']['google_analytics_roles'] = array_filter($form_state['values']['google_analytics_roles']);
    $form_state['values']['google_analytics_trackmessages'] = array_filter($form_state['values']['google_analytics_trackmessages']);

    // Replace all type of dashes (n-dash, m-dash, minus) with the normal dashes.
    $form_state['values']['google_analytics_account'] = str_replace(array('–', '—', '−'), '-', $form_state['values']['google_analytics_account']);

    if (!preg_match('/^UA-\d{4,}-\d+$/', $form_state['values']['google_analytics_account'])) {
      form_set_error('google_analytics_account', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
    }

    // If multiple top-level domains has been selected, a domain names list is required.
    if ($form_state['values']['google_analytics_domain_mode'] == 2 && empty($form_state['values']['google_analytics_cross_domains'])) {
      form_set_error('google_analytics_cross_domains', t('A list of top-level domains is required if <em>Multiple top-level domains</em> has been selected.'));
    }
    // Clear obsolete local cache if cache has been disabled.
    if (empty($form_state['values']['google_analytics_cache']) && $form['advanced']['google_analytics_cache']['#default_value']) {
      google_analytics_clear_js_cache();
    }

    // This is for the Newbie's who cannot read a text area description.
    if (stristr($form_state['values']['google_analytics_codesnippet_before'], 'google-analytics.com/analytics.js')) {
      form_set_error('google_analytics_codesnippet_before', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
    }
    if (stristr($form_state['values']['google_analytics_codesnippet_after'], 'google-analytics.com/analytics.js')) {
      form_set_error('google_analytics_codesnippet_after', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
    }
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['google_analytics_codesnippet_before'])) {
      form_set_error('google_analytics_codesnippet_before', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
    if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['google_analytics_codesnippet_after'])) {
      form_set_error('google_analytics_codesnippet_after', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }

    // Header section must be forced for multiple top-level domains.
    if ($form_state['values']['google_analytics_domain_mode'] == 2) {
      $form_state['values']['google_analytics_js_scope'] = 'header';
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->configFactory->get('google_analytics.settings');
    $config
      ->set('account', $form_state['values']['google_analytics_account'])
      ->set('cross_domains', $form_state['values']['google_analytics_cross_domains'])
      //->set('custom_var', $form_state['values']['google_analytics_custom_var'])
      ->set('codesnippet.before', $form_state['values']['google_analytics_codesnippet_before'])
      ->set('codesnippet.after', $form_state['values']['google_analytics_codesnippet_after'])
      ->set('domain_mode', $form_state['values']['google_analytics_domain_mode'])
      ->set('track.files', $form_state['values']['google_analytics_trackfiles'])
      ->set('track.files_extensions', $form_state['values']['google_analytics_trackfiles_extensions'])
      ->set('track.mailto', $form_state['values']['google_analytics_trackmailto'])
      ->set('track.messages', $form_state['values']['google_analytics_trackmessages'])
      ->set('track.outbound', $form_state['values']['google_analytics_trackmailto'])
      ->set('track.site_search', $form_state['values']['google_analytics_site_search'])
      //->set('track.adsense', $form_state['values']['google_analytics_trackadsense'])
      //->set('track.doubleclick', $form_state['values']['google_analytics_trackdoubleclick'])
      ->set('privacy.anonymizeip', $form_state['values']['google_analytics_tracker_anonymizeip'])
      ->set('privacy.donottrack', $form_state['values']['google_analytics_privacy_donottrack'])
      ->set('js_scope', $form_state['values']['google_analytics_js_scope'])
      ->set('cache', $form_state['values']['google_analytics_cache'])
      ->set('visibility.pages_enabled', $form_state['values']['google_analytics_visibility_pages'])
      ->set('visibility.pages', $form_state['values']['google_analytics_pages'])
      ->set('visibility.roles_enabled', $form_state['values']['google_analytics_visibility_roles'])
      ->set('visibility.roles', $form_state['values']['google_analytics_roles'])
      ->set('visibility.custom', $form_state['values']['google_analytics_custom'])
      ->save();

    if (isset($form_state['values']['google_analytics_translation_set'])) {
      $config->set('translation_set', $form_state['values']['google_analytics_translation_set'])->save();
    }

    parent::submitForm($form, $form_state);
  }

}
