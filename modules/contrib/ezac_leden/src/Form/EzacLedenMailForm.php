<?php

namespace Drupal\ezac_leden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ezac_leden\Model\EzacLid;

/**
 * UI to update leden recordvoor ledenlijst
 */
class EzacLedenMailForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_leden_mail_form';
  }

  /**
   * buildForm voor download etiketten
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="mailform">',
      '#suffix' => '</div>',
    ];

    $form['selectie'] = [
      '#title' => t('E-mail adressen voor'),
      '#type' => 'select',
      '#description' => t('Selecteer de doelgroep'),
      '#options' => [
        'selecteer' => '<selecteer>',
        'clubblad' => 'Clubblad',
        'vergadering' => 'Ledenvergadering',
        'receptie' => 'Receptie',
        'VL' => 'Vliegende leden',
        'instructeurs' => 'Instructie',
        'camping' => 'Camping',
        'alles' => 'Alle leden',
      ],
      '#default_value' => 'selecteer',
      // use ajax to fill mail field
      '#ajax' => [
        'callback' => '::formMailCallback',
        'wrapper' => 'mail',
      ],
    ];

    $form['opmaak'] = [
      '#title' => t('Opmaak adreslijst'),
      '#type' => 'select',
      '#default value' => 'outlook (met ;)',
      '#options' => [
        'gmail' => 'Gmail (met ,)',
        'outlook' => 'Outlook (met ;)',
        'regel' => 'Een adres per regel',
      ],
      '#default_value' => 'gmail',
      '#description' => t('Kies de opmaak'),
      // use ajax to fill mail field
      '#ajax' => [
        'callback' => '::formMailCallback',
        'wrapper' => 'mail',
      ],
    ];

    $form['mail'] = [
      '#title' => t('E-mail adressen'),
      '#description' => t('Kopieer deze in het BCC veld van je mail bericht'),
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 20,
      '#prefix' => '<div id="mail">',
      '#suffix' => '</div>',
      '#attributes' => [
        'name' => 'mail',
      ],
      '#states' => [
        // show this field only when tweezitter
        'invisible' => [
          ':input[name="selectie"]' => ['value' => 'selecteer'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * @param $selectie
   * @param $opmaak
   *
   * @return string
   */
  function buildMail($selectie, $opmaak) {

    if (($selectie == 'selecteer') or ($selectie == null)) return '';

    $condition = [
      'actief' => TRUE,
    ];

    switch ($selectie) {
      case "clubblad":
        $condition['etiketje'] = TRUE;
        break;
      case "vergadering":
        $condition['code'] = [
          'value' => ['VL', 'AL'],
          'operator' => 'IN',
        ];
        break;
      case "receptie":
        $condition['code'] = [
          'value' => ['AL', 'VL', 'AVL', 'DO', 'DB'],
          'operator' => 'IN',
        ];
        break;
      case "alles":
        $condition['code'] = [
          'value' => 'BF',
          'operator' => '<>',
        ];
        break;
      case "baby":
        $condition['babyvriend'] = TRUE;
        break;
      case "VL":
        $condition['code'] = [
          'value' => ['VL', 'AVL'],
          'operator' => 'IN',
        ];
        break;
      case "camping":
        $condition['camping'] = TRUE;
        break;
      case "instructeurs":
        $condition['instructie'] = TRUE;
        break;
    } //switch

    switch ($opmaak) {
      case "outlook":
        $sep = ';';
        break;
      case "gmail":
        $sep = ',';
        break;
      case "regel":
        $sep = "\r";
        break;
    } // switch

    //execute query
    $ledenMailIndex = array_unique(EzacLid::index($condition, 'e_mail', 'achternaam'));

    $adressen = "";
    //output result
    foreach ($ledenMailIndex as $e_mail) {
      if (($e_mail != null) and ($e_mail != '')) $adressen .= $e_mail .$sep;
    }
    // strip last seperator
    $adressen = substr($adressen, 0, -1);
    return $adressen;
  }

  function formMailCallBack(array &$form, FormStateInterface $form_state) {
    $form['mail']['#value'] = self::buildMail(
      $form_state->getValue('selectie'),
      $form_state->getValue('opmaak'));
    return $form['mail'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // leeg
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  } //submitForm

}
