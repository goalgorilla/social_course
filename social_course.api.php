<?php

/**
 * @file
 * Hooks provided by the social_course module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter content types which can be added to course sections.
 *
 * @param array $content_types
 *   List of machine names of content types.
 *
 * @ingroup social_course_api
 */
function hook_social_course_material_types_alter(&$content_types) {
  if (in_array('video', $content_types) && !in_array('audio', $content_types)) {
    $content_types[] = 'audio';
  }
}

/**
 * Provide a method to alter node IDs in the course section.
 *
 * @param array $node_ids
 *   List of node IDs of course section.
 *
 * @ingroup social_course_api
 */
function hook_social_course_materials_alter(&$node_ids) {
  $node_ids = array_diff($node_ids, [5, 7]);
}

/**
 * @} End of "addtogroup hooks".
 */
