<?php

namespace Drupal\mespronos_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mespronos_group\Entity\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Class GroupController.
 *
 * @package Drupal\mespronos_group\Controller
 */
class GroupController extends ControllerBase {


  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $renderer;
  /**
   * {@inheritdoc}
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function groupList() {
    $groups = self::loadGroups(true);
    $groups = self::parseGroupsForListing($groups);
    $user = \Drupal::currentUser();
    return [
      '#theme' => 'group-list',
      '#groups' => $groups,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [ 'user:'.$user->id(),'groups'],
        'max-age' => '120',
      ],
    ];
  }

  public static function loadGroups($onlyActive = true) {
    $storage = \Drupal::entityManager()->getStorage('group');
    $query =  \Drupal::entityQuery('group');
    if($onlyActive) {
      $query->condition('status',NODE_PUBLISHED);
    }
    $query->sort('name');

    $ids = $query->execute();
    if(count($ids)>0) {
      return $storage->loadMultiple($ids);
    }
    else {
      return [];
    }
  }

  /**
   * @param Group[] $groups
   * @return array
   */
  public static function parseGroupsForListing(&$groups) {
    $render_controller = \Drupal::entityManager()->getViewBuilder('group');
    $user = \Drupal::currentUser();
    $user = User::load($user->id());
    $groups_return = [];
    foreach ($groups as $group) {
      $groups_return[$group->id()] = [
        'entity' => $render_controller->view($group,'teaser'),
        'member' => t('@nb members',['@nb'=>$group->getMemberNumber()]),
        'is_member' => $group->isMemberOf($user),
        'display_join_link' => $user->id()>0,
        'join_url' => Url::fromRoute('mespronos_group.group.join',['group'=>$group->id()]),
      ];
    }
    return $groups_return;
  }

  public function joinTitle(Group $group) {
    return t('Join groupe %group_name',['%group_name'=>$group->label()]);
  }

  public function join(Group $group) {
    $form = \Drupal::formBuilder()->getForm('Drupal\mespronos_group\Form\GroupJoiningForm',$group);
    return $form;
  }
}