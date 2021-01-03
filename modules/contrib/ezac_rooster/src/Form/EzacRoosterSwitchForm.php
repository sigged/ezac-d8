<?php


namespace Drupal\ezac_rooster\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_rooster\Model\EzacRooster;

class EzacRoosterSwitchForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId()
  {
    return 'ezac_rooster_switch_form';
  }

  /**
   * Create rooster entries
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param null $id
   *
   * @return array
   */
  function buildForm(array $form, FormStateInterface $form_state, int $id = null) {
    // @todo bouw om voor ruilen van dienst
    // dienst id moet worden geruild
    // zoek vervangende dienst van niet zelf
    // alleen diensten binnen kader of instructie groep ruilen
    // in settings definitie voor kader en instructie diensten (dienst in kaderDiensten | instructieDiensten)

    // initialize messenger
    $messenger = Drupal::messenger();

    // read settings
    $settings = Drupal::config('ezac_rooster.settings');
    //set up diensten
    $diensten = $settings->get('rooster.diensten');
    //ezac_rooster_diensten Id Dienst Omschrijving
    $diensten['-'] = '-'; //placeholder voor lege dienst
    $form['diensten'] = array(
      '#type' => 'value',
      '#value' => $diensten,
    );
    $instructieDiensten = $settings->get('rooster.instructie');
    $kaderDiensten = $settings->get('rooster.kader');

    //set up periode
    $periodes = $settings->get('rooster.periodes');
    //store header info for periodes reference in submit function
    $form['periodes'] = array(
      '#type' => 'value',
      '#value' => $periodes,
    );

    // selecteer vliegende leden
    $condition = [ // selecteer alle leden
      //'code' => 'VL',
      //'actief' => TRUE,
    ];
    $leden = EzacUtil::getLeden($condition);
    unset($leden['']); // remove 'Onbekend' lid
    // store leden_lijst in form for use in validate and submit function
    $form['leden'] = array(
      '#type' => 'value',
      '#value' => $leden,
    );

    //get current user details
    $user = $this->currentUser();
    $may_edit = $user->hasPermission('EZAC_edit');
    // read own leden record
    $user_name = $user->getAccountName();
    $condition = [
      'user' => $user_name,
    ];
    $lidId = EzacLid::index($condition);
    if (count($lidId) == 1) {
      $lid = new EzacLid($id);
      $zelf = $lid->afkorting;
      $messenger->addMessage("lid $lid->achternaam gevonden voor $user_name");
    }
    else {
      $zelf = ''; // geen lid gevonden
      $messenger->addMessage("geen lid gevonden voor $user_name");
    }

    // read dienst to be switched in rooster1
    $rooster1 = new EzacRooster($id);
    if (!isset($rooster1)) { // niet gevonden
      $messenger->addError("dienst $id is niet gevonden");
      return [];
    }
    // prepare dienstSoort for rooster select
    if (in_array($rooster1->dienst, $instructieDiensten))
      $dienstSoort = $instructieDiensten;
    else $dienstSoort = $kaderDiensten;

    // get year from rooster1
    $year = substr($rooster1->datum, 0, 4);

    // read rooster for datum or datum range and dienstSoort
    EzacUtil::checkDatum($year, $datumStart, $datumEnd);
    //@todo deze selectie lijkt niet te werken
    $condition = [
      'datum' => [
        'value' => [$datumStart, $datumEnd],
        'operator' => 'BETWEEN',
      ],
      'dienst' => [
        'value' => $dienstSoort, // check if dienst in dienstSoort
        'operator' => 'IN',
      ],
    ];

    // read index of rooster datum
    $roosterData = array_unique(EzacRooster::index($condition, 'datum'));
    if (!isset($roosterData)) {
      $messenger->addError("Geen diensten gevonden om mee te ruilen");
      return NULL; // no entries for datum
    }

    //toon tabel met datum en diensten per periode
    //prepare header
    $header = array(t('Datum'));
    // voeg een kolom per periode toe
    foreach ($periodes as $periode => $omschrijving) {
      array_push($header, t($omschrijving));
    }

    $caption = t("Rooster voor " .EzacUtil::showDate($year));
    //show table with dienst entry field for each periode record per naam

    //vul tabel alleen voor te ruilen diensten
    $form['table'] = array(
      // Theme this part of the form as a table.
      '#type' => 'table',
      '#header' => $header,
      '#caption' => $caption,
      '#sticky' => TRUE,
      '#weight' => 5,
      '#prefix' => '<div id="table-div">',
      '#suffix' => '</div>',
    );

    // build table rows
    foreach ($roosterData as $rooster_dag) {

      // prepare table row
      $form['table'][$rooster_dag] = [];
      $form['table'][$rooster_dag]['datum'] = [
        '#type' => 'markup',
        '#markup' => $rooster_dag, // @todo format datum
      ];

      // initialize periodes
      $dienstPeriodes = [];
      foreach ($periodes as $periode => $omschrijving) {
        $dienstPeriodes[$periode] = '';
      }

      // lees alle diensten voor rooster_dag
      $condition = [
        'datum' => $rooster_dag,
        'dienst' => [ // toon alleen ruilbare diensten
          'value' => $dienstSoort,
          'operator' => 'IN',
        ],
        'naam' => [ // toon niet de eigen diensten
          'value' => $zelf,
          'operator' => '!=',
        ]
      ];
      $roosterIndex = EzacRooster::index($condition);
      foreach ($roosterIndex as $roosterId) {
        // add dienst to table for datum
        $rooster = new EzacRooster($roosterId);
        $t = $diensten[$rooster->dienst] .':' .$leden[$rooster->naam] .'<br>';
        //@todo if edit access or own afkorting add link for switching
        $dienstPeriodes[$rooster->periode] .= $t;
      }

      // fill columns for diensten
      foreach ($periodes as $periode => $omschrijving) {
        if ($dienstPeriodes[$periode] != '') {
          $form['table'][$rooster_dag][$periode] = [
            '#type' => 'markup',
            '#markup' => t($dienstPeriodes[$periode]),
          ];
        }
        else {
          $form['table'][$rooster_dag][$periode] = [
            '#type' => 'markup',
            '#markup' => t(''),
          ];
        }
      }

    }
    // @todo implement submit button

    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Implement submitForm() method.
  }

/*
    // read rooster for datum or datum range
    EzacUtil::checkDatum($datum, $datumStart, $datumEnd);
    $condition = [
      'datum' => [
        'value' => [$datumStart, $datumEnd],
        'operator' => 'BETWEEN',
      ]
    ];
    // @todo add further selection criteria for instructie only display
    // read index of datum values
    $roosterData = array_unique(EzacRooster::index($condition, 'datum'));

    //if (!isset($roosterIndex)) return NULL; // no entries for datum

    //toon tabel met datum en diensten per periode
    //prepare header
    $header = array(t('Datum'));
    // voeg een kolom per periode toe
    foreach ($periodes as $periode => $omschrijving) {
      array_push($header, t($omschrijving));
    }

    $caption = t("Rooster voor " .EzacUtil::showDate($datum));
    //show table with dienst entry field for each periode record per naam

    //vul tabel alleen voor actieve diensten, met veld voor toevoegen nieuwe dienst (naam, periodes)
    $form['table'] = array(
      // Theme this part of the form as a table.
      '#type' => 'table',
      '#header' => $header,
      '#caption' => $caption,
      '#sticky' => TRUE,
      '#weight' => 5,
      '#prefix' => '<div id="table-div">',
      '#suffix' => '</div>',
    );

    // build table rows
    foreach ($roosterData as $rooster_dag) {

      // prepare table row
      $form['table'][$datum] = [];
      $form['table'][$datum]['datum'] = [
        '#type' => 'markup',
        '#markup' => $rooster_dag, // @todo format datum
      ];
      // intialize columns for diensten
      foreach ($periodes as $periode => $omschrijving) {
        $form['table'][$datum][$periode] = '';
      }

      // lees alle diensten voor rooster_dag
      $condition = [
        'datum' => $rooster_dag,
      ];
      $roosterIndex = EzacRooster::index($condition);
      foreach ($roosterIndex as $id) {
        // add dienst to table for datum
        $rooster = new EzacRooster($id);
        $t = $diensten[$rooster->dienst] .':' .$leden[$rooster->naam] .'<br>';
        //@todo if edit access or own afkorting add link for switching
        $form['table'][$datum][$rooster->periode] .= $t;
      }
    }

    // @TODO -- HIER VERDER **************

    // D7 code
    // printing HTML result
    // Table tag attributes

    //return theme('table', $header, $row, $attributes);
    $build = array(
      'content' => array(
        '#theme' => 'table',
        '#rows' => $row,
        '#header' => $header,
        '#attributes' => $attributes,
        '#empty' => 't(Geen gegevens beschikbaar)'
      ),
    );
    return $build;
  }

  /**
   * Called when user goes to example.com/?q=rooster/select
   * Selecteer van een dienst om te ruilen
   * de te ruilen dienst heeft Id = ednr
   */
  function ezacroo_select($ednr, $Owner) {

    //global $user;
    //$Owner = $user->name;

    //Vul $Zelf met Code van $Owner...
    //$query  = 'SELECT CONCAT_WS(" ",VOORNAAM,VOORVOEG,ACHTERNAAM) wNaam, ';
    $query  = 'SELECT ';
    $query .= 'AFKORTING Naam ';
    $query .= 'FROM {ezac_Leden} WHERE User = :Owner'; //"' . $Owner . '"';
    $result = db_query($query, array(':Owner'=>$Owner)); // or ("Query failed: <" . $query . ">");

    $line = $result->fetchAssoc(); // fetch as an associative array
    $Zelf = $line["Naam"];

    (isset($ednr)) or ("Geen te ruilen dienst opgegeven");
    drupal_set_message(t('Kies de dienst waarmee wordt geruild'));

    $query  = 'SELECT r.*, d.Omschrijving, ';
    $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
    $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
    $query .= 'WHERE r.Id = :ednr '; // . $ednr . ' ';
    $query .= 'AND r.Naam = w1.AFKORTING ';
    $query .= 'AND r.Dienst = d.Dienst ';

    $result = db_query($query, array(':ednr'=>$ednr)); // or ("Query failed: <" . $query . ">");
    $line = $result->fetchAssoc();

    $Dat1 = $line["Datum"];
    $Dat = explode(" ", $Dat1);
    $YYMMDD = explode("-", $Dat[0]);
    $Dat2 = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0];
    $Naam1 = $line["Naam"];

    $output  = '<h3>Ruil op ' . $Dat2 . ' in de ' . $line['Periode'] . '-periode de ';
    $output .= $line['Omschrijving'] . '-dienst van ' . $line['wNaam'] . '</h3>';

    $build['tekst'] = array(
      '#type' => 'markup',
      '#markup' => $output
    );
    $AlleDienst = FALSE; // toegevoegd om fout te voorkomen
    if (($Naam1 <> $Zelf) and ($AlleDienst <> 1)) {
      die("Niet toegestaan");
    }

    //Toon aanwezige diensten vanaf vandaag
    $today  = date("Y-m-d", mktime());
    $query  = 'SELECT r.*, d.Omschrijving, ';
    $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
    $query .= ', w1.TELEFOON '; // tbv zwevend helpveld boven dienst
    $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
    //$query .= 'WHERE Datum >= "' .$today .'" ';
    $query .= 'WHERE Datum <> :Datum '; // >= "' .$today .'" '; //VOOR TEST ALLE DATA GESELECTEERD
    $query .= 'AND r.Naam = w1.AFKORTING ';
    $query .= 'AND r.Dienst = d.Dienst ';
    if (isset($Instructie)) { // Toon alleen instructie diensten
      $query .= 'AND r.Dienst = "I" OR r.Dienst = "T" OR r.Dienst = "D" ';
    }
    if (isset($Overig)) { // Toon geen instructie diensten
      $query .= 'AND r.Dienst = "P" OR r.Dienst = "L" OR r.Dienst = "B" ';
    }
    $query .= 'ORDER by Datum, Periode, Dienst ';
    //$query .= 'LIMIT 0,30';

    $result = db_query($query, array(':Datum'=>'')); // or ("Query failed: -" . $query . "-");

    // printing HTML result
    // Table tag attributes
    $attributes = array(
      'border' => 1,
      'cellspacing' => 0,
      'cellpadding' => 5,
      //    'class' => 'example',
      'width' => '90%',
    );
    // Header line
    $header = array(
      array('data' => t('Datum')),
      array('data' => t('A')),
      array('data' => t('B')),
      array('data' => t('C')),
    );

    $ActDatum = ""; // Indicator voor overgang op nieuwe rij
    $ActPeriode = "A"; // Indicator voor overgang op nieuwe kolom
    $row_field = array(
      'A' => '',
      'B' => '',
      'C' => '',
    );

    while ($line = $result->fetchAssoc()) {
      $Dat1 = $line["Datum"];
      $Dat = explode(" ", $Dat1);
      $YYMMDD = explode("-", $Dat[0]);
      $Datum = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0];
      $Periode = $line["Periode"];
      $Naam = $line["Naam"]; // AFKORTING van lid
      $wNaam = $line["wNaam"]; //Volledige naam van lid
      $Id = $line["Id"]; //record nummer van rooster entry
      $Dienst = $line["Omschrijving"];

      if ($Datum <> $ActDatum) {
        if ($ActDatum <> "") { // einde vorige rij
          $row[] = array(
            $ActDatum,
            $row_field['A'],
            $row_field['B'],
            $row_field['C'],
          );
        } //if
        $row_field['A'] = '';
        $row_field['B'] = '';
        $row_field['C'] = '';
        // print "<tr><td>" .$Datum ."</td>\n\t"; // nieuwe rij
        $ActDatum = $Datum;
        $ActPeriode = "A";
        //print "<td>";
      }
      if ($Periode <> $ActPeriode) {
        //print "</td><td>"; // volgende kolom
        if ($ActPeriode == "A") {
          $ActPeriode = "B";
        }
        else if ($ActPeriode == "B") {
          $ActPeriode = "C";
        }
      }
      if ($Periode <> $ActPeriode) {
        //print "</td><td>"; // skip kolom
        if ($ActPeriode == "A") {
          $ActPeriode = "B";
        }
        else if ($ActPeriode == "B") {
          $ActPeriode = "C";
        }
      }
      $row_field[$Periode] .= $Dienst . ":";
      if (($Naam <> $Zelf) or isset($AlleDienst)) {
        $row_field[$Periode] .= '<a href="?q=rooster/confirm/' . $ednr . '/' . $Id . '">'; //use form to confirm change
      }
      $row_field[$Periode] .= $wNaam;
      if (($Naam <> $Zelf) or isset($AlleDienst)) {
        $row_field[$Periode] .= "</a>";
      }
      $row_field[$Periode] .= "<br>";

    } // while

    // Produce last line
    $row[] = array(
      $ActDatum,
      $row_field['A'],
      $row_field['B'],
      $row_field['C'],
    );

    $build['content'] = array(
      '#theme' => 'table',
      '#rows' => $row,
      '#header' => $header,
      '#attributes' => $attributes,
      '#empty' => 't(Geen gegevens beschikbaar)'
    );
    return $build;
  }

  /**
   * Called when user goes to example.com/?q=rooster/change
   * Ruilen van de diensten
   * params $ednr en $ednr2 verwijzen naar de record nummers van de te ruilen diensten
   * Deze worden omgeruild in de database waarbij de mutatie wordt vastgelegd
   */
  function ezacroo_change($ednr = 0, $ednr2 = 0) {

    if (isset($ednr)) {
      //    drupal_set_message(t('Te ruilen dienst'));

      $query  = 'SELECT r.*, d.Omschrijving, w1.E_mail, ';
      $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
      $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
      $query .= 'WHERE r.Id = :ednr '; // . $ednr . ' ';
      $query .= 'AND r.Naam = w1.AFKORTING ';
      $query .= 'AND r.Dienst = d.Dienst ';

      $result = db_query($query, array(':ednr' => $ednr)); // or ("Query failed: <" . $query . ">");
      $line = $result->fetchAssoc();

      $Dat1 = $line["Datum"];
      $Datum1 = $Dat1; // vasthouden 1e datum
      $Dat = explode(" ", $Dat1); //verwijderen tijd
      $YYMMDD = explode("-", $Dat[0]);
      $Dat2 = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0]; //omzetten naar dd-mm-jjjj

      $EersteDatum = $Dat2; // tbv roo_mail
      $EersteNaam  = $line["wNaam"];
      $Naam1       = $line["Naam"];
      $Periode1    = $line["Periode"];
      $Dienst1     = $line["Omschrijving"];
      $E_mail1	 = $line["E_mail"];

    } //if isset($ednr)

    if (isset($ednr2)) {
      $query  = 'SELECT r.*, d.Omschrijving, w1.E_mail, ';
      $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
      $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
      $query .= 'WHERE r.Id = :ednr2 '; //' . $ednr2 . ' ';
      $query .= 'AND r.Naam = w1.AFKORTING ';
      $query .= 'AND r.Dienst = d.Dienst ';

      $result = db_query($query, array(':ednr2' => $ednr2)); // or ("Query failed: <" . $query . ">");
      $line = $result->fetchAssoc();

      $Dat1 = $line["Datum"];
      $Datum2 = $Dat1; // vasthouden 2e datum
      $Dat = explode(" ", $Dat1);
      $YYMMDD = explode("-", $Dat[0]);
      $Dat2 = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0];

      $TweedeDatum = $Dat2; // tbv roo_mail
      $TweedeNaam  = $line["wNaam"];
      $Naam2       = $line["Naam"];
      $Periode2    = $line["Periode"];
      $Dienst2     = $line["Omschrijving"];
      $E_mail2	 = $line["E_mail"];
    } //if

    $Mutatie = date("Y-m-d H:i:s"); //Huidige datum en tijd

    //Plaats het 1e record in de database - TODO NOG TE WIJZIGEN IN DYNAMIC DB CALL
    $query  = 'UPDATE {ezac_Rooster} ';
    $query .= 'SET Naam = :Naam2, ';
    $query .= 'Geruild = :Naam1, ';
    $query .= 'Mutatie = :Mutatie ';
    $query .= 'WHERE Id = :ednr';
    $result = db_query($query,array(
      ':Naam2'    => $Naam2,
      ':Naam1' 	=> $Naam1,
      ':Mutatie' 	=> $Mutatie,
      ':ednr'    	=> $ednr
    ))
    or ("Update 1 failed <" . $result . ">:" . $query);

    //Plaats het 2e record in de database
    $query  = 'UPDATE {ezac_Rooster} ';
    $query .= 'SET Naam = :Naam1, ';
    $query .= 'Geruild = :Naam2, ';
    $query .= 'Mutatie = :Mutatie ';
    $query .= 'WHERE Id = :ednr2';
    $result = db_query($query,array(
      ':Naam1'    => $Naam1,
      ':Naam2' 	=> $Naam2,
      ':Mutatie' 	=> $Mutatie,
      ':ednr2'    => $ednr2
    ))

    or ("Update 2 failed <" . $result . ">:" . $query);

    //Verstuur mail berichten voor EZAC roosterwijzigingen
    //Database EZAC
    //Tables Rooster, Dienst, Periode
    //Records met Id = ednr en ednr2
    //User  $Zelf

    /* recipients */
    $recipient = $E_mail1 ."; " .$E_mail2;
    $recipient .= "; webmaster@ezac.nl"; //ter controle

    /* subject */
    $subject = "Wijziging EZAC Dienstrooster op " . $EersteDatum;
    if ($EersteDatum <> $TweedeDatum) {
      $subject .= " en " . $TweedeDatum;
    }

    /* message */
    $message  = "";
    $message .= "<H1>Geruild:</H1>\n";
    $message .= $EersteNaam  . " heeft de ";
    $message .= $Dienst1     . " dienst in de ";
    $message .= $Periode1    . " periode van ";
    $message .= $EersteDatum . " geruild met ";
    $message .= $TweedeNaam  . "'s ";
    $message .= $Dienst2     . " dienst in de ";
    $message .= $Periode2    . "  periode ";
    if ($Datum1 <> $Datum2) {
      $message .= "van " . $TweedeDatum . "<p>\n";
    }

    $message .= "<H1>Overzicht van de diensten op " . $EersteDatum;
    if ($EersteDatum <> $TweedeDatum) {
      $message .= " en " . $TweedeDatum;
    }
    $message .= "</H1>\n";
    $message .= "<TABLE border=1>\n";
    $message .= "<THEAD><b>";
    $message .= "<TR><TD>Datum</TD>\t";
    $message .=     "<TD>Periode</TD>\t";
    $message .=     "<TD>Dienst</TD>\t";
    $message .=     "<TD>Naam</TD>\t";
    $message .= "</TR></b></THEAD>\n";

    $query  = 'SELECT r.*, d.Omschrijving, ';
    $query .= "CONCAT_WS(' ',w.VOORNAAM,w.VOORVOEG,w.ACHTERNAAM) wNaam ";
    $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w, {ezac_Rooster_Diensten} d ';
    $query .= 'WHERE r.Naam = w.AFKORTING ';
    $query .= 'AND r.Dienst = d.Dienst ';
    $query .= 'AND (Datum = :Datum1'; //"' . $Datum1 . '"';
    if ($Datum1 <> $Datum2) {
      $query .= ' OR Datum = :Datum2'; // . $Datum2 . '"';
    }
    $query .= ') ORDER BY Datum, Periode, Omschrijving';
    $result = db_query($query, array(':Datum1'=>$Datum1,':Datum2'=>$Datum2)); // or ("Query failed:" . $query);


    while ($line = $result->fetchAssoc()) {
      $Dat1 = explode(" ", $line["Datum"]);
      $Dat  = explode("-", $Dat1[0]);
      $message .= "<TR><TD>" . $Dat[2] . "-" . $Dat[1] . "-" . $Dat[0] . "</TD>\t";
      $message .= "<TD>" . $line["Periode"] . "</TD>\t";
      $message .= "<TD>" . $line["Omschrijving"] . "</TD>\t";
      $message .= "<TD>" . $line["wNaam"] . "</TD>\t";
      $message .= "</TR>\n";
    }
    $message .= "</TABLE>\n";

    /* you can add a stock signature */
    $message .= "<p>--<br>\r\n"; //Signature delimiter
    $message .= "<i>EZAC dienstrooster systeem</i>";

    /* additional header pieces for errors, From cc's, bcc's, etc */

    $headers  = "From: webmaster@ezac.nl\n";
    $headers .= "X-Mailer: PHP\n"; // mailer
    $headers .= "Return-Path: <webmaster@ezac.nl>\n"; // Return path for errors

    /* If you want to send html mail, uncomment the following line */
    $headers .= "Content-Type: text/html; charset=iso-8859-1\n"; // Mime type

    //$headers .= "cc: birthdayarchive@php.net\n"; // CC to
    //$headers .= "bcc: birthdaycheck@php.net, birthdaygifts@php.net"; // BCCs to

    /* and now mail it */
    $print = "Mail wordt verzonden";
    $print .= "<p>To: " . $recipient . "<br>\n";
    $print .= "Subject: " . $subject . "<br>\n";
    $print .= "<p>" . $message . "<br>\n";
    //  $print .= "<p>Headers: " . $headers . "\n";

    //mail alleen als er ook recipients zijn...
    if (isset($recipient)) {
      mail($recipient, $subject, $message, $headers); //mail even uitgezet voor test DEBUG
    }

    return $print;
  }

  /**
   * Use a form to confirm the rooster change requested
   * called from ezample.com/?q=rooster/confirm
   * params $ednr and $ednr2 indicate the records to be switched
   **/
  function ezacroo_confirm($ednr = 0, $ednr2 = 0) {
    $output = drupal_get_form('ezacroo_confirm_form',$ednr, $ednr2);
    return $output;
  }

  /**
   * form to confirm the rooster change
   * params $ednr and $ednr2 indicate the records to be switched
   **/
  function ezacroo_confirm_form($form, &$form_state, $ednr, $ednr2) { //$form added

    if (isset($ednr)) {
      //    drupal_set_message(t('Te ruilen dienst'));

      $query  = 'SELECT r.*, d.Omschrijving, w1.E_mail, ';
      $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
      $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
      $query .= 'WHERE r.Id = :ednr '; // . $ednr . ' ';
      $query .= 'AND r.Naam = w1.AFKORTING ';
      $query .= 'AND r.Dienst = d.Dienst ';

      $result = db_query($query, array(':ednr' => $ednr));
      $line = $result->fetchAssoc();

      $Dat1 = $line["Datum"];
      $Datum1 = $Dat1; // vasthouden 1e datum
      $Dat = explode(" ", $Dat1); //verwijderen tijd
      $YYMMDD = explode("-", $Dat[0]);
      $Dat2 = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0]; //omzetten naar dd-mm-jjjj

      $EersteDatum = $Dat2; // tbv roo_mail
      $EersteNaam  = $line["wNaam"];
      $Naam1       = $line["Naam"];
      $Periode1    = $line["Periode"];
      $Dienst1     = $line["Omschrijving"];
      $E_mail1	 = $line["E_mail"];

    } //if isset($ednr)

    if (isset($ednr2)) {
      $query  = 'SELECT r.*, d.Omschrijving, w1.E_mail, ';
      $query .= "CONCAT_WS(' ',w1.VOORNAAM,w1.VOORVOEG,w1.ACHTERNAAM) wNaam ";
      $query .= 'FROM {ezac_Rooster} r, {ezac_Leden} w1, {ezac_Rooster_Diensten} d ';
      $query .= 'WHERE r.Id = :ednr2 '; //' . $ednr2 . ' ';
      $query .= 'AND r.Naam = w1.AFKORTING ';
      $query .= 'AND r.Dienst = d.Dienst ';

      $result = db_query($query, array(':ednr2' => $ednr2)); // or ("Query failed: <" . $query . ">");
      $line = $result->fetchAssoc();

      $Dat1 = $line["Datum"];
      $Datum2 = $Dat1; // vasthouden 2e datum
      $Dat = explode(" ", $Dat1);
      $YYMMDD = explode("-", $Dat[0]);
      $Dat2 = $YYMMDD[2] . '-' . $YYMMDD[1] . '-' . $YYMMDD[0];

      $TweedeDatum = $Dat2; // tbv roo_mail
      $TweedeNaam  = $line["wNaam"];
      $Naam2       = $line["Naam"];
      $Periode2    = $line["Periode"];
      $Dienst2     = $line["Omschrijving"];
      $E_mail2	 = $line["E_mail"];
      //  drupal_set_messsage(t('met ' .$TweedeDatum
      //                      .' in de ' .$Periode2
      //                      .'-periode de ' .$Dienst2 .'-dienst'
      //                      .' van ' .$TweedeNaam));
    } //if

    // Build the form
    $form['Naam1'] = array(
      '#title' => $EersteNaam . t(' ruilt op ')
        .$EersteDatum .t(' in de ')
        .$Periode1 .t(' periode de ')
        .$Dienst1 .t(' dienst met de'),
      '#type' => 'item');
    $form['ednr'] = array(
      '#type' => 'value',
      '#value' => $ednr); //store first record id
    $form['Naam2'] = array(
      '#title' => $Dienst2 .t(' dienst van ')
        .$TweedeNaam .t(' op ')
        .$TweedeDatum .t(' in de ')
        .$Periode2 .t(' periode.'),
      '#type' => 'item');
    $form['ednr2'] = array(
      '#type' => 'value',
      '#value' => $ednr2); //store second record id
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Ruil deze diensten'));

    return $form;

  } //ezacroo_confirm_form

  /**
   * validate the form
   * placeholder as there is nothing to validate
   **/
  function ezacroo_confirm_form_validate($form, &$form_state) {
    // nothing to validate, just confirm
  } //ezacroo_confirm_form_validate

  /**
   * Handle post-validation form submission
   **/
  function ezacroo_confirm_form_submit($form, &$form_state) {
    $ednr = $form_state['values']['ednr'];
    $ednr2 = $form_state['values']['ednr2'];
    $form_state['redirect'] = 'rooster/change/' .$ednr .'/' .$ednr2;
    //ezacroo_change($ednr, $ednr2); //execute the change
  } //ezacroo_confirm_form_submit

}