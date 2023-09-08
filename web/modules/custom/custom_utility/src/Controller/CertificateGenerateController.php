<?php

// custom_utility/src/Controller/CustomUtilityController.php

namespace Drupal\custom_utility\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

class CertificateGenerateController extends ControllerBase {

  public function content(NodeInterface $node) {
    // Load user-specific data (e.g., user's name and course completion date).
    $user = User::load(\Drupal::currentUser()->id());
    $certificate_template = '';
    if ($user) {
      $username = $user->getDisplayName();
      $completionDate = date('Y-m-d');

      // Load the HTML certificate template.
      $certificate_template = file_get_contents(\Drupal::service('extension.list.module')->getPath('custom_utility') . '/templates/certificate_template.html');

      // Replace placeholders in the template with user-specific data.
      $certificate_template = str_replace('[USERNAME]', $username, $certificate_template);
      $certificate_template = str_replace('[COURSE_NAME]', $node->getTitle(), $certificate_template);
      $certificate_template = str_replace('[COMPLETION_DATE]', $completionDate, $certificate_template);

      // Add a "Print Certificate" button.
      $certificate_template .= '<a class="print-certificate button--primary button"> Print Certificate</a>';
    }

    // Output the certificate page.
    return [
      '#markup' => $certificate_template,
    ];
  }
}
