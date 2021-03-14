<?php

namespace Drupal\ezac_leden\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_vba\Model\EzacVbaBevoegdheid;

/**
 * UI to update leden record
 * tijdelijke aanpassing
 */
class EzacLedenUserForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_leden_user_form';
  }

  /**
   * buildForm for LEDEN update by the user with ID parameter
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param null $id
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="userform">',
      '#suffix' => '</div>',
    ];

    /*
     * form for user update of leden and bevoegdheden
     * for regular users, only available for own data
     * for permission EZAC_update_all, a user to edit may be selected
     * update of bevoegdheden with expiration date to set dates for start and expiration
     */

    // read lid for $id - even if other was selected
    if (isset($id) and is_numeric($id)) {
      $lid = new EzacLid($id);
    }

    // if permission - show select dialog
    if (Drupal::currentUser()->hasPermission('EZAC_edit_all')) {
      // show user select dialog
      $selectUser = true;
      $condition = [
        'code' => [
          'value' => ['AL', 'VL', 'AVL'],
          'operator' => 'IN',
          ],
        'actief' => true,
      ];
      $leden = EzacUtil::getLeden($condition);
      $form['select'] = [
        '#type' => 'select',
        '#title' => 'Selecteer naam',
        '#options' => $leden,
        '#weight' => 0,
        '#default_value' => (isset($lid)) ? $lid->afkorting : EzacUtil::getUser(),
        '#ajax' => [
          'event' => 'change',
          'wrapper' => 'data',
          'callback' => '::formCallback',
        ],
      ];
    }
    else $selectUser = false;

    // if permitted and selected - read selected lid record
    if ($selectUser) {
      // if allowed, select record
      $select = $form_state->getUserInput()['select'];
      if ($select != '') {
        // read selected user record
        $id = EzacLid::getId($select);
        $lid = new EzacLid($id);
      }
    }

    // if no lid record is read yet, read for logged in user
    if (!isset($lid)) {
      // find user id for logged in user
      $afkorting = EzacUtil::getUser();
      if ($afkorting != '') {
        $lid = new EzacLid(EzacLid::getId($afkorting));
        $id = $lid->id;
      }
      else { // geen ledenrecord voor deze drupal user aanwezig
        $lid = new EzacLid();
      }
    }

    $form['data'] = [
      '#type' => 'container',
      '#tree' => true,
      //'#prefix' => '<div id="data">',
      //'#suffix' => '</div>',
      '#attributes' => ['id' => 'data'],
    ];
    //VOORNAAM Tekst 13
    $form['data']['voornaam'] = [
      '#type' => 'textfield',
      '#title' => 'voornaam',
      '#size' => 13,
      '#maxlength' => 13,
      '#default_value' => $lid->voornaam,
      //'#value' => $lid->voornaam,
    ];

    //VOORVOEG Tekst 11
    $form['data']['voorvoeg'] = [
      '#type' => 'textfield',
      '#title' => 'voorvoeg',
      '#size' => 11,
      '#maxlength' => 11,
      '#default_value' => $lid->voorvoeg,
      //'#value' => $lid->voorvoeg,
    ];

    //ACHTERNAAM Tekst 35
    $form['data']['achternaam'] = [
      '#type' => 'textfield',
      '#title' => 'achternaam',
      '#size' => 35,
      '#maxlength' => 35,
      '#default_value' => $lid->achternaam,
      //'#value' => $lid->achternaam,
    ];

    //VOORLETTER Tekst 21
    $form['data']['voorletter'] = [
      '#type' => 'textfield',
      '#title' => 'voorletter',
      '#size' => 21,
      '#maxlength' => 21,
      '#default_value' => $lid->voorletter,
      //'#value' => $lid->voorletter,
    ];

    //ADRES Tekst 26
    $form['data']['adres'] = [
      '#type' => 'textfield',
      '#title' => 'adres',
      '#size' => 26,
      '#maxlength' => 26,
      '#default_value' => $lid->adres,
      //'#value' => $lid->adres,
    ];

    //POSTCODE Tekst 9
    $form['data']['postcode'] = [
      '#type' => 'textfield',
      '#title' => 'postcode',
      '#size' => 9,
      '#maxlength' => 9,
      '#default_value' => $lid->postcode,
      //'#value' => $lid->postcode,
    ];

    //PLAATS Tekst 24
    $form['data']['plaats'] = [
      '#type' => 'textfield',
      '#title' => 'plaats',
      '#size' => 24,
      '#maxlength' => 24,
      '#default_value' => $lid->plaats,
      //'#value' => $lid->plaats,
    ];

    //TELEFOON Tekst 14
    $form['data']['telefoon'] = [
      '#type' => 'textfield',
      '#title' => 'telefoon',
      '#size' => 14,
      '#maxlength' => 14,
      '#default_value' => $lid->telefoon,
      //'#value' => $lid->telefoon,
    ];

    //Mobiel Tekst 20
    $form['data']['mobiel'] = [
      '#type' => 'textfield',
      '#title' => 'mobiel',
      '#size' => 20,
      '#maxlength' => 20,
      '#default_value' => $lid->mobiel,
      //'#value' => $lid->mobiel,
    ];

    //LAND Tekst 10
    $form['data']['land'] = [
      '#type' => 'textfield',
      '#title' => 'land',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $lid->land,
      //'#value' => $lid->land,
    ];

    //E_mail Tekst 50
    $form['data']['e_mail'] = [
      '#type' => 'textfield',
      '#title' => 'e-mail',
      '#size' => 50,
      '#maxlength' => 50,
      '#default_value' => $lid->e_mail,
      //'#value' => $lid->e_mail,
    ];

    //Id
    $form['data']['id'] = [
      '#type' => 'value',
      '#value' => $lid->id,
    ];

    // select user-updatable bevoegdheden
    // read settings
    $settings = Drupal::config('ezac_vba.settings');

    //set up bevoegdheden
    $bevoegdheden = $settings->get('vba.bevoegdheden');
    $form['bevoegdheden'] = [
      '#type' => 'value',
      '#value' => $bevoegdheden,
    ];

    foreach ($bevoegdheden as $bevoegdheid => $onderdeel) {
      if ($onderdeel['vervalt'] == true) {
        // deze bevoegdheid heeft een vervaldatum
        $vervalt_list[] = $bevoegdheid;
      }
    }

    $condition = [
      'afkorting' => $lid->afkorting,
      'bevoegdheid' => [
        'value' => $vervalt_list,
        'operator' => 'IN',
      ],
    ];
    $bevoegdhedenIndex = EzacVbaBevoegdheid::index($condition);
    $bv_list = [];

    foreach ($bevoegdhedenIndex as $id) {
      $bl = new EzacVbaBevoegdheid($id);
      $bv_list[$id] = $bl->bevoegdheid;
      $form['data']['bevoegdheid'][$bl->bevoegdheid] = [
        '#type' => 'container',
        '#tree' => true,
      ];
      $form['data']['bevoegdheid'][$bl->bevoegdheid]['datum_aan'] = [
        '#type' => 'date',
        '#title' => t("$bl->bevoegdheid geldig vanaf"),
        '#default_value' => $bl->datum_aan,
        //'#value' => $bl->datum_aan,
      ];
      $form['data']['bevoegdheid'][$bl->bevoegdheid]['datum_uit'] = [
        '#type' => 'date',
        '#title' => t("$bl->bevoegdheid geldig tot"),
        '#default_value' => $bl->datum_uit,
        //'#value' => $bl->datum_uit,
      ];
    }

    // store bv_list for validate and submit processing
    $form['bv_list'] = [
      '#type' => 'value',
      '#value' => $bv_list,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#weight' => 31,
    ];

    return $form;
  }

  function formCallback(array $form, FormStateInterface $form_state) {
    $afkorting = $form_state->getValue('select');
    if ($afkorting != '') {
      $id = EzacLid::getId($afkorting);
      // load values
      $form['id']['#value'] = $id;
      $lid = new EzacLid($id);
      // process form fields for lid
      $fields = [
        'voornaam',
        'voorvoeg',
        'voorletter',
        'achternaam',
        'adres',
        'postcode',
        'plaats',
        'telefoon',
        'mobiel',
        'e_mail',
        'land',
      ];
      $lid_values = [];
      foreach ($fields as $field) {
        $lid_values[$field] = $lid->$field;
        $form['data'][$field]['#default_value'] = $lid->$field;
        $form['data'][$field]['#value'] = $lid->$field;
      }
      $form['data']['id'] = $lid->id;
      $form_state->setUserInput($lid_values);
    }
    $form_state->setRebuild();
    return $form['data'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // perform validate for edit of record
    $bv_list = $form_state->getValue('bv_list');
    foreach ($bv_list as $bevoegdheid) {
      $bev = $form_state->getValue('data')['bevoegdheid'][$bevoegdheid];
      $dat = $bev['datum_aan'];
      if ($dat != '') {
        $lv = explode('-', $dat);
        if (checkdate($lv[1], $lv[2], $lv[0]) == FALSE) { // month, day, year
          $form_state->setErrorByName($bevoegdheid, "Datum geldig vanaf voor $bevoegdheid is onjuist");
        }
      }
      $dat = $bev['datum_uit'];
      if ($dat != '') {
        $lv = explode('-', $dat);
        if (checkdate($lv[1], $lv[2], $lv[0]) == FALSE) {
          $form_state->setErrorByName($bevoegdheid, "Datum geldig tot voor $bevoegdheid is onjuist");
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = Drupal::messenger();

    // Save the submitted entry.
    // read old record for update
    $data = $form_state->getValue('data');
    $id = $data['id'];
    $lid = new EzacLid($id);

    // process form fields for lid
    $fields = [
      'voornaam',
      'voorvoeg',
      'voorletter',
      'achternaam',
      'adres',
      'postcode',
      'plaats',
      'telefoon',
      'mobiel',
      'e_mail',
      'land',
    ];
    $updated = false;
    foreach ($fields as $field) {
      if ($data[$field] != $form['data'][$field]['#default_value']) {
        // field has changed
        $lid->$field = $data[$field];
        $updated = true;
      }
    }
    if ($updated) {
      $count = $lid->update(); // update record in database
      $messenger->addMessage("$count record updated", $messenger::TYPE_STATUS);
    }

    // aanpassen bevoegdheden
    $bv_list = $form_state->getValue('bv_list');
    foreach ($bv_list as $id => $bevoegdheid) {
      $field = $data['bevoegdheid'][$bevoegdheid];
      $updated = false;
      if ($field['datum_aan'] != $form['data']['bevoegdheid'][$bevoegdheid]['datum_aan']['#default_value']) {
        $bv = new EzacVbaBevoegdheid($id);
        $bv->datum_aan = ($field['datum_aan'] != '') ? $field['datum_aan'] : NULL;
        $updated = true;
      }
      if ($field['datum_uit'] != $form['data']['bevoegdheid'][$bevoegdheid]['datum_uit']['#default_value']) {
        $bv = new EzacVbaBevoegdheid($id);
        $bv->datum_uit = ($field['datum_uit'] != '') ? $field['datum_uit'] : NULL;
        $updated = true;
      }
      if ($updated) {
        $count = $bv->update();
        $messenger->addMessage("$count bevoegdheid $bevoegdheid bijgewerkt", 'status');
      }
    }

    //go back to leden overzicht
    $redirect = Url::fromRoute(
      'ezac_leden_user_update'
    );
    $form_state->setRedirectUrl($redirect);
  } //submitForm

}
