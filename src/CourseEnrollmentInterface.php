<?php

namespace Drupal\social_course;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Course Enrollment entity.
 *
 * @ingroup social_course
 * @package Drupal\social_course
 */
interface CourseEnrollmentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const NOT_STARTED = -1;
  const IN_PROGRESS = 1;
  const FINISHED = 2;

  /**
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getCourse();

  /**
   * @return int
   */
  public function getCourseId();

  /**
   * @return \Drupal\node\NodeInterface
   */
  public function getSection();

  /**
   * @return int
   */
  public function getSectionId();

  /**
   * @return \Drupal\paragraphs\ParagraphInterface
   */
  public function getMaterial();

  /**
   * @return int
   */
  public function getMaterialId();

  /**
   * @return int
   */
  public function getStatus();

  /**
   * @param int $status
   *
   * @return \Drupal\social_course\CourseEnrollmentInterface
   */
  public function setStatus($status);

}
