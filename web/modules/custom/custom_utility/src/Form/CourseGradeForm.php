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
final class CourseGradeForm extends FormBase {


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
    return 'custom_utility_course_grade';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the current Node ID.    // Get the current Node ID.
    $node = $this->request->attributes->get('node');
    if ($node instanceof NodeInterface && $node->bundle() == 'course') {

      $course_id = $node->id();

      $form['course_id'] = [
        '#type' => 'hidden',
        '#value' => $course_id,
      ];

      $form['grade'] = [
        '#type' => 'select',
        '#title' => $this->t('Grade the Course'),
        '#default_value' => $this->commonService->getCourseGrade($course_id),
        '#options' => [
          '1' => $this->t('1 star'),
          '2' => $this->t('2 stars'),
          '3' => $this->t('3 stars'),
          '4' => $this->t('4 stars'),
          '5' => $this->t('5 stars'),
        ],
        '#required' => true,
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit Rating!'),
        ],
      ];

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
    $grade = $form_state->getValue('grade');
    $this->commonService->addCourseGradeEntry($course_id, (int)$grade);
    $this->messenger()->addStatus($this->t('Course Grade submitted successfully.'));
  }
}
