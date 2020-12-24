<?php


namespace Drupal\ezac_vba\Plugin\rest\resource;

use DateTimeZone;
use Drupal;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_vba\Model\EzacVbaDagverslag;
use Drupal\ezac\Util\EzacUtil;

  /**
   * Provides a resource for starts table reads
   *
   * @RestResource(
   *   id = "ezac_dagverslagen_resource",
   *   label = @Translation("EZAC dagverslagen"),
   *   uri_paths = {
   *     "canonical" = "/api/v2/dagverslagen",
   *     "create" = "/api/v2/dagverslagen"
   *   }
   * )
   */
class EzacDagverslagResource extends ResourceBase {

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
  public function get(): ResourceResponse {

    // Configure caching settings.
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    //get parameters
    $id = Drupal::request()->query->get('id');
    $datum = Drupal::request()->query->get('datum');

    // when id given, read that record
    if (isset($id) && ($id != '')) {
      // return record for id
      $record = (new EzacVbaDagverslag)->read($id);
      if (!empty($record)) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }
      throw new NotFoundHttpException("Invalid ID: $id");
    }

    //parse $datum
    if (!isset($datum) or $datum == NULL) {
      $datum = date('Y-m-d'); // defaults to today
    }
    //if $datum is a range, split and process
    // range is indicated by date:date format
    if (strpos($datum, ':')) {
      $datum_range = explode(':', $datum);
      // first datum is $datum_range[0]
      // second datum is [1]
      // take datumStart from first date
      $errmsg = EzacUtil::checkDatum($datum_range[0], $datumStart, $de);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
      //take datumEnd from second date
      $errmsg = EzacUtil::checkDatum($datum_range[1], $ds, $datumEnd);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
    }
    else { // single date
      $errmsg = EzacUtil::checkDatum($datum, $datumStart, $datumEnd);
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

    if ($condition != []) {
      // only send response when selection criteria are given
      $dagverslagenIndex = EzacVbaDagverslag::index($condition);
      if (isset($id) && ($id == '')) {
        // empty id value indicates index request
        return (new ResourceResponse($dagverslagenIndex))->addCacheableDependency($build);
      }
      else {
        // return selected dagverslagen records
        $result = [];
        foreach ($dagverslagenIndex as $id) {
          $result[] = (array) (new EzacVbaDagverslag)->read($id);
        }
        return (new ResourceResponse($result))->addCacheableDependency($build);
      }
    }
    else {
      // no parameter given
      throw new BadRequestHttpException('No valid parameter provided');
    }
  } //get

  /**
   * @param $id
   * @param $datum
   * @param $instructeur
   * @param $weer
   * @param $verslag
   *
   * @return \Drupal\ezac_vba\Model\EzacVbaDagverslag
   */
  private function processDagverslag($id,
                                     $datum,
                                     $instructeur,
                                     $weer,
                                     $verslag): EzacVbaDagverslag {
    // Build dagverslag record
    $dagverslagRecord = new EzacVbaDagverslag();

    // id
    if (isset($id)) {
      $dagverslagRecord->id = $id; // used for update
    }
    // datum
    if (isset($datum)) {
      // check datum
      $dc = explode('-', $datum);
      if (!checkdate($dc[1], $dc[2], $dc[0])) {
        throw new BadRequestHttpException("Invalid datum provided: $datum");
      }
      //$timezone = new DateTimeZone("Europe/Amsterdam");
      $dagverslagRecord->datum = date('Y-m-d H:i:s', strtotime($datum));
    }
    else {
      throw new BadRequestHttpException('No datum parameter provided');
    }

    // instructeur
    if (isset($instructeur)) {
      $dagverslagRecord->instructeur = substr(trim($instructeur), 0, 20);
    }
    else {
      throw new BadRequestHttpException('No instructeur parameter provided');
    }

    // weer
    if (isset($weer)) {
      $dagverslagRecord->weer = htmlentities($weer);
    }

    // verslag
    if (isset($verslag)) {
      $dagverslagRecord->verslag = htmlentities($verslag);
    }

    // set mutatie
    $dagverslagRecord->mutatie = date('Y-m-d H:i:s',strtotime('now'));

    return $dagverslagRecord;
  } //processDagverslag

  /**
   * Responds to POST requests.
   *
   * @param $datum
   * @param $instructeur
   * @param $weer
   * @param $verslag
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
  public function post(): ModifiedResourceResponse {

    //get parameters
    $id = null; // is assigned with create
    $datum = Drupal::request()->query->get('datum');
    $instructeur = Drupal::request()->query->get('instructeur');
    $weer = Drupal::request()->query->get('weer');
    $verslag = Drupal::request()->query->get('verslag');

    $dagverslagRecord = $this->processDagverslag(
      $id,
      $datum,
      $instructeur,
      $weer,
      $verslag);

    // write dagverslag record to database
    $record = $dagverslagRecord->create();
    return new ModifiedResourceResponse($record->id, 200);
  } // post

  /**
   * Responds to PATCH requests.
   *
   * @param $id
   * @param $datum
   * @param $instructeur
   * @param $weer
   * @param $verslag
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
  public function patch(): ModifiedResourceResponse {
    //get parameters
    $id = Drupal::request()->query->get('id');
    $datum = Drupal::request()->query->get('datum');
    $instructeur = Drupal::request()->query->get('instructeur');
    $weer = Drupal::request()->query->get('weer');
    $verslag = Drupal::request()->query->get('verslag');

    $dagverslagrecord = $this->processDagverslag($id, $datum, $instructeur, $weer, $verslag);
    // write start record to database
    $nrAffected = $dagverslagrecord->update();
    return new ModifiedResourceResponse($nrAffected, 200);

  }  //patch

  /**
   * @param $id
   *   id from record to be deleted
   * @param $datum
   *   datum value from record to be deleted
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function delete(): ModifiedResourceResponse {
    //get parameters
    $id = Drupal::request()->query->get('id');
    $datum = Drupal::request()->query->get('datum');
    // check validity of datum is record to be deleted - as a protection
    $record = (new EzacVbaDagverslag())->read($id);
    if ($record == FALSE) {
      throw new NotFoundHttpException("Invalid ID: $id");
    }
    if ($datum != $record->datum) {
      throw new BadRequestHttpException("Invalid datum: $datum");
    }
    // delete record
    $nrAffected = $record->delete();

    return new ModifiedResourceResponse();
  } //delete

}