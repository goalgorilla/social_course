<?php

namespace Drupal\social_course_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CourseStatisticsHeroBlock' block.
 *
 * @Block(
 *   id = "course_statistics_hero_block",
 *   admin_label = @Translation("Course statistics hero block"),
 *   context_definitions = {
 *    "group" = @ContextDefinition("entity:group", required = FALSE)
 *  }
 * )
 */
class CourseStatisticsHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a GroupHeroBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $group = _social_group_get_current_group();

    if (!empty($group)) {
      // Content.
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'statistics');

      $build['content'] = $content;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $group = _social_group_get_current_group();
    if (!empty($group)) {
      return Cache::mergeTags(parent::getCacheTags(), ['group_block:' . $group->id()]);
    } else {
      return parent::getCacheTags();
    }
  }

}
