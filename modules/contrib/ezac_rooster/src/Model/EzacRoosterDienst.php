<?php

namespace Drupal\ezac_rooster_diensten\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/Ezacrooster_diensten.php
 * The EZAC class definitions for the rooster_diensten table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the DloLid class
 */
class EzacRoosterDienst extends EzacStorage
{

    //Define rooster_diensten fields
    public static $fields = array(
        'id' => 'Record ID (uniek, auto_increment)',
        'dienst' => 'Dienst',
        'omschrijving' => 'Omschrijving',
    );

    // define the fields for the rooster_diensten table
    public $id = 0;
    public $dienst = '';
    public $omschrijving = '';

    /**
     * constructor for EzacRoosterDienst
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->ezacRead('rooster_diensten', get_class($this));
        }
    }

    /**
     * create - Create rooster_diensten record
     *
     * @return \Drupal\ezac_rooster_diensten\Model\EzacRoosterDienst ID of record created
     *   ID of record created
     */
    public function create(): EzacRoosterDienst {

        $this->id = $this->ezacCreate('rooster_diensten');
        return $this;
    }

    /**
     * read - Reads record from the rooster_diensten table in $this
     *
     * @param int id
     */
    public function read($id = NULL)
    {
      if (isset($id)) {
        $this->id = $id;
        //@todo className parameter is overbodig
        $this->ezacRead('rooster_diensten', get_class($this));
        if ($this->id == null) {
          // read failed
          return null;
        }
        // object is put in $this
      }
      else return null;
    }

    /**
     * update - Updates record in the rooster_diensten table
     *
     * @return int
     *   records_updated
     */
    public function update(): int {
        // build $condition
        return $this->ezacUpdate('rooster_diensten');
    }

    /**
     * delete - Deletes records from the rooster_diensten table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): int {
        return $this->ezacDelete('rooster_diensten');
    }

    /***
     * counter - Counts the number of rooster_diensten records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition): int {
        return EzacStorage::ezacCount("rooster_diensten", $condition);
    }

  /***
   * index - Gets the index of starts records
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
  public static function index($condition = NULL, $field = 'id', $sortkey = 'dienst', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE)
  {
    return EzacStorage::ezacIndex('rooster_diensten', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
  }

}
  
