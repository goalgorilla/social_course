<?php

namespace Drupal\social_course\Plugin\Block;

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
    $node = $this->getContextValue('node');

    if ($node) {
      $translation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      $title = $node->getTitle();
      $group_link = NULL;

      return [
        '#theme' => 'course_material_hero',
        '#title' => $title,
        '#node' => $node,
        '#section_class' => 'page-title',
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
