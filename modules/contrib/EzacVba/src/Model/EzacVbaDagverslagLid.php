<?php

namespace Drupal\ezacVba\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacDagverslagenLid.php
 * The EZAC class definitions for the vba dagverslagen table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the ezacVbaDagverslagLid class
 */
class ezacVbaDagverslagLid extends EzacStorage
{

    //Define vba dagverslagen lid fields
    public static $fields = array(
      'id' => 'Record ID (uniek, auto_increment)',
      'datum' => 'Datum',
      'afkorting' => 'Afkorting',
      'instructeur' => 'Instructeur',
      'verslag' => 'Verslag',
      'mutatie' => 'Mutatie'
    );

    // define the fields for the vba bevoegdheden status table
    public $id = 0;
    public $datum = '';
    public $afkorting = '';
    public $instructeur = '';
    public $verslag = '';
    public $mutatie = 0;

    /**
     * constructor for vba_bevoegdheden
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            return $this->ezacRead('vba_dagverslagen_lid', __CLASS__);
        }
        return $this;
    }

    /**
     * create - Create vba record
     *
     * @return \Drupal\ezacvba\Model\EzacVbaDagverslagen
     *   ID of record created
     */
    public function create()
    {

        $this->id = $this->ezacCreate('vba_dagverslagen_lid');
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
        return $this->ezacRead('vba_dagverslagen_lid');
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
        return $this->ezacUpdate('vba_dagverslagen_lid');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete()
    {
        return $this->ezacDelete('vba_dagverslagen_lid');
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
      return EzacStorage::ezacCount('vba_dagverslagen_lid', $condition);
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
        return EzacStorage::ezacIndex('vba_dagverslagen_lid', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
