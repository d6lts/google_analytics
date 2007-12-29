<?php
// $Id$
/**
 * @file
 * Administrative page callbacks for the googleanalytics module.
 */

/**
 * Implementation of hook_admin_settings() for configuring the module
 */
function googleanalytics_admin_settings() {
  $form['account'] = array(
    '#type' => 'fieldset',
    '#title' => t('Analytics Account Settings'),
    '#collapsible' => FALSE,
  );

  $form['account']['googleanalytics_account'] = array(
    '#type' => 'textfield',
    '#title' => t('User ID'),
    '#default_value' => variable_get('googleanalytics_account', 'UA-'),
    '#size' => 15,
    '#maxlength' => 20,
    '#required' => TRUE,
    '#description' => t('The user account is unique to the websites domain. You can obtain a user account from the <a href="@url">Google Analytics</a> website.', array('@url' => 'http://www.google.com/analytics/'))
  );

  $form['account']['googleanalytics_version'] = array(
    '#type' => 'checkbox',
    '#title' => t('Legacy Google Analytics'),
    '#default_value' => variable_get('googleanalytics_version', FALSE),
    '#description' => t('This will enable Legacy Google Analytics instead of most recent.<br /> Please note that Legacy Google Analytics will not receive feature updates and is not compatible with new features.'),
  );

  // Render the role overview.
  $result = db_query('SELECT * FROM {role} ORDER BY name');

  $form['roles'] = array(
    '#type' => 'fieldset',
    '#title' => t('User Role Tracking'),
    '#collapsible' => TRUE,
    '#description' => t('Define what user roles should be tracked by Google Analytics.'),
  );

  $form['roles']['googleanalytics_track__user1'] = array(
    '#type' => 'checkbox',
    '#title' => t('Admin (user 1)'),
    '#default_value' => variable_get('googleanalytics_track__user1', TRUE),
  );

  while ($role = db_fetch_object($result)) {
    // can't use empty spaces in varname
    $role_varname = $string = str_replace(' ', '_', $role->name);
    $form['roles']['googleanalytics_track_'. $role->rid] = array(
      '#type' => 'checkbox',
      '#title' => t($role->name),
      '#default_value' => variable_get('googleanalytics_track_'. $role->rid, FALSE),
    );
  }

  $form['segmentation'] = array(
    '#type' => 'fieldset',
    '#title' => t('User Segmentation'),
    '#collapsible' => TRUE,
    '#description' => t('If your users have profile fields completed, you can track your logged in users based on a defined profile field.')
  );

  if (!module_exists('profile')) {
    $form['segmentation']['profile'] = array(
      '#type' => 'markup',
      '#value' => t('You need to activate the !profile to use this feature.', array('!profile' => l(t('Profile module'), 'admin/build/modules'))),
      '#prefix' => '<p>',
      '#suffix' => '</p>'
      );
  }
  else {
    // Compile a list of fields to show.
    $fields = array(
      'uid' => t('User ID'),
      'name' => t('Username'),
      'roles' => t('User Roles')
    );
    $result = db_query('SELECT name, title, type, weight FROM {profile_fields} ORDER BY weight');
    while ($record = db_fetch_object($result)) {
      $fields[$record->name] = $record->title;
    }

    $form['segmentation']['googleanalytics_segmentation'] = array(
      '#type' => 'select',
      '#title' => t('Track'),
      '#description' => t('Selecting one or more values allows you to track users by profile values rather than simply an IP address. To select multiple items, hold down CTRL whilst selecting fields.'),
      '#default_value' => variable_get('googleanalytics_segmentation', ''),
      '#options' => $fields,
      '#size' => 10,
      '#multiple' => TRUE
    );
  }

  $form['googleanalytics_trackfiles'] = array(
    '#type' => 'textfield',
    '#title' => t('File Extensions To Track'),
    '#default_value' => variable_get('googleanalytics_trackfiles', GA_TRACKFILES),
    '#description' => t('A pipe seperated list of file extensions that should be tracked when clicked. Example !extensions', array('!extensions' => GA_TRACKFILES))
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('You can add custom Google Analytics code here.')
  );

  $form['advanced']['googleanalytics_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Cache tracking code file locally'),
    '#description' => t("If checked, the tracking code file is received from Google Analytics and cached locally. It is updated daily from Google's servers to ensure updates to tracking code are reflected in the local copy."),
    '#default_value' => variable_get('googleanalytics_cache', 0),
  );
  if (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PRIVATE) {
    $form['advanced']['googleanalytics_cache']['#disabled'] = TRUE;
    $form['advanced']['googleanalytics_cache']['#description'] .= ' '. t('<a href="!url">Public file transfers</a> must be enabled to allow local caching.', array('!url' => url('admin/settings/file-system', array('query' => drupal_get_destination()))));
  }

  $form['advanced']['googleanalytics_codesnippet'] = array(
    '#type' => 'textarea',
    '#title' => t('JavaScript Code'),
    '#default_value' => variable_get('googleanalytics_codesnippet', ''),
    '#rows' => 15,
    '#description' => t('Paste <a href="@snippets">custom code snippets here</a>. These will be added to every page that Google Analytics appears on. For help with this feature see the <a href="@blog">cutroni.com blog</a>. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://drupal.org/node/39282', '@blog' => 'http://cutroni.com/blog/'))
  );

  return system_settings_form($form);
}
