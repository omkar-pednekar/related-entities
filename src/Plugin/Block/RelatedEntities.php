<?php

namespace Drupal\related_entities\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\related_entities\RelatedEntitiesFinder;

/**
 * Provides a 'RelatedEntities' block.
 *
 * @Block(
 *  id = "related_entities",
 *  admin_label = @Translation("Related Entities"),
 * )
 */
class RelatedEntities extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * Drupal\related_entities\RelatedEntitiesFinder definition.
   *
   * @var \Drupal\related_entities\RelatedEntitiesFinder
   */
  protected $relatedEntitiesRelatedEntitiesFinder;
  /**
   * Constructs a new RelatedEntities object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RelatedEntitiesFinder $related_entities_related_entities_finder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->relatedEntitiesRelatedEntitiesFinder = $related_entities_related_entities_finder;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('related_entities.related_entities_finder')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $build = [];
    // Set display limit and bundle.
    // TODO: Make limit and bundle configurable.
    $limit = 5;
    $bundle = 'article';
    $related_entities = $this->relatedEntitiesRelatedEntitiesFinder->relatedEntities($limit, $bundle);
    $rows = [];
    foreach ($related_entities as $entity) {
      $created = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'custom', 'd-m-Y H:i:s');
      $category = NULL;
      if ($entity->hasField('field_category') && !$entity->get('field_category')->isEmpty()) {
        $category = $entity->field_category->entity->getName();
      }
      $header = ['Title', 'Category', 'Author', 'Created on'];
      $rows[] = [
        $entity->toLink()->toString(),
        $category,
        $entity->getOwner()->getDisplayName(),
        $created,
      ];
    }
    $build['related_entities'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => ['contexts' => ['url.path']],
      // '#cache' => ['max-age' => 0],
    ];
    return $build;
  }
}
