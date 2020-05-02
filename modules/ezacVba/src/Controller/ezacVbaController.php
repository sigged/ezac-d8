<?php

namespace Drupal\ezacVba\Controller;

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

  /* @TODO create main menu for VBA: status, entry and update */

    /**
     * Display the status of the EZAC vba table
     * @return array
     */
  public function status() {
    $content = [];

    //$schema = drupal_get_module_schema('ezac', 'vba');

    // show record count for each dagverslag type
    $headers = [
      t("Status"),
      t("Aantal vdagverslagen"),
    ];
    
    $total = 0;
    $condition = [];
    $datums = array_unique(ezacVbaDagverslag::index($condition, 'datum', 'datum','DESC'));
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
        'ezac_vba_overzicht_jaar',
        [
          'jaar' => $jaar
        ]
      )->toString();
      $urlExport = Url::fromRoute(
        'ezac_vba_export_jaar',
        [
          'filename' => "vba-$jaar.csv",
          'jaar' => $jaar
        ]
      )->toString();
      $rows[] = [
        t("<a href=$urlJaar>$jaar</a>"),
        $count,
        t("<a href=$urlExport>vba-$jaar.csv</a>"),
      ];
    }
    // add line for totals
    $urlExport = Url::fromRoute(
      'ezac_vba_export',
      [
        'filename' => "vba.csv",
      ]
    )->toString();
    $rows[]= [
      t('Totaal'),
      $total,
      t("<a href=$urlExport>vba.csv</a>"),
    ];
    //build table
    $content['table'] = [
      '#type' => 'table',
      '#caption' => t("Jaar overzicht van het EZAC vba bestand"),
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
     *  $jaar - categorie (optional)
     * @return array
     */
  public function dagverslag() {
    $content = array();

    $rows = [];
    $headers = [
        t('datum'),
        t('aantal vba'),
    ];


    $caption = "Overzicht EZAC vba data";
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
     * Render a list of entries in the database.
     * @param string
     *  $jaar - categorie (optional)
     * @return array
     */
    public function dagverslagLid() {
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
  public function bevoegdheidLid() {
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

  /**
     * Maak exportbestand uit vba tabel
     * geformatteerd voor input in bestand (csv)
     * Output via html headers naar attachment
     *
     * @param string $filename
     * @param null $jaar
     * @return mixed Response output text in csv format
     *   output text in csv format
     */
  public function export($filename = 'ezac.txt', $jaar = NULL) {

    $messenger = \Drupal::messenger();

    if ($filename == '') $filename = 'ezac.txt';

    // Determine Jaar  from vba for export
    if (isset($jaar)) {
        $condition = [
            'datum' => [
                'value' => ["$jaar-01-01", "$jaar-12-31"],
                'operator' => 'BETWEEN'
            ],
        ];
    }
    else $condition = []; //select all active records

    $records = EzacStart::index($condition); //read records index
    $count = count($records);
    $messenger->addMessage("Export $count records voor jaar [$jaar] naar bestand [$filename]"); //DEBUG

    $output = ""; // initialize output
    //build header line
    foreach (EzacStart::$fields as $field => $description) {
      $output .= '"' .$field .'";';
    }
    //remove last ";" 
    $output = rtrim($output, ";") ."\r\n";
    
    // export all records
    foreach ($records as $id) {
      $start = (new EzacStart)->read($id);
      // add all fields
      foreach (EzacStart::$fields as $field => $description) {
        $output .= sprintf('"%s";',$start->$field);
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
  
} //class EzacvbaController
