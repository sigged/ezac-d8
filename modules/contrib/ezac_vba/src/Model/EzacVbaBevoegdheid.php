<?php

namespace Drupal\ezac_vba\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacVbaBevoegdheid.php
 * The EZAC class definitions for the vba bevoegdheden table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the EzacVbabevoegdheid class
 */
class EzacVbaBevoegdheid extends EzacStorage
{
    // Define vba bevoegdheid status values
    public static $bevoegdheidStatus = array(
      '0' => 'Diverse',
      '1' => 'Leerling',
      '2' => 'Solist',
      '3' => 'Zweefvliegbewijs',
      '4' => 'Instructeur',
      '5' => 'Examinator'
    );

    //Define vba bevoegdheid fields
    public static $fields = array(
        'id' => 'Record ID (uniek, auto_increment)',
        'bevoegdheid' => 'Bevoegdheid',
        'naam' => 'Naam',
        'status' => 'Status',
        'instructeur' => 'Instructeur',
        'mutatie' => 'Mutatie',
    );

    // define the fields for the vba bevoegdheid table
    public $id = 0;
    public $bevoegdheid = '';
    public $naam = '';
    public $status = '';
    public $instructeur = '';
    public $mutatie = '';

    /**
     * constructor for vba_bevoegdheid
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            return $this->ezacRead('vba_bevoegdheden', __CLASS__);
        }
        return $this;
    }

    /**
     * create - Create vba record
     *
     * @return EzacVbaBevoegdheid ID of record created
     *   ID of record created
     */
    public function create(): EzacVbaBevoegdheid {

        $this->id = $this->ezacCreate('vba_bevoegdheden');
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
        $record = $this->ezacRead('vba_bevoegdheden', get_class($this));
        if (is_object($record)) {
          // cast in EzacVbaBevoegdheid object
          $VbaBevoegdheid = new EzacVbaBevoegdheid;
          $vars = get_object_vars($record);
          foreach ($vars as $var => $value) {
            $VbaBevoegdheid->$var = $value;
          }
          return $VbaBevoegdheid;
        }
        else {
          return $record;
        }
      }
      else return null;
    }

    static public function readAll($condition)
      //@TODO this function is used nowhere? - to be discarded also in EzacStorage
    {
      $condition = []; // select all records
      return EzacStorage::ezacReadAll('vba_bevoegdheden', $condition, __CLASS__);
    }
    /**
     * update - Updates record in the vba table
     *
     * @return int
     *   records_updated
     */
    public function update(): int {
        // build $condition
        return $this->ezacUpdate('vba_bevoegdheden');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): int {
        return $this->ezacDelete('vba_bevoegdheden');
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
      return EzacStorage::ezacCount('vba_bevoegdheden', $condition);
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
    public static function index($condition = NULL, $field = 'id', $sortkey = 'bevoegdheid', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE): array {
        return EzacStorage::ezacIndex('vba_bevoegdheden', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
