<?php

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

use Drupal\Component\Utility\SafeMarkup;

/**
 * Implements hook_token_info().
 */
function google_analytics_token_info() {
  $user['role-names'] = [
    'name' => t('User role names'),
    'description' => t('The role names the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];
  $user['role-ids'] = [
    'name' => t('User role ids'),
    'description' => t('The role ids the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];

  return [
    'tokens' => ['user' => $user],
  ];
}

/**
 * Implements hook_tokens().
 */
function google_analytics_tokens($type, $tokens, array $data = [], array $options = []) {
  $sanitize = !empty($options['sanitize']);
  $replacements = [];

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'role-names':
          $names = implode(',', $account->getRoles());
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($names) : $names;
          break;

        case 'role-ids':
          $ids = implode(',', array_keys($account->getRoles()));
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($ids) : $ids;
          break;
      }
    }
  }

  return $replacements;
}
