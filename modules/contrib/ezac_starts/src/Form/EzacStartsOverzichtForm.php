<?php

namespace Drupal\ezac_starts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_starts\Controller\EzacStartsController;
use Drupal\ezac_starts\Model\EzacStart;

/**
 * UI to show starts
 */

class EzacStartsOverzichtForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId(): string {
    return 'ezac_starts_overzicht_form';
  }

  /**
   * buildForm for starts overzicht per lid
   *
   * Voortgang en Bevoegdheid Administratie
   * Overzicht van de status en bevoegdheid voor een lid
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $periode = ''): array {
    // read settings
    //$settings = Drupal::config('ezac_starts.settings');

    // set up startMethode and soort
    //$startMethode = $settings->get('startmethode');
    //$soort = $settings->get('soort');

    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="startsform">',
      '#suffix' => '</div>',
    ];

    //maak container voor startlijst
    //[startlijst] form element wordt door AJAX opnieuw opgebouwd
    $form['startlijst'] = [
      '#title' => t('Starts'),
      '#type' => 'container',
      '#weight' => 1,
      '#prefix' => '<div id="startlijst-div">',
      //This section replaced by AJAX callback
      '#suffix' => '</div>',
      '#tree' => FALSE,
    ];

    if ($periode == '') {
      // periode not available from parameter in menu local task
      $periode_list = [
        'vandaag' => 'vandaag',
        'maand' => '1 maand',
        'seizoen' => 'dit seizoen',
        'jaar' => '12 maanden',
        'tweejaar' => '24 maanden',
        //'anders' => 'andere periode',
      ];

      $form['startlijst']['periode'] = [
        '#type' => 'select',
        '#title' => 'Periode',
        '#options' => $periode_list,
        '#default_value' => 'seizoen',
        '#weight' => 2,
        '#ajax' => [
          'wrapper' => 'startlijst-div',
          'callback' => '::formPersoonCallback',
          'effect' => 'fade',
          'progress' => ['type' => 'throbber'],
        ],
      ];

      $periode = $form_state->getValue('startlijst')['periode'];
    }

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
      case 'anders':
        //@todo select dates for list with form input
      default: // vandaag
        $datum_start = date('Y-m-d');
        $datum_eind = date('Y-m-d');
    }

    // store datum for callback processing
    $form['startlijst']['start'] = [
      '#type' => 'value',
      '#value' => $datum_start,
    ];
    $form['startlijst']['eind'] = [
      '#type' => 'value',
      '#value' => $datum_eind,
    ];

    // Kies gewenste vlieger voor overzicht dagverslagen
    // vul namen uit starts in periode
    $condition = [
      'datum' => [
        'value' => [$datum_start, $datum_eind],
        'operator' => 'BETWEEN',
      ],
    ];
    $startsIndex = EzacStart::index($condition);
    foreach ($startsIndex as $id) {
      $start = new EzacStart($id);
      if (!isset($namen[$start->gezagvoerder]) && ($start->gezagvoerder != '')) {
        $lidId = EzacLid::getID($start->gezagvoerder);
        if (isset($lidId)) {
          $lid = new EzacLid($lidId);
          $namen[$start->gezagvoerder] = "$lid->voornaam $lid->voorvoeg $lid->achternaam";
        }
      }
      if (!isset($namen[$start->tweede]) && ($start->tweede != '')) {
        $lidId = EzacLid::getID($start->tweede);
        if (isset($lidId)) {
          $lid = new EzacLid($lidId);
          $namen[$start->tweede] = "$lid->voornaam $lid->voorvoeg $lid->achternaam";
        }
      }
    }
    // sorteer namen op waardes
    asort($namen);

    //@todo selectie van persoon alleen indien toegestaan (#hidden)

    // haal de eigen afkorting op, is leeg indien niet aanwezig
    $eigen_afkorting = EzacUtil::getUser();

    if (count($namen)) {
      // toon selectie box alleen indien namen aanwezig
      $form['startlijst']['persoon'] = [
        '#type' => 'select',
        '#title' => 'Vlieger',
        '#options' => $namen,
        '#default_value' => $eigen_afkorting,
        '#weight' => 3,
        '#ajax' => [
          'wrapper' => 'startlijst-div',
          'callback' => '::formPersoonCallback',
          'effect' => 'fade',
        ],
      ];
    }

    $persoon = $form_state->getValue('persoon');
    if (!isset($persoon)) {
      // nog geen selectie gedaan
      if ($eigen_afkorting != '') {
        $persoon = $eigen_afkorting;
      }
      else $persoon = key($namen);
    }
    $ds = $form_state->getValue('start') ?? $datum_start;
    $de = $form_state->getValue('eind') ?? $datum_eind;

    $details = false;
    $form['startlijst']['details'] = [
      '#type' => 'checkbox',
      '#title' => 'toon details',
      '#checked' => $details,
      '#weight' => 4,
      '#ajax' => [
        'wrapper' => 'startlijst-div',
        'callback' => '::formPersoonCallback',
        'effect' => 'fade',
        'progress' => ['type' => 'throbber'],
      ],
    ];

    $details = $form_state->getValue('details');
    if (isset($persoon) && $persoon != '') {
      //toon vluchten dit jaar
      $form['startlijst']['starts'] = EzacStartsController::startOverzicht(
        $ds,
        $de,
        $persoon,
        $details);
      $form['startlijst']['starts']['#weight'] = 5;
    } //isset(persoon)

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|mixed
   */
  function formPersoonCallback(array $form, FormStateInterface $form_state): array {

    return $form['startlijst'];
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
