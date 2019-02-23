<?php

namespace Drupal\related_entities;

use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Class RelatedEntitiesFinder.
 */
class RelatedEntitiesFinder
{

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Number of nodes to display.
   * @var integer
   */
  protected $limit;

  /**
   * Node bundle type
   * @var [type]
   */
  protected $bundle;

  /**
   * Result of related entities.
   * @var array
   */
  protected $relatedEntities = [];
  /**
   * Constructs a new RelatedEntitiesFinder object.
   */
  public function __construct(CurrentRouteMatch $current_route_match)
  {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Set limit to query data.
   */
  public function setLimit($limit)
  {
    $this->limit = $limit;
  }

  /**
   * Get limit
   * @return integer
   */
  public function getLimit()
  {
    return $this->limit;
  }

  /**
   * Set Entity bundle.
   * @param string $bundle
   */
  public function setBundle($bundle)
  {
    $this->bundle = $bundle;
  }

  /**
   * Get entity bundle.
   * @return string
   */
  public function getBundle()
  {
    return $this->bundle;
  }

  /**
   * Set related entity result.
   * @param array $relatedEntities
   */
  public function setRelatedEntities($relatedEntities)
  {
    $this->relatedEntities = array_merge($this->getRelatedEntities(), $relatedEntities);
  }

  /**
   * Get related entity result.
   * @return array [description]
   */
  public function getRelatedEntities()
  {
    return $this->relatedEntities;
  }

  public function relatedEntities($limit = 5, $bundle = 'article')
  {
    // Set record count.
    $this->setLimit($limit);
    // Set Entity Bundle.
    $this->setBundle($bundle);
    // Get current node object.
    $currentEntity = $this->currentRouteMatch->getParameter('node');
    // Set current entity owner id.
    $entityAuthorId = $currentEntity->getOwnerId();
    // Initialise category ID with NULL. If category is not set, category should be NULL.
    $entityCategoryId = NULL;
    if ($currentEntity->hasField('field_category')) {
      $entityCategoryId = $currentEntity->get('field_category')->target_id;
    }
    $options = [
      'current_entity_id' => $currentEntity->id(),
      'entity_category_id' => $entityCategoryId,
      'entity_author_id' => $entityAuthorId,
    ];
    $this->findRelatedEntities($options);

    if (empty($this->getRelatedEntities())) {
      return [];
    }
    return \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($this->getRelatedEntities());
  }

  /**
   * Find entities with respective to custom content rules.
   * @param  array $options Provides Entity author, category and id.
   */
  public function findRelatedEntities($options)
  {
    for ($i=1; $i < 5; $i++) {
      if ($this->getLimit() == 0) {
        break;
      }
      switch ($i) {
        case 1:
          // Find content for Same Author and Same category
          $this->filterEntities($options, TRUE, TRUE);
          break;
        case 2:
          // Find content for different Author and Same category
          $this->filterEntities($options, FALSE, TRUE);
          break;
        case 3:
          // Find content for Same Author and different category
          $this->filterEntities($options, TRUE, FALSE);
          break;
        case 4:
          // Find content for different Author and different category
          $this->filterEntities($options, FALSE, FALSE);
          break;
      }
    }
  }

  /**
   * Perform Entity query based on different content rules.
   */
  public function filterEntities($options, $match_author = TRUE, $match_category = TRUE)
  {
    $bundle = $this->getBundle();
    $query = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->condition('status', 1)
      ->condition('nid', $options['current_entity_id'], '!=')
      ->sort('title', 'ASC')
      ->sort('created', 'DESC')
      ->range(0, $this->getLimit());

    // Add Author condition based whether we have to match author or not.
    if ($match_author) {
      // Condtion to filter content for same author.
      $query->condition('uid', $options['entity_author_id'], '=');
    }
    else {
      // Condition to filter content for different user.
      $query->condition('uid', $options['entity_author_id'], '!=');
    }

    // Category can be null if it is not set for current entity.
    if (is_null($options['entity_category_id'])) {
      if ($match_category) {
        // If Category is null, find records with null category.
        // This is applicable when content with same category needed.
        $query->notExists('field_category');
      }
      else {
        // If category is null, But we want to find records with different categories,
        // i.e. content having some category.
        $query->exists('field_category');
      }
    }
    else {
      if ($match_category) {
        // Category is set and we are looking for same category content.
        $query->condition('field_category.target_id', $options['entity_category_id'], '=');
      }
      else {
        // Category is set and we are looking for different category content.
        // For this rule, we need to include content with null category as well as
        // content with different category.
        $condition_or = $query->orConditionGroup();
        $condition_or->notExists('field_category');
        $condition_or->condition('field_category.target_id', $options['entity_category_id'], '!=');
        $query->condition($condition_or);
      }
    }
    $result = $query->execute();
    $this->setLimit($this->getLimit() - count($result));
    $this->setRelatedEntities($result);
  }
}
