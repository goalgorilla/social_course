<?php

namespace Drupal\social_course_advanced_request\Controller;

use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group_request\Controller\GroupRequestController;

/**
 * Returns responses for Group request routes.
 */
class AdvancedCourseRequestController extends GroupRequestController {

  /**
   * Callback to cancel the request of membership.
   */
  public function cancelRequest(GroupInterface $group) {
    $content_type_config_id = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $requests = $this->entityTypeManager()->getStorage('group_content')->loadByProperties([
      'type' => $content_type_config_id,
      'gid' => $group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);

    foreach ($requests as $request) {
      $request->delete();
    }

    $this->messenger()->addMessage($this->t('Membership has been successfully denied.'));
    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->redirect('view.group_information.page_group_about', ['group' => $group->id()]);
  }

}
