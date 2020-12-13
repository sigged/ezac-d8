<?php

namespace Drupal\ezacVba\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezacLeden\Model\EzacLid;
use Drupal\ezacStarts\Controller\EzacStartsController;
use Drupal\ezacStarts\Model\EzacStart;
use Drupal\ezacVba\Model\ezacVbaBevoegdheid;
use Drupal\ezacVba\Model\ezacVbaBevoegdheidLid;
use Drupal\ezacVba\Model\ezacVbaDagverslag;
use Drupal\ezacVba\Model\ezacVbaDagverslagLid;
use Twig\Error\RuntimeError;

/**
 * UI to show status of VBA records
 */


class ezacVbaLidForm extends FormBase
{

    /**
     * @inheritdoc
     */
    public function getFormId(): string {
        return 'ezac_vba_lid_form';
    }

  /**
   * buildForm for vba lid status and bevoegdheid
   *
   * Voortgang en Bevoegdheid Administratie
   * Overzicht van de status en bevoegdheid voor een lid
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @param $datum_start
   * @param $datum_eind
   *
   * @return array
   */
    public function buildForm(array $form, FormStateInterface $form_state, $datum_start = NULL, $datum_eind = NULL)
    {
      // Wrap the form in a div.
      $form = [
        '#prefix' => '<div id="statusform">',
        '#suffix' => '</div>',
      ];

      // apply the form theme
      //$form['#theme'] = 'ezac_vba_lid_form';

      // when datum not given, set default for this year
      if ($datum_start == NULL) {
        $datum_start = date('Y') . "-01-01";
      }
      if ($datum_eind == NULL) {
        $datum_eind = date('Y') . "-12-31";
      }
      $form['datum_start'] = [
        '#type' => 'value',
        '#value' => $datum_start,
      ];
      $form['datum_eind'] = [
        '#type' => 'value',
        '#value' => $datum_eind,
      ];

      $condition = [
        'code' => 'VL',
        'actief' => TRUE,
      ];
      $namen = EzacUtil::getLeden($condition);
      $namen[''] = '<selecteer>';

      $form['persoon'] = [
        '#type' => 'select',
        '#title' => 'Vlieger',
        '#options' => $namen,
        '#weight' => 2,
        '#ajax' => [
          'wrapper' => 'vliegers-div',
          'callback' => '::formPersoonCallback',
          'effect' => 'fade',
        ],
      ];


      // Kies gewenste vlieger voor overzicht dagverslagen
      $overzicht = TRUE; // @todo replace parameter $overzicht
      //@todo het overzicht van vluchten is initieel voor iedereen ipv leeg en de sortering lijkt willekeurig
      //@todo het overzicht van vluchten wordt ook niet aangepast nadat een vlieger is geselecteerd

      // D7 code start

      //$datum = $form_state->getValue('datum', date('Y-m-d'));

      //maak container voor vliegers
      //[vliegers] form wordt door AJAX opnieuw opgebouwd
      $form['vliegers'] = array(
        '#title' => t('Vlieger'),
        '#type' => 'container',
        '#weight' => 4,
        '#prefix' => '<div id="vliegers-div">', //This section replaced by AJAX callback
        '#suffix' => '</div>',
        '#tree' => TRUE,
      );

      //submit
      $form['vliegers']['submit'] = [
        '#type' => 'submit',
        '#description' => t('Opslaan'),
        '#value' => t('Opslaan'),
        '#weight' => 99,
      ];

      // D7 code end
      $form['actions'] = [
      '#type' => 'actions',
      ];
      return $form;
    }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|mixed
   */
  function formPersoonCallback(array $form, FormStateInterface $form_state) {

    $overzicht = TRUE; //@todo temporary fix
    $condition = [
      'code' => 'VL',
      'actief' => TRUE,
    ];
    $namen = EzacUtil::getLeden($condition);

    $persoon = $form_state->getValue('persoon');
    $datum_start = $form_state->getValue('datum_start');
    $datum_eind = $form_state->getValue('datum_eind');

    if (isset($persoon) && $persoon != '') {
      //toon vluchten dit jaar
      $vlieger_afkorting = $form_state->getValue('persoon');

      $lid = (new EzacLid)->read(EzacLid::getId($vlieger_afkorting));
      $helenaam = "$lid->voornaam $lid->voorvoeg $lid->achternaam";

      // @todo deze routine geeft niet de juiste starts terug
      $form['vliegers']['starts'] = self::startOverzicht($datum_start, $datum_eind, $vlieger_afkorting);

      if (!$overzicht) {
        //@todo param $overzicht nog hanteren? of apart form voor maken
        // invoeren opmerking
        $form['vliegers']['opmerking'] = [
          '#title' => t("Opmerkingen voor $helenaam"),
          '#type' => 'textarea',
          '#rows' => 3,
          '#required' => FALSE,
          '#weight' => 5,
          '#tree' => TRUE,
        ];
      }

      //Toon eerdere verslagen per lid
      // query vba verslag, bevoegdheid records
      $condition = ['afkorting' => $vlieger_afkorting];
      if (isset($datum_start)) {
        $condition ['datum'] =
          [
            'value' => [$datum_start, $datum_eind],
            'operator' => 'BETWEEN'
          ];
      }
      $verslagenIndex = ezacVbaDagverslagLid::index($condition);

      // put in table
      if (isset($verslagenIndex)) { //create fieldset
        $form['vliegers']['verslagen'][$vlieger_afkorting] = [
          '#title' => t("Eerdere verslagen voor $helenaam"),
          '#type' => 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => !$overzicht,
          '#weight' => 6,
          '#tree' => TRUE,
        ];

        $header = [
          ['data' => 'datum', 'width' => '20%'],
          ['data' => 'instructeur', 'width' => '20%'],
          ['data' => 'opmerking'],
        ];

        $rows = [];
        foreach ($verslagenIndex as $id) {
          $verslag = (new ezacVbaDagverslagLid)->read($id);
          $rows[] = [
            EzacUtil::showDate($verslag->datum),
            $namen[$verslag->instructeur],
            nl2br($verslag->verslag),
          ];
        }
        $form['vliegers']['verslagen'][$vlieger_afkorting]['tabel'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          //'#attributes' => $attributes,
        ];
      }

      $condition = [];
      $bevoegdhedenIndex = ezacVbaBevoegdheid::index($condition);
      $bv_list[0] = '<Geen wijziging>';
      if (isset($bevoegdhedenIndex)) {
        foreach ($bevoegdhedenIndex as $id) {
          $bevoegdheid = (new ezacVbaBevoegdheid)->read($id);
          $bv_list[$bevoegdheid->bevoegdheid] = $bevoegdheid->naam;
        }
      }
      //toon huidige bevoegdheden
      // query vba verslag, bevoegdheid records
      $condition['afkorting'] = $vlieger_afkorting;
      $condition['actief'] = TRUE;
      $vlieger_bevoegdhedenIndex = ezacVbaBevoegdheidLid::index($condition);

      // put in table
      $header = [
        ['data' => 'datum', 'width' => '20%'],
        ['data' => 'instructeur', 'width' => '20%'],
        ['data' => 'bevoegdheid'],
      ];
      $rows = [];

      if (!empty($vlieger_bevoegdhedenIndex)) { //create fieldset
        $form['vliegers']['bevoegdheden'][$vlieger_afkorting] = [
          '#title' => t("Bevoegdheden voor $helenaam"),
          '#type' => 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE, //!$overzicht,
          '#weight' => 7,
          '#tree' => TRUE,
        ];
        foreach ($vlieger_bevoegdhedenIndex as $id) {
          $bevoegdheid = (new ezacVbaBevoegdheidLid)->read($id);
          $rows[] = [
            EzacUtil::showDate($bevoegdheid->datum_aan),
            $namen[$bevoegdheid->instructeur],
            $bevoegdheid->bevoegdheid . ' - '
            . $bv_list[$bevoegdheid->bevoegdheid] . ' '
            . nl2br($bevoegdheid->onderdeel)
          ];
        }
        $form['vliegers']['bevoegdheden'][$vlieger_afkorting]['tabel'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          '#weight' => 7,
        ];
      }

      if (!$overzicht) {
        //invoer bevoegdheid
        $form['vliegers']['bevoegdheid'] = [
          '#title' => 'Bevoegdheid',
          '#type' => 'container',
          '#prefix' => '<div id="bevoegdheid-div">',
          '#suffix' => '</div>',
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
          '#weight' => 10,
          '#tree' => TRUE,
        ];

        $form['vliegers']['bevoegdheid']['keuze'] = [
          '#title' => t('Bevoegdheid'),
          '#type' => 'select',
          '#options' => $bv_list,
          '#default_value' => 0, //<Geen wijziging>
          '#weight' => 10,
          '#tree' => TRUE,
          '#ajax' => [
            'callback' => 'ezacvba_bevoegdheid_callback',
            'wrapper' => 'bevoegdheid-div',
            'effect' => 'fade',
            'progress' => ['type' => 'throbber'],
          ],
        ];

        if (isset($form_state['values']['vliegers']['bevoegdheid']['keuze'])
          && ($form_state->getValue(['bevoegdheid']['keuze']) <> '0')) {
          $form['vliegers']['bevoegdheid']['onderdeel'] = [
            '#title' => t('Onderdeel'),
            '#description' => 'Bijvoorbeeld overland type',
            '#type' => 'textfield',
            '#maxlength' => 30,
            '#required' => FALSE,
            '#default_value' => '',
            '#weight' => 11,
            '#tree' => TRUE,
          ];
        }
      } //!$overzicht
      // end copied code
    } //isset(persoon)
    return $form['vliegers'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

  } //submitForm

  /**
   * @param $datum_start
   * @param $datum_eind
   * @param null $vlieger
   * @return array $content
   */
  public static function startOverzicht($datum_start, $datum_eind, $vlieger = NULL) {
    $content = array();

    $rows = [];
    $headers = [
      t('datum'),
      t('start'),
      t('landing'),
      t('duur'),
      t('registratie'),
      t('gezagvoerder'),
      t('tweede'),
      t('soort'),
      t('start methode'),
      t('instructie'),
      t('opmerking'),
    ];

    $leden = EzacUtil::getLeden();
    // $kisten = EzacUtil::getKisten();

    // select all starts for selected date
    if (isset($datum_eind))
    {
      $condition['datum'] =
        [
          'value' => [$datum_start, $datum_eind],
          'operator' => 'BETWEEN',
        ];
    }
    else $condition = ['datum' => $datum_start];

    // add orGroup to selection

    $condition['OR'] =
      [
        'gezagvoerder' => $vlieger,
        'tweede' => $vlieger,
    ];

    dpm($condition, "condition after orGroup"); //debug

    // prepare pager
    // $total = EzacStart::counter($condition);
    $field = 'id';
    $sortkey = 'start';
    $sortdir = 'ASC'; // newest first

    // @todo pager not to be used
    /*
    $limit = 100;
    //$page = pager_default_initialize($total, $range); // deprecated
    $pager = \Drupal::service('pager.manager')
      ->createPager($total, $limit);
    $page = $pager
      ->getCurrentPage();

    $from = $limit * $page;
    $unique = FALSE; // return all results
    */
    $startsIndex = EzacStart::index($condition, $field, $sortkey, $sortdir); //, $from, $limit, $unique);
    foreach ($startsIndex as $id) {
      $start = (new EzacStart)->read($id);

      $urlString = Url::fromRoute(
        'ezac_starts_update',  // edit starts record
        ['id' => $start->id]
      )->toString();

      if (isset($leden[$start->gezagvoerder]) && $start->gezagvoerder <> '') {
        $gezagvoerder = $leden[$start->gezagvoerder];
      }
      else $gezagvoerder = $start->gezagvoerder; // un-edited record value

      if (isset($leden[$start->tweede]) && $start->tweede <> '') {
        $tweede = $leden[$start->tweede];
      }
      else $tweede = $start->tweede; // un-edited record value

      $rows[] = [
        //link each record to edit route
        $start->datum,
        t("<a href=$urlString>" .substr($start->start, 0, 5) ."</a>"),
        substr($start->landing,0,5),
        substr($start->duur, 0,5),
        $start->registratie,
        $gezagvoerder,
        $tweede,
        EzacStart::$startSoort[$start->soort],
        EzacStart::$startMethode[$start->startmethode],
        ($start->instructie) ? 'Ja' :'',
        $start->opmerking,
      ];
    }
    $d = EzacUtil::showDate($datum_start);
    if ($datum_eind <> $datum_start) $d.= " tot " .EzacUtil::showDate($datum_eind);
    $caption = "Overzicht EZAC Starts bestand $d";
    $content['table'] = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('Geen gegevens beschikbaar.'),
      '#sticky' => TRUE,
    ];
    // add pager
    $content['pager'] = [
      '#type' => 'pager',
      '#weight' => 5
    ];

    return $content;
  }

}
