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

    // when id given, read that record
    if (isset($id)) {
      if ($id == '') {
        //return index of id
        $condition = [];
        if (isset($datum)) {
          $condition['datum'] = $datum;
        }
        $roosterIndex = EzacRooster::index($condition);
        return (new ResourceResponse((array) $roosterIndex))->addCacheableDependency($build);
      }
      // return record for id
      $record = new EzacRooster($id);
      if ($record->id != null) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }
      throw new NotFoundHttpException("Invalid ID: $id");
    }

    // when no ID is given, datum has to be present
    if (isset($datum)) {
      // @todo test valid datum values  and range
      $condition = ['datum' => $datum];
      if (isset ($periode)) {
        $condition['periode'] = $periode;
      }
      if (isset($dienst)) {
        $condition['dienst'] = $dienst;
      }
      $roosterIndex = EzacRooster::index($condition);
      $result = [];
      foreach ($roosterIndex as $id) {
        $result[] = (array) new EzacRooster($id);
      }
      return (new ResourceResponse($result))->addCacheableDependency($build);
    }

    // no id code or afkorting parameter given
    // return index of rooster

    throw new BadRequestHttpException('No valid parameter provided');
  }

}