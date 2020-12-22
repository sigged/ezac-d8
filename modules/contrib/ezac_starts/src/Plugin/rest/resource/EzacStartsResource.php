<?php


namespace Drupal\ezac_starts\Plugin\rest\resource;

use Drupal;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_starts\Model\EzacStart;

  /**
   * Provides a resource for starts table reads
   *
   * @RestResource(
   *   id = "ezac_starts_resource",
   *   label = @Translation("EZAC starts table"),
   *   uri_paths = {
   *     "canonical" = "/api/v2/starts"
   *   }
   * )
   */
class EzacStartsResource extends ResourceBase {

  /**
   * @param $datum
   * @param $datumStart
   * @param $datumEnd
   *
   * @return string
   */
  private function check_datum($datum, &$datumStart, &$datumEnd): string {
    $errmsg = '';
    $datum_delen = explode('-', $datum);
    switch (strlen($datum)) {
      case 4: //YYYY
        if (!checkdate(01, 01, $datum_delen[0])) {
          $errmsg = 'Invalid value parameter datum YYYY [' .$datum .']';
        }
        $datumStart = $datum .'-01-01';
        $datumEnd   = $datum .'-12-31';
        break;
      case 7: //YYYY-MM
        if (!checkdate($datum_delen[1], 01, $datum_delen[0])) {
          $errmsg = 'Invalid value parameter datum YYYY-MM [' .$datum .']';
        }
        $datumStart = $datum .'-01';
        if     (checkdate($datum_delen[1], 31, $datum_delen[0])) $datumEnd = $datum .'-31';
        elseif (checkdate($datum_delen[1], 30, $datum_delen[0])) $datumEnd = $datum .'-30';
        elseif (checkdate($datum_delen[1], 29, $datum_delen[0])) $datumEnd = $datum .'-29';
        elseif (checkdate($datum_delen[1], 28, $datum_delen[0])) $datumEnd = $datum .'-28';
        break;
      case 10: //YYYY-MM-DD
        if (!checkdate($datum_delen[1], $datum_delen[2], $datum_delen[0])) { //mm dd yyyy
          $errmsg = 'Invalid value parameter datum YYYY-MM-DD [' .$datum .']';
        }
        $datumStart = $datum; // .' 00:00:00');
        $datumEnd   = $datum; // .' 23:59:59');
        break;
      default: //invalid
        $errmsg = 'Invalid length parameter datum [' .$datum .']';
    }
    return $errmsg;
  }

  /**
   * Responds to GET requests.
   *
   * Returns a starts table record for the specified ID.
   *
   * @param null $id
   *   The ID of the leden record.
   * @param string $datum
   *    Datum YYYY[-MM[-DD]] or date range YYYY-MM-DD:YYYY-MM-DD
   * @param string $naam
   *    Naam for gezagvoerder or tweede in start records
   * @param string $registratie
   *    Registratie in start recods
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the leden record or array of records.
   *
   */
  public function get() {

    // Configure caching settings.
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    //get parameters
    $id = Drupal::request()->query->get('id');
    $datum = Drupal::request()->query->get('datum');
    $naam = Drupal::request()->query->get('naam');
    $registratie = Drupal::request()->query->get('registratie');

    // when id given, read that record
    if (isset($id)) {
      if ($id != '') {
        // return record for id
        $record = (new EzacStart)->read($id);
        if (!empty($record)) {
          return (new ResourceResponse((array) $record))->addCacheableDependency($build);
        }
        throw new NotFoundHttpException("Invalid ID: $id");
      }
    }

    //parse $datum
    if (!isset($datum) or $datum == NULL) {
      $datum = date('Y-m-d'); // defaults to today
    }
    //if $datum is a range, split and process
    // range is indicated by date:date format
    if (strpos($datum, ':')) {
      $datum_range = explode(':', $datum);
      // eerste datum is $datum_range[0]
      // tweede datum is [1]
      // take datumStart from first date
      $errmsg = self::check_datum($datum_range[0], $datumStart, $de);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
      //take datumEnd from second date
      $errmsg = self::check_datum($datum_range[1], $ds, $datumEnd);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
    }
    else { // single date
      $errmsg = self::check_datum($datum, $datumStart, $datumEnd);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
    }

    // build selection condition
    $condition = [
      'datum' => [
        'value' => [$datumStart, $datumEnd],
        'operator' => 'BETWEEN',
      ]
    ];

    // registratie
    if (isset($registratie)) {
      //@TODO test valid registratie values
      $condition ['registratie'] = $registratie;
    }

    // naam
    if (isset($naam)) {
      $condition['OR'] = [
        'gezagvoerder' => $naam,
        'tweede' => $naam,
      ];
    }

    if ($condition != []) {
      // only send response when selection criteria are given
      $startsIndex = EzacStart::index($condition);
      if ($id == '') {
        // empty id value indicates index request
        return (new ResourceResponse($startsIndex))->addCacheableDependency($build);
      }
      else {
        // return selected starts records
        $result = [];
        foreach ($startsIndex as $id) {
          $result[] = (array) (new EzacStart)->read($id);
        }
        return (new ResourceResponse($result))->addCacheableDependency($build);
      }
    }
    else {
      // no parameter given
      throw new BadRequestHttpException('No valid parameter provided');
    }
  } //get

}