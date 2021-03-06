<?php

/**
 * @file
 * Contains Drupal\mespronos\Entity\League.
 */

namespace Drupal\mespronos\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mespronos\MPNEntityInterface;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\mespronos\Entity\RankingGeneral;

/**
 * Defines the League entity.
 *
 * @ingroup mespronos
 *
 * @ContentEntityType(
 *   id = "league",
 *   label = @Translation("League"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\mespronos\Entity\Controller\LeagueListController",
 *     "views_data" = "Drupal\mespronos\Entity\ViewsData\LeagueViewsData", *
 *     "form" = {
 *       "default" = "Drupal\mespronos\Entity\Form\LeagueForm",
 *       "add" = "Drupal\mespronos\Entity\Form\LeagueForm",
 *       "edit" = "Drupal\mespronos\Entity\Form\LeagueForm",
 *       "archive" = "Drupal\mespronos\Entity\Form\LeagueArchiveForm",
 *       "delete" = "Drupal\mespronos\Entity\Form\MPNDeleteForm",
 *     },
 *     "access" = "Drupal\mespronos\ControlHandler\LeagueAccessControlHandler",
 *   },
 *   base_table = "mespronos__league",
 *   admin_permission = "administer League entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/mespronos/league/{league}",
 *     "edit-form" = "/entity.league.edit_form",
 *     "recount_points" = "/entity.league.recount_points",
 *     "archive" = "/entity.league.archive",
 *     "delete-form" = "/entity.league.delete_form",
 *     "collection" = "/entity.league.collection"
 *   },
 *   field_ui_base_route = "league.settings"
 * )
 */
class League extends MPNContentEntityBase implements MPNEntityInterface {
  protected static $status_allowed_value = [
    'future' => 'À venir',
    'active' => 'En cours',
    'over' => 'Terminé',
    'archived' => 'Archivé',
  ];

  protected static $betting_types = [
    'score' => 'Score',
    'winner' => '1N2',
  ];

  protected static $points_default = [
    'points_score_found' => 5,
    'points_winner_found' => 3,
    'points_participation' => 1,
  ];

  public static $status_default_value = 'active';
  public static $betting_type_default_value = 'score';

  public static function load($id) {
    $storage = \Drupal::entityManager()->getStorage('league');
    $entity = $storage->loadMultiple(array($id));
    return array_pop($entity);
  }

  public function getStatus($asMachineName = false) {
    $s = $this->get('status')->value;
    if($asMachineName) {
      return $s;
    }
    else {
      return self::$status_allowed_value[$s];
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function getTheName() {
    return $this->get('name')->value;
  }

  public function getBettingType($asMachineName = false) {
    $s = $this->get('betting_type')->value;
    if($asMachineName) {
      return $s;
    }
    else {
      return self::$betting_types[$s];
    }
  }

  public function HasClassement() {
    return $this->get('classement')->value;
  }

  public function getDaysNumber() {
    $query = \Drupal::entityQuery('day')
      ->condition('league', $this->id());
    $ids = $query->execute();
    return count($ids);
  }

  public function getBettersNumber() {
    $injected_database = Database::getConnection();
    $query = $injected_database->select('mespronos__ranking_league','rl');
    $query->addExpression('count(rl.better)','nb_better');
    $query->condition('rl.league',$this->id());
    $results = $query->execute()->fetchObject();
    return $results->nb_better;
  }
  /**
   * Return all days for league
   * @return \Drupal\mespronos\Entity\Day[]
   */
  public function getDays() {
    $storage = \Drupal::entityManager()->getStorage('day');
    $query = \Drupal::entityQuery('day');

    $query->condition('league',$this->id());

    $query->sort('id','ASC');

    $ids = $query->execute();

    $days = $storage->loadMultiple($ids);

    return $days;
  }

  /**
   * Return all games for day
   * @return \Drupal\mespronos\Entity\Game[]
   */
  public function getGames() {
    $game_storage = \Drupal::entityManager()->getStorage('game');
    $injected_database = Database::getConnection();
    $query = $injected_database->select('mespronos__game','g');
    $query->join('mespronos__day','d','d.id = g.day');
    $query->addField('g','id');
    $query->condition('d.league',$this->id());
    $results = $query->execute()->fetchAllAssoc('id');

    $results = array_map(function($v) {return $v->id;},$results);
    $games = $game_storage->loadMultiple($results);
    return $games;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'creator' => \Drupal::currentUser()->id(),
    );
  }


  public static function validateBettingType(&$values) {
    if(!isset($values['betting_type']) || empty($values['betting_type'])) {
      $values['betting_type'] = self::$betting_type_default_value;
    }
    elseif(!in_array($values['betting_type'],array_keys(self::$betting_types))) {
      throw new \Exception(t('The choosen betting type is not valid'));
    }
  }

  public static function validateStatus(&$values) {
    if(!isset($values['status']) || empty($values['status'])) {
      $values['status'] = self::$status_default_value;
    }
    if(!in_array($values['status'],array_keys(self::$status_allowed_value))) {
      throw new \Exception(t('The choosen status is not valid'));
    }
  }

  public static function validateSport(&$values) {
    if(!isset($values['sport']) || empty($values['sport'])) {
      throw new \Exception(t('The sport for the league should be set'));
    }
    else {
      $sport = entity_load('sport',$values['sport']);
      if(!$sport) {
        throw new \Exception(t('The sport for the league is not valid'));
      }
    }
  }
  public static function validateName(&$values) {
    if(!isset($values['name']) || empty(trim($values['name']))) {
      throw new \Exception(t('The league\'s name should be set'));
    }
  }

  public static function validatePoints(&$values) {
    foreach(self::$points_default as $type => $points) {
      if(!isset($values[$type]) || empty(trim($values[$type]))) {
        $values[$type] = $points;
      }
    }
    if($values['betting_type'] == 'winner') {
      $values['points_score_found'] = $values['points_winner_found'];
    }
  }
  /**
   * @param array $values
   * @return League
   * @throws \Exception
   */
  public static function create(array $values = array()) {
    self::validateBettingType($values);
    self::validateStatus($values);
    self::validateSport($values);
    self::validateName($values);
    self::validatePoints($values);

    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getupdatedTime() {
    return $this->get('updated')->value;
  }

  public function getSport() {
    $sport = entity_load('sport', $this->get('sport')->target_id);
    return $sport;
  }

  public function label($as_entity = false) {
    if($as_entity) {
      $entity = entity_view($this,'full');
      return render($entity);
    }
    else {
      return $this->get('name')->value;
    }
  }

  public function getRenderableLabel() {

    return [
      '#theme' => 'league-small',
      '#league' => [
        'url' => Url::fromRoute('entity.league.canonical',['league'=>$this->id()]),
        'label' => $this->label(),
        'logo' => $this->getLogo('mini_logo')
      ]
    ];
  }

  public function getLogo($style_name = 'thumbnail') {
    $logo = $this->get("field_league_logo")->first();
    if($logo && !is_null($logo) && $logo_file = File::load($logo->getValue()['target_id'])) {
      return self::getImageAsRenderableArray($logo_file,$style_name);
    }
    else {
      return [];
    }
  }

  public function getPoints() {
    $points = [
      'points_score_found' => $this->get('points_score_found')->value,
      'points_winner_found' => $this->get('points_winner_found')->value,
      'points_participation' => $this->get('points_participation')->value,
    ];
    return $points;
  }

  /**
   * @param integer $points
   * @return array
   */
  public function getPointsCssClass($points) {
    switch ($points) {
      case $this->get('points_score_found')->value:
        $class='score_found';
        break;
      case $this->get('points_winner_found')->value:
        $class='winner_found';
        break;
      case $this->get('points_participation')->value:
        $class='participation';
        break;
      default:
        $class = '';
    }
    return [$class];
  }
  public function isActive() {
    return $this->get('status')->value == 'active';
  }

  public function close() {
    $this->set('status','archived');
    $this->save();
    \Drupal::logger('mespronos')->notice(t('League @league_label as been set as archived',['@league_label'=>$this->label()]));
    RankingGeneral::createRanking();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the League entity author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['sport'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sport'))
      ->setDescription(t('Sport entity reference'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'sport')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'entity_reference',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nom'))
      ->setDescription(t('Nom de la compétition.'))
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    //Création d'un champ booléen avec un widget checkbox
    $fields['classement'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Classement activé'))
      ->setDescription(t('Doit-on calculer le classement entre les équipes pour cette competitions'))
      //est-ce que l'on autorise les modifications d'affichage dans le formulaire
      ->setDisplayConfigurable('form', TRUE)
      //est-ce que l'on autorise les modifications d'affichage en frontoffice
      ->setDisplayConfigurable('view', TRUE)
      //définition de la valeur par défaut
      ->setDefaultValue(TRUE)
      //définition des options d'affichage par défaut (front => view, back => form)
      ->setDisplayOptions('form', array(
        //on veut une checkbox
        'type' => 'boolean_checkbox',
        'weight' => - 4,
        'settings' => array(
          'display_label' => TRUE,
        )
      ))
      ->setDisplayOptions('view', array('type' => 'hidden'));

    //Création d'une propriété "liste de texte"
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Statut de la compétition'))
      ->setRequired(true)
      ->setSettings(array(
        //définition des valeurs possible
        'allowed_values' => self::$status_allowed_value,
      ))
      //définition de la valeur par défaut
      ->setDefaultValue(self::$status_default_value)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['betting_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Betting type'))
      ->setRequired(true)
      ->setSettings(array(
        //définition des valeurs possible
        'allowed_values' => self::$betting_types,
      ))
      //définition de la valeur par défaut
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['points_score_found'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Points when the game\'s score is found'))
      ->setRequired(true)
      ->setDefaultValue(self::$points_default['points_score_found'])
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array('type' => 'hidden'))
      ->setDisplayOptions('form', array('type' => 'number'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['points_winner_found'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Points when the game\'s winner is found'))
      ->setRequired(true)
      ->setDefaultValue(self::$points_default['points_winner_found'])
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array('type' => 'hidden'))
      ->setDisplayOptions('form', array('type' => 'number'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['points_participation'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Points when nothing is right.'))
      ->setRequired(true)
      ->setDefaultValue(self::$points_default['points_participation'])
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array('type' => 'hidden'))
      ->setDisplayOptions('form', array('type' => 'number'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
