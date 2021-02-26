<?php

namespace Drupal\ezac_reserveringen\Controller;

use Drupal;
use Drupal\ezac_reserveringen\Model\EzacReservering;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;

/**
 * Controller for EZAC reserveringen
 */
class EzacReserveringenController extends ControllerBase {

  /**
   * toon reserveringen
   *
   * @param string $datum YYYY-MM-DD:YYYY-MM-DD
   *
   * @return array
   *  renderable array
   */
  public static function reserveringen($datum = null): array {

    // read settings
    $settings = Drupal::config('ezac_reserveringen.settings');
    $periodes = $settings->get('reservering.periodes');

    // prepare output
    $content = [];

    $header = [
      t('Datum'),
      t('Periode'),
      t('Soort'),
      t('Voor'),
      t('Doel'),
    ];

    if (!isset($datum)) $datum = date('Y-m-d') .':' .date('Y') .'12-31'; //toon reserveringen tot einde jaar
    $errmsg = EzacUtil::checkDatum($datum,
      $datum_start,
      $datum_eind);
    if ($errmsg != '') {
      // fout in datum
      $content[] = t($errmsg);
      return $content;
    }

    $condition = [
      'datum' => [
        'value' => [$datum_start, $datum_eind],
        'operator' => 'BETWEEN',
      ],
    ];
    $rows = [];
    $resIndex = EzacReservering::index($condition);
    foreach ($resIndex as $id) {
      $reservering = new EzacReservering($id);
      // add link for delete for own or all reserveringen
      $urlAnnulering = Url::fromRoute(
        'ezac_reserveringen_annulering_form',
        [
          'id' => $id,
        ]
      )->toString();
      $lid = new EzacLid($reservering->leden_id);
      $show_date = EzacUtil::showDate($reservering->datum);
      $own_id = EzacLid::getId(EzacUtil::getUser());
      //annulering link alleen tonen als eigen reservering of gemachtigd
      if (($reservering->leden_id == $own_id)
        or (Drupal::currentUser()->hasPermission('EZAC_update_all'))) {
        // add link
        $datum_with_link = "<a href=$urlAnnulering>$show_date</a>";
      }
      else {
        // no link added
        $datum_with_link = $show_date;
      }
      $rows[] = [
        t($datum_with_link),
        $periodes[$reservering->periode],
        $reservering->soort,
        sprintf("%s %s %s", $lid->voornaam, $lid->voorvoeg, $lid-> achternaam),
        $reservering->doel,
      ];
    }

    // define table for output
    $caption = "Overzicht EZAC reserveringen van $datum_start tot $datum_eind";
    $content['table'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    ];
    // add pager
    $content['pager'] = [
      '#type' => 'pager',
      '#weight' => 5
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  } // reserveringen

  //@TODO add annulering function

  public function overzicht($datum = null): array {
    return self::reserveringen($datum);
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
      $messenger->addMessage("Code voor reservering $id ontbreekt", 'error');
      return [];
    }
    // read reservering record
    /*
    $reserveringen = db_select('ezac_Reservering_Reserveringen', 'p')
      ->fields('p') //select *
      ->condition('p.id', $id, '=')
      ->execute()
      ->fetchAll();

    if (count($reserveringen) == 0) {
      $messenger->addMessage("Reservering $id niet gevonden", 'error');
      drupal_goto('reservering/reservering');
    }
    $reservering = $reserveringen[0]; //get single result
    */
    $reservering = new EzacReservering($id);
    if (!isset($reservering)) {
      $messenger->addMessage("Reservering $id niet gevonden", 'error');
      return [];
    }

    $datum    = substr($reservering->datum, 0,10);
    $periode  = $reservering->periode;
    $soort    = $reservering->soort;
    $leden_id = $reservering->leden_id;

    $hash_fields = array(
      'id' => $id,
      'datum' => $datum,
      'periode' => $periode,
      'soort' => $soort,
      'leden_id' => $leden_id,
    );
    $data = implode('/', $hash_fields);

    $calculated_hash = hash('sha256', $data, FALSE);
    if ($calculated_hash <> $hash) {
      $messenger->addMessage("Onjuiste code voor reservering $id", 'error');
      $messenger->addMessage("calculated - $calculated_hash", 'error');
      $messenger->addMessage("received   - $hash", 'error');
      return [];
    }

    // delete reservering
    $aantal = $reservering->delete();
    if ($aantal == 1) {
      $messenger->addMessage("Reservering voor $soort op $datum verwijderd", 'status');
    }
    else {
      $messenger->addMessage("Reservering niet verwijderd [$aantal]", 'error');
    }
    return [];
    //return drupal_get_form('ezacreserveer_delete_form', $reservering);

  }
} //class EzacReserveringenController
