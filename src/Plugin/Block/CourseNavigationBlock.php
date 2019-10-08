<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\CourseWrapperInterface;
use Drupal\social_course\Entity\CourseEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CourseNavigationBlock' block.
 *
 * @Block(
 *   id = "course_navigation",
 *   admin_label = @Translation("Course navigation block"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapperInterface
   */
  protected $courseWrapper;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a CourseNavigationBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\social_course\CourseWrapperInterface $course_wrapper
   *   The course wrapper.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityRepositoryInterface $entity_repository,
    CourseWrapperInterface $course_wrapper,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->courseWrapper = $course_wrapper;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('social_course.course_wrapper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $account = $this->currentUser;

    if ($node instanceof NodeInterface && $node->id()) {
      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $parent_section = $course_wrapper->getSectionFromMaterial($node);

      // Prepares variable with the course sections.
      $course_sections = [];
      /** @var \Drupal\node\NodeInterface $section */
      foreach ($course_wrapper->getSections() as $section) {
        $section_item = [
          'attributes' => new Attribute(),
          'parent' => FALSE,
          'number' => $course_wrapper->getSectionNumber($section) + 1,
          'parts_count' => 0,
          'parts_finished' => 0,
        ];

        // Set the values for progress indicator.
        $section_item['parts_count'] = count($course_wrapper->getMaterials($section));
        $section_item['parts_finished'] = count($course_wrapper->getFinishedMaterials($section, $account));

        $storage = \Drupal::entityTypeManager()->getStorage('course_enrollment');
        $entities = $storage->loadByProperties([
          'uid' => $this->currentUser->id(),
          'gid' => $course_wrapper->getCourse()->id(),
          'sid' => $section->id(),
        ]);

        // Adds status label for sections.
        if (!$entities) {
          $section_item['section_status'] = 'not-started';
          $section_item['allowed_start'] = $course_wrapper->sectionAccess($section, $this->currentUser, 'start')->isAllowed();
        }
        else {
          foreach ($entities as $entity) {
            if ($entity->getStatus() === CourseEnrollmentInterface::IN_PROGRESS) {
              $section_item['section_status'] = 'in-progress';
              $section_item['section_current'] = $entity->get('mid')->target_id;
              break;
            }
            elseif ($entity->getStatus() === CourseEnrollmentInterface::FINISHED) {
              $section_item['section_status'] = 'finished';
              $materials = $course_wrapper->getMaterials($section);
              $section_item['section_current'] = current($materials)->id();
            }
          }
        }

        // Adds access label for sections.
        $access = $this->courseWrapper->sectionAccess($section, $this->currentUser, 'view');
        if ($access->isAllowed()) {
          $section_item['label'] = $section->toLink()->toRenderable();
          // Mark the current section link as active.
          if ($section->id() === $parent_section->id()) {
            $section_item['attributes']->addClass('active');
            $section_item['parent'] = TRUE;
          }
        }
        else {
          $section_item['label'] = $section->label();
          $section_item['attributes']->addClass('not-allowed');
        }

        $course_sections[] = $section_item;
      }

      // Prepares variable with the material items of the active section.
      $items = [];
      $course_enrollment_storage = \Drupal::entityTypeManager()->getStorage('course_enrollment');
      $course_enrollments = $course_enrollment_storage->loadByProperties([
        'sid' => $parent_section->id(),
        'uid' => $this->currentUser->id(),
        'gid' => $course_wrapper->getCourse()->id(),
      ]);

      foreach ($course_enrollments as $key => $course_enrollment) {
        unset($course_enrollments[$key]);
        $course_enrollments[$course_enrollment->get('mid')->target_id] = $course_enrollment;
      }

      /** @var \Drupal\node\NodeInterface $material */
      foreach ($course_wrapper->getMaterials($parent_section) as $material) {
        $item = [
          'label' => $material->label(),
          'url' => FALSE,
          'type' => $material->bundle(),
          'number' => $course_wrapper->getMaterialNumber($material) + 1,
          'active' => FALSE,
          'finished' => FALSE,
        ];

        if ($material->id() === $node->id()) {
          $item['active'] = TRUE;
        }

        if (isset($course_enrollments[$material->id()]) && $course_enrollments[$material->id()]->getStatus() === CourseEnrollmentInterface::FINISHED) {
          $item['finished'] = TRUE;
        }

        if ($course_wrapper->materialAccess($material, $this->currentUser, 'view')->isAllowed()) {
          $item['url'] = $material->toUrl();
        }

        $items[] = $item;
      }

      return [
        '#theme' => 'course_navigation',
        '#items' => $items,
        '#course_sections' => $course_sections,
        '#parent_course' => [
          'label' => $course_wrapper->getCourse()->label(),
          'url' => $course_wrapper->getCourse()->toUrl(),
        ],
        '#parent_section' => [
          'label' => $section->label(),
          'url' => $section->toUrl(),
        ],
      ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $node = $this->getContextValue('node');

    if ($node instanceof NodeInterface && $node->id()) {
      $this->courseWrapper->setCourseFromMaterial($node);
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getCourse()->getCacheTags());
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getSectionFromMaterial($node)->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');

    if ($node instanceof NodeInterface && $node->id()) {
      $this->courseWrapper->setCourseFromMaterial($node);

      if ($this->courseWrapper->getSections()) {
        $group = $this->courseWrapper->getCourse();

        return AccessResult::allowedIf($group instanceof GroupInterface);
      }
    }

    return AccessResult::forbidden();
  }

}
