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
   *     "canonical" = "/api/v1/leden/",
   *     "https://www.drupal.org/link-relations/create" = "/api/v1/leden"
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
   *    * for all codes or one EzacLid::lidCode value
   * @param int $actief
   *    non-zero value selects only active Leden records
   * @param null $afkorting
   *    select Leden record for afkorting
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the leden record or array of records.
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
        $ledenIndex = EzacLid::index($condition);
        return (new ResourceResponse((array) $ledenIndex))->addCacheableDependency($build);
      }
      // return record for id
      $record = (new EzacLid)->read($id);
      if (!empty($record)) {
        return (new ResourceResponse((array) $record))->addCacheableDependency($build);
      }
      throw new NotFoundHttpException("Invalid ID: $id");
    }

    // when no ID is given, either code or afkorting has to be present
    if (isset($code)) {
      if ($code == '*') {
        $condition = []; //select all
      }
      // test valid CODE values
      elseif (!array_key_exists($code, EzacLid::$lidCode)) {
        //invalid code value
        throw new BadRequestHttpException("Invalid CODE: $code");
      }
      else {
        $condition = ['code' => $code];
      }
      if (isset($actief)) {
        if ($actief != '0') {
          $condition['actief'] = 1;
        }
      }
      $ledenIndex = EzacLid::index($condition);
      $result = [];
      foreach ($ledenIndex as $id) {
        $result[] = (array) (new EzacLid)->read($id);
      }
      return (new ResourceResponse($result))->addCacheableDependency($build);
    }

    if (isset($afkorting)) {
      //@TODO sanitize $afkorting
      $record = (new EzacLid)->read(EzacLid::getId($afkorting));
      return (new ResourceResponse((array) $record))->addCacheableDependency($build);
    }

    // no id code or afkorting parameter given
    // return index of leden

    throw new BadRequestHttpException('No valid parameter provided');
  }

}