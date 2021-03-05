<?php

namespace Drupal\ezac_leden\Form;

use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * UI for export leden to davilex
 */
class EzacLedenDavilexForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_leden_davilex_form';
  }

  /**
   * buildForm voor davilex export tbv penningmeester
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="davilexform">',
      '#suffix' => '</div>',
    ];

    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => t('bestandsnaam'),
      '#maxlength' => 20,
      '#size' => 20,
      '#default_value' => 'davilex.txt',
      '#weight' => 1,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Exporteer'),
      '#weight' => 3,
    ];

    return $form;
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
    $messenger = Drupal::messenger();

    $filename = $form_state->getValue('filename');
    if ($filename == '') $filename = 'davilex.txt';

    $messenger->addMessage("Output naar bestand [$filename]", 'status'); //DEBUG

    //redirect to davilex export

    $redirect = Url::fromRoute(
      'ezac_leden_davilex_export',
      [
        'filename' => $filename,
      ]
    );
    $form_state->setRedirectUrl($redirect);
    return;

  } //submitForm

}
