<?php

namespace Drupal\ezacLeden\Model;

use Drupal\ezac\Model\EzacStorage;

/**
 * @file ezac/EzacLid.php
 * The EZAC class definitions for the leden table
 *
 * @author Evert Fekkes evert@efekkes.nl
 */

/**
 * Provides the implementation of the DloLid class
 */
class EzacLid extends EzacStorage
{

    // Define Leden categories (kat field)
    public static $lidCode = array(
        'AL' => 'Administratief Lid',
        'VL' => 'Vliegend Lid',
        'DO' => 'Donateur',
        'DB' => 'Donateur Bedrijf',
        'CL' => 'Clubblad ontvanger',
        'OL' => 'Oud Lid',
        'BF' => 'Baby vriend',
    );

    //Define Leden fields
    public static $fields = array(
        'id' => 'Record ID (uniek, auto_increment)',
        'afkorting' => 'Afkorting',
        'voorvoeg' => 'voorvoeg',
        'achternaam' => 'Achternaam',
        'voornaam' => 'Voornaam',
        'voorletter' => 'Voorletters',
        'adres' => 'Adres',
        'postcode' => 'Postode',
        'plaats' => 'Plaats',
        'telefoon' => 'Telefoon',
        'mobiel' => 'Mobiel nummer',
        'land' => 'Land',
        'code' => 'Code lidmaatschap',
        'tienrittenkaart' => 'Opmerking 2',
        'geboorteda' => 'Geboortedatum',
        'opmerking' => 'Opmerking',
        'actief' => 'Actief lid',
        'lid_eind' => 'Einde lidmaatschap',
        'lid_van' => 'Start lidmaatschap',
        'rtlicense' => 'RT licentie',
        'leerling' => 'Leerling vlieger',
        'instructie' => 'Instructie indicator',
        'e_mail' => 'E-mail adres',
        'babyvriend' => 'Vriend van Nico Baby',
        'ledenlijstje' => 'Vermelding op ledenlijst',
        'etiketje' => 'Afdrukken etiket',
        'user' => 'Inlogcode website',
        'seniorlid' => 'Senior lidmaatschap',
        'jeugdlid' => 'Jeugd lidmaatschap',
        'peonderhoud' => 'Prive Eigenaar Onderhoud',
        'slotcode' => 'Code toegangsdeur',
        'mutatie' => 'Mutatie datum',
        'wijzigingsoort' => 'Wijziging',
        'lastaccess' => 'Laatst gewijzigd',
        'kenezacvan' => 'Ken EZAC van',
    );

    // define the fields for the leden table
    public $id = 0;
    public $afkorting = '';
    public $voorvoeg = '';
    public $achternaam = '';
    public $voornaam = '';
    public $voorletter = '';
    public $adres = '';
    public $postcode = '';
    public $plaats = '';
    public $telefoon = '';
    public $mobiel = '';
    public $land = '';
    public $code = '';
    public $tienrittenkaart = FALSE;
    public $geboorteda = '';
    public $opmerking = '';
    public $actief = TRUE;
    public $lid_eind = '';
    public $lid_van = '';
    public $rtlicense = FALSE;
    public $leerling = FALSE;
    public $instructie = FALSE;
    public $e_mail = '';
    public $babyvriend = FALSE;
    public $ledenlijstje = TRUE;
    public $etiketje = TRUE;
    public $user = '';
    public $seniorlid = FALSE;
    public $jeugdlid = FALSE;
    public $peonderhoud = FALSE;
    public $slotcode = '';
    public $mutatie = '';
    public $wijzigingsoort = '';
    public $lastaccess = '';
    public $kenezacvan = '';


    /**
     * constructor for EzacLid
     * @param null $id
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            return $this->ezacRead('leden', __CLASS__);
        }
        return $this;
    }

    /**
     * create - Create leden record
     *
     * @return EzacLid ID of record created
     *   ID of record created
     */
    public function create()
    {

        $this->id = $this->ezacCreate('leden');
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
        return $this->ezacRead('leden');
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
        return $this->ezacUpdate('leden');
    }

    /**
     * delete - Deletes records from the leden table
     *
     * @return int
     *   records_deleted
     */
    public function delete()
    {
        return $this->ezacDelete('leden');
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
        return EzacStorage::ezacCount("leden", $condition);
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
    public static function index($condition = NULL, $field = 'id', $sortkey = 'afkorting', $sortdir = 'ASC', $from = NULL, $range = NULL)
    {
        return EzacStorage::ezacIndex('leden', $condition, $field, $sortkey, $sortdir, $from, $range);
    }

    /***
     * getId - search id for record with afkorting
     * @param string afkorting
     * @return int id
     */
    public static function getId($afkorting)
    {
        //find id for lid
        $index = self::index(['afkorting' => $afkorting], 'id');
        if (isset($index[0])) { // record found
            return $index[0];
        } else return NULL;
    }
}
  
