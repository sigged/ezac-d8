<?php
namespace Drupal\ezac\Util;

use Drupal\ezacLeden\Model\EzacLid;

class getLeden
{
    /**
     * @file
     * return table with leden names
     * @param array $condition
     * @return array
     */
    public static function getLeden(array $condition = [])
    {
        if ($condition == []) {
            $condition = [
                'actief' => TRUE,
                'code' => 'VL',
            ];
        }
        $ledenIndex = EzacLid::index($condition,'id','achternaam');
        $leden = [];
        $leden[''] = "Onbekend";
        foreach ($ledenIndex as $id) {
            $lid = (new EzacLid)->read($id);
            $leden[$lid->afkorting] = "$lid->voornaam $lid->voorvoeg $lid->achternaam";
        }
        return $leden;
    }
}