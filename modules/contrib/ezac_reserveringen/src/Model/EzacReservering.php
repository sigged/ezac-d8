<?php

namespace Drupal\ezac_reserveringen\Model;

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
class EzacReservering extends EzacStorage
{
    // define the fields for the reserveringen table
    public $id = 0;
    public $datum = '';
    public $periode = '';
    public $soort = '';
    public $leden_id = 0;
    public $doel = '';
    public $aangemaakt = 0;
    public $reserve = 0;

    /**
     * constructor for reserveringen
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->ezacRead('reserveringen');
        }
    }

    /**
     * create - Create vba record
     *
     * @return int
     *   ID of record created
     */
    public function create(): ?int {

        $this->id = $this->ezacCreate('reserveringen');
        return $this->id;
    }

    /**
     * read - Reads record from the vba table
     *
     * @param int id
     * @return object DloStart
     */
    public function read($id = NULL)
    {
      if (isset($id)) {
        $this->id = $id;
        $this->ezacRead('reserveringen');
        if ($this->id == null) {
          // read failed
          return null;
        }
        // object is put in $this
      }
      else return null;
    }

    /**
     * update - Updates record in the vba table
     *
     * @return int
     *   records_updated
     */
    public function update(): ?int {
        // build $condition
        return $this->ezacUpdate('reserveringen');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): ?int {
        return $this->ezacDelete('reserveringen');
    }

    /***
     * counter - Counts the number of vba records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition): ?int {
      return EzacStorage::ezacCount('reserveringen', $condition);
    }

    /***
     * index - Gets the index of vba records
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
        return EzacStorage::ezacIndex('reserveringen', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
