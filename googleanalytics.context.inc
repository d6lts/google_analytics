<?php
/**
 * @file
 * Context reaction for Google Analytics Content Experiments.
 */

class GoogleAnalyticsReactionContentExperiment extends context_reaction {
  /**
   * Allow admins to provide a section title, section subtitle and section class.
   */
  function options_form($context) {
    $values = $this->fetch_from_context($context);
    $form = array(
      '#tree' => TRUE,
      '#title' => t('Google Analytics Content Experiment'),
      'googleanalytics_content_experiment_key' => array(
        '#title' => t('Experiment key'),
        '#description' => t('The experiment key comes from Google Analytics. It should appear under the code that Google Analytics instructs you to paste immediately after the opening of the &lt;head&gt; tag.'),
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#default_value' => isset($values['googleanalytics_content_experiment_key']) ? $values['googleanalytics_content_experiment_key'] : '',
      ),
    );
    return $form;
  }

  /**
   * Override of options_form_submit().
   */
  function options_form_submit($values) {
    if (!preg_match('/^\d+-\d+$/', $values['googleanalytics_content_experiment_key'])) {
      form_set_error('reactions[plugins][googleanalytics_reaction_content_group][googleanalytics_content_experiment_key]', t('Google Analytics Content Experiment - A valid experiment key is formatted like xxxxxxx-yy.'));
    }
    return $values;
  }

  /**
   * Output a list of active contexts.
   */
  function execute(&$variables) {
    $contexts = $this->get_contexts();
    foreach ($contexts as $context) {
      if (!empty($context->reactions[$this->plugin]['googleanalytics_content_experiment_key'])) {
        $script = '<!-- Google Analytics Content Experiment code -->' . "\n";
        $script .= '<script>function utmx_section(){}function utmx(){}(function(){var';
        $script .= 'k=' . drupal_json_encode($context->reactions[$this->plugin]['googleanalytics_content_experiment_key']) .',d=document,l=d.location,c=d.cookie;';
        $script .= 'if(l.search.indexOf(\'utm_expid=\'+k)>0)return;';
        $script .= 'function f(n){if(c){var i=c.indexOf(n+\'=\');if(i>-1){var j=c.';
        $script .= 'indexOf(\';\',i);return escape(c.substring(i+n.length+1,j<0?c.';
        $script .= 'length:j))}}}var x=f(\'__utmx\'),xx=f(\'__utmxx\'),h=l.hash;d.write(';
        $script .= '\'<sc\'+\'ript src="\'+\'http\'+(l.protocol==\'https:\'?\'s://ssl\':';
        $script .= '\'://www\')+\'.google-analytics.com/ga_exp.js?\'+\'utmxkey=\'+k+';
        $script .= '\'&utmx=\'+(x?x:\'\')+\'&utmxx=\'+(xx?xx:\'\')+\'&utmxtime=\'+new Date().';
        $script .= 'valueOf()+(h?\'&utmxhash=\'+escape(h.substr(1)):\'\')+';
        $script .= '\'" type="text/javascript" charset="utf-8"><\/sc\'+\'ript>\')})();';
        $script .= '</script><script>utmx(\'url\',\'A/B\');</script>' . "\n";
        $script .= '<!-- End of Google Analytics Content Experiment code -->' . "\n";

        drupal_add_html_head(array('#type' => 'markup', '#markup' => $script), 'googleanalytics_content_experiment');
      }
    }
  }
}

/**
 * Context reaction for Google Analytics Content Groups.
 */
class GoogleAnalyticsReactionContentGroup extends context_reaction {
  /**
   * Allow admins to provide a section title, section subtitle and section class.
   */
  function options_form($context) {
    $values = $this->fetch_from_context($context);

    $form = array(
      '#tree' => TRUE,
      '#title' => t('Google Analytics Content Group'),
      'googleanalytics_content_group_id' => array(
        '#title' => t('Group ID'),
        '#description' => t('The content group ID you\'d like to assign the context to.'),
        '#type' => 'select',
        '#options' => drupal_map_assoc(array(1,2,3,4,5)),
        '#empty_option' => t('- Select -'),
        '#default_value' => isset($values['googleanalytics_content_group_id']) ? $values['googleanalytics_content_group_id'] : '',
        //'#required' => TRUE,
      ),
      'googleanalytics_content_group_name' => array(
        '#title' => t('Group name'),
        '#description' => t('The content group name you\'d like to assign the context to.'),
        '#type' => 'textfield',
        '#maxlength' => 255,
        '#default_value' => isset($values['googleanalytics_content_group_name']) ? $values['googleanalytics_content_group_name'] : '',
      ),
    );

    return $form;
  }

  /**
   * Override of options_form_submit().
   */
  function options_form_submit($values) {
    if (empty($values['googleanalytics_content_group_id'])) {
      form_set_error('reactions[plugins][googleanalytics_reaction_content_group][googleanalytics_content_group_id]', t('Google Analytics Content Group - %field field is required.', array('%field' => t('Group ID'))));
    }
    if (!drupal_strlen($values['googleanalytics_content_group_name'])) {
      form_set_error('reactions[plugins][googleanalytics_reaction_content_group][googleanalytics_content_group_name]', t('Google Analytics Content Group - %field field is required.', array('%field' => t('Group name'))));
    }
    return $values;
  }

  /**
   * Output a list of active contexts.
   */
  function execute(&$variables) {
    $contexts = $this->get_contexts();
    foreach ($contexts as $context) {
      if (!empty($context->reactions[$this->plugin]['googleanalytics_content_group_id']) && !empty($context->reactions[$this->plugin]['googleanalytics_content_group_name'])) {
        $variables[$context->reactions[$this->plugin]['googleanalytics_content_group_id']] = $context->reactions[$this->plugin]['googleanalytics_content_group_name'];
      }
    }
  }
}

/**
 * Context reaction for Google Analytics Event.
 */
class GoogleAnalyticsReactionEvent extends context_reaction {
  /**
   * Allow admins to provide a section title, section subtitle and section class.
   */
  function options_form($context) {
    $values = $this->fetch_from_context($context);

    $form = array(
      '#tree' => TRUE,
      '#title' => t('Google Analytics Event Tracking'),
    );

    for ($i = 1; $i <= 5; $i++) {
      $form['events'][$i] = array(
        '#collapsed' => (empty($values['events'][$i]['googleanalytics_event_category']) && empty($values['events'][$i]['googleanalytics_event_action'])),
        '#collapsible' => TRUE,
        '#type' => 'fieldset',
        '#title' => t('Event #@number', array('@number' => $i)),
      );
      $form['events'][$i]['googleanalytics_event_category'] = array(
        '#type' => 'textfield',
        '#title' => t('Category'),
        '#default_value' => isset($values['events'][$i]['googleanalytics_event_category']) ? $values['events'][$i]['googleanalytics_event_category'] : '',
        '#description' => t('Typically the object that was interacted with (e.g. button).'),
        '#maxlength' => 255,
        '#states' => array(
          'required' => array(
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_label]"]' => array('filled' => TRUE)),
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_value]"]' => array('filled' => TRUE)),
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_noninteraction]"]' => array('checked' => TRUE)),
          ),
        ),
      );
      $form['events'][$i]['googleanalytics_event_action'] = array(
        '#type' => 'textfield',
        '#title' => t('Action'),
        '#default_value' => isset($values['events'][$i]['googleanalytics_event_action']) ? $values['events'][$i]['googleanalytics_event_action'] : '',
        '#description' => t('The type of interaction (e.g. click).'),
        '#maxlength' => 255,
        '#states' => array(
          'required' => array(
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_label]"]' => array('filled' => TRUE)),
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_value]"]' => array('filled' => TRUE)),
            array(':input[name="reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_noninteraction]"]' => array('checked' => TRUE)),
          ),
        ),
      );
      $form['events'][$i]['googleanalytics_event_label'] = array(
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#default_value' => isset($values['events'][$i]['googleanalytics_event_label']) ? $values['events'][$i]['googleanalytics_event_label'] : '',
        '#description' => t('Useful for categorizing events (e.g. nav buttons).'),
        '#maxlength' => 255,
      );
      $form['events'][$i]['googleanalytics_event_value'] = array(
        '#type' => 'textfield',
        '#title' => t('Value'),
        '#default_value' => isset($values['events'][$i]['googleanalytics_event_value']) ? $values['events'][$i]['googleanalytics_event_value'] : '',
        '#description' => t('Values must be non-negative. Useful to pass counts (e.g. 4 times)'),
        '#maxlength' => 255,
      );
      $form['events'][$i]['googleanalytics_event_noninteraction'] = array(
        '#type' => 'checkbox',
        '#title' => t('Non-interaction event'),
        '#default_value' => isset($values['events'][$i]['googleanalytics_event_noninteraction']) ? $values['events'][$i]['googleanalytics_event_noninteraction'] : 0,
        '#description' => t("Enabled, if you want such events to be excluded from bounce rate calculations because they don't track visitor interaction. You can mark these events as non-interaction events, so that they donÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢t affect the bounce rate for the page."),
      );
    }

    return $form;
  }

  /**
   * Override of options_form_submit().
   *
   * Reduce array to configured events.
   */
  function options_form_submit($values) {
    if (!empty($values['events'])) {
      foreach ($values['events'] as $i => $event) {
        // Force error on incomplete form settings.
        if (drupal_strlen($event['googleanalytics_event_label']) || drupal_strlen($event['googleanalytics_event_value']) || !empty($event['googleanalytics_event_noninteraction'])) {
          if (!drupal_strlen($event['googleanalytics_event_category'])) {
            form_set_error('reactions[plugins][googleanalytics_reaction_event][events]['. $i .'][googleanalytics_event_label]', t('Google Analytics Event Tracking - Event #@number: %field field is required.', array('@number' => $i, '%field' => t('Category'))));
          }
          if (!drupal_strlen($event['googleanalytics_event_action'])) {
            form_set_error('reactions[plugins][googleanalytics_reaction_event][events][' . $i . '][googleanalytics_event_action]', t('Google Analytics Event Tracking - Event #@number: %field field is required.', array('@number' => $i, '%field' => t('Action'))));
          }
        }

        if (!drupal_strlen($event['googleanalytics_event_category'])
          && !drupal_strlen($event['googleanalytics_event_action'])
          && !drupal_strlen($event['googleanalytics_event_label'])
          && !drupal_strlen($event['googleanalytics_event_value'])
          && empty($event['googleanalytics_event_noninteraction'])) {
          // Remove empty event configurations from settings array.
          unset($values['events'][$i]);
        }
      }

      return $values;
    }
  }

  /**
   * Output a list of active contexts.
   */
  function execute(&$variables) {
    $contexts = $this->get_contexts();
    foreach ($contexts as $context) {
      if (!empty($context->reactions[$this->plugin]['events'])) {
        foreach ($context->reactions[$this->plugin]['events'] as $event) {
          $event_array = array();
          // Required values.
          $event_array['eventCategory'] = $event['googleanalytics_event_category'];
          $event_array['eventAction'] = $event['googleanalytics_event_action'];

          // Optional values.
          if (drupal_strlen($event['googleanalytics_event_label'])) {
            $event_array['eventLabel'] = $event['googleanalytics_event_label'];
          }
          if (drupal_strlen($event['googleanalytics_event_value'])) {
            $event_array['eventValue'] = $event['googleanalytics_event_value'];
          }
          if ($event['googleanalytics_event_noninteraction'] == TRUE) {
            $event_array['nonInteraction'] = $event['googleanalytics_event_noninteraction'];
          }

          // Do not add invalid event configurations.
          if (!empty($event_array) && drupal_strlen($event['googleanalytics_event_category']) && drupal_strlen($event['googleanalytics_event_action'])) {
            $variables['events'][] = array_merge(array('hitType' => 'event'), $event_array);
          }
        }
      }
    }
  }
}

/**
 * Context reaction for Google Analytics code snippets.
 */
class GoogleAnalyticsReactionCodeSnippet extends context_reaction {
  /**
   * Allow admins to provide a section title, section subtitle and section class.
   */
  function options_form($context) {
    $values = $this->fetch_from_context($context);

    $form = array(
      '#tree' => TRUE,
      '#title' => t('Google Analytics Code Snippet'),
    );
    $form['codesnippet'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom JavaScript code'),
      '#description' => t('You can add custom Google Analytics <a href="@snippets">code snippets</a> here. These will be added every time tracking is in effect. Before you add your custom code, you should read the <a href="@ga_concepts_overview">Google Analytics Tracking Code - Functional Overview</a> and the <a href="@ga_js_api">Google Analytics Tracking API</a> documentation. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://drupal.org/node/248699', '@ga_concepts_overview' => 'https://developers.google.com/analytics/resources/concepts/gaConceptsTrackingOverview', '@ga_js_api' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/method-reference')),
    );
    $form['codesnippet']['googleanalytics_codesnippet_before'] = array(
      '#type' => 'textarea',
      '#title' => t('Code snippet (before)'),
      '#default_value' => isset($values['codesnippet']['googleanalytics_codesnippet_before']) ? $values['codesnippet']['googleanalytics_codesnippet_before'] : '',
      '#rows' => 5,
      '#description' => t('Code in this textarea will be added <strong>before</strong> <code>ga("send", "pageview");</code>.'),
    );
    $form['codesnippet']['googleanalytics_codesnippet_after'] = array(
      '#type' => 'textarea',
      '#title' => t('Code snippet (after)'),
      '#default_value' => isset($values['codesnippet']['googleanalytics_codesnippet_after']) ? $values['codesnippet']['googleanalytics_codesnippet_after'] : '',
      '#rows' => 5,
      '#description' => t('Code in this textarea will be added <strong>after</strong> <code>ga("send", "pageview");</code>. This is useful if you\'d like to track a site in two accounts.'),
    );

    return $form;
  }

  /**
   * Output a list of active contexts.
   */
  function execute(&$variables) {
    $contexts = $this->get_contexts();
    foreach ($contexts as $context) {
      if (drupal_strlen($context->reactions[$this->plugin]['codesnippet']['googleanalytics_codesnippet_before'])) {
        $variables['before'][] = $context->reactions[$this->plugin]['codesnippet']['googleanalytics_codesnippet_before'];
      }
      if (drupal_strlen($context->reactions[$this->plugin]['codesnippet']['googleanalytics_codesnippet_after'])) {
        $variables['after'][] = $context->reactions[$this->plugin]['codesnippet']['googleanalytics_codesnippet_after'];
      }
    }
  }
}
