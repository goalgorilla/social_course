<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_course\Form\CourseJoinAnonymousForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Course join routes.
 */
class CourseJoinController extends ControllerBase {

  /**
   * GroupRequestController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    EntityFormBuilderInterface $entity_form_builder
  ) {
    $this->formBuilder = $form_builder;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Callback to request membership for anonymous.
   */
  public function anonymousRequestMembership(GroupInterface $group) {
    $request_form = $this->formBuilder()->getForm(CourseJoinAnonymousForm::class, $group);

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Join a "%group_title" course', ['%group_title' => $group->label() ]), $request_form, [
      'width' => '337px',
      'dialogClass' => 'social_group-popup social_group-popup--anonymous',
    ]));

    return $response;
  }

}
