<?php

namespace Drupal\custom_utility\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_utility\CommonService;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Query IDs from custom table and pass it to views.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("custom_utility_table_query")
 */
class CustomTableFilter extends StringFilter {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['custom_utility_table_query_option'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['custom_utility_table_query_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the Query Type'),
      '#description' => $this->t('Select the Query option to be performed.'),
      '#default_value' => $this->options['custom_utility_table_query_option'],
      '#options' => [
        'courses_enrolled' => $this->t('Courses Enrolled'),
        'courses_completed' => $this->t('Courses Completed'),
        'courses_not_graded' => $this->t('Courses Not Graded'),
      ],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $table = array_key_first($query->tables);

    /** @var CommonService */
    $common_service = \Drupal::service('custom_utility.common_service');
    $option = $this->options['custom_utility_table_query_option'];
    $nids = $common_service->getCourseNidsFromCustom($option);
    if (count($nids) > 0) {
      $query->addWhere($this->options['group'], $table . '.nid', $nids, 'IN');
    }
  }
}
