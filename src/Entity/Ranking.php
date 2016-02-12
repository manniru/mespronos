<?php

namespace Drupal\mespronos\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mespronos\RankingInterface;
use Drupal\mespronos\MPNEntityInterface;

abstract class Ranking extends MPNContentEntityBase implements MPNEntityInterface,RankingInterface {

  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  public function setGameBetted($nb_games_betted) {
    $this->set('games_betted', $nb_games_betted);
    return $this;
  }

  public function getGameBetted() {
    return $this->get('games_betted')->value;
  }

  public function setPoints($points) {
    $this->set('points', $points);
    return $this;
  }

  public function getPoints() {
    return $this->get('points')->value;
  }

  public function getPosition() {
    return $this->get('position')->value;
  }


  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $better
   * @return \Drupal\mespronos\Entity\RankingDay
   */
  public static function getRankingForBetter(\Drupal\Core\Session\AccountProxyInterface $better,$type) {
    $storage = \Drupal::entityManager()->getStorage($type);
    $query = \Drupal::entityQuery($type);
    $query->condition('better', $better->id());
    $query->sort('position','ASC');
    $ids = $query->execute();
    $id = array_pop($ids);
    $rankings = $storage->load($id);
    return $rankings;
  }
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['better'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Better'))
      ->setDescription(t('The user ID of the Bet entity author.'))
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
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['games_betted'] = BaseFieldDefinition::create('integer')
      ->setLabel('Games betted')
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'integer',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 6,
      ));

    $fields['points'] = BaseFieldDefinition::create('integer')
      ->setLabel('Points won')
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'integer',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 6,
      ));

    $fields['position'] = BaseFieldDefinition::create('integer')
      ->setLabel('Position')
      ->setRevisionable(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'integer',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 6,
      ));

    return $fields;
  }

}