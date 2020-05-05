<?php

namespace Drupal\ezacVba\Controller;

use Drupal\ezacVba\Model\ezacVbaBevoegdheidLid;
use Drupal\ezacVba\Model\ezacVbaDagverslagLid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezacVba\Model\ezacVbaDagverslag;
use Drupal\ezac\Util\EzacUtil;

/**
 * Controller for EZAC start administration.
 */
class ezacVbaController extends ControllerBase {

  /**
   * toon dagverslagen
   *
   * @param string $datum_start
   * @param string $datum_eind
   *
   * @return array
   *  renderable array
   */
  public function dagverslagen($datum_start, $datum_eind) {
    $content = array();

    $rows = [];
    $headers = [
        t('Datum'),
        t('Verslag'),
    ];

    // build dagverslagen table - rows

    // START D7 code
    $condition = [];
    $namen = EzacUtil::getLeden($condition);
    // $bevoegdheden = ezacvba_get_bevoegdheden();

    //lees dagverslag index
    $condition = [
      'datum' => [
        'value' => [$datum_start, $datum_eind],
        'operator' => 'BETWEEN'
      ],
    ];
    $dagverslagIndex = ezacVbaDagverslag::index($condition);

    //lees dagverslagLid index
    $dagverslagLidIndex = ezacVbaDagverslagLid::index(($condition));

    // lees bevoegdheidLid index
    $condition = [
      'datum_aan' => [
        'value' => [$datum_start, $datum_eind],
        'operator' => 'BETWEEN'
      ],
    ];
    $bevoegdheidLidIndex = ezacVbaBevoegdheidLid::index($condition);

    $header = array(
      array('data' => 'datum', 'width' => '20%'),
      array('data' => 'verslag'),
    );
    $rows = array();

    foreach ($dagverslagIndex as $id) {
      $dagverslag = (new ezacVbaDagverslag)->read($id);
      $p_weer = nl2br($dagverslag->weer);
      $p_verslag = nl2br($dagverslag->verslag);
      $p_instructeur = $namen[$dagverslag->instructeur];
      $overzicht[$dagverslag->datum]['dagverslag'][$dagverslag->id] =
        t("Instructeur: $p_instructeur<br>Weer: $p_weer<br>Verslag: $p_verslag");
    }

    //verwerk dagverslagen_lid

    foreach ($dagverslagLidIndex as $id) {
      $dl = (new ezacVbaDagverslagLid)->read($id);
      $p_naam  = $namen[$dl->afkorting];
      $p_instr = $namen[$dl->instructeur];
      $p_verslag = nl2br($dl->verslag);
      $overzicht[$dl->datum]['dagverslag_lid'][$dl->id] =
        t("Opmerking voor $p_naam:<br>$p_verslag ($p_instr)</p>");
    }

    //verwerk bevoegdheid_lid
    foreach ($bevoegdheidLidIndex as $id) {
      $bl = (new ezacVbaBevoegdheidLid)->read($id);
      $p_naam  = $namen[$bl->afkorting];
      $p_instr = $namen[$bl->instructeur];
      $p_onderdeel = nl2br($bl->onderdeel);
      $p_opmerking = nl2br($bl->opmerking);
      $overzicht[$bl->datum_aan]['bevoegdheid_lid'][$bl->id] =
        t("Bevoegdheid voor $p_naam: <br>$bl->bevoegdheid $p_onderdeel $p_opmerking($p_instr)</p>");
    }

    //display verslagen
    if (isset($overzicht)) {
      krsort($overzicht); //sort overzicht on datum key (descending)
      foreach ($overzicht as $datum => $ovz) {
        if (isset($ovz['dagverslag'])) {
          foreach ($ovz['dagverslag'] as $id => $verslag) {
            $rows[] = array(
              ezacUtil::showDate($datum),
              $verslag,
            );
          }
        }
        if (isset($ovz['dagverslag_lid'])) {
          foreach ($ovz['dagverslag_lid'] as $id => $verslag) {
            $rows[] = array(
              ezacUtil::showDate($datum),
              $verslag,
            );
          }
        }
        if (isset($ovz['bevoegdheid_lid'])) {
          foreach ($ovz['bevoegdheid_lid'] as $id => $verslag) {
            $rows[] = array(
              ezacUtil::showDate($datum),
              $verslag,
            );
          }
        }
      }
    }
    // END D7 code

    // define table for output
    $caption = "Overzicht EZAC VBA verslagen van $datum_start tot $datum_eind";
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
  } // dagverslagen

    /**
     * Render a list of entries in the database.
     * @param string $datum_start
     *  $jaar - categorie (optional)
     * @param $datum_eind
     * @return array
     */
    public function dagverslagLid($datum_start, $datum_eind) {
        $content = array();

        $rows = [];
        $headers = [
            t('start'),
        ];

        $leden = EzacUtil::getLeden();
        // $kisten = EzacUtil::getKisten();

        $caption = "Overzicht EZAC vba bestand";
        $content['table'] = [
            '#type' => 'table',
            '#caption' => $caption,
            '#header' => $headers,
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
    } // dagverslagLid


  /**
   * Render a list of entries in the database.
   * @param string
   *  $jaar - categorie (optional)
   * @return array
   */
  public function bevoegdheidLid($datum_start, $datum_eind) {
    $content = array();

    $rows = [];
    $headers = [
      t('start'),
    ];

    $leden = EzacUtil::getLeden();

    $caption = "Overzicht EZAC vba bestand";
    $content['table'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $headers,
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
  } // bevoegdheidLid

} //class EzacvbaController
