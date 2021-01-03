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
      $lid = new EzacLid($lidId);
      $zelf = $lid->afkorting;
    }
    else {
      $zelf = ''; // geen lid gevonden
      $messenger->addMessage("geen leden record gevonden voor gebruiker $user_name");
    }

    // read dienst to be switched in rooster1
    $rooster1 = new EzacRooster($id);
    if (!isset($rooster1)) { // niet gevonden
      $messenger->addError("dienst $id is niet gevonden");
      return [];
    }

    // bewaar te ruilen rooster id in form
    $form['ruilen_van'] = [
      '#type' => 'value',
      '#value' => $id,
    ];
    // placeholder voor te ruilen met id in form - gevuld in validatie
    $form['ruilen_met'] = [
      '#type' => 'value',
      '#value' => null,
    ];

    // prepare dienstSoort for rooster select
    if (in_array($rooster1->dienst, $instructieDiensten))
      $dienstSoort = $instructieDiensten;
    else $dienstSoort = $kaderDiensten;

    // get year from rooster1
    $year = substr($rooster1->datum, 0, 4);

    // read rooster for datum or datum range and dienstSoort
    EzacUtil::checkDatum($year, $datumStart, $datumEnd);
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

    $naam = $leden[$rooster1->naam];
    $d = $diensten[$rooster1->dienst];
    $dat = EzacUtil::showDate($rooster1->datum);
    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => "<H2>Ruil $naam's $d dienst op $dat in $rooster1->periode periode</H2>",
    ];
    $form['intro2'] = [
      '#type' => 'markup',
      '#markup' => "Selecteer hieronder de te ruilen dienst",
    ];

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
        $dienstPeriodes[$periode] = [];
      }

      // lees alle te ruilen diensten voor rooster_dag
      $condition = [
        'datum' => $rooster_dag,
        'dienst' => [ // toon alleen ruilbare diensten
          'value' => $dienstSoort,
          'operator' => 'IN',
        ],
        'naam' => [ // toon niet de eigen diensten
          'value' => $rooster1->naam,
          'operator' => '!=',
        ]
      ];
      $roosterIndex = EzacRooster::index($condition);
      foreach ($roosterIndex as $roosterId) {
        // add dienst to table for datum
        $rooster = new EzacRooster($roosterId);
        //opmaken dienst beschrijving voor tabel
        $t = $diensten[$rooster->dienst] .':' .$leden[$rooster->naam] .'<br>';
        // zet beschrijving in tabel met als index dienst id
        $dienstPeriodes[$rooster->periode][$rooster->id] = $t;
      }

      // fill columns for diensten
      foreach ($periodes as $periode => $omschrijving) {
        if ($dienstPeriodes[$periode] != []) {
          // er zijn te ruilen diensten in deze periode
          $options = [];
          foreach ($dienstPeriodes[$periode] as $roosterId => $t) {
            // plaats dienst beschrijving als radios item
            $options[$roosterId] = $t;
          }
          //dpm($options, 'options'); //debug
          $form['table'][$rooster_dag][$periode] = [
            '#type' => 'checkboxes',
            '#options' => $options,
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
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Ruilen'),
      '#weight' => 31
    ];

    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // TODO: Change the autogenerated stub
    // check op meer dan 1 geselecteerde checkbox
    $table = $form_state->getValue('table');
    //dpm($table, 'table'); //debug
    $periodes = $form_state->getValue('periodes');
    $checked = [];
    foreach ($table as $datum => $diensten) {
      foreach ($periodes as $periode => $omschrijving) {
        foreach ($table[$datum][$periode] as $dienst => $check) {
          if ($check != 0) {
            $checked[$check] = $check;
            $last_check = $check;
          }
        }
      }
    }
    switch (count($checked)) {
      case 0:
        // geen dienst geselecteerd
        $form_state->setError($table, 'Geen dienst geselecteerd');
        break;
      case 1:
        // 1 dienst geselecteerd: Ok
        $form_state->setValue('ruilen_met', $last_check);
        break;
      default:
        // meer dan 1 dienst geselecteerd
        $form_state->setError($table, "Meer dan 1 dienst geselecteerd");
    }
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Implement submitForm() method.
    // switch diensten
    $messenger = Drupal::messenger();
    $ruilen_van = $form_state->getValue('ruilen_van');
    $ruilen_met = $form_state->getValue('ruilen_met');
    $messenger->addMessage("Ruilen dienst $ruilen_van met dienst $ruilen_met");

    $diensten = $form_state->getValue('diensten');
    $leden = $form_state->getValue('leden');

    // ruil diensten
    $rooster1 = new EzacRooster($ruilen_van);
    $naam1 = $rooster1->naam;
    $lid1 = new EzacLid(EzacLid::getId($naam1));
    $datum1 = EzacUtil::showDate($rooster1->datum);
    $dienst1 = $diensten[$rooster1->dienst];

    $rooster2 = new EzacRooster($ruilen_met);
    $naam2 = $rooster2->naam;
    $lid2 = new EzacLid(EzacLid::getId($naam2));
    $datum2 = EzacUtil::showDate($rooster2->datum);
    $dienst2 = $diensten[$rooster2->dienst];

    // verwissel namen en update diensten
    $rooster1->naam = $naam2;
    $rooster1->mutatie = date('Y-m-d h:m:s');
    $rooster1->geruild = $naam1;
    $nr_updated = $rooster1->update();
    if ($nr_updated != 1) {
      // update 1 mislukt
      $messenger->addError("Ruilen van dienst $ruilen_van is niet gelukt");
      return;
    }
    $rooster2->naam = $naam1;
    $rooster2->mutatie = date('Y-m-d h:m:s');
    $rooster2->geruild = $naam2;
    $nr_updated = $rooster2->update();
    if ($nr_updated != 1) {
      // update 2 mislukt
      $messenger->addError("Ruilen met dienst $ruilen_met is niet gelukt");
      // draai wijziging in rooster1 terug
      $rooster1->naam = $naam1;
      $rooster1->geruild = '';
      $nr_updated = $rooster1->update();
    }

    $message = "De $dienst1 dienst van $leden[$naam1] in de $rooster1->periode periode op $datum1 ";
    $message .= "is geruild met ";
    $message = "de $dienst2 dienst van $leden[$naam2] in de $rooster2->periode periode op $datum2 ";
    $messenger->addMessage($message);

    // @todo mail bericht over ruil aan iedereen die op die dag een dienst heeft

    // redirect naar rooster overzicht
    //go back to rooster overzicht
    $redirect = Url::fromRoute(
      'ezac_rooster_overzicht_jaar',
      ['jaar' => substr($rooster1->datum, 0, 4)]
    );
    $form_state->setRedirectUrl($redirect);

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