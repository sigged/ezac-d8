<?php


namespace Drupal\ezac_kisten\Plugin\rest\resource;

use Drupal;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_kisten\Model\EzacKist;

  /**
   * Provides a resource for kisten table reads
   *
   * @RestResource(
   *   id = "ezac_kisten_resource",
   *   label = @Translation("EZAC kisten table"),
   *   uri_paths = {
   *     "canonical" = "/api/v2/kisten"
   *   }
   * )
   */
class EzacKistenResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a leden table record for the specified ID.
   *
   * @param null $id
   *   The ID of the leden record.
   * @param string $registratie
   *    * for all registraties [empty] or one registratie
   * @param int $actief
   *    non-zero value selects only active Kisten records
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the leden record or array of records.
   *
   */
  public function get() {

    //get parameters
    $id = Drupal::request()->query->get('id');
    $registratie = Drupal::request()->query->get('registratie');
    $actief = Drupal::request()->query->get('actief');

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
        if (isset($actief)) {
          if ($actief != '0') {
            $condition['actief'] = 1;
          }
        }
        $kistenIndex = EzacKist::index($condition);
        return (new ResourceResponse((array) $kistenIndex))->addCacheableDependency($build);
      }
      // return record for id
      $record = new EzacKist($id);
      if (!empty($record)) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }
      throw new NotFoundHttpException("Invalid ID: $id");
    }

    // when no ID is given, registratie has to be present
    if (isset($registratie)) {
      if ($registratie == '*') {
        $condition = []; //select all
      }
      //@TODO test valid registratie values
      else {
        $condition = ['registratie' => $registratie];
      }
      if (isset($actief)) {
        if ($actief != '0') {
          $condition['actief'] = 1;
        }
      }
      $kistenIndex = EzacKist::index($condition);
      $result = [];
      foreach ($kistenIndex as $id) {
        $result[] = (array) new EzacKist($id);
      }
      return (new ResourceResponse($result))->addCacheableDependency($build);
    }

    // no id code or registratie parameter given
    throw new BadRequestHttpException('No valid parameter provided');
  }

}