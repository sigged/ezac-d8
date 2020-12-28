<?php

namespace Drupal\ezac_rooster\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezac_rooster\Model\EzacRooster;
use Drupal\ezac\Util\EzacUtil;

/**
 * Controller for EZAC administration.
 */
class EzacRoosterController extends ControllerBase {

    /**
     * Display the status of the EZAC rooster table
     * @return array
     */
  public function status() {
    $content = [];

      //$schema = drupal_get_module_schema('Ezac', 'rooster');

    // show record count for each Jaar
    $headers = [
      t("Jaar"),
      t("Aantal diensten"), 
      t("Uitvoer"),
    ];

    $total = 0;
    $condition = [];
    $datums = array_unique(EzacRooster::index($condition, 'datum', 'datum','DESC'));
    $jaren = [];
    foreach ($datums as $datum) {
      $dp = date_parse($datum);
      $year = $dp['year'];
      if (isset($jaren[$year])) $jaren[$year]++;
      else $jaren[$year] = 1;
    }
    foreach ($jaren as $jaar => $aantal) {
      $count = $aantal;
      $total = $total+$count;
      $urlJaar = Url::fromRoute(
        'ezac_rooster_overzicht_jaar',
        [
          'jaar' => $jaar
        ]
      )->toString();
      $urlExport = Url::fromRoute(
        'ezac_rooster_export_jaar',
        [
          'filename' => "Rooster-$jaar.csv",
          'jaar' => $jaar
        ]
      )->toString();
      $rows[] = [
        t("<a href=$urlJaar>$jaar</a>"),
        $count,
        t("<a href=$urlExport>Rooster-$jaar.csv</a>"),
      ];
    }
    // add line for totals
    $urlExport = Url::fromRoute(
      'ezac_rooster_export',
      [
        'filename' => "Rooster.csv",
      ]
    )->toString();
    $rows[]= [
      t('Totaal'),
      $total,
      t("<a href=$urlExport>Rooster.csv</a>"),
    ];
    //build table
    $content['table'] = [
      '#type' => 'table',
      '#caption' => t("Jaar overzicht van het EZAC Rooster"),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

    /**
     * Render a list of entries in the database.
     * @param string
     *  $code - categorie (optional)
     * @return array
     */
  public function overzicht($datum = NULL) {
    $content = array();
    $condition = [
      'code' => 'VL',
      //'actief' -> TRUE,
    ];
    $leden = EzacUtil::getLeden($condition);
    $rows = [];
    $headers = [
      t('datum'),
      t('periode'),
      t('dienst'),
      t('naam'),
      t('geruild met'),
      t('mutatie'),
    ];

    // select rooster dates
    if (isset($datum)) {
      $condition =
        [
          'datum' => $datum,
        ];
    }
    else $condition = [];
    
    // prepare pager
    $total = EzacRooster::counter($condition);
    $field = 'id';
    $sortkey = 'datum';
    $sortdir = 'ASC';
    $range = 50;
    $pager = \Drupal::service('pager.manager')
      ->createPager($total, $range);
    $page = $pager
      ->getCurrentPage();

    $from = $range * $page;
    
    $roosterIndex = EzacRooster::index($condition, $field, $sortkey, $sortdir, $from, $range);
    foreach ($roosterIndex as $id) {
      $rooster = new EzacRooster($id);
      $urlString = Url::fromRoute(
        'ezac_rooster_update',  // edit rooster record
        ['id' => $rooster->id]
      )->toString();
      $naam = $leden[$rooster->naam];
      $geruild = (isset($rooster->geruild)) ? $leden[$rooster->geruild] : '';
      $rows[] = [
        //link each record to edit route
        t("<a href=$urlString>$rooster->datum</a>"),
        t("$rooster->periode"),
        t("$rooster->dienst"),
        t("$naam"),
        t("$geruild"),
        t("$rooster->mutatie"),
      ];
    }
    $caption = "Overzicht EZAC rooster";
    if (isset($datum)) $caption .= " - " .EzacUtil::showDate($datum);
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
   * Render a list of entries in the database.
   * @param string
   *  $jaar - categorie (optional)
   * @return array
   */
  public function overzichtJaar($jaar) {
    $content = array();

    $rows = [];
    $headers = [
      t('datum'),
      t('aantal diensten'),
    ];

    // select all diensten dates for selected year
    $condition = [
      'datum' => [
        'value' => ["$jaar-01-01", "$jaar-12-31"],
        'operator' => 'BETWEEN'
      ],
    ];
    $from = null;
    $range = null;
    $field = 'datum';
    $sortkey = null;
    $sortdir = null;
    $unique = TRUE; // return unique results only

    // bepaal aantal dagen
    $total = count(EzacRooster::index($condition, $field, $sortkey, $sortdir, $from, $range, $unique));

    // prepare pager
    $range = 120;
    $pager = \Drupal::service('pager.manager')
      ->createPager($total, $range);
    $page = $pager
      ->getCurrentPage();

    $from = $range * $page;
    $field = 'datum';
    $sortkey = 'datum';
    $sortdir = 'ASC';

    $roosterDates = EzacRooster::index($condition, $field, $sortkey, $sortdir);
    $roosterIndex = array_unique($roosterDates);

    $dagen = [];
    foreach ($roosterDates as $datum) {
      if (isset($dagen[$datum])) $dagen[$datum]++;
      else $dagen[$datum] = 1;
    }

    foreach ($roosterIndex as $datum) {
      $urlString = Url::fromRoute(
        'ezac_rooster_overzicht',  // show rooster for datum
        [
          'datum_start' => $datum,
          'datum_eind' => $datum,
        ]
      )->toString();

      $d = EzacUtil::showDate($datum);
      $rows[] = [
        //link each record to overzicht route
        t("<a href=$urlString>$d"),
        $dagen[$datum],
      ];
    }
    $caption = "Overzicht EZAC rooster data voor $jaar";
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
  } // overzichtJaar
  
    /**
     * Maak exportbestand uit Leden tabel
     * geformatteerd voor input in bestand (csv)
     * Output via html headers naar attachment
     *
     * @param string $filename
     * @param null $code
     * @return mixed Response output text in csv format
     *   output text in csv format
     */
  public function export($filename = 'Ezac.txt', $datum = NULL) {

    $messenger = \Drupal::messenger();

    if ($filename == '') $filename = 'Ezac.txt';

    // Determine datum categorie from rooster for export
    // @TODO support datum range / jaar
    if (isset($datum)) {
      $condition = [
        'datum' => $datum,
      ];
    }
    else $condition = []; //select all  records

    $records = EzacRooster::index($condition); //read records index
    $count = count($records);
    $messenger->addMessage("Export $count records met datum [$datum] naar bestand [$filename]"); //DEBUG

    $output = ""; // initialize output
    //build header line
    foreach (EzacRooster::$fields as $field => $description) {
      $output .= '"' .$field .'";';
    }
    //remove last ";" 
    $output = rtrim($output, ";") ."\r\n";
    
    // export all records
    foreach ($records as $id) {
      $rooster = new EzacRooster($id);
      // add all fields
      foreach (EzacRooster::$fields as $field => $description) {
        $output .= sprintf('"%s";',$rooster->$field);
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

} //class EzacRoosterController
