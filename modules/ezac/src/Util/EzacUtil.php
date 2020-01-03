<?php


namespace Drupal\ezac\Util;

use Drupal\ezacKisten\Model\EzacKist;
use Drupal\ezacLeden\Model\EzacLid;

class EzacUtil
{
    /**
     * @file
     * adds a field to a form
     * @param array $form
     * @param string $label
     * @param string $type
     * @param string $title
     * @param string $description
     * @param $default_value
     * @param integer $maxlength
     * @param integer $size
     * @param boolean $required
     * @param integer $weight
     * @param array $options
     * @return array
     */
    public static function addField(array $form,
                                    string $label,
                                    string $type,
                                    string $title,
                                    string $description,
                                    $default_value,
                                    int $maxlength,
                                    int $size,
                                    bool $required,
                                    int $weight,
                                    array $options = null)
    {
        if (isset($type)) $form[$label]['#type'] = $type;
        if (isset($title)) $form[$label]['#title'] = $title;
        if (isset($description)) $form[$label]['#description'] = $description;
        if (isset($default_value)) $form[$label]['#default_value'] = $default_value;
        if (isset($maxlength)) $form[$label]['#maxlength'] = $maxlength;
        if (isset($size)) $form[$label]['#size'] = $size;
        if (isset($required)) $form[$label]['#required'] = $required;
        if (isset($weight)) $form[$label]['#weight'] = $weight;
        if (isset($options)) $form[$label]['#options'] = $options;
        return $form;
    }

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