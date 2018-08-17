<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var SourceItemRepositoryInterface $sourceItemRepository */
$sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
/** @var SourceItemsDeleteInterface $sourceItemsDelete */
$sourceItemsDelete = $objectManager->get(SourceItemsDeleteInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder->addFilter(
    SourceItemInterface::SKU,
    [
        'configurable_out_of_stock',
        'configurable_1', 'configurable_2',
        'simple_11', 'simple_21', 'simple_31',
        'simple_12', 'simple_22', 'simple_32'
    ],
    'in'
)->create();
$sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();

/**
 * Tests which are wrapped with MySQL transaction clear all data by transaction rollback.
 * In that case there is "if" which checks that SKU1, SKU2 and SKU3 still exists in database.
 */
if (!empty($sourceItems)) {
    $sourceItemsDelete->execute($sourceItems);
}
