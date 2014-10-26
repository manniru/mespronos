<?php

/**
 * @file
 * Contains Drupal\mespronos_leagues\Entity\League.
 */

namespace Drupal\mespronos_leagues\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mespronos_leagues\LeagueInterface;
use Drupal\user\UserInterface;

/**
 * Defines the League entity.
 *
 * @ingroup mespronos_leagues
 *
 * @ContentEntityType(
 *   id = "league",
 *   label = @Translation("League entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\mespronos_leagues\Entity\Controller\LeagueListController",
 *     "views_data" = "Drupal\mespronos_leagues\Entity\LeagueViewsData",
 *
 *
 *     "form" = {
 *       "add" = "Drupal\mespronos_leagues\Entity\Form\LeagueForm",
 *       "edit" = "Drupal\mespronos_leagues\Entity\Form\LeagueForm",
 *       "delete" = "Drupal\mespronos_leagues\Entity\Form\LeagueDeleteForm",
 *     },
 *     "access" = "Drupal\mespronos_leagues\LeagueAccessControlHandler",
 *   },
 *   base_table = "league",
 *   admin_permission = "administer League entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "league.edit",
 *     "admin-form" = "league.settings",
 *     "delete-form" = "league.delete"
 *   },
 *   field_ui_base_route = "league.settings"
 * )
 */
class League extends ContentEntityBase implements LeagueInterface
{

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the League entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the League entity.'))
      ->setReadOnly(TRUE);


    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nom'))
      ->setDescription(t('Nom de la compétition'))
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
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['classement'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Classement activé'))
      ->setDescription(t('Doit-on calculer le classement entre les équipes pour cette competitions'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        )
      ))
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ));

    $fields['status'] = BaseFieldDefinition::create('list_string')
       ->setLabel(t('Statut du championnat'))
      ->setRequired(true)
       ->setSettings(array(
         'allowed_values' => array(
           'active' => 'En cours',
           'over' => 'Terminé',
           'archived' => 'Archivé',
         ),
       ))
      ->setDefaultValue('active')
       ->setDisplayOptions('view', array(
         'type' => 'hidden',
       ))
       ->setDisplayOptions('form', array(
         'type' => 'options_select',
       ))
       ->setDisplayConfigurable('form', TRUE)
       ->setDisplayConfigurable('view', TRUE);


    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of League entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));
    return $fields;
  }
}
