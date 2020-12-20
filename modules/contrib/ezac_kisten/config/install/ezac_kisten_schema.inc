<?php
$schema['kisten'] = array(
    'description' => 'Kisten',
    'fields' => array(
        'id' => array(
            'description' => 'unique id',
            'type' => 'serial',
            'not null' => TRUE,
            'autoincrement' => TRUE,
        ),
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
    'primary key' => array('id'),
);

/*
From MySQL
CREATE TABLE IF NOT EXISTS `Ezac`.`kisten` (
`id` INT(11) NOT NULL AUTO_INCREMENT,
  `registratie` TEXT NULL DEFAULT NULL,
  `callsign` TEXT NULL DEFAULT NULL,
  `type` TEXT NULL DEFAULT NULL,
  `bouwjaar` TEXT NULL DEFAULT NULL,
  `inzittenden` INT(11) NULL DEFAULT NULL,
  `flarm` TEXT NULL DEFAULT NULL,
  `adsb` TEXT NULL DEFAULT NULL,
  `opmerking` TEXT NULL DEFAULT NULL,
  `eigenaar` TEXT NULL DEFAULT NULL,
  `prive` INT(11) NULL DEFAULT NULL,
  `actief` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 40
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci
*/
