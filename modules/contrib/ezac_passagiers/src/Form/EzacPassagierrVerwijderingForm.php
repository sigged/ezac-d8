<?php

namespace Drupal\ezac_passagiers\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_passagiers\Controller\EzacPassagiersController;
use Drupal\ezac_passagiers\Model\EzacPassagier;

/**
 * UI to show passagiers verwijder form
 * Verwijdering reservering door EZAC lid
 */
class EzacPassagierrVerwijderingForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_passagiers_verwijdering_form';
  }

  /**
   * buildForm for passagiers dagen
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param int|null $id
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = null) {
    $messenger = Drupal::messenger();

    // read settings
    $settings = Drupal::config('ezac_passagiers.settings');
    $slots = $settings->get('slots');
    $dagen = $settings->get('dagen');
    $texts = $settings->get('texts');

    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="annuleringform">',
      '#suffix' => '</div>',
    ];

    if (isset($id) and is_numeric($id)) {
      $reservering = new EzacPassagier($id);
    }
    else {
      $messenger->addMessage("Ongeldig id $id", 'error');
      $form_state->setRedirect('ezac_passagiers');
      return $form;
    }
    if ($reservering == null) {
      $messenger->addMessage("Reservering $id niet gevonden", 'error');
      $form_state->setRedirect('ezac_passagiers');
      return $form;
    }
    $datum = EzacUtil::showDate($reservering->datum);
    $tijd = substr($reservering->tijd, 0, 5);
    $naam = $reservering->naam;
    $telefoon = $reservering->telefoon;
    $email = $reservering->mail;

    $form[0]['#type'] = 'markup';
    $form[0]['#markup'] = $texts['verwijder_reservering'];
    $form[0]['#weight'] = 0;
    $form[0]['#prefix'] = '<div class="ezacpass-intro-div">';
    $form[0]['#suffix'] = '</div>';

    $form[1]['#type'] = 'markup';
    $form[1]['#markup'] = "<p>Datum: $datum om $tijd";
    $form[1]['#markup'] .= "<br>Naam: $naam";
    $form[1]['#markup'] .= "<br>Telefoon: $telefoon";
    $form[1]['#markup'] .= "<br>E-mail: $email</p>";
    $form[1]['#weight'] = 1;

    $form['reden']['#type'] = 'textfield';
    $form['reden']['#title'] = 'Geef de reden voor de annulering';
    $form['reden']['#maxlength'] = 30;
    $form['reden']['#size'] = 30;
    $form['reden']['#weight'] = 2;

    $form[2]['#type'] = 'markup';
    $form[2]['#markup'] = $texts['verwijder_waarschuwing'];
    $form[2]['#prefix'] = '<div class="ezacpass-intro-div">';
    $form[2]['#suffix'] = '</div>';
    $form[2]['#weight'] = 3;

    $form['id'] = array(
      '#type'=> 'hidden',
      '#value'=> $id,
    );

    $form['remove'] = array(
      '#type'  => 'submit',
      '#value' => t('Verwijder_reservering'),
      '#weight' => 4,
    );

    $form['cancel'] = array(
      '#type'  => 'submit',
      '#value' => t('Annuleren'),
      '#weight' => 5,
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //$messenger = Drupal::messenger();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = Drupal::messenger();

    $op = $form_state->getValue('op');
    if ($op == 'Verwijder reservering') {
      $id = $form_state['values']['id'];
      $reden = $form_state['values']['reden'];
      // verwijder reservering en mail passagier
      EzacPassagiersController::verwijderen($id, $reden);
    }
    else {
      $messenger->addMessage("Geen reservering verwijderd", 'status');
    }
    $form_state->setRedirect('passagiers');
  }

}
