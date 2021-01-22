<?php


namespace Drupal\ezac_rooster\Plugin\rest\resource;

use Drupal;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_rooster\Model\EzacRooster;

  /**
   * Provides a resource for rooster table reads
   *
   * @RestResource(
   *   id = "ezac_rooster_resource",
   *   label = @Translation("EZAC rooster table"),
   *   uri_paths = {
   *     "canonical" = "/api/v2/rooster"
   *   }
   * )
   */
class EzacRoosterResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a rooster table record for the specified ID.
   *
   * @param null $id
   *   The ID of the rooster record.
   * @param string $datum
   *    select rooster datum
   * @param string $periode
   *    select rooster periode
   * @param string $dienst
   *    select rooster record for dienst
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the rooster record or array of records.
   *
   */
  public function get() {

    //get parameters
    $id = Drupal::request()->query->get('id');
    $datum = Drupal::request()->query->get('datum');
    $periode = Drupal::request()->query->get('periode');
    $dienst = Drupal::request()->query->get('dienst');

    // Configure caching settings.
    $build = [
      '#cache' => [
      'max-age' => 0,
      ],
    ];

    // prepare selection condition
    $condition = [];

    // when id given, read that record
    if (isset($id) && $id !='') {
      // return record for id
      $record = new EzacRooster($id);
      if ($record->id != null) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }
      throw new NotFoundHttpException("Invalid ID: $id");
    }

    // when no ID is given, datum or other parameter has to be present
    if (isset($datum)) {
      // test valid datum values and range
      $errmsg = Drupal\ezac\Util\EzacUtil::checkDatum($datum, $datumStart, $datumEnd);
      if ($errmsg != '') {
        // invalid date
        throw new NotFoundHttpException($errmsg);
      }
      $condition = [
        'datum' => [
          'value' => [$datumStart, $datumEnd],
          'operator' => 'BETWEEN',
        ]
      ];
    }

    if (isset ($periode)) {
      // read settings
      $settings = Drupal::config('ezac_rooster.settings');
      $periodes = $settings->get('rooster.periodes');
      if (!key_exists($periode, $periodes)) {
        // invalid periode
        throw new NotFoundHttpException("periode $periode invalid");
      }
      $condition['periode'] = $periode;
    }

    if (isset($dienst)) {
      // read settings
      if (!isset($settings)) $settings = Drupal::config('ezac_rooster.settings');
      $diensten = $settings->get('rooster.diensten');
      if (!key_exists($dienst, $diensten)) {
        // invalid dienst
        throw new NotFoundHttpException("dienst $dienst invalid");
      }
      $condition['dienst'] = $dienst;
    }

    // if no selection condition then return error
    if ($condition == []) {
      throw new BadRequestHttpException('No valid parameter provided');
    }

    // read selected rooster entry IDs
    $roosterIndex = EzacRooster::index($condition);

    // return IDs or full records
    if (isset($id) && $id == '') {
      // return indexes only
      $result = $roosterIndex;
    }
    else {
      // return full records
      $result = [];
      foreach ($roosterIndex as $id) {
        $result[] = (array) new EzacRooster($id);
      }
    }
    return (new ResourceResponse($result))->addCacheableDependency($build);
  }
}