<?php

declare(strict_types=1);

namespace Drupal\custom_utility\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Custom Utility form.
 */
final class LessonCompleteForm extends FormBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  protected $database;
  protected $request;

  public function __construct(
    AccountProxyInterface $current_user,
    Connection $database,
    Request $request
  ) {
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->request = $request;
  }
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
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
    $node = $this->request->attributes->get('node');
    $lesson_id = $node->id();
    dump($node->field_course[0]->entity->id());exit;
    $course_id = $node->field_course[0]->entity->id();

    $form['course_id'] = [
      '#type' => 'hidden',
      '#value' => $course_id,
    ];
    $form['lesson_id'] = [
      '#type' => 'hidden',
      '#value' => $lesson_id,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Mark the lesson as completed'),
      ],
    ];
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
    $creation_date = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime());
    $course_id = $form_state->getValue('course_id');
    $lesson_id = $form_state->getValue('lesson_id');
    $user_id = $this->currentUser->id();
    // Check if an entry already exists in the custom table with the same node_id.
    $query = $this->database->select('custom_utility_course_user_table', 'ct')
      ->fields('ct', ['id'])
      ->condition('ct.course_id', $course_id)
      ->condition('ct.user_id', $user_id)
      ->execute()
      ->fetchField();

    if ($query !== FALSE) {
      $this->messenger()->addStatus($this->t('Already enrolled for the course.'));
    } else {
      // Insert data into the custom course user table.
      $this->database->insert('custom_utility_course_user_table')
        ->fields([
          'course_id' => $course_id,
          'lesson_id' => $lesson_id,
          'user_id' => $user_id,
          'created_date' => $creation_date,
          'updated_date' => $creation_date,
        ])
        ->execute();

      $this->messenger()->addStatus($this->t('Successfully Enrolled for the course.'));
    }
  }
}
