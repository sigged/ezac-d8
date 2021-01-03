<?php

namespace Drupal\ezac_rooster\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_rooster\Model\EzacRooster;

/**
 * UI to update rooster record
 */

class EzacRoosterUpdateForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId()
    {
        return 'ezac_rooster_update_form';
    }

    /**
     * buildForm for rooster update with ID parameter
     * This is also used to CREATE new rooster record (no ID param)
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
            $rooster = new EzacRooster($id); // using constructor
            $newRecord = FALSE;
        } else { // prepare new record
            $rooster = new EzacRooster(); // create empty rooster occurrence
            $newRecord = TRUE;
        }

        //store indicator for new record for submit function
        $form['new'] = [
            '#type' => 'value',
            '#value' => $newRecord, // TRUE or FALSE
        ];

      // get names of leden
      $condition = [
        'actief' => TRUE,
        'code' => 'VL',
      ];
      $leden = EzacUtil::getLeden($condition);
      //set up diensten
      $diensten = Drupal::config('ezac_rooster.settings')->get('rooster.diensten');
      //set up periode
      $periodes = Drupal::config('ezac_rooster.settings')->get('rooster.periodes');
      //Naam Type Omvang
      //DATUM date 11
      $form = EzacUtil::addField($form,'datum', 'date','Datum', 'Datum', $rooster->datum, 11, 11, TRUE, 1);
      //PERIODE Tekst 1
      $form = EzacUtil::addField($form,'periode', 'select','Periode', 'Periode', $rooster->periode, 20, 1, TRUE, 2, $periodes);
      //DIENST Tekst 1
      $form = EzacUtil::addField($form,'dienst', 'select','Dienst', 'Dienst', $rooster->dienst, 1, 1, TRUE, 3, $diensten);
      //NAAM Tekst 13
      $form = EzacUtil::addField($form,'naam', 'select', 'Naam', 'Naam', $rooster->naam, 20, 1, TRUE, 4, $leden);
      //GERUILD Tekst 20
      $form = EzacUtil::addField($form,'geruild', 'select', 'Geruild met', 'Geruild met', $rooster->geruild, 20, 1, TRUE, 6, $leden);
      //MUTATIE
      //@TODO mutatie is datetime, form is voor date
      $form = EzacUtil::addField($form,'mutatie', 'date','Mutatie', 'Mutatie', $rooster->mutatie, 11, 11, FALSE, 5);

      //Mutatie timestamp
      //maak tekstlabel met datum laatste wijziging (wordt automatisch bijgewerkt)

      //Id
      //Toon het het Id nummer van het record
      $form = EzacUtil::addField($form,'id', 'hidden','Record nummer (Id)', '', $rooster->id, 8, 8, FALSE, 28);

      $form['actions'] = [
          '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $newRecord ? t('Invoeren') : t('Update'),
          '#weight' => 31
      ];

      //insert Delete button  gevaarlijk ivm dependencies
      if (Drupal::currentUser()->hasPermission('EZAC_delete')) {
          if (!$newRecord) {
              $form['actions']['delete'] = [
                  '#type' => 'submit',
                  '#value' => t('Verwijderen'),
                  '#weight' => 32
              ];
          }
      }
      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

        // perform validate for edit of record

      // datum
      $dat = $form_state->getValue('datum');
      if ($dat !== '') {
        $lv = explode('-', $dat);
        if (checkdate($lv[1], $lv[0], $lv[2]) == FALSE) {
          $form_state->setErrorByName('datum', t('Datum is onjuist'));
        }
      }

      // periode
      // dienst
      // naam
      $naam = $form_state->getValue('naam');
      if ($naam <> $form['naam']['#default_value']) {
          if (EzacLid::index(['afkorting' => $naam]) == []) { // not found
              $form_state->setErrorByName('naam', t("Afkorting $naam bestaat niet"));
          }
      }
      //mutatie
      //geruild
      $geruild = $form_state->getValue('geruild');
      if (isset($geruild)) {
        if ($geruild <> $form['geruild']['#default_value']) {
          if (EzacLid::index(['afkorting' => $geruild]) == []) { // not found
            $form_state->setErrorByName('naam', t("Afkorting $geruild bestaat niet"));

          }
        }
      }

    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $messenger = Drupal::messenger();

        // delete record
        if ($form_state->getValue('op') == 'Verwijderen') {
            if (!Drupal::currentUser()->hasPermission('DLO_delete')) {
                $messenger->addMessage('Verwijderen niet toegestaan', $messenger::TYPE_ERROR);
                return;
            }
            $lid = new EzacLid; // initiate Lid instance
            $lid->id = $form_state->getValue('id');
            $count = $lid->delete(); // delete record in database
            $messenger->addMessage("$count record verwijderd");
        } else {
            // Save the submitted entry.
            $lid = new EzacLid;
            // get all fields
            foreach (EzacLid::$fields as $field => $description) {
                $lid->$field = $form_state->getValue($field);
            }
            //Check value newRecord to select insert or update
            if ($form_state->getValue('new') == TRUE) {
                $lid->create(); // add record in database
                $messenger->addMessage("Leden record aangemaakt met id [$lid->id]", $messenger::TYPE_STATUS);

            } else {
                $count = $lid->update(); // update record in database
                $messenger->addMessage("$count record updated", $messenger::TYPE_STATUS);
            }
        }
        //go back to rooster overzicht
        $redirect = Url::fromRoute(
            'ezac_rooster'
        );
        $form_state->setRedirectUrl($redirect);
    } //submitForm
}
