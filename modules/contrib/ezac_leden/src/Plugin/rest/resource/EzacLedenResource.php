<?php


namespace Drupal\ezac_leden\Plugin\rest\resource;

use Drupal;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\ezac_leden\Model\EzacLid;

  /**
   * Provides a resource for leden table reads
   *
   * @RestResource(
   *   id = "ezac_leden_resource",
   *   label = @Translation("EZAC leden table"),
   *   uri_paths = {
   *     "canonical" = "/api/v1/leden",
   *   }
   * )
   */
class EzacLedenResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a leden table record for the specified ID.
   *
   * @param null $id
   *   The ID of the leden record.
   * @param string $code
   * @param int $actief
   * @param null $afkorting
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the leden record.
   *
   */
  public function get() {

    //get parameters
    $id = Drupal::request()->query->get('id');
    $code = Drupal::request()->query->get('code');
    $actief = Drupal::request()->query->get('actief');
    $afkorting = Drupal::request()->query->get('afkorting');

    // Configure caching settings.
    $build = [
      '#cache' => [
      'max-age' => 0,
      ],
    ];
    if (isset($id)) {
      $record = (new EzacLid)->read($id);
      if (!empty($record)) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }

      throw new NotFoundHttpException("Leden entry with ID '$id' was not found");
    }

    // when no ID is given, either code or afkorting has to be present
    if (isset($code)) {
      //@TODO sanitize $code
      $condition = ['code' => $code];
      if (isset($actief)) {
        $condition['actief'] = $actief;
      }
      $ledenIndex = EzacLid::index($condition);
      $result = [];
      foreach ($ledenIndex as $lidIndex) {
        $result[] = (array) (new EzacLid)->read($lidIndex);
      }
      return (new ResourceResponse($result))->addCacheableDependency($build);
    }

    if (isset($afkorting)) {
      //@TODO sanitize $afkorting
      $record = (new EzacLid)->read(EzacLid::getId($afkorting));
      return (new ResourceResponse((array) $record))->addCacheableDependency($build);
    }

    // no code or afkorting parameter given
    throw new BadRequestHttpException('No valid parameter provided');
  }

}