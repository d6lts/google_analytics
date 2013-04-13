<?php

/**
 * @file
 * Administrative page callbacks for the googleanalytics module.
 */

/**
 * Layout for the custom variables table in the admin settings form.
 */
function theme_googleanalytics_admin_custom_var_table($variables) {
  $form = $variables['form'];

  $header = array(
    array('data' => t('Slot')),
    array('data' => t('Name')),
    array('data' => t('Value')),
    array('data' => t('Scope')),
  );

  $rows = array();
  foreach (element_children($form['slots']) as $key => $id) {
    $rows[] = array(
      'data' => array(
        drupal_render($form['slots'][$id]['slot']),
        drupal_render($form['slots'][$id]['name']),
        drupal_render($form['slots'][$id]['value']),
        drupal_render($form['slots'][$id]['scope']),
      ),
    );
  }

  $output = theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render($form['googleanalytics_custom_var_description']);
  $output .= drupal_render($form['googleanalytics_custom_var_token_tree']);

  return $output;
}

/**
 * Validate a form element that should have tokens in it.
 *
 * For example:
 * @code
 * $form['my_node_text_element'] = array(
 *   '#type' => 'textfield',
 *   '#title' => t('Some text to token-ize that has a node context.'),
 *   '#default_value' => 'The title of this node is [node:title].',
 *   '#element_validate' => array('googleanalytics_token_element_validate'),
 * );
 * @endcode
 */
function googleanalytics_token_element_validate(&$element, &$form_state) {
  $value = isset($element['#value']) ? $element['#value'] : $element['#default_value'];

  if (!drupal_strlen($value)) {
    // Empty value needs no further validation since the element should depend
    // on using the '#required' FAPI property.
    return $element;
  }

  $tokens = token_scan($value);
  $invalid_tokens = _googleanalytics_get_forbidden_tokens($tokens);
  if ($invalid_tokens) {
    form_error($element, t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', array('%element-title' => $element['#title'], '@invalid-tokens' => implode(', ', $invalid_tokens))));
  }

  return $element;
}

function _googleanalytics_get_forbidden_tokens($value) {
  $invalid_tokens = array();
  $value_tokens = is_string($value) ? token_scan($value) : $value;

  foreach ($value_tokens as $type => $tokens) {
    if (array_filter($tokens, '_googleanalytics_contains_forbidden_token')) {
      $invalid_tokens = array_merge($invalid_tokens, array_values($tokens));
    }
  }

  array_unique($invalid_tokens);
  return $invalid_tokens;
}

/**
 * Validate if a string contains forbidden tokens not allowed by privacy rules.
 *
 * @param $token_string
 *   A string with one or more tokens to be validated.
 * @return boolean
 *   TRUE if blacklisted token has been found, otherwise FALSE.
 */
function _googleanalytics_contains_forbidden_token($token_string) {
  // List of strings in tokens with personal identifying information not allowed
  // for privacy reasons. See section 8.1 of the Google Analytics terms of use
  // for more detailed information.
  //
  // This list can never ever be complete. For this reason it tries to use a
  // regex and may kill a few other valid tokens, but it's the only way to
  // protect users as much as possible from admins with illegal ideas.
  //
  // User tokens are not prefixed with colon to catch 'current-user' and 'user'.
  //
  // TODO: If someone have better ideas, share them, please!
  $token_blacklist = array(
    ':author]',
    ':author:edit-url]',
    ':author:url]',
    ':author:path]',
    ':current-user]',
    ':current-user:original]',
    ':fid]',
    ':mail]',
    ':name]',
    ':uid]',
    ':one-time-login-url]',
    ':owner]',
    ':owner:cancel-url]',
    ':owner:edit-url]',
    ':owner:url]',
    ':owner:path]',
    'user:cancel-url]',
    'user:edit-url]',
    'user:url]',
    'user:path]',
    'user:picture]',
    // addressfield_tokens.module
    ':first-name]',
    ':last-name]',
    ':name-line]',
    ':mc-address]',
    ':thoroughfare]',
    ':premise]',
    // realname.module
    ':name-raw]',
    // token.module
    ':ip-address]',
  );

  return preg_match('/' . implode('|', array_map('preg_quote', $token_blacklist)) . '/i', $token_string);
}
