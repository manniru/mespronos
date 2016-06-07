<?php

/**
 * @file
 * Contains \Drupal\pathauto\Plugin\AliasType\ForumAliasType.
 */

namespace Drupal\mespronos_group\Plugin\pathauto\AliasType;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pathauto\Plugin\pathauto\AliasType\EntityAliasTypeBase;

/**
 * A pathauto alias type plugin for forum terms.
 *
 * @AliasType(
 *   id = "group",
 *   label = @Translation("Group"),
 *   types = {"group"},
 *   provider = "mespronos_group",
 *   context = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class GroupAliasType extends EntityAliasTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ForumAliasType instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $language_manager, $entity_type_manager);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return 'group';
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePrefix() {
    return '/mespronos/groups/';
  }

  /**
   * {@inheritdoc}
   */
  public function applies($object) {
    dd($object);
    if (parent::applies($object)) {
      return true;
    }
    return FALSE;
  }

}
