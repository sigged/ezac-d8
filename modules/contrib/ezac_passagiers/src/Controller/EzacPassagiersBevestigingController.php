<?php

namespace Drupal\ezac_passagiers\Controller;

use Drupal;
use Drupal\ezac\Util\EzacMail;
use Drupal\ezac_passagiers\Model\EzacPassagier;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;

/**
 * Controller for EZAC passagiers
 */
class EzacPassagiersBevestigingController extends ControllerBase {

  /**
   * bevestig reservering
   *
   * @param int $id
   * @param string $hash
   *
   * @return array
   *  renderable array
   */
  public static function bevestiging($id, $hash): array {
    $messenger = Drupal::messenger();

    // read settings
    $settings = Drupal::config('ezac_passagiers.settings');
    $parameters = $settings->get('parameters');

    // prepare output
    $content = [];
    // $hash ingevuld?
    if (!isset($hash) || empty($hash)) {
      $messenger->addMessage("Code voor reservering $id ontbreekt", 'error');
      //drupal_goto('passagiers/reservering');
      return [];
    }
    // read reservering record
    $reservering = new EzacPassagier($id);

    $datum    = substr($reservering->datum, 0,10);
    $tijd     = substr($reservering->tijd, 0, 5); //skip seconds
    $naam     = $reservering->naam;
    $telefoon = $reservering->telefoon;
    $mail    = $reservering->mail;

    $hash_fields = array(
      'id' => $id,
      'datum' => $datum,
      'tijd' => $tijd,
      'naam' => $naam,
      'mail' => $mail,
      'telefoon' => $telefoon,
    );
    $data = implode('/', $hash_fields);
    $show_datum = EzacUtil::showDate($datum);
    $calculated_hash = hash('sha256', $data, FALSE);

    if ($calculated_hash <> $hash) {
      $messenger->addMessage("Onjuiste code voor reservering $id", 'error');
      return [];
      //drupal_goto('passagiers/reservering');
    }
    $reservering->status = $parameters['reservering_bevestigd'];
    // update reservering record
    $result = $reservering->update();
    if ($result == 1) {
      $messenger->addMessage("Je reservering op $show_datum $tijd is bevestigd", 'status');
      //send confirmation mail
      $url_reservering = Url::fromRoute('ezac_passagiers_reservering')->toString();
      $subject = "Reservering meevliegen EZAC op $show_datum $tijd is BEVESTIGD";
      unset($body);
      $body  = "<html lang='nl'><body>";
      $body .= "<p>De reservering voor meevliegen bij de EZAC voor $naam op $show_datum $tijd is bevestigd";
      $body .= "<br>Deze reservering geldt voor 1 persoon";
      $body .= "<br>Graag een kwartier van tevoren aanwezig zijn (Justaasweg 5 in Axel)";
      $body .= "<br>";
      $body .= "<br>Voor verdere contact gegevens: zie de <a href=http://www.ezac.nl>EZAC website</a>";
      $body .= "<br>";
      $body .= "<br>Met vriendelijke groet,";
      $body .= "<br>Eerste Zeeuws Vlaamse Aero Club";
      $body .= "</body></html>";
      EzacMail::mail('ezac_passagiers', 'bevestiging', $mail, $subject, $body);
    }
    else {
      $messenger->addError("Bevesting van reservering $id is NIET gelukt ($result)");
    }
    return [$body];
  }

  /**
   * @param int $id
   * @param string $hash
   *
   * @return array
   */
  public function annulering(int $id, string $hash): array {
    $messenger = Drupal::messenger();
    // $hash ingevuld?
    if (!isset($hash) || empty($hash)) {
      $messenger->addMessage("Code voor passagier $id ontbreekt", 'error');
      return [];
    }
    // read passagier record
    $passagier = new EzacPassagier($id);
    if (!isset($passagier)) {
      $messenger->addMessage("passagier $id niet gevonden", 'error');
      return [];
    }

    $datum    = substr($passagier->datum, 0,10);
    $tijd = substr($passagier->tijd, 0, 5);
    $soort    = $passagier->soort;
    $afkorting = $passagier->aanmaker;

    $hash_fields = array(
      'id' => $id,
      'datum' => $datum,
      'tijd' => $tijd,
      'naam' => $passagier->naam,
      'mail' => $passagier->mail,
      'telefoon' => $passagier->telefoon,
    );
    $data = implode('/', $hash_fields);

    $calculated_hash = hash('sha256', $data, FALSE);
    if ($calculated_hash <> $hash) {
      $messenger->addMessage("Onjuiste code voor passagier $id", 'error');
      //$messenger->addMessage("calculated - $calculated_hash", 'error');
      //$messenger->addMessage("received   - $hash", 'error');
      return [];
    }

    // delete passagier
    $aantal = $passagier->delete();
    if ($aantal == 1) {
      $messenger->addMessage("passagier voor $soort op $datum verwijderd", 'status');
    }
    else {
      $messenger->addMessage("passagier niet verwijderd [$aantal]", 'error');
    }
    return [];
    //return drupal_get_form('ezacreserveer_delete_form', $passagier);

  }
} //class EzacPassagiersBevestigingController
