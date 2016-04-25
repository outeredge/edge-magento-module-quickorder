<?php
/**
 * Autocomplete queries list
 */
class Edge_QuickOrder_Block_Autocomplete extends Mage_Core_Block_Abstract
{
    protected $_suggestData = null;

    protected function _toHtml()
    {
        $html = '';

        if (!$this->_beforeToHtml()) {
            return $html;
        }

        $suggestData = $this->getSuggestData();
        if (!($count = count($suggestData))) {
            return $html;
        }

        $count--;

        $html = '<ul style="width:500px"><li style="display:none"></li>';
        foreach ($suggestData as $index => $item) {
            if ($index == 0) {
                $item['row_class'] .= ' first';
            }

            if ($index == $count) {
                $item['row_class'] .= ' last';
            }

            $options = json_encode($item['options']);
            $options = str_replace(' ', '&#32;', $options);

            $html .=  '<li title='.$options.' class="' . $item['row_class'] . '">'
                  .   '<div><table><tbody><tr>'
                  .   '<td><img src="' . $item['image'] . '"  width="60" height="57"></td>'
                  .   '<td><strong>"' . $this->escapeHtml($item['name']) . '"</strong>'
                  .   '<br> SKU: '.$this->escapeHtml($item['value']).'</td>'
                  .   '</tr></tbody></table></div></li>';
        }

        $html.= '</ul>';

        return $html;
    }

    protected function getSuggestData()
    {
        $params  = $this->getRequest()->getParams();
        $string  = strip_tags(trim($params['q']));
        $query   = '%'. ($string) .'%';
        $storeId = Mage::app()->getStore()->getId();
        $extra   = false;

        if ($query == '') {
            return;
        }

        if (!$this->_suggestData) {
            $collection = $this->getSuggestCollection($query);

            $data = array();
            foreach ($collection as $item) {
                $options = $activeOptions = $array = '';

                if ($item->getStockItem()->getIsInStock()) {

                    if ($item->getRequiredOptions() && $item->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {

                        $configOpt = $item->getTypeInstance(true)->getConfigurableOptions($item);

                        foreach (reset($configOpt) as $config) {
                            $activeOptions[] = $config['option_title'];
                        }

                        $confAttributes = $item->getTypeInstance(true)->getConfigurableAttributesAsArray($item);

                        foreach ($confAttributes as $conf) {
                            $array = '';
                            foreach ($conf['values'] as $option) {
                                if (in_array($option['store_label'], $activeOptions)) {
                                   $array[$option['value_index']] = $option['store_label'];
                                }
                            }
                            $options[$conf['attribute_id']] = $array;
                        }
                    }

                    //Stock Lights
                    if ($item->isAvailable()) {
                        $_product = Mage::getModel('catalog/product')->load($item->getId());
                        $lowStockCutoff = (int)Mage::getStoreConfig('cataloginventory/options/stock_threshold_qty');
                        if ($_product->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                            $stockQty = 0;
                            foreach ($_product->getTypeInstance(true)->getUsedProducts(null, $_product) as $simple) {
                                $stockQty += $simple->getStockItem()->getQty();
                            }
                        } else {
                            $stockQty = $_product->getStockItem()->getQty();
                        }

                        if ($stockQty < $lowStockCutoff) {
                             $stock = 'low-stock';
                        } else {
                            $stock = 'in-stock';
                        }
                    } else {
                        $stock = 'out-of-stock';
                    }

                    $extraDeliveryCharges = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getId(), 'extra_delivery_charges', $storeId);
                    $tcForExtraDelivery   = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getId(), 'tc_for_extra_delivery', $storeId);
                    if (!empty($extraDeliveryCharges) && !empty($tcForExtraDelivery)) {
                        $extra = ['extra_delivery_charges' => $extraDeliveryCharges,
                                  'tc_for_extra_delivery'  => $tcForExtraDelivery,
                                  'url_tc_extra_delivery'  => $this->getUrl('terms')];
                    }

                    $imageUrl = $this->getImageUrl($item);
                    $_data = array(
                        'value'     => $item->getSku(),
                        'name'      => $item->getName(),
                        'options'   => ["sku"   => $item->getSku(),
                                        "opt"   => $options,
                                        "extra" => $extra,
                                        "stock" => $stock],
                        'image'     => $imageUrl
                    );
                    $data[] = $_data;
                }
            }
            $this->_suggestData = $data;
        }
        return $this->_suggestData;
    }

    protected function getSuggestCollection($query)
    {
        $productAdapter = new Mage_Catalog_Model_Convert_Adapter_Product();

        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addFieldToFilter('name',array('like'=> $query))
            ->addAttributeToSelect(array('sku','name','small_image','is_salable','image','thumbnail'))
            ->addAttributeToFilter('visibility',array('in'=> Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
            ->addAttributeToFilter('type_id',array('in'=> $productAdapter->getProductTypes()))
            ->addAttributeToFilter('status', 1)
            ->setPageSize(10);

        if (empty($collection->getData())) {

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addFieldToFilter('sku',array('like'=>$query))
                ->addAttributeToSelect(array('name','sku','small_image','image','is_salable','thumbnail'))
                ->addAttributeToFilter('visibility',array('in'=> Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
                ->addAttributeToFilter('status', 1)
                ->addAttributeToFilter('type_id',array('in'=> $productAdapter->getProductTypes()))
                ->setPageSize(10);
	}

        return $collection;
    }

    protected function getImageUrl($_product)
    {
        $product = $_product->getData();

        if ($product['thumbnail']!='') {
            $image = $product['thumbnail'];
            $attr  = 'thumbnail';
        } elseif ($product['small_image']!='') {
            $image = $product['small_image'];
            $attr  = 'small_image';
        } else {
            $image = $product['image'];
            $attr  = 'image';
        }

        $url = (string)$_product->getMediaConfig()->getMediaUrl($image);
        if (file_exists($url)) {
            $imageUrl =	(string)Mage::helper('catalog/image')->init($_product, $attr)->resize(85,95);
        } else {
            $imageUrl = (string)Mage::helper('catalog/image')->init($_product, $attr);
        }

        return $imageUrl;
    }
}