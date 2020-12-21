<?php

namespace Drupal\ezac_vba\Model;

use Drupal\Ezac\Model\EzacStorage;

/**
 * @file Ezac/EzacBevoegdheidLid.php
 * The EZAC class definitions for the vba bevoegdheden status table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the EzacVbaBevoegdheden class
 */
class EzacVbaBevoegdheidLid extends EzacStorage
{
    //Define vba bevoegdheden lid fields
    public static $fields = array(
      'id' => 'Record ID (uniek, auto_increment)',
      'afkorting' => 'Afkorting',
      'bevoegdheid' => 'Bevoegdheid',
      'onderdeel' => 'Onderdeel',
      'datum_aan' => 'Datum vanaf',
      'datum_uit' => 'Datum tot',
      'actief' => 'Actief',
      'instructeur' => 'Instructeur',
      'opmerking' => 'Opmerking',
      'mutatie' => 'Mutatie',
    );

    // define the fields for the vba bevoegdheden lid table
    public $id = 0;
    public $afkorting = '';
    public $onderdeel = '';
    public $bevoegdheid = '';
    public $datum_aan = '';
    public $datum_uit = '';
    public $actief = 0;
    public $instructeur = '';
    public $opmerking = '';
    public $mutatie = 0;

    /**
     * constructor for vba_bevoegdheden
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            return $this->ezacRead('vba_bevoegdheid_lid', __CLASS__);
        }
        return $this;
    }

    /**
     * create - Create vba record
     *
     * @return \Drupal\ezac_vba\Model\EzacVbaBevoegdheidLid
     *   ID of record created
     */
    public function create(): EzacVbaBevoegdheidLid {

        $this->id = $this->ezacCreate('vba_bevoegdheid_lid');
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
        return $this->ezacRead('vba_bevoegdheid_lid');
    }

    /**
     * update - Updates record in the vba table
     *
     * @return int
     *   records_updated
     */
    public function update(): int {
        // build $condition
        return $this->ezacUpdate('vba_bevoegdheid_lid');
    }

    /**
     * delete - Deletes records from the vba table
     *
     * @return int
     *   records_deleted
     */
    public function delete(): int {
        return $this->ezacDelete('vba_bevoegdheid_lid');
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
      return EzacStorage::ezacCount('vba_bevoegdheid_lid', $condition);
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
    public static function index($condition = NULL, $field = 'id', $sortkey = 'datum_aan', $sortdir = 'ASC', $from = NULL, $range = NULL, $unique = FALSE): array {
        return EzacStorage::ezacIndex('vba_bevoegdheid_lid', $condition, $field, $sortkey, $sortdir, $from, $range, $unique);
    }

}
  
