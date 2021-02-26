<?php

namespace Drupal\ezac_reserveringen\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_reserveringen\Model\EzacReservering;
use Drupal\ezac_reserveringen\Controller\EzacReserveringenController;
use Drupal\ezac\Util\EzacUtil;


/**
 * UI to show status of VBA records
 */


class EzacReserveringenAnnuleringForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId()
    {
        return 'ezac_reserveringen_annulering_form';
    }

  /**
   * buildForm for reserveringen status
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param int $id te annuleren reservering
   *
   * @return array
   */
    public function buildForm(array $form, FormStateInterface $form_state, int $id = null) {
      $messenger = Drupal::messenger();

      if ($id == null) {
        $messenger->addMessage("Geen reservering opgegeven", 'error');
        return [];
      }
      $reservering = new EzacReservering($id);
      if ($reservering->id == null) {
        // read failed
        $messenger->addMessage("reservering $id is niet gevonden", 'error');
        return[];
      }

      // Wrap the form in a div.
      $form = [
        '#prefix' => '<div id="annuleringform">',
        '#suffix' => '</div>',
      ];

      //opzoeken leden record
      $lid = new EzacLid($reservering->leden_id);
      if ($lid->id != null) {
        $naam = sprintf("%s %s %s", $lid->voornaam, $lid->voorvoeg, $lid->achternaam);
        $email = $lid->e_mail;
      }
      else $naam = "naam onbekend";

      $soort = $reservering->soort;
      $show_datum = EzacUtil::showDate($reservering->datum);
      $periode = $reservering->periode;
      $doel = $reservering->doel;

      $form[0]['#type'] = 'markup';
      $form[0]['#markup'] = '<p>Verwijderen reservering></p>';
      $form[0]['#weight'] = 0;
      $form[0]['#prefix'] = '<div class="ezacreserveer-intro-div">';
      $form[0]['#suffix'] = '</div>';

      $form[0]['#type'] = 'markup';
      $form[0]['#markup'] = "<p><h3>$soort reservering van $show_datum verwijderen</h3></p>";
      $form[0]['#markup'] .= "<p>Deze reservering is gemaakt voor $naam in de $periode periode";
      $form[0]['#markup'] .= "<br>met als doel $doel";
      $form[0]['#weight'] = 1;
      $form[0]['#prefix'] = '<div class="ezacreserveer-delete-div">';
      $form[0]['#suffix'] = '</div>';

      $form['id'] = array(
        '#title' => 'id',
        '#type' => 'hidden',
        '#value'=> $id
      );
      $form['datum'] = array(
        '#title' => 'Datum',
        '#type' => 'hidden',
        '#value' => $reservering->datum,
      );
      $form['soort'] = array(
        '#title' => 'Soort',
        '#type' => 'hidden',
        '#value' => $soort,
      );
      $form['periode'] = array(
        '#title' => 'Periode',
        '#type' => 'hidden',
        '#value' => $periode,
      );
      $form['doel'] = array(
        '#title' => 'Doel',
        '#type' => 'hidden',
        '#value' => $doel,
      );
      $form['naam'] = array(
        '#title' => 'Naam',
        '#type' => 'hidden',
        '#value' => $naam,
      );
      $form['mail'] = array(
        '#title' => 'E-mail',
        '#type' => 'hidden',
        '#value' => $email,
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Verwijder reservering'),
        '#weight' => 3,
      );

      $form['annuleer'] = array(
        '#type' => 'submit',
        '#value' => t('Annuleer'),
        '#weight' => 4,
      );

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
      // validate
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $messenger = Drupal::messenger();

      // vastleggen reservering
      $id = $form_state->getValue('id');

      $op = $form_state->getValue('op');
      if ($op == 'Annuleer') {
        //$form_state['redirect'] = 'reservering/reservering';
        $messenger->addMessage("Reservering is NIET verwijderd", 'status');
        // redirect naar reservering invoer formulier
        $form_state->setRedirect('ezac_reserveringen_reservering');
        return;
      }

      $id    = $form_state->getValue('id');
      $datum = EzacUtil::showDate($form_state->getValue('datum'));
      $naam  = $form_state->getValue('naam');
      $soort = $form_state->getValue('soort');
      $periode = $form_state->getValue('periode');
      $doel = $form_state->getValue('doel');
      $email = $form_state->getValue('mail');

      $reservering = new EzacReservering($id);
      $aantal = $reservering->delete();
      if ($aantal == 1) {
        $messenger->addMessage("Reservering $id is verwijderd", 'status');
        //mail bevestiging van verwijdering
        $subject = "Reservering $doel bij EZAC op $datum is GEANNULEERD";
        unset($body);
        $body  = "<html><body>";
        $body .= "<p>De reservering voor $soort bij de EZAC voor $naam op $datum in de $periode periode is geannuleerd";
        $body .= "<br>";
        $body .= "<br>Voor verdere contact gegevens: zie de <a href=http://www.ezac.nl>EZAC website</a>";
        $body .= "<br>";
        $body .= "<br>Met vriendelijke groet,";
        $body .= "<br>Eerste Zeeuws Vlaamse Aero Club";
        $body .= "</body></html>";
        mail($email, $subject, $body);
      }
      else $messenger->addMessage("Reservering $id is NIET verwijderd", 'error');

      // redirect naar reservering invoer formulier
      $form_state->setRedirect('ezac_reserveringen_reservering');
    } //submitForm
}
