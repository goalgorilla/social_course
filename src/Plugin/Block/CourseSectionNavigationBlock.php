<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag_test_plugins\Plugin\Flag\AccessDenied;
use Drupal\group\Entity\GroupInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a 'CourseSectionNavigationBlock' block.
 *
 * @Block(
 *   id = "course_section_navigation",
 *   admin_label = @Translation("Course section navigation block"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseSectionNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    if ($node) {
      $translation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $parent_section = $course_wrapper->getSectionFromMaterial($node);
      $items = [];

      foreach ($course_wrapper->getSections() as $section) {
        $item = [
          'label' => $section->label(),
          'url' => FALSE,
        ];

        if ($course_wrapper->sectionAccess($section, \Drupal::currentUser(), 'view')->isAllowed()) {
          $item['url'] = $section->toUrl();
        }

        if ($section->id() !== $parent_section->id()) {
          $items[] = $item;
        }
      }

      return [
        '#theme' => 'course_section_navigation',
        '#items' => $items,
      ];
    }
    else {
      $request = \Drupal::request();

      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);

        return [
          '#type' => 'page_title',
          '#title' => $title,
        ];
      }
      else {
        return [
          '#type' => 'page_title',
          '#title' => '',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    if ($node = $this->getContextValue('node')) {
      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $tags = Cache::mergeTags($tags, $course_wrapper->getCourse()->getCacheTags());
      $tags = Cache::mergeTags($tags, $course_wrapper->getSectionFromMaterial($node)->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($node = $this->getContextValue('node')) {
      $group = \Drupal::service('social_course.course_wrapper')
        ->setCourseFromMaterial($node)
        ->getCourse();

      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      if (count($course_wrapper->getSections()) > 1) {
        return AccessResult::allowedIf($group instanceof GroupInterface);
      }
      else {
        return AccessResult::forbidden();
      }
    }

    return parent::blockAccess($account);
  }

}
