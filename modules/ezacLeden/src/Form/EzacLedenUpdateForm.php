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

class formUtil
{
    /**
     * @file
     * adds a field to a form
     * @param array $form
     * @param string $label
     * @param string $type
     * @param string $title
     * @param string $description
     * @param string $default_value
     * @param integer $maxlength
     * @param integer $size
     * @param boolean $required
     * @param integer $weight
     * @param array $options
     * @return array
     */
    public static function addField(array $form,string $label, string $type,string $title, string $description, string $default_value, int $maxlength, int $size, bool $required, int $weight, array $options = [])
    {
        if (isset($type)) $form[$label]['#type'] = $type;
        if (isset($title)) $form[$label]['#title'] = $title;
        if (isset($description)) $form[$label]['#description'] = $description;
        if (isset($default_value)) $form[$label]['#default_value'] = $default_value;
        if (isset($maxlength)) $form[$label]['#maxlength'] = $maxlength;
        if (isset($size)) $form[$label]['#size'] = $size;
        if (isset($required)) $form[$label]['#required'] = $required;
        if (isset($weight)) $form[$label]['#weight'] = $weight;
        if (isset($options)) $form['$label']['#options'] = $options;
        dpm($form); // debug
        return $form;
    }
}
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
        $form = formUtil::addField($form,'voorvoeg', 'textfield','Voorvoeg', 'Voorvoegsel', $lid->voorvoeg, 11, 11, FALSE, 1);
        //ACHTERNAAM Tekst 35
        $form = formUtil::addField($form,'achternaam', 'textfield','Achternaam', 'Achternaam', $lid->achternaam, 35, 35, TRUE, 2);
        //AFKORTING Tekst 9
        $form = formUtil::addField($form,'afkorting', 'textfield','Afkorting', 'UNIEKE afkorting voor startadministratie', $lid->afkorting, 9, 9, FALSE, 3);
        //VOORNAAM Tekst 13
        $form = formUtil::addField($form,'voornaam', 'textfield','Voornaam', 'Voornaam', $lid->voornaam, 13, 13, FALSE, 4);
        //VOORLETTER Tekst 21
        $form = formUtil::addField($form,'voorletter', 'textfield','Voorletters', 'Voorletters', $lid->voorletter, 21, 21, FALSE, 5);
        //ADRES Tekst 26
        $form = formUtil::addField($form,'adres', 'textfield','Adres', 'Adres', $lid->adres, 26, 26, TRUE, 6);
        //POSTCODE Tekst 9
        $form = formUtil::addField($form,'postcode', 'textfield','Postcode', 'Postcode', $lid->postcode, 9, 9, TRUE, 7);
        //PLAATS Tekst 24
        $form = formUtil::addField($form,'plaats', 'textfield','Plaats', 'Plaats', $lid->plaats, 24, 24, TRUE, 8);
        //TELEFOON Tekst 14
        $form = formUtil::addField($form,'telefoon', 'textfield','Telefoon', 'Telefoon', $lid->telefoon, 14, 14, FALSE, 9);
        //Mobiel Tekst 50
        $form = formUtil::addField($form,'mobiel', 'textfield','Mobiel', 'Mobiel nummer', $lid->mobiel, 50, 14, FALSE, 10);
        //LAND Tekst 10
        $form = formUtil::addField($form,'land', 'textfield','Land', 'Land', $lid->land, 10, 10, FALSE, 11);
        //CODE Tekst 5
        $form = formUtil::addField($form,'code', 'select','Code', 'Code', $lid->code, 10, 10, FALSE, 12, EzacLid::$lidCode);
        // Tienrittenkaart
        $form = formUtil::addField($form,'tienrittenkaart', 'select','Tienrittenkaart', 'Tienrittenkaarthouder', $lid->tienrittenkaart, 1, 1, FALSE, 12, $options_yn);
        //GEBOORTEDA Datum/tijd 8
        $gd = substr($lid->geboorteda, 0, 10);
        if ($gd != NULL) {
            $lv = explode('-', $gd);
            $gebdat = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $gebdat = '';
        $form = formUtil::addField($form,'geboortedatum', 'textfield','Geboortedatum', 'Geboortedatum [dd-mm-jjjj]', $gebdat, 10, 10, FALSE, 13);
        //OPMERKING Tekst 27
        $form = formUtil::addField($form,'opmerking', 'textfield','Opmerking', 'Opmerking', $lid->opmerking, 27, 27, FALSE, 14);
        //INSTRUCTEU Tekst 9
        //Actief Ja/nee 1
        $form = formUtil::addField($form,'actief', 'select','actief', 'Nog actief lid?', $lid->actief, 1, 1, TRUE, 15, $options_yn);
        //LID_VAN Datum/tijd 8
        $ls = substr($lid->lid_van, 0, 10);
        if ($ls != NULL) {
            $lv = explode('-', $ls);
            $lid_van = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $lid_van = '';
        $form = formUtil::addField($form,'lidvan', 'textfield','Lid vanaf', 'Ingangsdatum lidmaatschap [dd-mm-jjjj]', $lid_van, 10, 10, FALSE, 16);
        //LID_EIND Datum/tijd 8
        $le = substr($lid->lid_eind, 0, 10);
        if ($le != NULL) {
            $lv = explode('-', $le);
            $lid_eind = sprintf('%s-%s-%s', $lv[2], $lv[1], $lv[0]);
        } else $lid_eind = '';
        $form = formUtil::addField($form,'lideind', 'textfield','Lid einde', 'Datum einde lidmaatschap [dd-mm-jjjj]', $lid_eind, 10, 10, FALSE, 17);
        //leerling Ja/nee 0
        $form = formUtil::addField($form,'leerling', 'select','Leerling', 'Leerling (Ja/nee)', $lid->leerling, 1, 1, FALSE, 18, $options_yn);
        //Instructie Ja/nee 1
        $form = formUtil::addField($form,'instructie', 'select','Instructie', 'Instructeur (Ja/nee)', $lid->instructie, 1, 1, FALSE, 19, $options_yn);
        //E_mail Tekst 50
        $form = formUtil::addField($form,'e_mail', 'email','E-mail', 'E-mail adres', $lid->e_mail, 50, 50, FALSE, 20);
        //Babyvriend Ja/nee 1
        $form = formUtil::addField($form,'babyvriend', 'select','Babyvriend', 'Vriend van Nico Baby(Ja/nee)', $lid->babyvriend, 1, 1, FALSE, 21, $options_yn);
        //Ledenlijstje Ja/nee 1
        $form = formUtil::addField($form,'ledenlijst', 'select','Ledenlijst', 'Vermelding op ledenlijst (Ja/nee)', $lid->ledenlijstje, 1, 1, FALSE, 21, $options_yn);
        //Etiketje Ja/nee 1
        $form = formUtil::addField($form,'etiket', 'select','Etiket', 'Etiket afdrukken (Ja/nee)', $lid->etiketje, 1, 1, FALSE, 22, $options_yn);
        //User Tekst 50
        $form = formUtil::addField($form,'user', 'textfield','Usercode website', 'Usercode website (VVAAAA)', $lid->user, 6, 6, FALSE, 23);
        //seniorlid Ja/nee 1
        $form = formUtil::addField($form,'seniorlid', 'select','Senior lid', 'Senior lid (Ja/nee)', $lid->seniorlid, 1, 1, FALSE, 24, $options_yn);
        //jeugdlid Ja/nee 1
        $form = formUtil::addField($form,'jeugdlid', 'select','Jeugd / inwonend lid', 'Jeugd / inwonend lid (Ja/nee)', $lid->jeugdlid, 1, 1, FALSE, 25, $options_yn);
        //PEonderhoud Ja/nee 1
        $form = formUtil::addField($form,'peonderhoud', 'select','Prive Eigenaar onderhoud (CAMO)', 'Prive Eigenaar onderhoud(Ja/nee)', $lid->peonderhoud, 1, 1, FALSE, 26, $options_yn);
        //Slotcode varchar(8)
        $form = formUtil::addField($form,'slotcode', 'textfield','Slot code', 'Slotcode (nnnnnn)', $lid->slotcode, 8, 8, FALSE, 27);

        //Mutatie timestamp
        //maak tekstlabel met datum laatste wijziging (wordt automatisch bijgewerkt)

        //Id
        //Toon het het Id nummer van het record
        $form = formUtil::addField($form,'id', 'hidden','Record nummer (Id)', '', $lid->id, 8, 8, FALSE, 28);
        //WijzigingSoort
        //Toon de soort mutatie NIEUW WIJZIGING VERVALLEN
        $form = formUtil::addField($form,'wijzigingsoort', 'hidden','Soort wijziging', '', $lid->wijzigingsoort, 15, 25, FALSE, 29);
        //KenEZACvan
        //Hoe is EZAC ontdekt
        $form = formUtil::addField($form,'kenezacvan', 'textfield','Ken EZAC van', '', $lid->kenezacvan, 20, 20, FALSE, 30);

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $newRecord ? t('Invoeren') : t('Update'),
            '#weight' => 31
        ];

        //insert Delete button  gevaarlijk ivm dependencies
        if (\Drupal::currentUser()->hasPermission('EZAC_delete')) {
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
