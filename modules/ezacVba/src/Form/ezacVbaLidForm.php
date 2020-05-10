<?php

namespace Drupal\ezacVba\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ezac\Util\EzacUtil;
use Drupal\ezacStarts\Controller\EzacStartsController;
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
    public function getFormId()
    {
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

      $periode_list = [
        'seizoen' => 'dit seizoen',
        'tweejaar' => '24 maanden',
        'jaar' => '12 maanden',
        'maand' => '1 maand',
        'vandaag' => 'vandaag',
        //'anders' => 'andere periode',
      ];

      $form['periode'] = [
        '#type' => 'select',
        '#title' => 'Periode',
        '#options' => $periode_list,
        '#weight' => 1,
        '#ajax' => [
          'wrapper' => 'vlieger-div',
          'callback' => '::formCallback',
          'effect' => 'fade',
          'progress' => ['type' => 'throbber'],
        ],
      ];
      $periode = $form_state->getValue('periode', key($periode_list));

      switch ($periode) {
        case 'vandaag' :
          $datum_start = date('Y-m-d');
          $datum_eind = date('Y-m-d');
          break;
        case 'maand' :
          $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n') - 1, date('j'), date('Y')));
          $datum_eind = date('Y-m-d'); //previous month
          break;
        case 'jaar' :
          $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y') - 1));
          $datum_eind = date('Y-m-d'); //previous year
          break;
        case 'tweejaar' :
          $datum_start = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y') - 2));
          $datum_eind = date('Y-m-d'); //previous 2 year
          break;
        case 'seizoen' :
          $datum_start = date('Y') . '-01-01'; //this year
          $datum_eind = date('Y') . '-12-31';
          break;
        case 'anders' :
          if (!isset($form_state['values']['datum_start'])) {
            $datum = date('Y-m-d'); //default vandaag
          }
          else {
            $datum_start = $form_state['values']['datum_start'];
            $datum_eind = $form_state['values']['datum_eind'];
          }
      }

      global /** @var array $namen */
      $namen;

      $namen = EzacUtil::getLeden();
      $namen['selecteer'] = '<selecteer>';

      // store $namen in form for callback
      $form['namen'] = [
        '#type' => 'value',
        '#value' => $namen,
      ];

      $form['datum_start'] =[
        '#type' => 'value',
        '#value' => $datum_start,
      ];

      $form['datum_eind'] =[
        '#type' => 'value',
        '#value' => $datum_eind,
      ];

      $form['persoon'] = [
        '#type' => 'select',
        '#title' => 'Vlieger',
        '#options' => $namen,
        '#default' => 'selecteer',
        '#weight' => 2,
        '#ajax' => [
          'wrapper' => 'vlieger-div',
          'callback' => '::formCallback',
          //'effect' => 'fade',
          //'progress' => array('type' => 'throbber'),
        ],
      ];

      $condition = [
        'datum' => [
          'value' => [$datum_start, $datum_eind],
          'operator' => 'BETWEEN'
        ],
        'afkorting' => $form_state->getValue('persoon', key($namen)),
      ];
      $dagverslagenLidCount = ezacVbaDagverslagLid::counter($condition);

      $condition = [
        'datum_aan' => [
          'value' => [$datum_start, $datum_eind],
          'operator' => 'BETWEEN'
        ],
      ];


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
  function formCallback(array $form, FormStateInterface $form_state)
    {
      // Kies gewenste periode / vlieger voor overzicht dagverslagen
      $overzicht = TRUE; // @todo replace parameter $overzicht
      // D7 code start

      $namen = $form_state->getValue(('namen'));
      $datum_start = $form_state->getValue('datum_start');
      $datum_eind = $form_state->getValue('datum_eind');

      $vlieger_afkorting = $form_state->getValue('persoon', key($namen));
      $helenaam = $namen[$vlieger_afkorting];

      //$datum = $form_state->getValue('datum', date('Y-m-d'));

      //toon vluchten dit jaar
      $form['vliegers']['starts'] = EzacStartsController::startOverzicht($datum_start, $datum_eind, $vlieger_afkorting);

      if (!$overzicht) {
        //@todo param $overzicht nog hanteren? of apart form voor maken
        // invoeren opmerking
        $form['vliegers']['opmerking'] = array(
          '#title' => t("Opmerkingen voor $helenaam"),
          '#type' => 'textarea',
          '#rows' => 3,
          '#required' => FALSE,
          '#weight' => 5,
          '#tree' => TRUE,
        );
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
      if (!empty($verslagen)) { //create fieldset
        $form['vliegers']['verslagen'][$vlieger_afkorting] = array(
          '#title' => t("Eerdere verslagen voor $helenaam"),
          '#type'=> 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => !$overzicht,
          '#weight' => 6,
          '#tree' => TRUE,
        );

        $header = array(
          array('data' => 'datum', 'width' => '20%'),
          array('data' => 'instructeur', 'width' => '20%'),
          array('data' => 'opmerking'),
        );

        $rows = array();
        foreach ($verslagenIndex as $id) {
          $verslag = (new ezacVbaDagverslagLid)->read($id);
          $rows[] = array(
            EzacUtil::showDate($verslag->datum),
            $namen[$verslag->instructeur],
            nl2br($verslag->verslag),
          );
        }
        $form['vliegers']['verslagen'][$vlieger_afkorting]['tabel'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          //'#attributes' => $attributes,
        );
      }

      $condition = [];
      $bevoegdhedenIndex = ezacVbaBevoegdheid::index($condition);
      $bv_list[0] = '<Geen wijziging>';
      if (isset($bevoegdhedenIndex))
      {
        foreach ($bevoegdhedenIndex as $id)
        {
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
      $header = array(
        array('data' => 'datum', 'width' => '20%'),
        array('data' => 'instructeur', 'width' => '20%'),
        array('data' => 'bevoegdheid'),
      );
      $rows = array();

      if (!empty($vlieger_bevoegdhedenIndex)) { //create fieldset
        $form['vliegers']['bevoegdheden'][$vlieger_afkorting] = array(
          '#title' => t("Bevoegdheden voor $helenaam"),
          '#type'=> 'fieldset',
          '#edit' => FALSE,
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE, //!$overzicht,
          '#weight' => 7,
          '#tree' => TRUE,
        );
        foreach ($vlieger_bevoegdhedenIndex as $id) {
          $bevoegdheid = (new ezacVbaBevoegdheidLid)->read($id);
          $rows[] = array(
            EzacUtil::showDate($bevoegdheid->datum_aan),
            $namen[$bevoegdheid->instructeur],
            $bevoegdheid->bevoegdheid .' - '
            .$bv_list[$bevoegdheid->bevoegdheid] .' '
            .nl2br($bevoegdheid->onderdeel)
          );
        }
        $form['vliegers']['bevoegdheden'][$vlieger_afkorting]['tabel'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('Geen gegevens beschikbaar'),
          '#weight' => 7,
        );
      }

      if (!$overzicht) {
        //invoer bevoegdheid
        $form['vliegers']['bevoegdheid'] = array(
          '#title' => 'Bevoegdheid',
          '#type' => 'container',
          '#prefix' => '<div id="bevoegdheid-div">',
          '#suffix' => '</div>',
          '#required' => FALSE,
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
          '#weight' => 10,
          '#tree' => TRUE,
        );

        $form['vliegers']['bevoegdheid']['keuze'] = array(
          '#title' => t('Bevoegdheid'),
          '#type' => 'select',
          '#options' => $bv_list,
          '#default_value' => 0, //<Geen wijziging>
          '#weight' => 10,
          '#tree' => TRUE,
          '#ajax' => array(
            'callback' => 'ezacvba_bevoegdheid_callback',
            'wrapper' => 'bevoegdheid-div',
            'effect' => 'fade',
            'progress' => array('type' => 'throbber'),
          ),
        );

        if (isset($form_state['values']['vliegers']['bevoegdheid']['keuze'])
          && ($form_state->getValue(['bevoegdheid']['keuze']) <> '0'))
        {
          $form['vliegers']['bevoegdheid']['onderdeel'] = array(
            '#title' => t('Onderdeel'),
            '#description' => 'Bijvoorbeeld overland type',
            '#type' => 'textfield',
            '#maxlength' => 30,
            '#required' => FALSE,
            '#default_value' => '',
            '#weight' => 11,
            '#tree' => TRUE,
          );
        }

        //submit
        $form['vliegers']['submit'] = array(
          '#type' => 'submit',
          '#description' => t('Opslaan'),
          '#value' => t('Opslaan'),
          '#weight' => 99,
        );
      }


      // D7 code end

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
}
