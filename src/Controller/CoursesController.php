<?php

/**
 * @file
 * Contains \Drupal\social_course\Controller\CoursesController.
 */

namespace Drupal\social_course\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\CourseEnrollmentInterface;
use Drupal\social_course\CourseWrapper;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Courses controller.
 */
class CoursesController extends ControllerBase {

  /**
   * Controller action.
   *
   * @return mixed
   *   Renderable drupal array.
   */
  public function content() {
    $content = [];
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();

    foreach ($this->entityTypeManager()->getStorage('group_type')->loadMultiple() as $type) {
      if (in_array($type->id(), $bundles)) {
        $content[$type->id()] = $type;
      }
    }

    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity.group.add_form', ['group_type' => $type->id()]);
    }

    return [
      '#theme' => 'course_add_list',
      '#content' => $content,
    ];
  }

  /**
   * Determines if user has access to course creation page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public static function access(AccountInterface $account) {
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $bundles = $course_wrapper::getAvailableBundles();

    foreach ($bundles as $bundle) {
      if ($account->hasPermission("create {$bundle} group")) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Callback of "/group/{group}/section/{node}/start".
   */
  public function startSection(GroupInterface $group, NodeInterface $node) {
    // Get first material.
    $material = $node->get('field_section_content')->get(0)->entity;

    // Join user to course.
    $storage = \Drupal::entityTypeManager()->getStorage('course_enrollment');
    $storage->create([
      'gid' => $group->id(),
      'sid' => $node->id(),
      'mid' => $material->id(),
      'status' => CourseEnrollmentInterface::IN_PROGRESS,
    ])->save();

    drupal_set_message($this->t('You have successfully enrolled'));

    $tags = $group->getCacheTags();
    $tags = Cache::mergeTags($tags, $node->getCacheTags());
    $tags = Cache::mergeTags($tags, $material->getCacheTags());
    Cache::invalidateTags($tags);

    return $this->redirect('entity.node.canonical', [
      'node' => $material->id(),
    ]);
  }

  /**
   * Access callback function for "/group/{group}/section/{node}/start" page.
   */
  public function startSectionAccess(GroupInterface $group, NodeInterface $node) {
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $course_wrapper->setCourse($group);
    return $course_wrapper->sectionAccess($node, \Drupal::currentUser(), 'start');
  }

  /**
   * Callback of "/group/{group}/section/{node}/next".
   */
  public function nextMaterial(GroupInterface $group, NodeInterface $node) {
    $storage = \Drupal::entityTypeManager()->getStorage('course_enrollment');
    $field = $node->get('field_section_content');
    /** @var \Drupal\social_course\CourseWrapper $course_wrapper */
    $course_wrapper = \Drupal::service('social_course.course_wrapper');
    $course_wrapper->setCourse($group);
    $current_material = NULL;
    $next_material = NULL;
    $account = \Drupal::currentUser();

    foreach ($field->getValue() as $key => $value) {
      $course_enrollment = $storage->loadByProperties([
        'gid' => $group->id(),
        'sid' => $node->id(),
        'mid' => $value['target_id'],
        'uid' => $account->id(),
      ]);

      if (!$course_enrollment) {
        $storage->create([
          'gid' => $group->id(),
          'sid' => $node->id(),
          'mid' => $value['target_id'],
          'status' => CourseEnrollmentInterface::IN_PROGRESS,
        ])->save();
        $next_material = $field->get($key)->entity;
        break;
      }
      elseif (!$current_material) {
        $course_enrollment = current($course_enrollment);

        // Set the correct status for all previous materials.
        if ($course_enrollment->getStatus() !== CourseEnrollmentInterface::FINISHED) {
          $course_enrollment->setStatus(CourseEnrollmentInterface::FINISHED);
          $course_enrollment->save();

          if ($field->get($key + 1)) {
            $current_material = $field->get($key)->entity;
            $next_material = $field->get($key + 1)->entity;
          }
        }
      }
    }

    // Redirect after finishing course.
    if (!$group->get('field_course_redirect_url')->isEmpty()) {
      $uri = $group->get('field_course_redirect_url')->uri;
      $parsed_url = parse_url($uri);

      if (isset($parsed_url['host'])) {
        $response = new TrustedRedirectResponse($uri);
      }
      else {
        try {
          $url = Url::fromUri($uri);
        } catch (\InvalidArgumentException $exception) {
          $url = Url::fromUserInput($uri);
        }
        $response = new RedirectResponse($url->toString());
      }
    }
    else {
      $response = $this->redirect('entity.group.canonical', [
        'group' => $group->id(),
      ]);
    }

    // Check if user has already seen last material.
    if (!$next_material) {
      $next_section = $course_wrapper->getSection($node, 1);

      if ($next_section) {
        $course_enrollment = $storage->loadByProperties([
          'gid' => $group->id(),
          'sid' => $next_section->id(),
          'uid' => $account->id(),
        ]);
        $course_enrollment = current($course_enrollment);

        if (!$course_enrollment || $course_enrollment->getStatus() !== CourseEnrollmentInterface::FINISHED) {
          drupal_set_message($this->t('You have successfully finished the @title section', [
            '@title' => $node->label(),
          ]));
        }

        // Redirect after finishing section.
        $current_section = $course_wrapper->getSection($node, 0);
        if (!$current_section->get('field_section_redirect_url')->isEmpty()) {
          $uri = $current_section->get('field_section_redirect_url')->uri;
          $parsed_url = parse_url($uri);

          if (isset($parsed_url['host'])) {
            $response = new TrustedRedirectResponse($uri);
          }
          else {
            try {
              $url = Url::fromUri($uri);
            } catch (\InvalidArgumentException $exception) {
              $url = Url::fromUserInput($uri);
            }
            $response = new RedirectResponse($url->toString());
          }
        }
        else {
          $response = self::nextMaterial($group, $next_section);
        }

      }
    }
    else {
      $response = $this->redirect('entity.node.canonical', [
        'node' => $next_material->id(),
      ]);
    }

    $tags = $group->getCacheTags();
    $tags = Cache::mergeTags($tags, $node->getCacheTags());

    if ($next_material) {
      $tags = Cache::mergeTags($tags, $course_wrapper->getMaterial($next_material, -1)->getCacheTags());
    }

    Cache::invalidateTags($tags);

    return $response;
  }

  /**
   * Access callback of "/group/{group}/section/{node}/next" page.
   */
  public function nextMaterialAccess(GroupInterface $group, NodeInterface $node) {
    $bunles = CourseWrapper::getAvailableBundles();

    // Forbid if group is not a course.
    if (!in_array($group->bundle(), $bunles)) {
      return AccessResult::forbidden();
    }

    // Forbid if node is not a section.
    if ($node->bundle() != 'section') {
      return AccessResult::forbidden();
    }

    // Forbid if section does not contain materials.
    $field = $node->get('field_section_content');

    if ($field->isEmpty()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
