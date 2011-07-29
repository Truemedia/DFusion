<?php

/**
 * @file
 * User page callbacks for the dfusion module.
 */

/**
 * Menu callback; Process an DFusion authentication.
 */
function dfusion_authentication_page() {
  $result = dfusion_complete();
  switch ($result['status']) {
    case 'success':
      return dfusion_authentication($result);
    case 'failed':
      drupal_set_message(t('DFusion login failed.'), 'error');
      break;
    case 'cancel':
      drupal_set_message(t('DFusion login cancelled.'));
      break;
  }
  drupal_goto();
}

/**
 * Menu callback; Manage DFusion identities for the specified user.
 */
function dfusion_user_identities($account) {
  drupal_set_title(format_username($account));
  drupal_add_css(drupal_get_path('module', 'dfusion') . '/dfusion.css');

  // Check to see if we got a response
  $result = dfusion_complete();
  if ($result['status'] == 'success') {
    $identity = $result['dfusion.claimed_id'];
    $query = db_insert('authmap')
      ->fields(array(
        'uid' => $account->uid,
        'authname' => $identity,
        'module' => 'dfusion',
      ))
      ->execute();
    drupal_set_message(t('Successfully added %identity', array('%identity' => $identity)));
  }

  $header = array(t('DFusion'), t('Operations'));
  $rows = array();

  $result = db_query("SELECT * FROM {authmap} WHERE module='dfusion' AND uid=:uid", array(':uid' => $account->uid));
  foreach ($result as $identity) {
    $rows[] = array(check_plain($identity->authname), l(t('Delete'), 'user/' . $account->uid . '/dfusion/delete/' . $identity->aid));
  }

  $build['dfusion_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $build['dfusion_user_add'] = drupal_get_form('dfusion_user_add');
  return $build;
}

/**
 * Form builder; Add an DFusion identity.
 *
 * @ingroup forms
 * @see dfusion_user_add_validate()
 */
function dfusion_user_add() {
  $form['dfusion_identifier'] = array(
    '#type' => 'textfield',
    '#title' => t('DFusion'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Add an DFusion'));
  return $form;
}

function dfusion_user_add_validate($form, &$form_state) {
  // Check for existing entries.
  $claimed_id = dfusion_normalize($form_state['values']['dfusion_identifier']);
  if (db_query("SELECT authname FROM {authmap} WHERE authname = :authname", (array(':authname' => $claimed_id)))->fetchField()) {
    form_set_error('dfusion_identifier', t('That DFusion is already in use on this site.'));
  }
}

function dfusion_user_add_submit($form, &$form_state) {
  $return_to = url('user/' . arg(1) . '/dfusion', array('absolute' => TRUE));
  dfusion_begin($form_state['values']['dfusion_identifier'], $return_to);
}

/**
 * Menu callback; Delete the specified DFusion identity from the system.
 */
function dfusion_user_delete_form($form, $form_state, $account, $aid = 0) {
  $authname = db_query("SELECT authname FROM {authmap} WHERE uid = :uid AND aid = :aid AND module = 'dfusion'", array(
    ':uid' => $account->uid,
    ':aid' => $aid,
  ))
  ->fetchField();
  return confirm_form(array(), t('Are you sure you want to delete the DFusion %authname for %user?', array('%authname' => $authname, '%user' => $account->name)), 'user/' . $account->uid . '/dfusion');
}

function dfusion_user_delete_form_submit($form, &$form_state) {
  $query = db_delete('authmap')
    ->condition('uid', $form_state['build_info']['args'][0]->uid)
    ->condition('aid', $form_state['build_info']['args'][1])
    ->condition('module', 'dfusion')
    ->execute();
  if ($query) {
    drupal_set_message(t('DFusion deleted.'));
  }
  $form_state['redirect'] = 'user/' . $form_state['build_info']['args'][0]->uid . '/dfusion';
}
