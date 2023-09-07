<?php

namespace Drupal\custom_utility;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Service description.
 */
class CommonService {

  protected AccountProxyInterface $currentUser;
  protected Connection $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CommonService object.
   *
   * @param AccountProxyInterface $current_user
   *   The current user.
   * @param Connection $database
   *   The database connector.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  function checkCourseLessonEntryExist(string $course_id, string $lesson_id = null): mixed {
    $query = $this->database->select('custom_utility_course_user_table', 'ct')
      ->fields('ct', ['id'])
      ->condition('ct.course_id', $course_id);
    if ($lesson_id) {
      $query->condition('ct.lesson_id', $lesson_id);
    }
    $query->condition('ct.user_id', $this->currentUser->id())
      ->countQuery();
    $count = $query->execute()
      ->fetchField();
    return $count;

    // $query = $this->database->select('custom_utility_course_user_table', 'ct')
    //   ->fields('ct', ['id'])
    //   ->condition('ct.course_id', $course_id)
    //   ->condition('ct.user_id', $this->currentUser->id())
    //   ->execute()
    //   ->fetchField();
    // return $query;

  }

  function checkCourseLessonCompleted(string $course_id, string $lesson_id): mixed {
    $query = $this->database->select('custom_utility_course_user_table', 'ct')
      ->fields('ct', ['id'])
      ->condition('ct.course_id', $course_id)
      ->condition('ct.lesson_id', $lesson_id)
      ->condition('ct.lesson_completed', 1)
      ->condition('ct.user_id', $this->currentUser->id())
      ->execute()
      ->fetchField();
    return $query;
  }

  function addCourseLessonEntry(string $course_id, string $lesson_id, bool $lesson_completed = FALSE): mixed {
    $creation_date = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime());

    // Insert data into the custom course user table.
    $query = $this->database->merge('custom_utility_course_user_table')
      ->key([
        'course_id' => $course_id,
        'lesson_id' => $lesson_id,
        'user_id' => $this->currentUser->id(),
      ])
      ->fields([
        'lesson_completed' => $lesson_completed ? 1 : 0,
        'created_date' => $creation_date,
        'updated_date' => $creation_date,
      ])
      ->execute();
    return $query;
  }


  function getCompletedLessonCount(string $course_id): int {
    $query = $this->database->select('custom_utility_course_user_table', 'ct')
      ->fields('ct', ['id'])
      ->condition('ct.course_id', $course_id)
      ->condition('ct.lesson_completed', 1)
      ->condition('ct.user_id', $this->currentUser->id())
      ->countQuery();
    $count = $query->execute()
      ->fetchField();
    return $count;
  }

  function getCoursePercentage(NodeInterface $course): int {
    $total_lessons = count($course->field_lessons);
    $lessons_completed = $this->getCompletedLessonCount($course->id());
    $percentage =
      ($total_lessons > 0) ? ($lessons_completed / $total_lessons) * 100 : 0;
    return $percentage;
  }
}
