<?php
$schema['kisten'] = array(
    'description' => 'Kisten',
    'fields' => array(
        'registratie' => array(
            'description' => 'Registratie',
            'type' => 'varchar',
            'length' => '7',
            'not null' => TRUE,
            'default' => '',
        ),
        'callsign' => array(
            'description' => 'Callsign',
            'type' => 'varchar',
            'length' => '5',
            'not null' => FALSE,
        ),
        'type' => array(
            'description' => 'Type',
            'type' => 'varchar',
            'length' => '9',
            'not null' => FALSE,
        ),
        'bouwjaar' => array(
            'description' => 'Bouwjaar',
            'type' => 'varchar',
            'length' => '4',
            'not null' => FALSE,
        ),
        'inzittenden' => array(
            'description' => 'Aantal inzittenden',
            'type' => 'int',
            'not null' => FALSE,
        ),
        'flarm' => array(
            'description' => 'flarm code 6 hex',
            'type' => 'varchar',
            'length' => '6',
            'not null' => TRUE,
        ),
        'adsb' => array(
            'description' => 'adsb code 6 hex',
            'type' => 'varchar',
            'length' => '6',
            'not null' => TRUE,
        ),
        'opmerking' => array(
            'description' => 'Opmerking',
            'type' => 'varchar',
            'length' => '30',
            'not null' => FALSE,
        ),
        'eigenaar' => array(
            'description' => 'Eigenaar',
            'type' => 'varchar',
            'length' => '20',
            'not null' => FALSE,
        ),
        'prive' => array(
            'description' => 'Prive',
            'type' => 'int',
            'size' => 'tiny',
            'not null' => FALSE,
        ),
        'actief' => array(
            'description' => 'Actief',
            'type' => 'int',
            'size' => 'tiny',
            'not null' => FALSE,
        ),
    ),
    'primary key' => array('registratie'),
);

