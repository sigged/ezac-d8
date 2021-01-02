<?php

namespace Drupal\ezac_rooster\Controller;

use Drupal;
use Drupal\ezac_leden\Model\EzacLid;
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
      t("Aantal dagen"),
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
     *  $datum - categorie (optional)
     * @return array
     */
  public function overzicht($datum = NULL) {

    // selecteer vliegende leden
    $condition = [
      //'code' => 'VL',
      //'actief' => TRUE,
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

    $condition = [];
    // select rooster dates
    if (isset($datum)) {
      EzacUtil::checkDatum($datum, $datumStart, $datumEnd);
      $condition = [
        'datum' => [
          'value' => [$datumStart, $datumEnd],
          'operator' => 'BETWEEN',
        ]
      ];
    }
    // prepare pager
    $total = EzacRooster::counter($condition);
    $field = 'id';
    $sortkey = 'datum';
    $sortdir = 'ASC';
    $range = 50;
    $pager = Drupal::service('pager.manager')
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
      $geruild = ($rooster->geruild != '') ? $leden[$rooster->geruild] : '';
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
    // read settings
    $settings = Drupal::config('ezac_rooster.settings');
    //set up diensten
    $diensten = $settings->get('rooster.diensten');
    //ezac_rooster_diensten Id Dienst Omschrijving
    $diensten['-'] = '-'; //placeholder voor lege dienst
    $form['diensten'] = array(
      '#type' => 'value',
      '#value' => $diensten,
    );

    //set up periode
    $periodes = $settings->get('rooster.periodes');
    //store header info for periodes reference in submit function
    $form['periodes'] = array(
      '#type' => 'value',
      '#value' => $periodes,
    );

    // selecteer vliegende leden
    $condition = [
      //'code' => 'VL',
      //'actief' => TRUE,
    ];
    $leden = EzacUtil::getLeden($condition);

    //get current user details
    $user = $this->currentUser();
    $may_edit = $user->hasPermission('EZAC_edit');

    // read own leden record
    $condition = [
      'user' => $user->getAccountName(),
    ];
    $lid = new EzacLid(EzacLid::getId($condition));
    $zelf = $lid->afkorting;

    // initialize page content
    $content = array();
    $rows = [];

    //prepare header
    $header = array(t('Datum'));
    // voeg een kolom per periode toe
    foreach ($periodes as $periode => $omschrijving) {
      array_push($header, t($omschrijving));
    }

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
    $roosterDates = EzacRooster::index($condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    $total = count($roosterDates);

    // prepare pager
    $range = 120;
    $pager = Drupal::service('pager.manager')
      ->createPager($total, $range);
    $page = $pager
      ->getCurrentPage();

    $from = $range * $page;
    $field = 'datum';
    $sortkey = 'datum';
    $sortdir = 'ASC';

    foreach ($roosterDates as $datum) {
      $urlString = Url::fromRoute(
        //'ezac_rooster_overzicht',  // show rooster for datum
        'ezac_rooster_table',  // show rooster for datum
        [
          'datum' => $datum,
        ]
      )->toString();

      // build periode columns for diensten
      // intialize columns for diensten
      $dienst = [];
      foreach ($periodes as $periode => $omschrijving) {
        $dienst[$periode] = '';
      }
      // lees alle diensten voor rooster_dag
      $condition = [
        'datum' => $datum,
      ];
      $roosterIndex = EzacRooster::index($condition);
      foreach ($roosterIndex as $id) {
        // add dienst to table for datum
        $rooster = new EzacRooster($id);
        $t = $diensten[$rooster->dienst] .':' .$leden[$rooster->naam] .'<br>';
        //@todo if edit access or own afkorting add link for switching
        $dienst[$rooster->periode] .= $t;
      }

      $d = EzacUtil::showDate($datum);
      $rows[] = [
        //link each record to overzicht route
        // @todo check op may_edit
        t("<a href=$urlString>$d"),
        $dienst, // diensten for datum
      ];
    }
    $caption = "Overzicht EZAC rooster data voor $jaar";
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
  } // overzichtJaar
  
    /**
     * Maak exportbestand uit Leden tabel
     * geformatteerd voor input in bestand (csv)
     * Output via html headers naar attachment
     *
     * @param string $filename
     * @param null $datum
     * @return mixed Response output text in csv format
     *   output text in csv format
     */
  public function export($filename = 'Ezac.txt', $datum = NULL) {

    $messenger = Drupal::messenger();

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
