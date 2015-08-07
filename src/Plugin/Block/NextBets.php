<?php

/**
 * @file
 * Contains Drupal\mespronos\Plugin\Block\NextBets.
 */

namespace Drupal\mespronos\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mespronos\Entity\Controller\DayController;
use Drupal\mespronos\Entity\Controller\UserInvolveController;
use Drupal\mespronos\Entity\Day;

/**
 * Provides a 'NextBets' block.
 *
 * @Block(
 *  id = "next_bets",
 *  admin_label = @Translation("next_bets"),
 * )
 */
class NextBets extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_days_to_display'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of days to display'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['number_of_days_to_display']) ? $this->configuration['number_of_days_to_display'] : 5,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_days_to_display'] = $form_state->getValue('number_of_days_to_display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user_uid =  \Drupal::currentUser()->id();
    dpm($user_uid);
    $user_involvements = array();
    $days = DayController::getNextDaysToBet($this->configuration['number_of_days_to_display']);

    foreach ($days  as $day) {
      $day_entity = $day->entity;
      $league_id = $day_entity->get('league')->first()->getValue()['target_id'];
      if(!isset($user_involvements[$league_id])) {
        $user_involvements[$league_id] = UserInvolveController::isUserInvolve($user_uid ,$league_id);
      }
    }

    $build = [];
    $build['next_bets_number_of_days_to_display']['#markup'] = '<p>' . $this->configuration['number_of_days_to_display'] . '</p>';

    return $build;
  }

}