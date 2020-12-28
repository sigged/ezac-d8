<?php

namespace Drupal\ezac_rooster\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacRooster.php
 * The EZAC class definitions for the rooster table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the DloLid class
 */
class EzacRoosterperiode extends EzacStorage
{

    //Define Rooster fields
    public static $fields = array(
        'id' => 'Record ID (uniek, auto_increment)',
        'periode' => 'Periode',
        'omschrijving' => 'Omschrijving',
    );

    // define the fields for the rooster table
    public $id = 0;
    public $periode = '';
    public $omschrijving = '';

    /**
     * constructor for EzacRooster
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->ezacRead('rooster_periode', get_class($this));
        }
    }

    /**
     * create - Create rooster record
     *
     * @return \Drupal\ezac_rooster\Model\EzacRoosterperiode ID of record created
     *   ID of record created
     */
    public function create(): EzacRoosterperiode {

        $this->id = $this->ezacCreate('rooster_periode');
        return $this;
    }

    /**
     * read - Reads record from the rooster table in $this
     *
     * @param int id
     */
    public function read($id = NULL)
    {
      if (isset($id)) {
        $this->id = $id;
        //@todo className parameter is overbodig
        $this->ezacRead('rooster_periode', get_class($this));
        if ($this->id == null) {
          // read failed
          return null;
        }
        // object is put in $this
      }
      else return null;
    }

    /**
     * update - Updates record in the rooster table
     *
     * @return int
     *   records_updated
     */
    public function update(): int {
        // build $condition
        return $this->ezacUpdate('rooster_periode');
    }

    /**
     * delete - Deletes records from the rooster table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): int {
        return $this->ezacDelete('rooster_periode');
    }

    /***
     * counter - Counts the number of rooster_periode records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition): int {
        return EzacStorage::ezacCount("rooster_periode", $condition);
    }

  /***
   * index - Gets the index of rooster_periode records
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
  public static function index($condition = NULL, $field = 'id', $sortkey = 'periode', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE)
  {
    return EzacStorage::ezacIndex('rooster_periode', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
  }

}
  
