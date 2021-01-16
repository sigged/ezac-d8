<?php

namespace Drupal\ezac_vba\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ezac\Util\EzacUtil;
use Drupal\ezac_leden\Model\EzacLid;
use Drupal\ezac_starts\Model\EzacStart;
use Drupal\ezac_vba\Model\EzacVbaBevoegdheid;
use Drupal\ezac_vba\Model\EzacVbaDagverslag;
use Drupal\ezac_vba\Model\EzacVbaDagverslagLid;

/**
 * UI to show status of VBA records
 */


class EzacVbaVerslagForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId(): string {
    return 'ezac_vba_verslag_form';
  }

  /**
   * Build dagrapport form.
   *
   * Voortgang en Bevoegdheid Administratie
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // prepare message area
    $messenger = Drupal::messenger();

    // 1. prepare form data elements
    // read settings
    $settings = Drupal::config('ezac_vba.settings');
    //set up bevoegdheden
    $bevoegdheden = $settings->get('vba.bevoegdheden');
    $form['bevoegdheden'] = [
      '#type' => 'value',
      '#value' => $bevoegdheden,
    ];

    // set up status van bevoegdheden
    $status = $settings->get('vba.status');
    $form['status'] = [
      '#type' => 'value',
      '#value' => $status,
    ];

    //maak lijst van namen voor dropdown menu
    $condition = [
      'code' => 'VL',
      'actief' => TRUE,
    ];
    $namen = EzacUtil::getLeden($condition);
    $namen[''] = '<selecteer>';

    //opslag voor ingevoerde opmerkingen en bevoegdheden per vlieger
    //dit is een array van $vlieger['data'] gegevens (opmerking en bevoegdheid)
    $form['vlieger_storage'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );

    //maak lijst van bevoegdheden voor dropdown menu
    //$bevoegdheden = ezacvba_get_bevoegdheden();
    $bv_list[0] = '<Geen wijziging>';
    if (isset($bevoegdheden)) {
      foreach ($bevoegdheden as $bevoegdheid => $bevoegdheid_array) {
        $bv_list[$bevoegdheid] = $bevoegdheid_array['naam'];
      }
    }

    // find this year's flight days, descending
    $errmsg = EzacUtil::checkDatum(date('Y'), $datumStart, $datumEnd);
    $condition = [
      'datum' => [
        'value' => [$datumStart, $datumEnd],
        'operator' => 'BETWEEN',
      ],
    ];
    $starts = array_unique(EzacStart::index($condition,'datum', 'datum', 'DESC'));

    $start_dates = array();
    foreach ($starts as $start) {
      $start_dates[$start] = EzacUtil::showDate($start); //list of dates for selection
    }
    $datum = (isset($start_dates[0]))
      ? $start_dates[0]->datum // most recent date value
      : date('Y-m-d');

    // 2. build form contents
    //@todo this markup is superfluous with the form title set in routing
    $form['titel']['#type'] = 'markup';
    $form['titel']['#markup'] = '<p><h2>Invoer dagverslag</h2></p>';
    $form['titel']['#weight'] = 0;

    //form radio button for select date from list or manual (with callback)
    //build container for date selection
    $form['datum_select'] = array(
      '#type' => 'container',
      '#weight' => 1,
      '#prefix' => '<div id="datum-div">',
      '#suffix' => '</div>',
      '#tree' => TRUE, // is this necessary?
    );
    //if (!isset($starts)) //do not show chooser and dropdown selection
    //@todo use conditional field like in EzacStartsUpdateForm
    $form['datum_select']['chooser'] = array(
      '#title' => 'Kies andere datum',
      '#type' => 'checkbox',
      '#default_value' => 0, //unchecked
      '#ajax' => array(
        'callback' => '::verslagDatumCallback',
        'wrapper' => 'datum-div',
        'effect' => 'fade',
        'progress' => array('type' => 'throbber'),
      ),
    );

    // make most recent day active
    // present as default date in dropdown list
    $form['datum_select'] = [
      '#title' => t('Datum'),
      '#type' => 'select',
      '#options' => $start_dates,
      '#default_value' => $datum,  //most recent date
      '#states' => [
        'visible' => [
          ':input[name="andere_datum"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Enter datum manually if requested or no list available
    $form['datum_entry'] = [
      '#title' => t('Datum'),
      '#type' => 'date', //extension to 'date'
      '#date_format' => 'Y-m-d',
      '#default_value' => $datum, //today
      '#states' => [
        'visible' => [
          ':input[name="andere_datum"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['andere_datum'] = [
      '#title' => t('Kies andere datum'),
      '#type' => 'checkbox',
      '#value' => !isset($starts), // checked when no start dates exist
      '#checked' => !isset($starts),
      '#attributes' => ['name' => 'andere_datum'],
    ];

    // set instructeur default to value of current user
    // present drop list with leden
    // via AJAX wordt vervolgens de lijst van vliegers voor die instructeur getoond
    //get current user details
    $user = $this->currentUser();
    $user_name = $user->getAccountName();
    $condition = ['user' => $user_name];
    $id = EzacLid::index($condition);
    if (count($id) == 1) {
      $lid = new EzacLid($id);
      $afkorting = $lid->afkorting;
    }
    else $afkorting = ''; // geen lid gevonden

    $form['instructeur'] = array(
      '#title' => t('Instructeur / verantwoordelijke'),
      '#type' => 'select',
      '#options' => $namen,  //@TODO select only instructeur from namen
      '#default_value' => $afkorting,
      //'#description' => t('Instructeur of verantwoordelijke'),
      '#weight' => 2,
      '#ajax' => array(
        'callback' => '::verslagCallback',
        'wrapper' => 'vliegers-div',
        'effect' => 'fade',
        'progress' => array('type' => 'throbber'),
      ),
    );

    $instructeur = $form_state->getValue('instructeur') ?? $afkorting;
    //optie om alleen eigen leerlingen te selecteren of alle vliegers van die dag
    $form['leerling'] = array(
      '#title' => t('Selecteer alleen eigen leerlingen'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#weight' => 2,
      '#ajax' => array(
        'callback' => '::verslagCallback',
        'wrapper' => 'vliegers-div',
        'effect' => 'fade',
        'progress' => array('type' => 'throbber'),
      ),
    );

    $eigen_leerling = $form_state->getValue('leerling') ?? TRUE;

    // textaera field for 'weer' (2 lines)
    $form['weer'] = array(
      '#title' => t('Weer en baanrichting'),
      '#type' => 'textarea',
      '#rows' => 2,
      //'#default_value' => $verslag_waarde,
      '#weight' => 3,
      '#prefix' => '<div id="weer">',
      '#suffix' => '</div>',
    );
    // textarea field for 'verslag'  (10 lines)
    $form['verslag'] = array(
      '#title' => t('Algemeen verslag'),
      '#type' => 'textarea',
      '#rows' => 10,
      //'#default_value' => $verslag,
      '#required' => TRUE,
      '#weight' => 4,
      '#prefix' => '<div id="verslag">',
      '#suffix' => '</div>',
    );

    // generate form element with vliegers for the selected day
    // get starts->gezagvoerder, starts->tweede in $namen
    //$datum_form = $form_state->getValue('datum_select')['datum'] ?? $datum;

    if ($form_state->getValue('andere_datum')) {
      // manual data entry selected
      $datum_form = $form_state->getValue('datum_entry');
    }
    else {
      // date selected from list
      $datum_form = $form_state->getValue('datum_select');
    }
    dpm($datum_form, 'datum'); //debug
    /*
    $query = db_select('ezac_Starts', 's');
    $query->fields('s',array('gezagvoerder', 'tweede'));
    $query->condition('s.datum', $datum_form, '=');
    $starts = $query->execute()->fetchAll();
    */
    $condition = ['datum' => $datum_form];
    $startsIndex = EzacStart::index($condition);
    $starts = [];
    foreach ($startsIndex as $startId) {
      $starts[] = new EzacStart($startId);
    }
    //$vliegers[afkorting] = naam
    $vliegers = array();
    foreach ($starts as $start) {
      $gezagvoerder = $start->gezagvoerder;
      if ($eigen_leerling == FALSE) {
        if (!isset($vliegers[$gezagvoerder]))
          $vliegers[$gezagvoerder] = $namen[$gezagvoerder];
        if (isset($start->tweede) && ($start->tweede !='')) {
          $tweede = $start->tweede;
          if (!isset($vliegers[$tweede]))
            $vliegers[$tweede] = $namen[$tweede];
        }
      }
      if (($eigen_leerling == TRUE) && ($gezagvoerder == $instructeur)) {
        if (isset($start->tweede) && ($start->tweede !='')) {
          $tweede = $start->tweede;
          if (!isset($vliegers[$tweede]))
            $vliegers[$tweede] = $namen[$tweede];
        }
      }
    }

    //sorteer $vliegers array
    asort($vliegers);

    //[vliegers] form wordt door AJAX opnieuw opgebouwd

    $form['vliegers'] = array(
      '#title' => t('Opmerkingen per vlieger'),
      '#type' => 'container',
      '#weight' => 5,
      '#prefix' => '<div id="vliegers-div">', //This section replaced by AJAX callback
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );

    // Check of $vliegers gevuld is ...
    if (count($vliegers) > 0) {
      $form['vliegers']['select'] = array(
        '#title' => t('Selecteer een vlieger'),
        '#description' => t('Je kunt verschillende vliegers na elkaar selecteren en invullen<br>Kies Opslaan nadat je de laatste hebt ingevuld'),
        '#type' => 'select',
        '#options' => $vliegers,
        '#ajax' => array(
          'callback' => 'ezacvba_verslag_callback',
          'wrapper' => 'vliegers-div',
          'effect' => 'fade',
          'progress' => array('type' => 'throbber'),
        ),
      );

      //Toon eerdere verslagen voor de geselecteerde vlieger
      $afkorting = $form_state->getValue('vliegers')['select'] ?? key($vliegers); // default eerste waarde
      $helenaam  = $vliegers[$afkorting];

      // query vba verslag, bevoegdheid records
      /*
      $query = db_select('ezac_vba_dagverslagen_lid', 'l');
      $query->fields('l', array('id', 'afkorting', 'instructeur', 'datum', 'verslag'));
      $query->condition('afkorting', $afkorting, '=');
      $verslagen = $query->execute()->fetchAll();
      */
      $condition = ['afkorting' => $afkorting];
      $dagverslagenIndex = EzacVbaDagverslag::index($condition);
      $verslagen = [];
      foreach ($dagverslagenIndex as $id) {
        $verslagen[] = new EzacVbaDagverslag($id);
      }
      $form['vliegers']['data'][$afkorting] = array(
        '#title' => $helenaam,
        '#type'=> 'fieldset',
        '#required' => FALSE,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );

      if (!empty($verslagen)) {
        //create fieldset
        $form['vliegers']['data'][$afkorting]['verslagen'] = array(
          '#title' => t("Eerdere opmerkingen voor $helenaam"),
          '#type'=> 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#weight' => 6,
          '#tree' => TRUE,
        );

        $header = array(
          array('data' => 'datum', 'width' => '20%'),
          array('data' => 'instructeur', 'width' => '20%'),
          array('data' => 'opmerking'),
        );
        $rows = array();

        foreach ($verslagen as $verslag) {
          $rows[] = array(
            EzacUtil::showDate($verslag->datum),
            $namen[$verslag->instructeur],
            nl2br($verslag->verslag),
          );

        }
        $form['vliegers']['data'][$afkorting]['verslagen']['tabel'] = array(
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          //'#attributes' => $attributes,
        );
      } //!empty(verslagen)

      //  list with persons who have flown that day - each may be selected
      //   enter persoon_verslag
      //   possible entry of 'bevoegdheid' as of datum

      // invoeren opmerking
      // lees uit ['vlieger_storage'] eventueel eerder ingevoerde waarde voor #default_value
      $opmerking = (isset($form_state['vlieger_storage'][$afkorting]['opmerking']))
        ? $form_state['vlieger_storage'][$afkorting]['opmerking']
        : '';
      $form['vliegers']['data'][$afkorting]['opmerking'] = array(
        '#title' => t("Opmerkingen voor $helenaam"),
        '#type' => 'textarea',
        '#rows' => 3,
        '#required' => FALSE,
        '#weight' => 5,
        '#tree' => TRUE,
        '#default_value' => $opmerking,
      );

      //toon huidige bevoegdheden
      // query vba verslag, bevoegdheid records
      /*
      $query = db_select('ezac_vba_bevoegdheid_lid', 'b');
      $query->fields('b', array('id', 'afkorting', 'instructeur', 'datum_aan',
        'bevoegdheid', 'actief', 'onderdeel', 'opmerking'));
      $query->condition('afkorting', $afkorting, '=');
      $query->condition('actief', TRUE, '=');
      $vlieger_bevoegdheden = $query->execute()->fetchAll();
      */
      $condition = ['afkorting' => $afkorting];
      $bevoegdhedenIndex = EzacVbaBevoegdheid::index($condition);
      $vlieger_bevoegdheden = [];
      foreach ($bevoegdhedenIndex as $id) {
        $bevoegdheid = new EzacVbaBevoegdheid($id);
        $vlieger_bevoegdheden[] = $bevoegdheid;
      }
      // put in table
      $header = array(
        array('data' => 'datum', 'width' => '20%'),
        array('data' => 'instructeur', 'width' => '20%'),
        array('data' => 'bevoegdheid'),
      );
      $rows = array();

      // lees uit ['vlieger_storage'] eventueel eerder ingevoerde waarde voor #default_value
      if (!empty($vlieger_bevoegdheden)) { //create fieldset
        $form['vliegers']['data'][$afkorting]['bevoegdheden'] = array(
          '#title' => t("Bevoegdheden van $helenaam"),
          '#type'=> 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#weight' => 7,
          '#tree' => TRUE,
        );
        foreach ($vlieger_bevoegdheden as $bevoegdheid) {
          $rows[] = array(
            EzacUtil::showDate($bevoegdheid->datum_aan),
            $namen[$bevoegdheid->instructeur],
            $bevoegdheid->bevoegdheid .' - '
            .$bv_list[$bevoegdheid->bevoegdheid] .' '
            .nl2br($bevoegdheid->onderdeel)
          );
        }
        $form['vliegers']['data'][$afkorting]['bevoegdheden']['tabel'] = array(
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          '#weight' => 7,
        );
      }

      //formatteer bevoegdheid en onderdeel in een tabel regel
      $soort = $form_state->getValue('vlieger_storage')[$afkorting]['bevoegdheid']['rows'][0]['soort'] ?? 0;
      $onderdeel = $form_state->getValue('vlieger_storage')[$afkorting]['bevoegdheid']['rows'][0]['onderdeel'] ?? '';
      $tabel_rows = array(
        '#tree' => TRUE,
        array(
          'soort' => array(
            '#description' => 'Toekennen van een nieuwe bevoegdheid',
            '#type' => 'select',
            '#options' => $bv_list,
            '#default_value' => $soort, //<Geen wijziging>
          ),
          'onderdeel' => array(
            '#description' => 'Bijvoorbeeld overland type',
            '#type' => 'textfield',
            '#required' => FALSE,
            '#default_value' => $onderdeel,
          ),
        ),
      );

      // bevoegdheid en onderdeel in tabel vormgeven
      $form['vliegers']['data'][$afkorting]['bevoegdheid'] = array(
        '#type' => 'table',
        '#header' => array('bevoegdheid', 'onderdeel'),
        '#tree' => TRUE,
        '#prefix' => "<div id='bevoegdheid-div'>",
        '#suffix' => '</div>',
        '#weight' => 10,
        'rows' => $tabel_rows,
      );
      // einde invoeren bevoegdheid

    } //if count($vliegers)
    //submit
    $form['submit'] = array(
      '#type' => 'submit',
      '#description' => t('Verslag opslaan en via mail verzenden'),
      '#value' => t('Opslaan'),
      '#weight' => 99,
    );

    return $form;
  }

  /**
   * Selects the piece of the form we want to use as replacement text and returns
   * it as a form (renderable array).
   *
   * @param $form
   * @param $form_state
   *
   * @return  array (the textfields element)
   */
  function verslagCallback($form, $form_state): array {
    return $form['vliegers']; //HTML for verslag form['vliegers']
  }

  function verslagDatumCallback($form, $form_state) {
    return $form['datum_select']; //HTML for verslag form['datum_select']
  }


  /**
   * Validate the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //controleer ingevoerde datum op juistheid
    $dc = explode('-', $form_state->getValue('datum_select')['datum']);
    if (!checkdate($dc[1], $dc[2], $dc[0])) $form_state->setErrorByName('datum', 'ongeldige datum');

    //kopieer $vliegers naar een array voor submit
    //validate wordt bij elke AJAX call aangeroepen dus ook bij keuze vlieger uit dropdown
    //  om te zorgen dat informatie voor elke ingevoerde vlieger bij SUBMIT beschikbaar is,
    //  wordt de informatie hier in een aparte array in form_state['values']['vlieger-storage'] gezet
    $vliegers_data = $form_state->getValue('vliegers');
    if (isset($vliegers_data)) {
      foreach ($vliegers_data['data'] as $afkorting => $verslag) {
        if (($verslag['opmerking'] <> '') ||
          ($verslag['bevoegdheid']['rows'][0]['soort'] != '0')){
          //opmerking of bevoegdheid is ingevoerd - sla op in vlieger_storage
          $s = $form_state->getValue('vlieger_storage');
          $s[$afkorting] = $verslag;
          $form_state->setValue('vlieger_storage', $s);
        }
      }
    }
  }

  /**
   * Handle post-validation form submission.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = Drupal::messenger();

    $dagverslag = new EzacVbaDagverslag();
    $namen = $form_state->getValue('namen');
    $dagverslag->datum = $form_state->getValue('datum_select')['datum'];
    $dagverslag->instructeur = $form_state->getValue('instructeur');
    $dagverslag->weer = htmlentities($form_state->getValue('weer'));
    $dagverslag->verslag = htmlentities($form_state->getValue('verslag'));
    $dagverslag->mutatie = date('Y-m-d h:m:s');

    //write verslag to vba_dagverslagen
    if ($dagverslag->weer . $dagverslag->verslag != '') { // verslag ingevuld
      $dagverslag = $dagverslag->create(); // write to database
      $id = $dagverslag->id;
      $message->addMessage("Dagverslag [$id] voor "
        .EzacUtil::showDate($dagverslag->datum) .' aangemaakt', 'status');
    }
    //write verslag per vlieger
    $vlieger_storage = $form_state->getValue('vlieger_storage');
    if (isset($vlieger_storage)) {
      foreach ($vlieger_storage as $afkorting => $verslag) {
        if ($verslag['opmerking'] <> '') {
          //opmerking is ingevoerd
          $dagverslagLid = new EzacVbaDagverslagLid();
          $dagverslagLid->datum = $form_state->getValue('datum_select')['datum'];
          $dagverslagLid->afkorting = $afkorting;
          $dagverslagLid->instructeur = $form_state->getValue('instructeur');
          $dagverslagLid->verslag = htmlentities($verslag['opmerking']);
          $dagverslagLid->mutatie = date('Y-m-d h:m:s');
          $dagverslagLid = $dagverslagLid->create();
          $message->addMessage('Verslag voor ' .$namen[$afkorting] .' aangemaakt', 'status');
        }
        //update bevoegdheden per vlieger
        if ($verslag['bevoegdheid']['rows'][0]['soort'] != '0') {
          //Bevoegdheid ingevoerd
          $bevoegdheid = new EzacVbaBevoegdheid();
          $bevoegdheid->bevoegdheid = $verslag['bevoegdheid']['rows'][0]['soort'];
          $bevoegdheid->onderdeel = htmlentities($verslag['bevoegdheid']['rows'][0]['onderdeel']);
          $bevoegdheid->datum_aan = $form_state->getValue('datum_select')['datum'];
          $bevoegdheid->afkorting = $afkorting;
          $bevoegdheid->instructeur = $form_state->getValue('instructeur');
          $bevoegdheid->actief = TRUE;
          $id = $bevoegdheid->create();
          $message->addMessage('Bevoegdheid ' .$verslag['bevoegdheid']['rows'][0]['soort']
            .' voor ' .$namen[$afkorting] ." aangemaakt [$id]", 'status');
        }
      }
    }

    //mail verslag naar instructeurs
    self::verslagenMail($dagverslagLid->datum);

    //@todo redirect naar calling url
    /*
    if ($current_url != "") {
      $form_state['redirect'] = $current_url;
    }
    else $form_state['redirect'] = 'vba';
    */
    // return result
    return;
  }

  /**
   * Mail Verslag (dag en per lid)
   * @param string datum
   **/
  function verslagenMail($datum) {
    $condition = [
      'code' => 'VL',
      'actief' => TRUE,
    ];
    $namen = EzacUtil::getLeden($condition);

    //mail verslag naar instructeurs
    $condition = [
      'instructie' => TRUE,
      'actief' => TRUE,
    ];
    $instructeurs = EzacLid::index($condition, 'e_mail');
    $to = '';
    foreach ($instructeurs as $email) {
      $to .= $email .'; ';
    }
    $to .= 'webmaster@ezac.nl'; //instructie@ezac.nl //TEST DEBUG

    $subject = "EZAC instructie verslag $datum";

    //Haal omstandigheden en verslag uit de database
    $condition = ['datum' => $datum];
    $dagverslagenIndex = EzacVbaDagverslag::index($condition);
    $message = '';
    if (count($dagverslagenIndex)) {
      foreach ($dagverslagenIndex as $id) {
        $dagverslag = new EzacVbaDagverslag($id);
        $mail_instructeur = $namen($dagverslag->instructeur);
        $message .= "<p><h1>Verslag van $mail_instructeur</h1></p>/r/n";
        $message .= "<p><h2>Omstandigheden</h2></p>/r/n";
        $message .= "<p>" .$dagverslag->weer ."</p>/r/n";
        $message .= "<p><h2>Verslag</h2></p>";
        $message .= "<p>" .$dagverslag->verslag ."</p>/r/n";
      }
    }

    $message .= "<p><h2>Opmerkingen per leerling</h2></p>/r/n";

    //haal de opmerkingen uit de database ivm los ingevoerde opmerkingen
    $condition = ['datum' => $datum];
    $verslagenIndex = EzacVbaDagverslagLid::index($condition);
    if (count($verslagenIndex)) {
      foreach ($verslagenIndex as $id) {
        $verslag = new EzacVbaDagverslagLid($id);
        $message .= "<p><h3> $namen($verslag->afkorting) </h3></p>/r/n";
        $message .= "<p> $verslag->verslag </p>/r/n";
      }
    }

    $condition = ['datum_aan' => $datum];
    $bevoegdhedenIndex = EzacVbaBevoegdheid::index($condition);
    if (count($bevoegdhedenIndex)) {
      $message .= "<p><h2>Bevoegdheden toegekend per leerling</h2></p>";
      foreach($bevoegdhedenIndex as $id) {
        $bevoegdheid = new EzacVbaBevoegdheid($id);
        $message .= "<p> $namen($bevoegdheid->afkorting) : $bevoegdheid->bevoegdheid";
        $message .= " door instructeur $namen($bevoegdheid->instructeur) </p>/r/n";
      }
    }
    // @todo gebruikte functie was ezac_mail, nog na te kijken
    mail($to, $subject, $message); //send e-mail

  } // verslagenMail

}
