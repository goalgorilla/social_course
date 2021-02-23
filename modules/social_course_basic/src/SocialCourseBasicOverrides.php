<?php

namespace Drupal\social_course_basic;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialCourseBasicOverrides.
 */
class SocialCourseBasicOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.group_manage_members';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'value' => [
                    'course_basic-group_membership' => 'course_basic-group_membership',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'core.entity_form_display.group.course_basic.default';

    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_settings.children');
      $children[] = 'field_flexible_group_visibility';

      $content = $config->get('content');
      $content['field_flexible_group_visibility'] = [
        'weight' => 100,
        'settings' => [
          'display_label' => TRUE,
        ],
        'third_party_settings' => [],
        'type' => 'options_buttons',
        'region' => 'content',
      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_settings' => [
              'children' => $children,
            ],
          ],
        ],
        'content' => $content,
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialCourseBasicOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
