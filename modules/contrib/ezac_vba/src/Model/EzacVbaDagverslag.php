<?php

namespace Drupal\ezac_vba\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacDagverslag.php
 * The EZAC class definitions for the vba dagverslagen table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the EzacVbaBevoegdheden class
 */
class EzacVbaDagverslag extends EzacStorage
{

    //Define vba dagverslagen fields
    public static $fields = array(
      'id' => 'Record ID (uniek, auto_increment)',
      'datum' => 'Datum',
      'instructeur' => 'Instructeur',
      'weer' => 'Weer',
      'verslag' => 'Verslag',
      'mutatie' => 'Mutatie'
    );

    // define the fields for the vba dagverslag table
    public $id = 0;
    public $datum = '';
    public $instructeur = '';
    public $weer = '';
    public $verslag = '';
    public $mutatie = null;

    /**
     * constructor for vba_bevoegdheden
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->ezacRead('vba_dagverslagen');
        }
        return $this;
    }

    /**
     * create - Create vba record
     *
     * @return \Drupal\ezac_vba\Model\EzacVbaDagverslag
     *   ID of record created
     */
    public function create(): EzacVbaDagverslag {
        $this->id = $this->ezacCreate('vba_dagverslagen');
        return $this;
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
        //@todo className parameter is overbodig
        $this->ezacRead('vba_dagverslagen');
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
    public function update(): int {
        // build $condition
        return $this->ezacUpdate('vba_dagverslagen');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): int {
        return $this->ezacDelete('vba_dagverslagen');
    }

    /***
     * counter - Counts the number of vba records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition): int {
      return EzacStorage::ezacCount('vba_dagverslagen', $condition);
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
        return EzacStorage::ezacIndex('vba_dagverslagen', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
