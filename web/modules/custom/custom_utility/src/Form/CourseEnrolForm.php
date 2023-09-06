<?php

declare(strict_types=1);

namespace Drupal\custom_utility\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\custom_utility\CommonService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Custom Utility form.
 */
final class CourseEnrolForm extends FormBase {


  protected AccountProxyInterface $currentUser;
  protected CommonService $commonService;
  protected Connection $database;
  protected Request $request;

  public function __construct(
    AccountProxyInterface $current_user,
    CommonService $common_service,
    Connection $database,
    Request $request
  ) {
    $this->currentUser = $current_user;
    $this->commonService = $common_service;
    $this->database = $database;
    $this->request = $request;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('custom_utility.common_service'),
      $container->get('database'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_utility_course_enrol';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the current Node ID.    // Get the current Node ID.
    $node = $this->request->attributes->get('node');
    if ($node instanceof NodeInterface && $node->bundle() == 'course') {

      $course_id = $node->id();
      $lesson_id = $node->field_lessons[0]->entity->id();

      $form['course_id'] = [
        '#type' => 'hidden',
        '#value' => $course_id,
      ];
      $form['lesson_id'] = [
        '#type' => 'hidden',
        '#value' => $lesson_id,
      ];
      $query = $this->commonService->checkCourseLessonEntryExist($course_id);

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t($query ?  'Already Enrolled!' : 'Enroll For this course'),
        ],
      ];

      if ($query !== FALSE) {
        $form['actions']['submit']['#attributes'] = [
          'readonly' => 'readonly',
          'disabled' => 'disabled',
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $course_id = $form_state->getValue('course_id');
    $lesson_id = $form_state->getValue('lesson_id');
    // Check if an entry already exists in the custom table with the same node_id.
    $count = $this->commonService->checkCourseLessonEntryExist($course_id);

    if ($count !== FALSE) {
      $this->messenger()->addStatus($this->t('Already Enrolled.'));
    } else {
      $this->commonService->addCourseLessonEntry($course_id, $lesson_id);
      $this->messenger()->addStatus($this->t('Enrolled for the course successfully.'));
    }
  }
}
