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
    $message = [
      'student' => 'Student',
      'instructor' => 'Instructor',
      'administrator' => 'Administrator',
    ];

    // Load and display user-specific data based on their role.
    $content = [];
    $content['page_title'] = $message[$roles[1]] . ' Dashboard';
    $content['message'] = 'Welcome, ' . $this->currentUser->getDisplayName();

    return [
      '#theme' => 'custom_utility_user_dashboard',
      '#content' => $content,
    ];
  }
}
