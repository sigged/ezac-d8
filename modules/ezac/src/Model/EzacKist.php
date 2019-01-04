<?php

namespace Drupal\ezac\Model;

//use Drupal\ezac\Model\EzacStorage;

/**
 * @file ezac/EzacKist.php
 * The EZAC class definitions for the kisten table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the DloLid class
 */
class EzacKist extends EzacStorage
{

    //Define Leden fields
    public static $fields = array(
        'id' => 'Record ID (uniek, auto_increment)',
        'registratie' => 'Registratie',
        'callsign' => 'Callsign',
        'type' => 'Type',
        'bouwjaar' => 'Bouwjaar',
        'inzittenden' => 'Aantal inzittenden',
        'flarm' => 'Flarm adres',
        'adsb' => 'ADSB adres',
        'eigenaar' => 'Eigenaar',
        'prive' => 'Prive',
        'opmerking' => 'Opmerking',
    );

    // define the fields for the leden table
    public $id = 0;
    public $registratie = '';
    public $callsign = '';
    public $type = '';
    public $bouwjaar = '';
    public $inzittenden = 1;
    public $flarm = '';
    public $adsb = '';
    public $eigenaar = '';
    public $prive = 0;
    public $opmerking = '';

    /**
     * constructor for EzacLid
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            return $this->ezacRead('kisten', __CLASS__);
        }
        return $this;
    }

    /**
     * create - Create leden record
     *
     * @return EzacKist ID of record created
     *   ID of record created
     */
    public function create()
    {

        $this->id = $this->ezacCreate('kisten');
        return $this;
    }

    /**
     * read - Reads record from the leden table
     *
     * @param int id
     * @return object DloLid
     */
    public function read($id = NULL)
    {
        if (isset($id)) $this->id = $id;
        return $this->ezacRead('kisten');
    }

    /**
     * update - Updates record in the leden table
     *
     * @return int
     *   records_updated
     */
    public function update()
    {
        // build $condition
        return $this->ezacUpdate('kisten');
    }

    /**
     * delete - Deletes records from the leden table
     *
     * @return int
     *   records_deleted
     */
    public function delete()
    {
        return $this->ezacDelete('kisten');
    }

    /***
     * counter - Counts the number of leden records
     *
     * @param array
     *   $condition
     * @return int
     *   number of records
     */
    public static function counter($condition)
    {
        return EzacStorage::ezacCount("kisten", $condition);
    }

    /***
     * index - Gets the index of leden records
     *
     * @param array
     *   $condition for select
     * @param string
     *  $field to be returned as index
     * @param string $sortkey
     * @param string $sortdir
     * @param $from
     * @param $range
     * @return array of id values
     */
    public static function index($condition = NULL, $field = 'id', $sortkey = 'registratie', $sortdir = 'ASC', $from = NULL, $range = NULL)
    {
        return EzacStorage::ezacIndex('kisten', $condition, $field, $sortkey, $sortdir, $from, $range);
    }

    /***
     * getId - search id for record with afkorting
     * @param string registratie
     * @return int id
     */
    public static function getId($registratie)
    {
        //find id for lid
        $index = self::index(['registratie' => $registratie], 'id');
        if (isset($index[0])) { // record found
            return $index[0];
        } else return NULL;
    }
}
  
