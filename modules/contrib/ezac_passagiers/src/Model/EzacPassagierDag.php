<?php

namespace Drupal\ezac_passagiers\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacReservering.php
 * The EZAC class definitions for the reserveringen table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the EzacVbaBevoegdheden class
 */
class EzacPassagierDag extends EzacStorage
{
    // define the fields for the reserveringen table
    public $id = 0;
    public $datum = '';

    /**
     * constructor for reserveringen
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->ezacRead('passagiers_dagen');
        }
    }

    /**
     * create - Create vba record
     *
     * @return int
     *   ID of record created
     */
    public function create(): ?int {

        $this->id = $this->ezacCreate('passagiers_dagen');
        return $this->id;
    }

    /**
     * read - Reads record from the passagiers table
     *
     * @param int id
     * @return object passagier
     */
    public function read($id = NULL)
    {
      if (isset($id)) {
        $this->id = $id;
        $this->ezacRead('passagiers_dagen');
        if ($this->id == null) {
          // read failed
          return null;
        }
        // object is put in $this
      }
      else return null;
    }

    /**
     * update - Updates record in the passagiers table
     *
     * @return int
     *   records_updated
     */
    public function update(): ?int {
        // build $condition
        return $this->ezacUpdate('passagiers_dagen');
    }

    /**
     * delete - Deletes records from the passagiers table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): ?int {
        return $this->ezacDelete('passagiers_dagen');
    }

    /***
     * counter - Counts the number of passagiers records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition): ?int {
      return EzacStorage::ezacCount('passagiers_dagen', $condition);
    }

    /***
     * index - Gets the index of passagiers records
     *
     * @param null $condition
     * @param string $field
     * @param string $sortkey
     * @param string $sortdir
     * @param $from
     * @param $range
     * @param bool $unique
     * @return array of id values
     */
    public static function index($condition = NULL, $field = 'id', $sortkey = 'datum', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE): array {
        return EzacStorage::ezacIndex('passagiers_dagen', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }
  /***
   * getId - search id for record with datum
   * @param string datum
   * @return int id
   */
  public static function getId($datum): ?int {
    //find id for dag
    $index = self::index(['datum' => $datum]);
    if (isset($index[0])) { // record found
      return $index[0];
    } else return NULL;
  }

}
  
