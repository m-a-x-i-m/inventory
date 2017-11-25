<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Indexer\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink;

/**
 * Returns assigned Stock ids by given Source ids
 */
class GetAssignedStockIds
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int[] $sourceIds
     * @return int[]
     */
    public function execute(array $sourceIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceStockLinkTable = $this->resourceConnection->getTableName(
            StockSourceLinkResourceModel::TABLE_NAME_STOCK_SOURCE_LINK
        );

        $select = $connection
            ->select()
            ->from($sourceStockLinkTable, StockSourceLink::STOCK_ID)
            ->where(StockSourceLink::SOURCE_ID . ' IN (?)', $sourceIds)
            ->group(StockSourceLink::STOCK_ID);

        $stockIds = $connection->fetchCol($select);
        $stockIds = array_map('intval', $stockIds);
        return $stockIds;
    }
}
