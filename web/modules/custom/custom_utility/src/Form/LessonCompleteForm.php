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
final class LessonCompleteForm extends FormBase {

  /**
   * Current User Object.
   */
  protected AccountProxyInterface $currentUser;
  /**
   * Common Service Object.
   */
  protected CommonService $commonService;
  /**
   * Database connection Object.
   */
  protected Connection $database;
  /**
   * Symfony Request Object.
   */
  protected Request $request;

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
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
    return 'custom_utility_lesson_complete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the current Node ID.

    /** @var \Drupal\node\NodeInterface */
    $node = $this->request->attributes->get('node');
    if ($node instanceof NodeInterface && $node->bundle() == 'lesson') {

      $lesson_id = $node->id();
      $form['lesson_id'] = [
        '#type' => 'hidden',
        '#value' => $lesson_id,
      ];

      $course_id = $node->field_course[0]->entity->id();
      $form['course_id'] = [
        '#type' => 'hidden',
        '#value' => $course_id,
      ];
      $query = $this->commonService->checkCourseLessonCompleted($course_id, $lesson_id);
      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $query ? 'Already completed!' : 'Mark the lesson as completed',
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
    /** @var \Drupal\node\NodeInterface */
    $node = $this->request->attributes->get('node');
    $course_obj = $node->field_course[0]->entity;

    $course_id = $form_state->getValue('course_id');
    $lesson_id = $form_state->getValue('lesson_id');
    // Check if an entry already exists in the custom table with the same id.
    $count = $this->commonService->checkCourseLessonCompleted($course_id, $lesson_id);

    if ($count !== FALSE) {
      $this->messenger()->addStatus($this->t('Already marked as completed.'));
    }
    else {
      $this->commonService->addCourseLessonEntry($course_id, $lesson_id, TRUE);
      $this->messenger()->addStatus($this->t('Lesson Completed successfully.'));
      if ($this->commonService->getCoursePercentage($course_obj) == 100) {
        $this->commonService->addCourseCompletionEntry($course_id, 1);
        $form_state->setRedirect('entity.node.canonical', ['node' => $course_id]);
        $this->messenger()->addStatus($this->t('Redirected to Course page as all the lessons are completed.'));
      }
    }
  }

}
