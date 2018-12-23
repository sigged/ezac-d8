<?php

namespace Drupal\ezac\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

use Drupal\ezac\Model\EzacLid;

/**
 * Controller for DLO administration.
 */
class EzacLedenController extends ControllerBase {

    /**
     * Display the status of the EZAC leden table
     * @return array
     */
  public function status() {
    $content = array();

      Database::setActiveConnection('ezac');
      $schema = Database::getConnection()->schema();
      Database::setActiveConnection();
      dpm($schema); //debug

    // show record count for each Code value
    $headers = array(
      t("Code"),
      t("Aantal"), 
      t("Uitvoer"),
    );
    
    $total = 0;
    foreach (EzacLid::$lidCode as $code => $description) {
      $count = EzacLid::counter(['code' => $code]);
      $total = $total+$count;
      $url = Url::fromRoute(
        'ezac_leden_overzicht_code',
        array(
          'code' => $code
        )
      );
      $urlKat = $url->toString();
      $url = Url::fromRoute(
        'ezac_leden_export_code',
        array(
          'filename' => "Leden-$code.csv",
          'code' => $code
        )
      );
      $urlExport = $url->toString();
      $rows[] = array(
        t("<a href=$urlKat>$description</a>"),
        $count,
        t("<a href=$urlExport>Leden-$code.csv</a>"),
      );
    }
    // add line for totals
    $url = Url::fromRoute(
      'ezac_leden_overzicht'
    );
    $urlKat = $url->toString();
    $url = Url::fromRoute(
      'ezac_leden_export',
      array(
        'filename' => "Leden.csv",
      )
    );
    $urlExport = $url->toString();
    $rows[]= array(
      t("<a href=$urlKat>Totaal</a>"), 
      $total,
      t("<a href=$urlExport>Leden.csv</a>"),      
    );
    //build table
    $content['table'] = array(
      '#type' => 'table',
      '#caption' => t("Categorie overzicht van het EZAC LEDEN bestand"),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    );
    
    //toon functie om user-ids voor de EZAC website aan te maken
    $url = Url::fromRoute(
      'ezac_leden_user_create'
    );
    $urlCreate = $url->toString();
    $content['users'] = array(
      '#type' => 'markup',
      '#markup' => "<a href=$urlCreate>Aanmaken user-id voor EZAC website</a>",
    );
    
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    //apply css
    //$content['#attached']['library'][] = 'ezac/dlotable'; // of met ['css']
    return $content;
  }

    /**
     * Render a list of entries in the database.
     * @param string
     *  $code - categorie (optional)
     * @param string
     *  $jaar
     * @return array
     */
  public function overzicht($code = NULL, $jaar = NULL) {
    $content = array();

    $rows = array();
    $headers = array(
      t('afkorting'),
      t('code'),
      t('naam<br>email'),
      t('adres<br>postcode<br>plaats'),
      t('telefoon<br>mobiel'),
      t('opmerking'),
    );

    if (isset($code)) {
      $condition = ['kat' => $code];
    }
    else $condition = array();
    
    // prepare pager
    $total = EzacLid::counter($condition);
    $field = 'id';
    $sortkey = 'code';
    $sortdir = 'ASC';
    $range = 50;
    $page = pager_default_initialize($total, $range);
    $from = $range * $page;
    
    $ledenIndex = EzacLid::index($condition, $field, $sortkey, $sortdir, $from, $range);
    foreach ($ledenIndex as $id) {
      $lid = (new EzacLid)->read($id);
      $url = Url::fromRoute(
        'ezac_leden_edit',  // edit leden record
        array(
          'id' => $lid->id,
        )
      );
      $urlString = $url->toString();
      $rows[] = array(
        //link each record to edit route
        t("<a href=$urlString>$lid->afkorting"),
        t("$lid->code"),
        t("$lid->voornaam $lid->voorvoeg $lid->achternaam<br>$lid->email"),
        t("$lid->adres<br>$lid->postcode $lid->plaats"),
        t("$lid->telefoon<br>$lid->mobiel"),
        t("$lid->opmerking"),
      );
    }
    $caption = "Overzicht EZAC Leden bestand $jaar";
    if (isset($code)) $caption .= " - " . EzacLid::$lidCode[$code];
    $content['table'] = array(
        '#type' => 'table',
        '#caption' => $caption,
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => t('Geen gegevens beschikbaar.'),
        '#sticky' => TRUE,
      );
    // add pager
    $content['pager'] = array(
        '#type' => 'pager',
        '#weight' => 5
    );
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  } // overzicht

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
  public function export($filename = 'ezac.txt', $code = NULL) {

      $messenger = \Drupal::messenger();

    if ($filename == '') $filename = 'ezac.txt';

    // Determine KAT categorie from Leden for export
    if (isset($code)) {
      $condition = array(
        'kat' => $code,
      );
    }
    else $condition = NULL; //select all

    $records = EzacLid::index($condition); //read records index
    $count = count($records);
      $messenger->addMessage("Export $count records van categorie [$code] naar bestand [$filename]"); //DEBUG

    $output = ""; // initialize output
    //build header line
    foreach (EzacLid::$fields as $field => $description) {
      $output .= '"' .$field .'";';
    }
    //remove last ";" 
    $output = rtrim($output, ";") ."\r\n";
    
    // export all records
    foreach ($records as $id) {
      $lid = (new EzacLid)->read($id);
      // add all fields
      foreach (EzacLid::$fields as $field => $description) {
        $output .= sprintf('"%s";',$lid->$field);
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
  
} //class DLOLedenController
