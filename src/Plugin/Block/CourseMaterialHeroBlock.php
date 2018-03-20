<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\taxonomy\Entity\Term;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Block\Plugin\Block\PageTitleBlock;

/**
 * Provides a 'CourseMaterialHeroBlock' block.
 *
 * @Block(
 *   id = "course_material_hero",
 *   admin_label = @Translation("Course material hero block"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseMaterialHeroBlock extends PageTitleBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $parent_course_type = NULL;
    $node = $this->getContextValue('node');

    if ($node) {
      $translation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      $title = $node->getTitle();

      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      if (!$course_wrapper->getCourse()->get('field_course_type')->isEmpty()) {
        $parent_course_type = Term::load($course_wrapper->getCourse()->field_course_type->target_id)->getName();
      }
      return [
        '#theme' => 'course_material_hero',
        '#title' => $title,
        '#node' => $node,
        '#section_class' => 'page-title',
        '#parent_course_type' => $parent_course_type,
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

}
