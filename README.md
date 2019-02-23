# related-entities
Drupal 8 Module to get related entities block with some custom content rules.
Rules:
-- Show only first 5 nodes based on rules
-- Block shows content only of type article
-- Block is displayed only on nodes of type article
-- Display nodes in same category by same author first
-- Display nodes in same category by different author next
-- Display nodes in different category by same author next
-- Display nodes in different category by different author next
-- Sort by title asc, created desc within each rule (So if there are 3 content for rule 1, these 3 should be sorted by title, created and then next 2 should be again sorted based the group they belong to)
-- Block result should never contain current node
