<?php


namespace Drupal\ezac_passagiers\Plugin\rest\resource;

use Drupal;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_passagiers\Model\EzacPassagier;
use Drupal\ezac\Util\EzacUtil;

  /**
   * Provides a resource for reserveringen table reads
   *
   * @RestResource(
   *   id = "ezac_reserveringen_resource",
   *   label = @Translation("EZAC reserveringen"),
   *   uri_paths = {
   *     "canonical" = "/api/v2/reserveringen",
   *   }
   * )
   */
class EzacPassagiersResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a passagiers reservering record for the specified ID.
   *
   * @param null $id
   *   The ID of the passagier record.
   * @param string $datum
   *    Datum YYYY[-MM[-DD]] or date range YYYY-MM-DD:YYYY-MM-DD
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the passagier record or array of records.
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
      $record = new EzacPassagier($id);
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
    $errmsg = EzacUtil::checkDatum($datum, $datumStart, $datumEnd);
    if ($errmsg != '') {
      throw new NotFoundHttpException($errmsg);
    }
    // range is indicated by date:date format

    // build selection condition
    $condition = [
      'datum' => [
        'value' => [$datumStart, $datumEnd],
        'operator' => 'BETWEEN',
      ]
    ];

    if ($condition != []) {
      // only send response when selection criteria are given
      $passagiersIndex = EzacPassagier::index($condition);
      if (isset($id) && ($id == '')) {
        // empty id value indicates index request
        return (new ResourceResponse($passagiersIndex))->addCacheableDependency($build);
      }
      else {
        // return selected dagverslagen records
        $result = [];
        foreach ($passagiersIndex as $id) {
          $result[] = (array) new EzacPassagier($id);
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