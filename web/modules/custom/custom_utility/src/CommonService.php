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

  /**
   * Current User Object.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Database connection Object.
   */
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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connector.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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

  /**
   * Checks if Course lesson entry exist.
   */
  public function checkCourseLessonEntryExist(string $course_id, string $lesson_id = NULL): mixed {
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
  }

  /**
   * CheckCourseLessonCompleted.
   */
  public function checkCourseLessonCompleted(string $course_id, string $lesson_id): mixed {
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

  /**
   * AddCourseLessonEntry.
   */
  public function addCourseLessonEntry(string $course_id, string $lesson_id, bool $lesson_completed = FALSE): mixed {
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

  /**
   * AddCourseCompletionEntry.
   */
  public function addCourseCompletionEntry(string $course_id, int $lesson_completed = 0): void {
    $creation_date = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime());
    $this->database->merge('custom_utility_course_completion_table')
      ->key([
        'course_id' => $course_id,
        'user_id' => $this->currentUser->id(),
      ])
      ->fields([
        'completed' => $lesson_completed,
        'completion_date' => $creation_date,
      ])
      ->execute();
  }

  /**
   * GetCompletionDate.
   */
  public function getCompletionDate(string $course_id): string {
    $query = $this->database->select('custom_utility_course_completion_table', 'ct')
      ->fields('ct', ['completion_date'])
      ->condition('ct.course_id', $course_id)
      ->condition('ct.user_id', $this->currentUser->id());
    return $query->execute()
      ->fetchField();
  }

  /**
   * GetCompletedLessonCount.
   */
  public function getCompletedLessonCount(string $course_id): int {
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

  /**
   * GetCoursePercentage.
   */
  public function getCoursePercentage(NodeInterface $course): int {
    $total_lessons = count($course->field_lessons);
    $lessons_completed = $this->getCompletedLessonCount($course->id());
    $percentage =
      ($total_lessons > 0) ? ($lessons_completed / $total_lessons) * 100 : 0;
    return $percentage;
  }

  /**
   * AddCourseGradeEntry.
   */
  public function addCourseGradeEntry(string $course_id, int $grade): mixed {
    $creation_date = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime());

    // Insert data into the custom course user table.
    $query = $this->database->merge('custom_utility_course_grade_table')
      ->key([
        'course_id' => $course_id,
        'user_id' => $this->currentUser->id(),
      ])
      ->fields([
        'course_grade' => $grade,
        'created_date' => $creation_date,
      ])
      ->execute();
    return $query;
  }

  /**
   * GetCourseGrade.
   */
  public function getCourseGrade(string $course_id): mixed {
    $query = $this->database->select('custom_utility_course_grade_table', 'ct')
      ->fields('ct', ['course_grade'])
      ->condition('ct.course_id', $course_id)
      ->condition('ct.user_id', $this->currentUser->id());
    return $query->execute()->fetchField();
  }

  /**
   * GetAverageCourseGrade.
   */
  public function getAverageCourseGrade(string $course_id): mixed {
    $query = $this->database->select('custom_utility_course_grade_table', 'cgt');
    $query->addExpression('AVG(cgt.course_grade)', 'average_score');
    $query->condition('cgt.course_id', $course_id);
    // Group by course_id.
    $query->groupBy('cgt.course_id');
    $queryString = $query->__toString();

    // Execute the query and get the average score.
    $result = $query->execute();
    return $result->fetchField();
  }

  /**
   * GetCourseNidsFromCustom.
   */
  public function getCourseNidsFromCustom(string $option): array {
    $return_obj = NULL;
    $return_assoc = [];
    switch ($option) {
      case 'courses_enrolled':
        $query = $this->database->select('custom_utility_course_completion_table', 'cct')
          ->fields('cct', ['course_id'])
          ->condition('cct.completed', 0)
          ->condition('cct.user_id', $this->currentUser->id());
        $result = $query->execute();
        $return_obj = $result->fetchAll();

        break;

      case 'courses_completed':
        $query = $this->database->select('custom_utility_course_completion_table', 'cct')
          ->fields('cct', ['course_id'])
          ->condition('cct.completed', 1)
          ->condition('cct.user_id', $this->currentUser->id());
        $result = $query->execute();
        $return_obj = $result->fetchAll();

        break;

      case 'courses_not_graded':
        $query = $this->database->select('custom_utility_course_grade_table', 'cct')
          ->fields('cct', ['course_id'])
          ->condition('cct.user_id', $this->currentUser->id());
        $result = $query->execute();
        $return_obj = $result->fetchAll();
        $exclude_nids = $this->parseArrayObject($return_obj);
        // Node Query @ToDo later these 2 queries can be merged to an optimised
        // one using Join.
        $nodeQuery = $this->database->select('node', 'n');
        $nodeQuery->addExpression('n.nid', 'course_id');
        $nodeQuery->condition('n.type', 'course');
        if ($exclude_nids) {
          $nodeQuery->condition('n.nid', $exclude_nids, 'NOT IN');
        }
        $return_obj = $nodeQuery->execute()->fetchAll();
        break;

      default:
        break;
    }
    if ($return_obj) {
      $return_assoc = $this->parseArrayObject($return_obj);
    }
    return $return_assoc;
  }

  /**
   * ParseArrayObject.
   */
  protected function parseArrayObject($return_obj): array {
    return array_map(function ($object) {
      return $object->course_id;
    }, $return_obj);
  }

}
