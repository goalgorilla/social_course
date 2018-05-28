<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GroupController extends EntityController {

  /**
   * Callback function of group page.
   */
  public function canonical(GroupInterface $group) {
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();
    $url = Url::fromRoute('social_course.group_stream', [
      'group' => $group->id(),
    ]);

    if (!in_array($group->bundle(), $bundles) && $url->access()) {
      return new RedirectResponse($url->toString());
    }

    return $this->redirect('view.group_information.page_group_about', [
      'group' => $group->id(),
    ]);
  }

  /**
   * Access callback of the group page.
   */
  public function access(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $access = AccessResult::forbidden();

    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();

    // Allow if group does not have field that regulates access or it is published.
    if (!$group->hasField('status') || $group->get('status')->value) {
      $access = AccessResult::allowed();
    }
    // Allow if user has the 'bypass group access' permission.
    elseif ($account->hasPermission('bypass group access')) {
      $access = AccessResult::allowed();
    }
    // Allow if user has access to all unpublished groups.
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

    $field = 'field_course_opening_status';
    if ($group->hasField($field) && !$group->get($field)->isEmpty() && !$group->get($field)->value) {
      $message = $this->t('Course sections can only be accessed after the course starts. This course can only be enrolled before the course has started.');
      drupal_set_message($message, 'warning');
    }
    elseif (in_array($group->bundle(), $bundles) && !$group->getMember($account)) {
      $message = $this->t('Course sections and other information can only be accessed after enrolling the course.');
      drupal_set_message($message, 'warning');
    }

    return $access
      ->cachePerPermissions()
      ->cachePerUser();
  }

  /**
   * Callback of "/stream" page.
   */
  public function stream() {
    return [
      '#markup' => '',
    ];
  }

  /**
   * Access callback of "/stream" page.
   */
  public function streamAccess(GroupInterface $group) {
    return AccessResult::allowedIf($group->bundle() !== 'course_basic');
  }

}
