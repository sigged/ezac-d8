<?php

namespace Drupal\ezacKisten\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezacKisten\Model\EzacKist;

/**
 * UI to update kisten record
 */
class EzacKistenUpdateForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId()
    {
        return 'ezac_kisten_update_form';
    }

    /**
     * buildForm for KISTEN update with ID parameter
     * This is also used to CREATE new kisten record (no ID param)
     * @param array $form
     * @param FormStateInterface $form_state
     * @param null $id
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
    {
        // Wrap the form in a div.
        $form = [
            '#prefix' => '<div id="updateform">',
            '#suffix' => '</div>',
        ];
        // Query for items to display.
        // if $id is set, perform UPDATE else CREATE
        if (isset($id)) {
            $kist = (new EzacKist)->read($id);
            //$lid = new EzacLid($id); // using constructor
            $newRecord = FALSE;
        } else { // prepare new record
            $kist = new EzacKist(); // create empty lid occurrence
            $newRecord = TRUE;
        }

        //store indicator for new record for submit function
        $form['new'] = [
            '#type' => 'value',
            '#value' => $newRecord, // TRUE or FALSE
        ];

        $options_yn = [t('Nee'), t('Ja')];

        //Naam Type Omvang
        $form['registratie'] = [
            '#title' => t('Registratie'),
            '#type' => 'textfield',
            '#description' => t('PH-'),
            '#default_value' => $kist->registratie,
            '#maxlength' => 7,
            '#required' => TRUE,
            '#size' => 7,
            '#weight' => 1,];
        $form['callsign'] = [
            '#title' => t('Callsign'),
            '#type' => 'textfield',
            '#description' => t('Wedstrijdnummer'),
            '#default_value' => $kist->callsign,
            '#maxlength' => 5,
            '#required' => FALSE,
            '#size' => 5,
            '#weight' => 2,];
        $form['type'] = [
            '#title' => t('Type'),
            '#type' => 'textfield',
            '#description' => t('Type vliegtuig'),
            '#default_value' => $kist->type,
            '#maxlength' => 9,
            '#required' => FALSE,
            '#size' => 9,
            '#weight' => 3,];
        $form['bouwjaar'] = [
            '#title' => t('Bouwjaar'),
            '#type' => 'textfield',
            '#description' => t('Bouwjaar'),
            '#default_value' => $kist->bouwjaar,
            '#maxlength' => 4,
            '#required' => FALSE,
            '#size' => 4,
            '#weight' => 4,];
        $form['inzittenden'] = [
            '#title' => t('Inzittenden'),
            '#type' => 'number',
            '#description' => t('Aantal inzittenden'),
            '#default_value' => $kist->inzittenden,
            '#maxlength' => 21,
            '#required' => TRUE,
            '#weight' => 5,];
        $form['flarm'] = [
            '#title' => t('Flarm'),
            '#type' => 'textfield',
            '#description' => t('Flarm adres (6 hex)'),
            '#default_value' => $kist->flarm,
            '#maxlength' => 6,
            '#required' => FALSE,
            '#size' => 6,
            '#weight' => 6,];
        $form['adsb'] = [
            '#title' => t('ADSB'),
            '#type' => 'textfield',
            '#description' => t('ADSB adres (6 hex)'),
            '#default_value' => $kist->adsb,
            '#maxlength' => 6,
            '#required' => FALSE,
            '#size' => 6,
            '#weight' => 7,];
        $form['eigenaar'] = [
            '#title' => t('Eigenaar'),
            '#type' => 'textfield',
            '#description' => t('Eigenaar'),
            '#default_value' => $kist->eigenaar,
            '#maxlength' => 20,
            '#required' => FALSE,
            '#size' => 20,
            '#weight' => 8,];
        $form['prive'] = [
            '#title' => t('Prive'),
            '#type' => 'select',
            '#options' => $options_yn,
            '#description' => t('Prive kist?'),
            '#default_value' => $kist->prive,
            '#maxlength' => 1,
            '#required' => TRUE,
            '#size' => 1,
            '#weight' => 9,];
        $form['opmerking'] = [
            '#title' => t('Opmerking'),
            '#type' => 'textfield',
            '#description' => t('Opmerking'),
            '#default_value' => $kist->opmerking,
            '#maxlength' => 27,
            '#required' => FALSE,
            '#size' => 27,
            '#weight' => 14];
        $form['actief'] = [
            '#title' => t('Actief'),
            '#type' => 'select',
            '#options' => $options_yn,
            '#description' => t('Nog in gebruik?'),
            '#default_value' => $kist->actief,
            '#maxlength' => 1,
            '#required' => TRUE,
            '#size' => 1,
            '#weight' => 15];

        //Id
        //Toon het het Id nummer van het record
        $form['id'] = [
            '#type' => 'hidden',
            '#title' => t('Record nummer (Id)'),
            '#maxlength' => 8,
            '#size' => 8,
            '#value' => $kist->id,
            '#weight' => 36
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $newRecord ? t('Invoeren') : t('Update'),
            '#weight' => 39
        ];

        //insert Delete button  gevaarlijk ivm dependencies
        if (\Drupal::currentUser()->hasPermission('EZAC_delete')) {
            if (!$newRecord) {
                $form['delete'] = [
                    '#type' => 'submit',
                    '#value' => t('Verwijderen'),
                ];
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm( &$form, FormStateInterface $form_state) //removed array type
    {

        // perform validate for edit of record
        $registratie = $form_state->getValue('registratie');
        if ($registratie <> $form['registratie']['#default_value']) {
            if (EzacKist::counter(['registratie' => $registratie])) {
                $form_state->setErrorByName('registratie', t("Registratie $registratie bestaat al"));
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function submitForm( &$form, FormStateInterface $form_state) // removed array type
    {
        $messenger = \Drupal::messenger();

        // delete record
        if ($form_state->getValue('op') == 'Verwijderen') {
            if (!\Drupal::currentUser()->hasPermission('EZAC_delete')) {
                $messenger->addMessage('Verwijderen niet toegestaan', $messenger::TYPE_ERROR);
                return;
            }
            $kist = new EzacKist; // initiate Kist instance
            $kist->id = $form_state->getValue('id');
            $count = $kist->delete(); // delete record in database
            $messenger->addMessage("$count record verwijderd");
        } else {
            // Save the submitted entry.
            $kist = new EzacKist();
            // get all fields
            foreach (EzacKist::$fields as $field => $description) {
                $kist->$field = $form_state->getValue($field);
            }
            //Check value newRecord to select insert or update
            if (TRUE == $form_state->getValue('new')) {
                $kist->create(); // add record in database
                $messenger->addMessage("Kisten record aangemaakt met id [$kist->id]", $messenger::TYPE_STATUS);

            } else {
                $count = $kist->update(); // update record in database
                $messenger->addMessage("$count record updated", $messenger::TYPE_STATUS);
            }
        }
        //go back to leden overzicht
        $redirect = Url::fromRoute(
            'ezac_kisten_overzicht'
        );
        $form_state->setRedirectUrl($redirect);
    } //submitForm
}
