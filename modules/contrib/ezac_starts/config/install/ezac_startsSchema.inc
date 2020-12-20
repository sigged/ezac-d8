<?php
$schema['starts'] = array(
    'description' => 'Starts tabel',
    'fields' => array(
        'id' => array(
            'description' => 'unique id',
            'type' => 'serial',
            'not null' => TRUE,
            'autoincrement' => TRUE,
        ),
        'datum' => array(
            'description' => 'datum',
            'type' => 'date',
            'not null' => TRUE,
        ),
        'registratie' => array(
            'description' => 'registratie',
            'type' => 'varchar',
            'length' => '10',
            'not null' => FALSE,
        ),
        'gezagvoerder' => array(
            'description' => 'gezagvoerder',
            'type' => 'varchar',
            'length' => '20',
            'not null' => FALSE,
        ),
        'tweede' => array(
            'description' => 'tweede',
            'type' => 'varchar',
            'length' => '20',
            'not null' => FALSE,
        ),
        'soort' => array(
            'description' => 'soort start',
            'type' => 'varchar',
            'length' => '4',
            'not null' => FALSE,
        ),
        'startmethode' => array(
            'description' => 'startmethode',
            'type' => 'text',
            'not null' => FALSE,
        ),
        'start' => array(
            'description' => 'start tijd',
            'type' => 'time',
            'not null' => FALSE,
        ),
        'landing' => array(
            'description' => 'landing tijd',
            'type' => 'time',
            'not null' => FALSE,
        ),
        'duur' => array(
            'description' => 'vluchtduur',
            'type' => 'time',
            'not null' => FALSE,
        ),
        'instructie' => array(
            'description' => 'instructie vlucht',
            'type' => 'int',
            'size' => 'tiny',
            'not null' => FALSE,
        ),
        'opmerking' => array(
            'description' => 'opmerking',
            'type' => 'varchar',
            'length' => '30',
            'not null' => FALSE,
        ),
   ),
    'primary key' => array('id'),
    'unique keys' => array(
        'Id' => array('id')
    ),
);
