-- phpMyAdmin SQL Dump
-- version 5.0.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Gegenereerd op: 26 mrt 2021 om 14:56
-- Serverversie: 10.3.25-MariaDB-0ubuntu0.20.04.1
-- PHP-versie: 7.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `EZAC`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `kisten`
--

CREATE TABLE `kisten` (
  `id` int(11) NOT NULL,
  `registratie` varchar(7) NOT NULL DEFAULT '',
  `callsign` varchar(5) DEFAULT NULL,
  `type` varchar(9) DEFAULT NULL,
  `bouwjaar` varchar(4) DEFAULT NULL,
  `inzittenden` int(1) DEFAULT NULL,
  `flarm` varchar(6) NOT NULL COMMENT 'flarm code 6 hex',
  `adsb` varchar(6) NOT NULL COMMENT 'adsb code 6 hex',
  `opmerking` varchar(30) DEFAULT NULL,
  `eigenaar` varchar(20) DEFAULT NULL,
  `prive` tinyint(1) DEFAULT NULL,
  `actief` tinyint(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `leden`
--

CREATE TABLE `leden` (
  `id` int(11) NOT NULL,
  `voorvoeg` varchar(11) DEFAULT NULL,
  `achternaam` varchar(35) DEFAULT NULL,
  `afkorting` varchar(9) DEFAULT NULL,
  `voornaam` varchar(13) DEFAULT NULL,
  `voorletter` varchar(21) DEFAULT NULL,
  `adres` varchar(26) DEFAULT NULL,
  `postcode` varchar(9) DEFAULT NULL,
  `plaats` varchar(24) DEFAULT NULL,
  `telefoon` varchar(14) DEFAULT NULL,
  `mobiel` varchar(20) DEFAULT NULL,
  `land` varchar(10) CHARACTER SET utf8 DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL,
  `tienrittenkaart` tinyint(1) DEFAULT NULL,
  `geboorteda` date DEFAULT NULL,
  `opmerking` varchar(27) DEFAULT NULL,
  `instructeu` varchar(9) DEFAULT NULL,
  `actief` tinyint(1) DEFAULT NULL,
  `lid_eind` date DEFAULT NULL,
  `lid_van` date DEFAULT NULL,
  `rtlicense` tinyint(1) DEFAULT NULL,
  `leerling` int(1) DEFAULT 0,
  `instructie` tinyint(1) DEFAULT NULL,
  `bevoegdheid` smallint(6) DEFAULT NULL,
  `e_mail` varchar(50) DEFAULT NULL,
  `camping` tinyint(1) DEFAULT NULL,
  `tourcaravan` tinyint(1) DEFAULT NULL,
  `stacaravan` tinyint(1) DEFAULT NULL,
  `winterstallingcaravan` tinyint(1) DEFAULT NULL,
  `winterstallingkist` tinyint(1) DEFAULT NULL,
  `zomerstallingkist` tinyint(1) DEFAULT NULL,
  `babyvriend` tinyint(1) DEFAULT NULL,
  `ledenlijstje` tinyint(1) DEFAULT NULL,
  `etiketje` tinyint(1) DEFAULT NULL,
  `specafk` tinyint(1) DEFAULT NULL,
  `user` varchar(8) DEFAULT NULL,
  `seniorlid` tinyint(1) DEFAULT NULL,
  `jeugdlid` varchar(1) DEFAULT NULL,
  `peonderhoud` tinyint(1) DEFAULT NULL,
  `slotcode` varchar(8) DEFAULT NULL,
  `mutatie` timestamp NULL DEFAULT current_timestamp(),
  `wijzigingsoort` text DEFAULT NULL,
  `lastaccess` timestamp NULL DEFAULT NULL,
  `kenezacvan` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `passagiers`
--

CREATE TABLE `passagiers` (
  `id` int(4) DEFAULT NULL,
  `datum` varchar(10) DEFAULT NULL,
  `tijd` varchar(8) DEFAULT NULL,
  `naam` varchar(28) DEFAULT NULL,
  `telefoon` varchar(18) DEFAULT NULL,
  `mail` varchar(33) DEFAULT NULL,
  `aangemaakt` varchar(19) DEFAULT NULL,
  `aanmaker` varchar(7) DEFAULT NULL,
  `soort` varchar(9) DEFAULT NULL,
  `status` varchar(9) DEFAULT NULL,
  `gevonden` varchar(30) DEFAULT NULL,
  `mail_list` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `passagiers_dagen`
--

CREATE TABLE `passagiers_dagen` (
  `id` int(2) DEFAULT NULL,
  `datum` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `reserveringen`
--

CREATE TABLE `reserveringen` (
  `id` int(4) NOT NULL,
  `datum` date DEFAULT NULL,
  `periode` varchar(1) DEFAULT NULL,
  `soort` varchar(19) DEFAULT NULL,
  `leden_id` int(4) DEFAULT NULL,
  `doel` varchar(20) DEFAULT NULL,
  `aangemaakt` varchar(19) DEFAULT NULL,
  `reserve` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `rooster`
--

CREATE TABLE `rooster` (
  `id` double NOT NULL,
  `datum` date DEFAULT NULL,
  `periode` varchar(255) DEFAULT NULL,
  `dienst` varchar(255) DEFAULT NULL,
  `naam` varchar(255) DEFAULT NULL,
  `mutatie` varchar(40) DEFAULT NULL,
  `geruild` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Dienstenrooster EZAC';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `starts`
--

CREATE TABLE `starts` (
  `id` int(11) NOT NULL,
  `datum` date DEFAULT NULL,
  `registratie` varchar(10) DEFAULT NULL,
  `gezagvoerder` varchar(20) DEFAULT NULL,
  `tweede` varchar(20) DEFAULT NULL,
  `soort` varchar(4) DEFAULT NULL,
  `startmethode` text DEFAULT NULL,
  `start` time DEFAULT NULL,
  `landing` time DEFAULT NULL,
  `duur` time DEFAULT NULL,
  `instructie` tinyint(1) DEFAULT NULL,
  `opmerking` varchar(30) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='EZAC startlijst';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `vba_bevoegdheden`
--

CREATE TABLE `vba_bevoegdheden` (
  `id` int(11) NOT NULL,
  `afkorting` varchar(20) NOT NULL,
  `bevoegdheid` varchar(20) NOT NULL,
  `onderdeel` varchar(30) DEFAULT NULL,
  `datum_aan` date NOT NULL,
  `datum_uit` date DEFAULT NULL,
  `actief` tinyint(1) NOT NULL,
  `instructeur` varchar(20) NOT NULL,
  `opmerking` varchar(30) DEFAULT NULL,
  `mutatie` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='VBA bevoegdheden per lid';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `vba_dagverslagen`
--

CREATE TABLE `vba_dagverslagen` (
  `id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `instructeur` text NOT NULL,
  `weer` longtext NOT NULL,
  `verslag` longtext NOT NULL,
  `mutatie` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='VBA verslagen per dag';

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `vba_dagverslagen_lid`
--

CREATE TABLE `vba_dagverslagen_lid` (
  `id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `afkorting` varchar(20) NOT NULL,
  `instructeur` varchar(20) NOT NULL,
  `verslag` longtext NOT NULL,
  `mutatie` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='VBA verslagen per lid';

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `kisten`
--
ALTER TABLE `kisten`
  ADD PRIMARY KEY (`registratie`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexen voor tabel `leden`
--
ALTER TABLE `leden`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Id` (`id`);

--
-- Indexen voor tabel `reserveringen`
--
ALTER TABLE `reserveringen`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_2` (`id`);

--
-- Indexen voor tabel `rooster`
--
ALTER TABLE `rooster`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Id` (`id`);

--
-- Indexen voor tabel `starts`
--
ALTER TABLE `starts`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `vba_bevoegdheden`
--
ALTER TABLE `vba_bevoegdheden`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `vba_dagverslagen`
--
ALTER TABLE `vba_dagverslagen`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `vba_dagverslagen_lid`
--
ALTER TABLE `vba_dagverslagen_lid`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `kisten`
--
ALTER TABLE `kisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `leden`
--
ALTER TABLE `leden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `reserveringen`
--
ALTER TABLE `reserveringen`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `rooster`
--
ALTER TABLE `rooster`
  MODIFY `id` double NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `starts`
--
ALTER TABLE `starts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `vba_bevoegdheden`
--
ALTER TABLE `vba_bevoegdheden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `vba_dagverslagen`
--
ALTER TABLE `vba_dagverslagen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `vba_dagverslagen_lid`
--
ALTER TABLE `vba_dagverslagen_lid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
