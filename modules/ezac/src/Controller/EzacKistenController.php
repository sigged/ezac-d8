<?php

namespace Drupal\ezac\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezac\Model\EzacKist;

/**
 * Controller for EZAC Kisten administration.
 */
class EzacKistenController extends ControllerBase {

    /**
     * Display the status of the EZAC kisten table
     * @return array
     */
  public function status() {
    $content = [];

      //$schema = drupal_get_module_schema('ezac', 'kisten');
      //dpm($schema); //debug

    // show record count for each Code value
    $headers = [
      t("Kisten"),
      t("Aantal"),
      t("Uitvoer"),
    ];
    
      $count = EzacKist::counter(['actief' => TRUE]);
      $urlCode = Url::fromRoute(
        'ezac_kisten_overzicht'
      )->toString();
      $urlExport = Url::fromRoute(
        'ezac_kisten_export',
        [
          'filename' => "Kisten.csv",
        ]
      )->toString();
      $rows[] = [
        t("<a href=$urlCode>Overzicht</a>"),
        $count,
        t("<a href=$urlExport>Kisten.csv</a>"),
      ];
    //build table
    $content['table'] = [
      '#type' => 'table',
      '#caption' => t("Overzicht van het EZAC KISTEN bestand"),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    //apply css
    //$content['#attached']['library'][] = 'ezac/dlotable'; // of met ['css']
    return $content;
  }

    /**
     * Render a list of entries in the database.
     * @param $actief
     * @return array
     */
  public function overzicht($actief = TRUE) {
    $content = array();

    $rows = [];
    $headers = [
      t('registratie<br>callsign'),
      t('type<br>bouwjaar'),
      t('inzittenden'),
      t('flarm<br>adsb'),
      t('eigenaar<br>prive'),
      t('actief'),
      t('opmerking'),
    ];

    // select only kisten records where actief == TRUE
    if (isset($code)) {
      $condition =
        [
          'code' => $code,
          'actief' => $actief
        ];
    }
    else $condition = ['actief' => $actief];
    
    // prepare pager
    $total = EzacKist::counter($condition);
    $field = 'id';
    $sortkey = 'registratie';
    $sortdir = 'ASC';
    $range = 50;
    $page = pager_default_initialize($total, $range);
    $from = $range * $page;
    
    $kistenIndex = EzacKist::index($condition, $field, $sortkey, $sortdir, $from, $range);
    foreach ($kistenIndex as $id) {
      $kist = (new EzacKist)->read($id);
      $urlString = Url::fromRoute(
        'ezac_kisten_update',  // edit kisten record
        ['id' => $kist->id]
      )->toString();
      $rows[] = [
        //link each record to edit route
        t("<a href=$urlString>$kist->registratie</a><br>$kist->callsign"),
        t("$kist->type<br>$kist->bouwjaar"),
        t("$kist->inzittenden"),
        t("$kist->flarm<br>$kist->adsb"),
        t("$kist->eigenaar<br>$kist->prive"),
        t("$kist->actief"),
        t("$kist->opmerking"),
      ];
    }
    $caption = "Overzicht EZAC Kisten bestand";
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
  } // overzicht

    /**
     * Maak exportbestand uit Kisten tabel
     * geformatteerd voor input in bestand (csv)
     * Output via html headers naar attachment
     *
     * @param string $filename
     * @param null $code
     * @return mixed Response output text in csv format
     *   output text in csv format
     */
  public function export($filename = 'ezac.txt', $code = NULL) {

    $messenger = \Drupal::messenger();

    if ($filename == '') $filename = 'ezac.txt';

    // Determine CODE categorie from Kisten for export
    if (isset($code)) {
      $condition = [
        'code' => $code,
        'actief' => TRUE
      ];
    }
    else $condition = ['actief' => TRUE]; //select all active records

    $records = EzacKist::index($condition); //read records index
    $count = count($records);
    $messenger->addMessage("Export $count records met code [$code] naar bestand [$filename]"); //DEBUG

    $output = ""; // initialize output
    //build header line
    foreach (EzacKist::$fields as $field => $description) {
      $output .= '"' .$field .'";';
    }
    //remove last ";" 
    $output = rtrim($output, ";") ."\r\n";
    
    // export all records
    foreach ($records as $id) {
      $kist = (new EzacKist)->read($id);
      // add all fields
      foreach (EzacKist::$fields as $field => $description) {
        $output .= sprintf('"%s";',$kist->$field);
      }
      //remove last ";" 
      $output = rtrim($output, ";") ."\r\n";
    }

    $response = new Response(
      $output,
      Response::HTTP_OK,
      array(
        'content-type' => 'text/plain',
      )
    );

    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );
    $response->headers->set('Content-Disposition', $disposition);
    $response->setCharset('UTF-8');

      /** @var mixed $response */
      return $response;
  } // export  
  
} //class EzacKistenController
