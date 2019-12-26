<?php

namespace Drupal\ezacLeden\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezacLeden\Model\EzacLid;

/**
 * UI to update leden record
 * tijdelijke aanpassing
 */
class EzacLedenUpdateForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId()
    {
        return 'ezac_leden_update_form';
    }

    /**
     * buildForm for LEDEN update with ID parameter
     * This is also used to CREATE new leden record (no ID param)
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

        // apply the form theme
        //$form['#theme'] = 'ezac_leden_update_form';

        // Query for items to display.
        // if $id is set, perform UPDATE else CREATE
        if (isset($id)) {
            $lid = (new EzacLid)->read($id);
            //$lid = new EzacLid($id); // using constructor
            $newRecord = FALSE;
        } else { // prepare new record
            $lid = new EzacLid(); // create empty lid occurrence
            $newRecord = TRUE;
        }

        //store indicator for new record for submit function
        $form['new'] = [
            '#type' => 'value',
            '#value' => $newRecord, // TRUE or FALSE
        ];

        $options_yn = [t('Nee'), t('Ja')];

        //Naam Type Omvang
        //VOORVOEG Tekst 11
        $form['voorvoeg'] = [
            '#title' => t('Voorvoeg'),
            '#type' => 'textfield',
            '#description' => t('Voorvoegsel'),
            '#default_value' => $lid->voorvoeg,
            '#maxlength' => 11,
            '#required' => FALSE,
            '#size' => 11,
            '#weight' => 1,];
        //ACHTERNAAM Tekst 35
        $form['achternaam'] = [
            '#title' => t('Achternaam'),
            '#type' => 'textfield',
            '#description' => t('Achternaam'),
            '#default_value' => $lid->achternaam,
            '#maxlength' => 35,
            '#required' => TRUE,
            '#size' => 35,
            '#weight' => 2,];
        //AFKORTING Tekst 9
        $form['afkorting'] = [
            '#title' => t('Afkorting'),
            '#type' => 'textfield',
            '#description' => t('UNIEKE afkorting voor startadministratie'),
            '#default_value' => $lid->afkorting,
            '#maxlength' => 9,
            '#required' => FALSE,
            '#size' => 9,
            '#weight' => 3,];
        //VOORNAAM Tekst 13
        $form['voornaam'] = [
            '#title' => t('Voornaam'),
            '#type' => 'textfield',
            '#description' => t('Voornaam'),
            '#default_value' => $lid->voornaam,
            '#maxlength' => 13,
            '#required' => TRUE,
            '#size' => 13,
            '#weight' => 4,];
        //VOORLETTER Tekst 21
        $form['voorletter'] = [
            '#title' => t('Voorletters'),
            '#type' => 'textfield',
            '#description' => t('Voorletters'),
            '#default_value' => $lid->voorletter,
            '#maxlength' => 21,
            '#required' => TRUE,
            '#size' => 21,
            '#weight' => 5,];
        //ADRES Tekst 26
        $form['adres'] = [
            '#title' => t('Adres'),
            '#type' => 'textfield',
            '#description' => t('Adres'),
            '#default_value' => $lid->adres,
            '#maxlength' => 26,
            '#required' => TRUE,
            '#size' => 26,
            '#weight' => 6,];
        //POSTCODE Tekst 9
        $form['postcode'] = [
            '#title' => t('Postcode'),
            '#type' => 'textfield',
            '#description' => t('Postcode'),
            '#default_value' => $lid->postcode,
            '#maxlength' => 9,
            '#required' => TRUE,
            '#size' => 9,
            '#weight' => 7,];
        //PLAATS Tekst 24
        $form['plaats'] = [
            '#title' => t('Plaats'),
            '#type' => 'textfield',
            '#description' => t('Plaats'),
            '#default_value' => $lid->plaats,
            '#maxlength' => 24,
            '#required' => TRUE,
            '#size' => 24,
            '#weight' => 8,];
        //TELEFOON Tekst 14
        $form['telefoon'] = [
            '#title' => t('Telefoon'),
            '#type' => 'textfield',
            '#description' => t('Telefoon'),
            '#default_value' => $lid->telefoon,
            '#maxlength' => 14,
            '#required' => FALSE,
            '#size' => 14,
            '#weight' => 9,];
        //Mobiel Tekst 50
        $form['mobiel'] = [
            '#title' => t('Mobiel'),
            '#type' => 'textfield',
            '#description' => t('Mobiel nummer'),
            '#default_value' => $lid->mobiel,
            '#maxlength' => 20,
            '#required' => false,
            '#size' => 14,
            '#weight' => 10,];
        //LAND Tekst 10
        $form['land'] = [
            '#title' => t('Land'),
            '#type' => 'textfield',
            '#description' => t('Land'),
            '#default_value' => $lid->land,
            '#maxlength' => 10,
            '#required' => FALSE,
            '#size' => 10,
            '#weight' => 11,];
        //CODE Tekst 5
        //$default_soort = array_search($lid->code, EzacLid::$lidCode);
        $form['code'] = [
            '#title' => t('Code'),
            '#type' => 'select',
            '#default_value' => $lid->code,
            '#description' => t('Soort lidmaatschap (code)'),
            '#options' => EzacLid::$lidCode,
            '#weight' => 12
        ];
        $form['tienrittenkaart'] = [
            '#title' => t('Tienrittenkaart'),
            '#type' => 'select',
            '#options' => $options_yn,
            '#description' => t('Tienrittenkaarthouder?'),
            '#default_value' => $lid->tienrittenkaart,
            '#maxlength' => 1,
            '#required' => TRUE,
            '#size' => 1,
            '#weight' => 12];
        //GEBOORTEDA Datum/tijd 8
        $gd = substr($lid->geboorteda, 0, 10);
        if ($gd != NULL) {
            $lv = explode('-', $gd);
            $gebdat = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $gebdat = '';
        $form['geboortedatum'] = [
            '#title' => t('Geboortedatum'),
            '#type' => 'textfield',
            '#description' => t('Geboortedatum [dd-mm-jjjj]'),
            '#default_value' => $gebdat,
            '#maxlength' => 10,
            '#required' => FALSE,
            '#size' => 10,
            '#weight' => 13
        ];
        //OPMERKING Tekst 27
        $form['opmerking'] = [
            '#title' => t('Opmerking'),
            '#type' => 'textfield',
            '#description' => t('Opmerking'),
            '#default_value' => $lid->opmerking,
            '#maxlength' => 27,
            '#required' => FALSE,
            '#size' => 27,
            '#weight' => 14];
        //INSTRUCTEU Tekst 9
        //Actief Ja/nee 1
        $form['actief'] = [
            '#title' => t('Actief'),
            '#type' => 'select',
            '#options' => $options_yn,
            '#description' => t('Nog actief lid?'),
            '#default_value' => $lid->actief,
            '#maxlength' => 1,
            '#required' => TRUE,
            '#size' => 1,
            '#weight' => 15];
        //LID_VAN Datum/tijd 8
        $ls = substr($lid->lid_van, 0, 10);
        if ($ls != NULL) {
            $lv = explode('-', $ls);
            $lid_van = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $lid_van = '';
        //$lv = explode('-', $lid->lid_van);
        //$lid_van = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        $form['lidvan'] = [
            '#title' => t('Lid vanaf'),
            '#type' => 'textfield', //DATE
            '#description' => t('Ingangsdatum lidmaatschap [dd-mm-jjjj]'),
            '#default_value' => $lid_van,
            '#maxlength' => 10,
            '#required' => FALSE,
            '#size' => 10,
            '#weight' => 16];
        //LID_EIND Datum/tijd 8
        $le = substr($lid->lid_eind, 0, 10);
        if ($le != NULL) {
            $lv = explode('-', $le);
            $lid_eind = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $lid_eind = '';
        $form['lideind'] = [
            '#title' => t('Lid einde'),
            '#type' => 'date', //DATE
            '#description' => t('Datum einde lidmaatschap [dd-mm-jjjj]'),
            '#default_value' => $lid_eind,
            '#maxlength' => 10,
            '#required' => FALSE,
            '#size' => 10,
            '#weight' => 17];

        //leerling Ja/nee 0
        $form['leerling'] = [
            '#title' => t('Leerling'),
            '#type' => 'select',
            '#default_value' => $lid->leerling,
            '#description' => t('Leerling (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 18
        ];
        //Instructie Ja/nee 1
        $form['instructie'] = [
            '#title' => t('Instructie'),
            '#type' => 'select',
            '#default_value' => $lid->instructie,
            '#description' => t('Instructeur (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 19
        ];

        //E_mail Tekst 50
        $form['e_mail'] = [
            '#title' => t('E-mail'),
            '#type' => 'email',
            '#description' => t('E-mail adres'),
            '#default_value' => $lid->e_mail,
            '#maxlength' => 50,
            '#required' => FALSE,
            '#size' => 30,
            '#weight' => 20];

        //Babyvriend Ja/nee 1
        $form['babyvriend'] = [
            '#title' => t('Babyvriend'),
            '#type' => 'select',
            '#default_value' => $lid->babyvriend,
            '#description' => t('Vriend van Nico Baby(Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 27
        ];
        //Ledenlijstje Ja/nee 1
        $form['ledenlijst'] = [
            '#title' => t('Ledenlijst'),
            '#type' => 'select',
            '#default_value' => $lid->ledenlijstje,
            '#description' => t('Vermelding op ledenlijst (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 28
        ];

        //Etiketje Ja/nee 1
        $form['etiket'] = [
            '#title' => t('Etiket'),
            '#type' => 'select',
            '#default_value' => $lid->etiketje,
            '#description' => t('Etiket afdrukken (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 29
        ];

        //User Tekst 50
        $form['user'] = [
            '#title' => t('UserCode website'),
            '#type' => 'textfield',
            '#description' => t('Usercode website (VVAAAA)'),
            '#default_value' => $lid->user,
            '#maxlength' => 6,
            '#required' => FALSE,
            '#size' => 6,
            '#weight' => 31
        ];

        //seniorlid Ja/nee 1
        $form['seniorlid'] = [
            '#title' => t('Senior lid'),
            '#type' => 'select',
            '#default_value' => $lid->seniorlid,
            '#description' => t('Senior lid status (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 32
        ];

        //jeugdlid Ja/nee 1
        $form['jeugdlid'] = [
            '#title' => t('Jeugd / inwonend lid'),
            '#type' => 'select',
            '#default_value' => $lid->jeugdlid,
            '#description' => t('Jeugd- of inwonend lid (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 33
        ];

        //PEonderhoud Ja/nee 1
        $form['peonderhoud'] = [
            '#title' => t('Prive Eigenaar onderhoud (CAMO)'),
            '#type' => 'select',
            '#default_value' => $lid->peonderhoud,
            '#description' => t('Prive Eigenaar onderhoud (Ja/nee)'),
            '#options' => $options_yn,
            '#weight' => 34
        ];

        //Slotcode varchar(8)
        $form['slotcode'] = [
            '#title' => t('Slotcode'),
            '#type' => 'textfield',
            '#description' => t('Slotcode (nnnnnn)'),
            '#default_value' => $lid->slotcode,
            '#maxlength' => 8,
            '#required' => FALSE,
            '#size' => 8,
            '#weight' => 35
        ];

        //Mutatie timestamp
        //maak tekstlabel met datum laatste wijziging (wordt automatisch bijgewerkt)

        //Id
        //Toon het het Id nummer van het record
        $form['id'] = [
            '#type' => 'hidden',
            '#title' => t('Record nummer (Id)'),
            '#maxlength' => 8,
            '#size' => 8,
            '#value' => $lid->id,
            '#weight' => 36
        ];

        //WijzigingSoort
        //Toon de soort mutatie NIEUW WIJZIGING VERVALLEN
        $form['wijzigingsoort'] = [
            '#type' => 'hidden',
            '#title' => t('Soort wijziging'),
            '#maxlength' => 15,
            '#size' => 15,
            '#value' => $lid->WijzigingSoort,
            '#weight' => 37
        ];

        //KenEZACvan
        //Hoe is EZAC ontdekt
        $form['kenezacvan'] = [
            '#type' => 'textfield',
            '#title' => t('Ken EZAC van'),
            '#default_value' => $lid->kenezacvan,
            '#maxlength' => 20,
            '#size' => 20,
            '#weight' => 38
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
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

        // perform validate for edit of record

        // Voorvoeg
        // Achternaam
        // Afkorting
        $afkorting = $form_state->getValue('afkorting');
        if ($afkorting <> $form['afkorting']['#default_value']) {
            if (EzacLid::counter(['afkorting' => $afkorting])) {
                $form_state->setErrorByName('afk', t("Afkorting $afkorting bestaat al"));
            }
        }
        if (!array_key_exists($form_state->getValue('code'), EzacLid::$lidCode)) {
            $form_state->setErrorByName('code', t("Ongeldige code"));
        }
        // Voornaam
        // Voorletter
        // Adres
        // Postcode
        // Plaats
        // Telefoon
        // Mobiel
        // Land
        // Code
        // Geboorteda
        $dat = $form_state['values']['geboortedatum'];
        if ($dat !== '') {
            $lv = explode('-', $dat);
            if (checkdate($lv[1], $lv[0], $lv[2]) == FALSE) {
                $form_state->setErrorByName('geboortedatum', t('Geboortedatum is onjuist'));
            }
        }
        // Opmerking
        // Instructeu
        // Actief
        // Lid_van
        $dat = $form_state['values']['lidvan'];
        if ($dat !== '') {
            $lv = explode('-', $dat);
            if (checkdate($lv[1], $lv[0], $lv[2]) == FALSE) {
                $form_state->setErrorByName('lidvan', 'Datum begin lidmaatschap is onjuist');
            }
        }

        // Lid_eind
        $dat = $form_state['values']['lideind'];
        if ($dat !== '') {
            $lv = explode('-', $dat);
            if (checkdate($lv[1], $lv[0], $lv[2]) == FALSE) {
                $form_state->setErrorByName('lideind', 'Datum einde lidmaatschap is onjuist');
            }
        }
        // E_mail
        // Babyvriend
        // Ledenlijst
        // Etiketje
        // User
        // seniorlid
        // jeugdlid
        // PEonderhoud
        // Slotcode
        // Mutatie
        // KenEZACvan
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $messenger = \Drupal::messenger();

        // delete record
        if ($form_state->getValue('op') == 'Verwijderen') {
            if (!\Drupal::currentUser()->hasPermission('DLO_delete')) {
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
        //go back to leden overzicht
        $redirect = Url::fromRoute(
            'ezac_leden'
        );
        $form_state->setRedirectUrl($redirect);
    } //submitForm
}
