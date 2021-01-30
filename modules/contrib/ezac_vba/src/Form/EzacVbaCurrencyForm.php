<?php

namespace Drupal\ezac_vba\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_starts\Model\EzacStart;
use Drupal\ezac_vba\Model\EzacVbaBevoegdheid;
use Drupal\ezac_vba\Model\EzacVbaDagverslagLid;

/**
 * UI to show status of VBA records
 */
class EzacVbaCurrencyForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId(): string {
    return 'ezac_vba_currency_form';
  }

  /**
   * buildForm for vba currency overview
   *
   * Voortgang en Bevoegdheid Administratie
   * Overzicht van de currency voor een lid
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @param $datum_start
   * @param $datum_eind
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $datum_start = NULL, $datum_eind = NULL): array {
    // read settings
    $settings = Drupal::config('ezac_vba.settings');
    //set up bevoegdheden
    $bevoegdheden = $settings->get('vba.bevoegdheden');
    $form['bevoegdheden'] = [
      '#type' => 'value',
      '#value' => $bevoegdheden,
    ];

    // set up status van bevoegdheden
    $status = $settings->get('vba.status');
    $form['status'] = [
      '#type' => 'value',
      '#value' => $status,
    ];

    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="currencyform">',
      '#suffix' => '</div>',
    ];

    // when datum not given, set default for this year
    //@todo params datum_start, datum_eind are selected in dropdown, to be removed here?
    if ($datum_start == NULL) {
      $datum_start = date('Y') . "-01-01";
    }
    if ($datum_eind == NULL) {
      $datum_eind = date('Y') . "-12-31";
    }
    $form['datum_start'] = [
      '#type' => 'value',
      '#value' => $datum_start,
    ];
    $form['datum_eind'] = [
      '#type' => 'value',
      '#value' => $datum_eind,
    ];

    //@todo put periode_list in settings
    $periode_list = [
      'seizoen' => 'dit seizoen',
      'tweejaar' => '24 maanden',
      'jaar' => '12 maanden',
      'maand' => '1 maand',
      'vandaag' => 'vandaag',
      //'anders' => 'andere periode',
    ];

    $form['periode'] = [
      '#type' => 'select',
      '#title' => 'Periode',
      '#options' => $periode_list,
      '#weight' => 2,
      '#ajax' => [
        'wrapper' => 'currency-div',
        'callback' => '::formPeriodeCallback',
        'effect' => 'fade',
        'progress' => array('type' => 'throbber'),
      ],
    ];

    $periode = $form_state->getValue('periode', key($periode_list)); // default is current pointed key in periode_list

    switch ($periode) {
      case 'vandaag' :
        $datum_start = date('Y-m-d');
        $datum_eind = date('Y-m-d');
        break;
      case 'maand' :
        $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n') - 1, date('j'), date('Y')));
        $datum_eind = date('Y-m-d'); //previous month
        break;
      case 'jaar' :
        $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y') - 1));
        $datum_eind = date('Y-m-d'); //previous year
        break;
      case 'tweejaar' :
        $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y') - 2));
        $datum_eind = date('Y-m-d'); //previous 2 year
        break;
      case 'seizoen' :
        $datum_start = date('Y') . '-01-01'; //this year
        $datum_eind = date('Y') . '-12-31';
        break;
    }

    // lees alle leden
    $condition = [
      // 'code' => 'VL',
      // 'actief' => TRUE,
    ];
    $leden = EzacUtil::getLeden($condition);
    unset($leden['']); // verwijder 'Onbekend'

    // lees currency for datum range
    $condition = [
      'datum' => [
        'value' => [$datum_start, $datum_eind],
        'operator' => 'BETWEEN',
      ],
    ];
    // currency: naam | startmethode = aantal, [laatste] = datum
    // naam | instructie = aantal, [laatste] = datum
    $currency = [];
    $startsIndex = EzacStart::index($condition);
    foreach ($startsIndex as $id) {
      $start = (new EzacStart($id));
      // voor gezagvoerder telt alles
      if ($start->gezagvoerder != '') { // gezagvoerder moet ingevuld zijn
        if (isset($currency[$start->gezagvoerder][$start->startmethode])) {
          $currency[$start->gezagvoerder][$start->startmethode]['aantal']++;
        }
        else {
          $currency[$start->gezagvoerder][$start->startmethode]['aantal'] = 1;
        }
        $currency[$start->gezagvoerder][$start->startmethode]['laatste'] = $start->datum;
        if ($start->instructie) {
          if (isset($currency[$start->gezagvoerder]['instructie'])) {
            $currency[$start->gezagvoerder]['instructie']['aantal']++;
          }
          else {
            $currency[$start->gezagvoerder]['instructie']['aantal'] = 1;
          }
          $currency[$start->gezagvoerder]['instructie']['laatste'] = $start->datum;
        }
      }
      // voor tweede telt currency alleen bij instructie start
      if (($start->instructie) && isset($leden[$start->tweede])) {
        if (isset($currency[$start->tweede][$start->startmethode])) {
          $currency[$start->tweede][$start->startmethode]['aantal']++;
        }
        else $currency[$start->tweede][$start->startmethode]['aantal'] = 1;
        $currency[$start->tweede][$start->startmethode]['laatste'] = $start->datum;
      }
    }

    $header = [
      t('Naam'),
      t('Instructie'),
    ];
    // voeg startmethodes toe
    foreach (EzacStart::$startMethode as $methode) {
      $header[] = $methode;
    }

    $rows = [];
    // vul tabel met currency waardes
    foreach ($currency as $person => $cur) {
      $row = [];
      $urlString = Url::fromRoute(
        'ezac_starts_overzicht_lid',  // show starts
        [
          'datum_start' => $datum_start,
          'datum_eind' => $datum_eind,
          'vlieger' => $person,
        ]
      )->toString();
      if (isset($leden[$person])) {
        $row[] = t("<a href=$urlString>$leden[$person]</a>");
      }
      else $row[] = t("<a href=$urlString>$person *</a>"); // onbekende afkorting

      // instructie
      if (isset($cur['instructie'])) {
        $cell = $cur['instructie']['aantal'] .'<br>';
        $cell .= EzacUtil::showDate($cur['instructie']['laatste']);
        $row[] = t($cell);
      }
      else $row[] = '';
      foreach (EzacStart::$startMethode as $m => $methode) {
        if (isset($cur[$m])) {
          $cell = $cur[$m]['aantal'] .'<br>';
          $cell .= EzacUtil::showDate($cur[$m]['laatste']);
          $row[] = t($cell);
        }
        else $row[] = '';
      }
      $rows[] = $row;
    }

    // sorteer op naam
    asort($rows);

    //maak tabel voor currency overzicht
    //[currency] form wordt door AJAX opnieuw opgebouwd
    $form['currency'] = [
      '#title' => t('Currency'),
      '#type' => 'table',
      '#weight' => 4,
      '#prefix' => '<div id="currency-div">',
      //This section replaced by AJAX callback
      '#suffix' => '</div>',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    ];

    //submit
    $form['currency']['submit'] = [
      '#type' => 'submit',
      '#description' => t('Opslaan'),
      '#value' => t('Opslaan'),
      '#weight' => 99,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|mixed
   */
  function formPeriodeCallback(array $form, FormStateInterface $form_state) {
    // Kies gewenste periode voor overzicht dagverslagen
    return $form['currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  } //submitForm

}
