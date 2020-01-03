<?php

namespace Drupal\ezacStarts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezacKisten\Model\EzacKist;
use Drupal\ezacLeden\Model\EzacLid;
use Drupal\ezacStarts\Model\EzacStart;
use Drupal\ezac\Util\EzacUtil;
use False\MyClass;

/**
 * UI to update starts record
 * tijdelijke aanpassing
 */


class EzacStartsUpdateForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId()
    {
        return 'ezac_starts_update_form';
    }

    /**
     * buildForm for STARTS update with ID parameter
     * This is also used to CREATE new starts record (no ID param given as input)
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
        //$form['#theme'] = 'ezac_starts_update_form';

        // Query for items to display.
        // if $id is set, perform UPDATE else CREATE
        if (isset($id)) {
            $start = (new EzacStart)->read($id);
            //$start = new EzacStart($id); // using constructor
            $newRecord = FALSE;
        } else { // prepare new record
            $start = new EzacStart(); // create empty start occurrence
            $newRecord = TRUE;
        }

        //store indicator for new record for submit function
        $form['new'] = [
            '#type' => 'value',
            '#value' => $newRecord, // TRUE or FALSE
        ];

        if ($form_state->getValue('registratie')) {
            // Check op tweezitter via (changed) form element
            $tweezitter = ((new EzacKist)->read(EzacKist::getID($form_state->getValue('registratie')))->inzittenden == 2);
        }
        else {
            // Check op tweezitter via start record
            $tweezitter = ((new EzacKist)->read(EzacKist::getID($start->registratie))->inzittenden == 2);
        }
        //$tweezitter = ((new EzacKist)->read(EzacKist::getID($start->registratie))->inzittenden == 2);
        $form['tweezitter'] = [
            '#type' => 'checkbox',
            '#title' => 'Tweezitter',
            '#value' => $tweezitter,
            //'#attributes' => ['name' => 'tweezitter'],
        ];

        $options_yn = [t('Nee'), t('Ja')];
        $leden = EzacUtil::getLeden();
        $kisten = EzacUtil::getKisten();
        $form = EzacUtil::addField($form,'datum', 'date','Datum', 'datum', $start->datum, 10, 10, TRUE, 1);

        // @todo use ajax to dynamically add tweede field and show instructie field
        $ajax = array(
            'callback' => '::form_tweede_callback',
            'wrapper' => 'tweede-div',
            'effect' => 'fade',
            'progress' => array('type' => 'throbber'),
        );
        $form = EzacUtil::addField($form,'registratie', 'select','registratie', 'registratie', $start->registratie, 10, 1, TRUE, 2, $kisten, $ajax);
        $form = EzacUtil::addField($form,'gezagvoerder', 'select', 'gezagvoerder', 'gezagvoerder', $start->gezagvoerder, 20, 1, TRUE, 3, $leden);

        /*
        if ($tweezitter) {
            $form = EzacUtil::addField($form, 'tweede', 'select', 'tweede', 'tweede', $start->tweede, 20, 1, FALSE, 4, $leden);
        }
        else $form = EzacUtil::addField($form, 'tweede', 'hidden', 'tweede', 'tweede', $start->tweede, 20, 1, FALSE, 4);
        */
        $form = EzacUtil::addField($form, 'tweede', 'select', 'tweede', 'tweede', $start->tweede, 20, 1, FALSE, 4, $leden);
        $form["tweede"]['#states'] = [
            // show this field only when tweezitter == TRUE
            'visible' => [
                ':input[name="tweezitter"]' => ['checked' => TRUE],
            ],
        ];
        $form = EzacUtil::addField($form,'soort', 'select','soort', 'soort', $start->soort, 4, 1, FALSE, 5, EzacStart::$startSoort);
        $form = EzacUtil::addField($form,'startmethode', 'select','startmethode', 'startmethode', $start->startmethode, 1, 1, FALSE, 6, EzacStart::$startMethode);
        $form = EzacUtil::addField($form,'start', 'textfield','start', 'start', $start->start, 10, 10, FALSE, 7);
        $form = EzacUtil::addField($form,'landing', 'textfield','landing', 'landing', $start->landing, 10, 10, FALSE, 8);
        $form = EzacUtil::addField($form,'duur', 'textfield','duur', 'duur', $start->duur, 10, 10, FALSE, 9);
        $form = EzacUtil::addField($form,'instructie', 'select','instructie', 'instructie', $start->instructie, 5, 1, FALSE, 10, $options_yn);
        $form = EzacUtil::addField($form,'opmerking', 'textfield','opmerking', 'opmerking', $start->opmerking, 30, 30, FALSE, 11);

        //Id
        //Toon het het Id nummer van het record
        $form = EzacUtil::addField($form,'id', 'hidden','Record nummer (Id)', '', $start->id, 8, 8, FALSE, 28);

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
                $form = EzacUtil::addField($form,'deletebox','checkbox', 'verwijder', 'verwijder record', FALSE,1,1,FALSE,29);
                $form['actions']['delete'] = [
                    '#type' => 'submit',
                    '#value' => t('Verwijderen'),
                    '#weight' => 32
                ];
            }
        }
        return $form;
    }

    private function form_tweede_callback(array &$form, FormStateInterface $form_state)
    {
        // Check op tweezitter
        $form['tweezitter'] = ((new EzacKist)->read(EzacKist::getID($form_state->getValue('registratie')))->inzittenden == 2);
        dpm($form['tweezitter'],'tweezitter'); //debug
        return $form['tweezitter'];
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

        // perform validate for edit of record

        // gezagvoerder
        $gezagvoerder = $form_state->getValue('gezagvoerder');
        if ($gezagvoerder <> $form['gezagvoerder']['#default_value']) {
            if (EzacLid::counter(['afkorting' => $gezagvoerder]) == 0) {
                $form_state->setErrorByName('gezagvoerder', t("Afkorting $gezagvoerder bestaat niet"));
            }
        }
        if (!array_key_exists($form_state->getValue('soort'), EzacStart::$startSoort)) {
            $form_state->setErrorByName('soort', t("Ongeldige soort"));
        }
        // datum
        $dat = $form_state->getValue('datum');
        if ($dat !== '') {
            $lv = explode('-', $dat);
            if (checkdate($lv[1], $lv[2], $lv[0]) == FALSE) {
                $form_state->setErrorByName('datum', t("Datum [$dat] is onjuist"));
            }
        }
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
            if ($form_state->getValue('deletebox') == FALSE) {
                $messenger->addMessage('Verwijdering niet geselecteerd', $messenger::TYPE_ERROR);
                return;
            }
            $start = new EzacStart; // initiate Start instance
            $start->id = $form_state->getValue('id');
            $count = $start->delete(); // delete record in database
            $messenger->addMessage("$count record verwijderd");
        } else {
            // Save the submitted entry.
            $start = new EzacStart;
            // get all fields
            foreach (EzacStart::$fields as $field => $description) {
                $start->$field = $form_state->getValue($field);
            }
            //Check value newRecord to select insert or update
            if ($form_state->getValue('new') == TRUE) {
                $start->create(); // add record in database
                $messenger->addMessage("Starts record aangemaakt met id [$start->id]", $messenger::TYPE_STATUS);

            } else {
                $count = $start->update(); // update record in database
                $messenger->addMessage("$count record updated", $messenger::TYPE_STATUS);
            }
        }
        //go back to starts overzicht
        $redirect = Url::fromRoute(
            'ezac_starts_overzicht',
            ['datum' => $form_state->getValue('datum')]
        );
        $form_state->setRedirectUrl($redirect);
    } //submitForm
}
