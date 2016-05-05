<?php

class Edge_QuickOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * return product stock availability
     * @param type $_product
     * @return type
     */
    public function checkProductStock($_product)
    {
        $stockAvailable = true;
        $stockLight = $stockLightText = '';
        $lowStockCutoff = (int)Mage::getStoreConfig('cataloginventory/options/stock_threshold_qty');

        //Configurable product
        if ($_product->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $stockQty = 0;
            foreach ($_product->getTypeInstance(true)->getUsedProducts(null, $_product) as $simple) {
                $stockQty += $simple->getStockItem()->getQty();
            }
        } else {
        //Simple product
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
            $stockQty = $stock->getQty();
        }
        //Bundle product
        if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $bundled_product = new Mage_Catalog_Model_Product();
            $bundled_product->load($_product->getId());

            $selectionCollection = $bundled_product->getTypeInstance(true)->getSelectionsCollection(
                $bundled_product->getTypeInstance(true)->getOptionsIds($bundled_product),
                $bundled_product);

            foreach ($selectionCollection as $option) {
                $bundleItemStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($option->product_id);
                $bundleStockArray[] = $bundleItemStock->getQty();
            }
            $stockQty = min($bundleStockArray);
        }

        //Traffic Light Stock System
        if ($stockQty <= 0) {
            $stockAvailable = false;
            $stockLight = 'out-of-stock';
            $stockLightText = 'Out Of Stock';
        }elseif($stockQty < $lowStockCutoff){
            $stockLight = 'low-stock';
            $stockLightText = 'Low Stock';
        }else{
            $stockLight = 'in-stock';
            $stockLightText = 'In Stock';
        }

        return compact("stockAvailable", "stockQty", "stockLight", "stockLightText");
    }
}
