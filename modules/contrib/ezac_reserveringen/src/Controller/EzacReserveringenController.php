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

    if (!isset($datum)) $datum = date('Y'); //-m-d') .':' .date('Y') .'-12-31'; //toon reserveringen voor dit jaar
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

} //class EzacReserveringenController
