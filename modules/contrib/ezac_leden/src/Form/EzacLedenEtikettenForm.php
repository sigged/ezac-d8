<?php

namespace Drupal\ezac_leden\Form;

use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * UI to update leden recordvoor ledenlijst
 */
class EzacLedenEtikettenForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ezac_leden_etiketten_form';
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
      '#prefix' => '<div id="ledenlijstform">',
      '#suffix' => '</div>',
    ];

    $form['selectie'] = [
      '#title' => t('Etiketten voor'),
      '#type' => 'radios',           // select is misschien mooier !
      '#default_value' => 'clubblad',
      '#options' => [
        'clubblad' => 'Clubblad',
        'vergadering' => 'Ledenvergadering',
        'receptie' => 'Receptie',
        'VL' => 'Vliegende leden',
        'camping' => 'Camping',
        'alles' => 'Alle leden',
      ],
      '#description' => t('Selecteer de doelgroep'),
    ];
    $form['sortering'] = [
      '#title' => t('Sortering'),
      '#description' => t('Kies de sortering'),
      '#type' => 'radios',
      '#default_value' => 'adres',
      '#options' => [
        'adres' => 'op Adres',
        'achternaam' => 'op Naam',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Download etikettenbestand'),
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
    $selectie = $form_state->getValue('selectie');
    $sortering = $form_state->getValue('sortering');
    $messenger->addMessage("Etiketten voor $selectie op $sortering aangemaakt",'status');

    //redirect to etiketten export
    $redirect = Url::fromRoute(
      'ezac_leden_etiketten_export',
      [
        'selectie' => $selectie,
        'sortering' => $sortering,
      ]
    );
    $form_state->setRedirectUrl($redirect);

  } //submitForm

}
