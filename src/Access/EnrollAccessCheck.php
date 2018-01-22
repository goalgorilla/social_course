<?php

namespace Drupal\social_course\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

class EnrollAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $parameters = $route_match->getParameters();
    $group = $parameters->get('group');
    $course_wrapper = \Drupal::service('social_course.course_wrapper');

    if (in_array($group->bundle(), $course_wrapper::getAvailableBundles())) {
      return AccessResult::allowedIf(!$group->field_course_opening_status->value);
    }

    return AccessResult::neutral();
  }

}
