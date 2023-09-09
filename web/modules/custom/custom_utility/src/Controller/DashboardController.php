<?php

namespace Drupal\custom_utility\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DashboardController extends ControllerBase {

  protected $currentUser;

  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Page callback for the user dashboard.
   *
   * @return array
   *   A render array.
   */
  public function dashboard() {
    // Determine the user's role.
    $roles = $this->currentUser->getRoles();

    // Load and display user-specific data based on their role.
    $content = [];

    if (in_array('student', $roles)) {
      $content['message'] = 'Welcome, Student!';
      // Load and display student-specific data here.
    }
    elseif (in_array('instructor', $roles)) {
      $content['message'] = 'Welcome, Instructor!';
      // Load and display instructor-specific data here.
    }
    elseif (in_array('administrator', $roles)) {
      $content['message'] = 'Welcome, Administrator!';
      // Load and display administrator-specific data here.
    }

    return [
      '#theme' => 'dashboard_example_dashboard', // Adjust to your theme name.
      '#content' => $content,
    ];
  }

}
