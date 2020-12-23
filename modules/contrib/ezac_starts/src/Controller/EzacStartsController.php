<?php

namespace Drupal\ezac_starts\Controller;

use Drupal;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Drupal\ezac_starts\Model\EzacStart;
use Drupal\ezac\Util\EzacUtil;

/**
 * Controller for EZAC start administration.
 */
class EzacStartsController extends ControllerBase {

  /**
   * Display the status of the EZAC starts table
   * @return array
   */
  public function status() {
    $content = [];

    //$schema = drupal_get_module_schema('Ezac', 'starts');

    // show record count for each Code value
    $headers = [
      t("Jaar"),
      t("Aantal vliegdagen"),
      t("Uitvoer"),
    ];
    
    $total = 0;
    $condition = [];
    $datums = array_unique(EzacStart::index($condition, 'datum', 'datum','DESC'));
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
        'ezac_starts_overzicht_jaar',
        [
          'jaar' => $jaar
        ]
      )->toString();
      $urlExport = Url::fromRoute(
        'ezac_starts_export_jaar',
        [
          'filename' => "Starts-$jaar.csv",
          'jaar' => $jaar
        ]
      )->toString();
      $rows[] = [
        t("<a href=$urlJaar>$jaar</a>"),
        $count,
        t("<a href=$urlExport>Starts-$jaar.csv</a>"),
      ];
    }
    // add line for totals
    $urlExport = Url::fromRoute(
      'ezac_starts_export',
      [
        'filename' => "Starts.csv",
      ]
    )->toString();
    $rows[]= [
      t('Totaal'),
      $total,
      t("<a href=$urlExport>Starts.csv</a>"),
    ];
    //build table
    $content['table'] = [
      '#type' => 'table',
      '#caption' => t("Jaar overzicht van het EZAC STARTS bestand"),
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
  public function overzichtJaar($jaar) {
    $content = array();

    $rows = [];
    $headers = [
        t('datum'),
        t('aantal starts'),
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
    $total = count(EzacStart::index($condition, $field, $sortkey, $sortdir, $from, $range, $unique));

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

    $startsDates = EzacStart::index($condition, $field, $sortkey, $sortdir);
    $startsIndex = array_unique($startsDates);

    $dagen = [];
    foreach ($startsDates as $datum) {
      if (isset($dagen[$datum])) $dagen[$datum]++;
      else $dagen[$datum] = 1;
    }

    foreach ($startsIndex as $datum) {
      $urlString = Url::fromRoute(
        'ezac_starts_overzicht',  // show starts for datum
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
    $caption = "Overzicht EZAC Starts data voor $jaar";
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
   * @param $datum_start
   * @param $datum_eind
   * @param null $vlieger
   * @return array $content
   */
  public static function startOverzicht($datum_start, $datum_eind, $vlieger = NULL) {
    //@TODO this routine to be removed here
    $content = array();

    $rows = [];
    $headers = [
      t('datum'),
      t('start'),
      t('landing'),
      t('duur'),
      t('registratie'),
      t('gezagvoerder'),
      t('tweede'),
      t('soort'),
      t('start methode'),
      t('instructie'),
      t('opmerking'),
    ];

    $leden = EzacUtil::getLeden();
    // $kisten = EzacUtil::getKisten();

    // select all starts for selected date
    if (isset($datum_eind))
    {
      $condition['datum'] =
        [
          'value' => [$datum_start, $datum_eind],
          'operator' => 'BETWEEN',
        ];
    }
    else $condition = ['datum' => $datum_start];

    if (isset($vlieger)) {
      // add orGroup to selection
      $condition['OR'] =
        [
          'gezagvoerder' => $vlieger,
          'tweede' => $vlieger,
        ];
    }

    // prepare pager
    $total = EzacStart::counter($condition);
    $field = 'id';
    $sortkey = 'start';
    $sortdir = 'ASC'; // newest first
    $limit = 100;
    //$page = pager_default_initialize($total, $range); // deprecated
    $pager = \Drupal::service('pager.manager')
      ->createPager($total, $limit);
    $page = $pager
      ->getCurrentPage();

    $from = $limit * $page;
    $unique = FALSE; // return all results

    $startsIndex = EzacStart::index($condition, $field, $sortkey, $sortdir, $from, $limit, $unique);
    foreach ($startsIndex as $id) {
      $start = (new EzacStart)->read($id);

      $urlString = Url::fromRoute(
        'ezac_starts_update',  // edit starts record
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

      if (key_exists($start->soort, EzacStart::$startSoort)) {
        $startSoort = EzacStart::$startSoort[$start->soort];
      }
      else $startSoort = $start->soort;

      if (key_exists($start->startmethode, EzacStart::$startMethode)) {
        $startMethode = EzacStart::$startMethode[$$start->startmethode];
      }
      else $startMethode = $start->startmethode;

      $rows[] = [
        //link each record to edit route
        $start->datum,
        t("<a href=$urlString>" .substr($start->start, 0, 5) ."</a>"),
        substr($start->landing,0,5),
        substr($start->duur, 0,5),
        $start->registratie,
        $gezagvoerder,
        $tweede,
        $startSoort,
        $startMethode,
        ($start->instructie) ? 'Ja' :'',
        $start->opmerking,
      ];
    }
    $d = EzacUtil::showDate($datum_start);
    if ($datum_eind <> $datum_start) $d.= " tot " .EzacUtil::showDate($datum_eind);
    $caption = "Overzicht EZAC Starts bestand $d";
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

    return $content;
  }
    /**
     * Render a list of entries in the database.
     * @param string $datum_start
     * @param string $datum_eind
     * @return array
     */
    public function overzicht($datum_start = NULL, $datum_eind = NULL) {
      //@todo add filter params for vlieger and registratie
      $content = self::startOverzicht($datum_start, $datum_eind, NULL);
      // Don't cache this page.
      $content['#cache']['max-age'] = 0;

      return $content;
    } // overzicht

    /**
     * Maak exportbestand uit Starts tabel
     * geformatteerd voor input in bestand (csv)
     * Output via html headers naar attachment
     *
     * @param string $filename
     * @param null $jaar
     * @return mixed Response output text in csv format
     *   output text in csv format
     */
  public function export($filename = 'Ezac.txt', $jaar = NULL) {

    $messenger = Drupal::messenger();

    if ($filename == '') $filename = 'Ezac.txt';

    // Determine Jaar  from Starts for export
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
  
} //class EzacStartsController
