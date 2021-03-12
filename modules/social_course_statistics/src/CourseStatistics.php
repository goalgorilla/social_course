<?php

namespace Drupal\social_course_statistics;

use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\CourseWrapper;
use Drupal\social_course\Entity\CourseEnrollmentInterface;

/**
 * Class CourseStatistics.
 *
 * @package Drupal\social_course_statistics
 */
class CourseStatistics {

  use StringTranslationTrait;

  /**
   * Course enrollment table.
   */
  const COURSE_ENROLLMENT_TABLE = 'course_enrollment';

  /**
   * The default database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapper
   */
  protected $courseWrapper;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the course statistics service.
   *
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   *   The entity field manager.
   */
  public function __construct(
    Connection $connection,
    CourseWrapper $course_wrapper,
    RouteMatchInterface $route_match
  ) {
    $this->connection = $connection;
    $this->courseWrapper = $course_wrapper;
    $this->routeMatch = $route_match;
  }

  /**
   * Statistics available roles.
   */
  public static function statisticsRolesAvailable() {
    return [
      'administrator',
      'sitemanager',
      'contentmanager',
    ];
  }

  /**
   * Allowed group types.
   */
  public static function allowedGroupTypes() {
    return [
      'course_advanced-group_membership',
      'course_basic-group_membership',
    ];
  }

  /**
   * Course members id's.
   */
  public function getCourseMembers(GroupInterface $group) {
    $allowed_group = $this::allowedGroupTypes();

    $table_name = 'group_content_field_data';
    $uids = $this->connection->select($table_name)
      ->fields($table_name, ['uid'])
      ->condition($table_name . '.gid', $group->id())
      ->condition($table_name . '.type', $allowed_group, 'IN')
      ->execute()
      ->fetchCol();

    return empty($uids) ? [] : $uids;
  }

  /**
   * Course sections status.
   */
  public function getCourseSectionsStatus(GroupInterface $group, AccountInterface $account) {
    $course_wrapper = $this->courseWrapper;
    $course_wrapper->setCourse($group);

    $sections = $course_wrapper->getSections();
    $course_sections = count($sections);

    $finished_sections = 0;
    foreach ($sections as $section) {
      $status = $course_wrapper->getSectionStatus($section, $account);
      if ($status === CourseEnrollmentInterface::FINISHED) {
        $finished_sections++;
      }
    }

    $status = $this->t('@finished_sections/@course_sections Sections finished', [
      '@finished_sections' => $finished_sections,
      '@course_sections' => $course_sections,
    ]);
    if ($course_sections && ($course_sections === $finished_sections)) {
      $status = $this->getStatusWithIcon();
    }

    return $status;
  }

  /**
   * The course start date for a specific user.
   */
  public function getCourseStartDatePerUser(GroupInterface $group, AccountInterface $account) {
    $table_name = $this::COURSE_ENROLLMENT_TABLE;
    return $this->connection->select($table_name)
      ->fields($table_name, ['created'])
      ->condition($table_name . '.gid', $group->id())
      ->condition($table_name . '.uid', $account->id())
      ->orderBy($table_name . '.created')
      ->execute()
      ->fetchField();
  }

  /**
   * The course last active for a specific user.
   */
  public function getCourseLastActivePerUser(GroupInterface $group, AccountInterface $account, NodeInterface $node = NULL) {
    $table_name = $this::COURSE_ENROLLMENT_TABLE;
    $query = $this->connection->select($table_name)
      ->fields($table_name, ['changed'])
      ->condition($table_name. '.gid', $group->id())
      ->condition($table_name. '.uid', $account->id());

    if (!is_null($node)) {
      $query->condition($table_name . '.sid', $node->id());
    }

    return $query->orderBy($table_name . '.changed', 'DESC')
      ->execute()
      ->fetchField();
  }

  /**
   * Check if it is a statistics route.
   */
  public function isCourseStatisticsRoute() {
    $route = \Drupal::routeMatch()->getRouteName();
    $statistics_routes = [
      'view.course_statistics.page_course_statistics',
      'view.course_sections_statistics.page_1',
    ];

    return in_array($route, $statistics_routes);
  }

  /**
   * Check if it is a statistics route.
   */
  public function getStatusWithIcon() {
    return [
      '#type' => 'inline_template',
      '#template' => '<svg><use xlink:href="#icon-finished"></use></svg> {{ status }}',
      '#context' => [
        'status' => $this->t('Finished'),
      ]
    ];
  }

}
