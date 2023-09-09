<?php

// custom_utility/src/Controller/CustomUtilityController.php

namespace Drupal\custom_utility\Controller;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\custom_utility\CommonService;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CertificateGenerateController extends ControllerBase {

  protected $commonService;

  public function __construct(CommonService $common_service) {
    $this->commonService = $common_service;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_utility.common_service')
    );
  }

  public function content(NodeInterface $node) {
    // Load user-specific data (e.g., user's name and course completion date).
    $user = User::load(\Drupal::currentUser()->id());
    $certificate_template = '';
    if ($user) {
      $username = $user->getDisplayName();
      $completion_date_string = $this->commonService->getCompletionDate($node->id());

      $dateTime = new DateTime($completion_date_string);
      $formatted_date = $dateTime->format('Y-m-d');

      // Load the HTML certificate template.
      $certificate_template = file_get_contents(\Drupal::service('extension.list.module')->getPath('custom_utility') . '/templates/certificate_template.html');

      // Replace placeholders in the template with user-specific data.
      $certificate_template = str_replace('[USERNAME]', $username, $certificate_template);
      $certificate_template = str_replace('[COURSE_NAME]', $node->getTitle(), $certificate_template);
      $certificate_template = str_replace('[COMPLETION_DATE]', $formatted_date, $certificate_template);

      // Add a "Print Certificate" button.
      $link = Link::createFromRoute(
        t('Back to Course page'),
        'entity.node.canonical',
        ['node' => $node->id()],
        ['attributes' => ['class' => 'button--primary button']]
      );
      $certificate_template .= '<div class="flex-wrapper my-2"><a class="print-certificate button--primary button"> Print Certificate</a>';
      $certificate_template .= $link->toString() . '</div>';
    }

    // Output the certificate page.
    return [
      '#markup' => $certificate_template,
    ];
  }
}
