<?php

namespace Drupal\ezac_passagiers\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_passagiers\Model\EzacPassagierDag;
use Throwable;


/**
 * UI to show status of VBA records
 */
class EzacPassagiersDagenForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_passagiers_dagen_form';
  }

  /**
   * buildForm for passagiers dagen
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $messenger = Drupal::messenger();
    /* Set locale to Dutch */
    setlocale(LC_ALL, 'nl_NL');

    // read settings
    $settings = Drupal::config('ezac_passagiers.settings');
    $mededeling = $settings->get('texts.mededeling');

    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="dagenform">',
      '#suffix' => '</div>',
    ];

    if ($mededeling != '') {
      $form['mededeling'] = [
        '#type' => 'markup',
        '#markup' => t($mededeling),
        '#weight' => 0,
      ];
    }

    // present in list
    // create intro
    $form[0]['#type'] = 'markup';
    $form[0]['#markup'] = '<p><h2>Instellen dagen voor meevliegen</h2></p>';
    $form[0]['#weight'] = 0;

    $form['dagenlist'] = [
      '#type' => 'fieldlist',
      '#title' => 'Dagen voor meevliegen',
      '#prefix' => '<div id="dagenlist-div">',
      '#suffix' => '</div>',
      '#weight' => 1,
      '#tree' => TRUE,
    ];

    //lees beschikbare dagen uit ezac_Passagiers_Dagen
    $condition = [];
    $dagenIndex = EzacPassagierDag::index($condition);

    //build initial checkbox list
    $i = 0;
    foreach ($dagenIndex as $id) {
      $dag = (new EzacPassagierDag($id))->datum;
      $form['dagenlist']['checkboxes'][$i] = [
        '#type' => 'checkbox',
        '#title' => $dag . ' ' . date('l j F Y', strtotime($dag)),
        '#value' => 0,
        '#tree' => TRUE,
        '#weight' => 1 + ($i / 100),
      ];
      $form['dagenlist']['dagen'][$i] = [
        '#type' => 'value',
        '#value' => $dag,
        '#tree' => TRUE,
      ];
      $i++;
    }

    $form['nr_dagen'] = [
      '#type' => 'value',
      '#value' => $i,
    ];

    $form['remove'] = [
      '#type' => 'submit',
      '#value' => 'Verwijder gemarkeerde items',
      '#prefix' => '<div id="verwijder-div">',
      '#suffix' => '</div>',
      '#weight' => 2,
    ];

    // create new series from weekend date for x weeks DATUM_REEKS
    $form[1]['#type'] = 'markup';
    $form[1]['#markup'] = '<p><h2>Instellen reeks weekends voor reserveringen</h2></p>';
    $form[1]['#markup'] .= '<p>Geef de eerste zaterdag van de reeks op en het aantal weekends</p>';
    $form[1]['#weight'] = 3;

    // find first weekend after March 13 (two weeks into the season)
    $first_date = date('Y-m-d', strtotime('saturday', strtotime(date('Y') . '-04-13')));
    $form['serie_start'] = [
      '#title' => 'Nieuwe datum serie vanaf zaterdag',
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $first_date,
      '#description' => t('Datum in JJJJ-MM-DD formaat, zaterdag'),
      '#weight' => 4,
    ];
    $form['serie_aantal'] = [
      '#title' => 'Aantal weekends',
      '#type' => 'textfield',
      '#size' => 2,
      '#default_value' => 26,
      '#description' => 'aantal weekends voor reguliere passagiers',
      '#weight' => 5,
    ];
    $form['reeks'] = [
      '#type' => 'submit',
      '#value' => 'Maak datum reeks aan',
      '#prefix' => '<div id="reeks-div">',
      '#suffix' => '</div>',
      '#weight' => 6,
    ];

    // Toevoegen van een enkele dag
    $form[2]['#type'] = 'markup';
    $form[2]['#markup'] = '<p><h2>Toevoegen van een dag voor reserveringen</h2></p>';
    $form[2]['#markup'] .= '<p>Geef de gewenste datum</p>';
    $form[2]['#weight'] = 7;

    $form['dag'] = [
      '#title' => 'Nieuwe beschikbare datum',
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '',
      '#description' => 'Datum in JJJJ-MM-DD formaat',
      '#weight' => 8,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Voeg toe',
      '#weight' => 9,
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $messenger = Drupal::messenger();
    $op = $form_state->getValue('op');

    if ($op == 'Verwijder gemarkeerde items') {
      // geen database actie?
      $nr_dagen = $form_state->getValue('nr_dagen');
      $dagenlist = $form_state->getValue('dagenlist');
      // checked status must be verified via user input rather than via value
      $input = $form_state->getUserInput();
      for ($i=0; $i < $nr_dagen; $i++) {
        $dag = $dagenlist['dagen'][$i];
        if (key_exists($i, $input['dagenlist']['checkboxes'])) {
          $checked = $input['dagenlist']['checkboxes'][$i];
        }
        else $checked = false;
        if ($checked) {
          //remove dag
          $dag_remove = new EzacPassagierDag(EzacPassagierDag::getId($dag));
          $num_deleted = $dag_remove->delete();
          if ($num_deleted) {
            $messenger->addMessage('Datum ' .$form_state->getValue('dagenlist')['dagen'][$i] .' is verwijderd');
          }
        }
      }
      $form_state->setRebuild(true);
    }
    elseif ($op == 'Maak datum reeks aan') { // aanmaken datum reeks
      $serie_start = $form_state->getValue('serie_start');
      if (empty($serie_start)) {
        $form_state->setErrorByName('serie_start', 'Geen datum ingevuld');
        return;
      }
      // validate $dag field for valid date
      $datum = explode('-', $serie_start);
      if (!checkdate($datum[1], $datum[2], $datum[0])) {
        $form_state->setErrorByName('serie_start', "datum $datum is onjuist");
        return;
      }
      // check of reeks op een zaterdag begint
      $weekday = date('l', strtotime($serie_start));
      //dpm($weekday, 'weekday'); //debug
      if ($weekday <> 'Saturday') {
        $form_state->setErrorByName('serie_start', "datum reeks moet op een zaterdag beginnen");
        return;
      }
      $datum2 = sprintf('%04u-%02u-%02u', $datum[0], $datum[1], $datum[2]);
      // check whether dag already exists
      $exists = (EzacPassagierDag::getId($datum2) != null);
      if ($exists) {
        $form_state->setErrorByName('serie_start', "Datum $datum2 bestaat al");
        return;
      }
      $serie_aantal = $form_state->getValue('serie_aantal');
      if (!is_numeric($serie_aantal)) {
        $form_state->setErrorByName('serie_aantal', "Ongeldig aantal weken $serie_aantal");
        return;
      }
      if (intval($serie_aantal) < 2) {
        $form_state->setErrorByName('serie_aantal', "Te weinig weken [$serie_aantal]");
        return;
      }
      if (intval($serie_aantal) > 52) {
        $form_state->setErrorByName('serie_aantal', "Te veel weken [$serie_aantal]");
        return;
      }
    }
    else { //opvoeren dag
      $dag = $form_state->getValue('dag');
      if (empty($dag)) {
        $form_state->setErrorByName('dag', 'Geen datum ingevuld');
        return;
      }
      // validate $dag field for valid date
      $datum = explode('-', $dag);
      if (!checkdate($datum[1], $datum[2], $datum[0])) {
        $form_state->setErrorByName('dag', "datum $dag is onjuist");
        return;
      }
      // @TODO - check op weekend

      $datum2 = sprintf('%04u-%02u-%02u', $datum[0], $datum[1], $datum[2]);
      // check whether dag already exists
      $exists = (EzacPassagierDag::getId($datum2) != null);

      if ($exists) {
        $form_state->setErrorByName('dag', "Datum $dag bestaat al");
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = Drupal::messenger();

    // start D7 code

    // create slot in database
    $op = $form_state->getValue('op');
    if ($op == t('Verwijder gemarkeerde items')) { //verwijderen datum
      // geen database actie?
    }
    elseif ($op == t('Maak datum reeks aan')) { //aanmaken datum reeks
      $serie_start = $form_state->getValue('serie_start');
      $serie_aantal = $form_state->getValue('serie_aantal');
      $datum = $serie_start;
      for ($i=0; $i<$serie_aantal; $i++) {
        //aanmaken zaterdag
        try {
          $dag = new EzacPassagierDag();
          $dag->datum = $datum;
          $id = $dag->create();
        } catch (Throwable $e) {
          $message = $e->getMessage();
          $messenger->addMessage("Datum $datum niet opnieuw aangemaakt");
        }
        // datum naar zondag
        $datum = date('Y-m-d', strtotime($datum .' next day'));
        //aanmaken zondag
        try {
          $dag = new EzacPassagierDag();
          $dag->datum = $datum;
          $id = $dag->create();
        } catch (Throwable $e) {
          $message = $e->getMessage();
          $messenger->addMessage("Datum $datum niet opnieuw aangemaakt");
        }
        //datum naar volgende zaterdag
        $datum = date('Y-m-d', strtotime($datum .' next saturday'));
      }
      $messenger->addMessage("$i weekends aangemaakt vanaf $serie_start");
      return;
    }
    else { //opvoeren datum
      $dag = $form_state->getValue('dag');
      $datum = explode('-', $dag);
      $datum2 = sprintf('%04u-%02u-%02u', $datum[0], $datum[1], $datum[2]);
      $dag = new EzacPassagierDag();
      $dag->datum = $datum2;
      $id = $dag->create();
      $messenger->addMessage("Datum $datum2 toegevoegd");
    }
    return;

    // end D7 code

  } //submitForm

}
