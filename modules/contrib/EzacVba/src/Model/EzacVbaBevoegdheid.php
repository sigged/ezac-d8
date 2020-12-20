<?php

namespace Drupal\EzacVba\Model;

use Drupal\Ezac\Model\EzacStorage;

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
    public function create()
    {

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
        if (isset($id)) $this->id = $id;
        return $this->ezacRead('vba_bevoegdheden');
    }

    static public function readAll($condition)
    {
      $condition = []; // select all records
      $bevoegdheden = EzacStorage::ezacReadAll('vba_bevoegdheden', $condition, __CLASS__);
      return $bevoegdheden;
    }
    /**
     * update - Updates record in the vba table
     *
     * @return int
     *   records_updated
     */
    public function update()
    {
        // build $condition
        return $this->ezacUpdate('vba_bevoegdheden');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete()
    {
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
    public static function counter($condition)
    {
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
    public static function index($condition = NULL, $field = 'id', $sortkey = 'datum', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE)
    {
        return EzacStorage::ezacIndex('vba_bevoegdheden', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
