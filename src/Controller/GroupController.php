<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;

class GroupController extends EntityController {

  /**
   * Callback function of group page.
   */
  public function canonical(GroupInterface $group) {
    $bundles = ['course_basic', 'course_advanced'];

    if (in_array($group->bundle(), $bundles)) {
      return $this->redirect('view.group_information.page_group_about', [
        'group' => $group->id(),
      ]);
    }
    else {
      return $this->redirect('social_course.group_stream', [
        'group' => $group->id(),
      ]);
    }
  }

  /**
   * Access callback of the group page.
   */
  public function access(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $access = AccessResult::forbidden();

    // Allow if group does not have field that regulates access or it is published.
    if (!$group->hasField('status') || $group->get('status')->value) {
      $access = AccessResult::allowed();
    }
    // Allow if user has the 'bypass group access' permission.
    elseif ($account->hasPermission('bypass group access')) {
      $access = AccessResult::allowed();
    }
    // Allow if user has access to all unpublushed groups.
    elseif ($account->hasPermission('view unpublished groups')) {
      $access = AccessResult::allowed();
    }
    // Allow if user is an author of the group and has access to view
    // own unpublished groups.
    elseif ($account->hasPermission('view own unpublished groups')) {
      if ($group->getOwnerId() === $account->id()) {
        $access = AccessResult::allowed();
      }
    }

    return $access
      ->cachePerPermissions()
      ->cachePerUser();
  }

  /**
   * Callback of "/stream" page.
   *
   * @return array
   */
  public function stream() {
    return [
      '#markup' => '',
    ];
  }

  /**
   * Access callback of "/stream" page.
   *
   * @return array
   */
  public function streamAccess(GroupInterface $group) {
    return AccessResult::allowedIf($group->bundle() !== 'course_basic');
  }

}
