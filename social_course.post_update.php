<?php
/**
 * @file
 * Post update hooks for the Social Course module.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Toggle search indexes to fix fields in index.
 */
function social_course_post_update_0001_fix_search_indexes() {
  // The search indexes are disabled and re-enabled to ensure that the added/
  // changed fields are properly added to the database.
  try {
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage("search_api_index");
  }
  // If the search_api is not available then we're done.
  catch (PluginNotFoundException $e) {
    return;
  }

  // Load all indexes that are somehow linked to groups
  // (including group content).
  $indices = $storage->loadMultiple([
    'search_api.index.social_all',
    'search_api.index.social_content',
    'search_api.index.social_groups',
  ]);

  /** @var \Drupal\search_api\Entity\Index $index */
  foreach ($indices as $index) {
    // If an index is already unused then we can skip it.
    if (!$index->status()) {
      continue;
    }

    $index->disable()->save();
    $index->enable()->save();
  }
}
