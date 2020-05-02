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
  public function overzichtJaar($jaar = NULL) {
    $content = array();

    $rows = [];
    $headers = [
        t('datum'),
        t('aantal vba'),
    ];

    // select all start dates for selected year
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
      $total = count(EzacStart::index($condition, 'datum', $sortkey, $sortdir, $from, $range, $unique));

      // prepare pager
      $range = 120;
      $page = pager_default_initialize($total, $range);
      $from = $range * $page;
      $field = 'datum';
      $sortkey = 'datum';
      $sortdir = 'ASC';
      $unique = TRUE; // return unique results only

      $vbaIndex = EzacStart::index($condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    foreach ($vbaIndex as $datum) {
      $condition = ['datum' => $datum];
      $count = EzacStart::counter($condition);

      $urlString = Url::fromRoute(
        'ezac_vba_overzicht',  // show vba for datum
        ['datum' => $datum]
      )->toString();
      $rows[] = [
        //link each record to overzicht route
        t("<a href=$urlString>$datum"),
        $count,
      ];
    }
    $caption = "Overzicht EZAC vba data voor $jaar";
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
    public function overzicht($datum = NULL) {
        $content = array();

        $rows = [];
        $headers = [
            t('start'),
            t('landing'),
            t('duur'),
            t('registratie'),
            t('gezagvoerder'),
            t('tweede'),
            t('soort'),
            t('startmethode'),
            t('instructie'),
            t('opmerking'),
        ];

        $leden = EzacUtil::getLeden();
        // $kisten = EzacUtil::getKisten();

        // select all vba for selected date
        $condition = ['datum' => $datum];

        // prepare pager
        $total = EzacStart::counter($condition);
        $field = 'id';
        $sortkey = 'start';
        $sortdir = 'ASC'; // newest first
        $range = 50;
        $page = pager_default_initialize($total, $range);
        $from = $range * $page;
        $unique = FALSE; // return all results

        $vbaIndex = EzacStart::index($condition, $field, $sortkey, $sortdir, $from, $range, $unique);
        foreach ($vbaIndex as $id) {
            $start = (new EzacStart)->read($id);

            $urlString = Url::fromRoute(
                'ezac_vba_update',  // edit vba record
                ['id' => $start->id]
            )->toString();

            if (isset($leden[$start->gezagvoerder]) && $start->gezagvoerder <> '') {
                $gezagvoerder = $leden[$start->gezagvoerder];
            }
            else $gezagvoerder = $start->gezagvoerder; // un-edited record value

            if (isset($leden[$start->tweede]) && $start->tweede <> '') {
                $tweede = $leden[$start->tweede];
            }
            else $tweede = $start->tweede; // un-edited record value

            $rows[] = [
                //link each record to edit route
                t("<a href=$urlString>$start->start</a>"),
                $start->landing,
                $start->duur,
                $start->registratie,
                $gezagvoerder,
                $tweede,
                EzacStart::$vbaoort[$start->soort],
                EzacStart::$startMethode[$start->startmethode],
                $start->instructie,
                $start->opmerking,
            ];
        }
        $caption = "Overzicht EZAC vba bestand $datum";
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
