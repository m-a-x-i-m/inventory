<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider;

use Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider\SelectBuilderForAttribute\JoinStockConditionToSelect;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Store\Model\Store;

/**
 * Build select for attribute.
 */
class SelectBuilderForAttribute
{
    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var JoinStockConditionToSelect
     */
    private $joinStockCondition;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param ResourceConnection $resource
     * @param ScopeResolverInterface $scopeResolver
     * @param JoinStockConditionToSelect $joinStockCondition
     * @param Session $customerSession
     */
    public function __construct(
        ResourceConnection $resource,
        ScopeResolverInterface $scopeResolver,
        JoinStockConditionToSelect $joinStockCondition,
        Session $customerSession
    ) {
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->joinStockCondition = $joinStockCondition;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Select $select
     * @param AbstractAttribute $attribute
     * @param int $currentScope
     *
     * @return Select
     */
    public function build(Select $select, AbstractAttribute $attribute, int $currentScope): Select
    {
        if ($attribute->getAttributeCode() === 'price') {
            /** @var Store $store */
            $store = $this->scopeResolver->getScope($currentScope);
            if (!$store instanceof Store) {
                throw new \RuntimeException('Illegal scope resolved');
            }
            $table = $this->resource->getTableName('catalog_product_index_price');
            $select->from(['main_table' => $table], null)
                ->columns([BucketInterface::FIELD_VALUE => 'main_table.min_price'])
                ->where('main_table.customer_group_id = ?', $this->customerSession->getCustomerGroupId())
                ->where('main_table.website_id = ?', $store->getWebsiteId());
        } else {
            $currentScopeId = $this->scopeResolver->getScope($currentScope)->getId();
            $table = $this->resource->getTableName(
                'catalog_product_index_eav' . ($attribute->getBackendType() === 'decimal' ? '_decimal' : '')
            );
            $subSelect = $select;
            $subSelect->from(['main_table' => $table], ['main_table.entity_id', 'main_table.value'])
                ->distinct()
                ->where('main_table.attribute_id = ?', $attribute->getAttributeId())
                ->where('main_table.store_id = ? ', $currentScopeId);
            $subSelect = $this->joinStockCondition->execute($subSelect);

            $parentSelect = $this->resource->getConnection()->select();
            $parentSelect->from(['main_table' => $subSelect], ['main_table.value']);
            $select = $parentSelect;
        }

        return $select;
    }
}
