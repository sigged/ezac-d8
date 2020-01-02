<?php
namespace Drupal\ezac\Util;

use Drupal\ezacKisten\Model\EzacKist;

class getKisten
{
    /**
     * @file
     * return table with kisten names
     * @param array $condition
     * @return array
     */
    public static function getKisten(array $condition = [])
    {
        if ($condition == []) {
            $condition = [
                'actief' => TRUE,
            ];
        }
        $kistenIndex = EzacKist::index($condition,'id','registratie');
        $kisten = [];
        $kisten[''] = "Onbekend";
        foreach ($kistenIndex as $id) {
            $kist = (new EzacKist)->read($id);
            $kisten[$kist->registratie] = "$kist->registratie $kist->callsign ($kist->inzittenden)";
        }
        return $kisten;
    }
}