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

            $html .=  '<li title="' . $this->escapeHtml($item['value']) . '" class="' . $item['row_class'] . '">';
            $html .=  '<div><table><tbody><tr>';
            $html .=  '<td><img src="' . $item['image'] . '"  width="60" height="57"></td>';
            $html .=  '<td><strong>"' . $this->escapeHtml($item['name']) . '"</strong>'
                . '<br> SKU: "' . $this->escapeHtml($item['value']) . ' "' . $this->escapeHtml($item['option']) . '</td>';
            $html .=  '</tr></tbody></table></div>';
            $html .=  '</li>';
        }

        $html.= '</ul>';

        return $html;
    }

    protected function getSuggestData()
    {
        $params = $this->getRequest()->getParams();
        $string = strip_tags(trim($params['q']));
        $query  = '%'. ($string) .'%';

        if ($query == '') {
            return;
        }

        if (!$this->_suggestData) {
            $collection = $this->getSuggestCollection($query);

            $option = '';
            $data = array();
            foreach ($collection as $item) {

                if ($item->getStockItem()->getIsInStock()) {

                    $parent_id = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($item->getId());

                    if (!empty($parent_id)) {
                        $product = Mage::getModel('catalog/product')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->load($item->getId());

                        $parent_product = Mage::getModel('catalog/product')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->load($parent_id);

                        $confAttributes = $parent_product->getTypeInstance(true)->getConfigurableAttributesAsArray($parent_product);

                        foreach ($confAttributes as $conf) {
                            if (isset($product[$conf['attribute_code']])) {
                                foreach ($conf['values'] as $value) {
                                    if ($value['value_index'] == $product[$conf['attribute_code']]) {
                                        $option = $value['default_label'] . '  ';
                                    }
                                }
                            }
                        }
                    }

                    $imageUrl = $this->getImageUrl($item);
                    $_data = array(
                        'value'     => $item->getSku(),
                        'name'      => $item->getName(),
                        'option'    => $option,
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
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addFieldToFilter('name',array('like'=> $query))
            ->addAttributeToSelect(array('sku','name','small_image','is_salable','image','thumbnail'))
            ->addAttributeToFilter('type_id',array('in'=> 'simple'))
            ->addAttributeToFilter('status', 1)
            ->setPageSize(10);

        if (empty($collection->getData())) {

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addFieldToFilter('sku',array('like'=>$query))
                ->addAttributeToSelect(array('name','sku','small_image','image','is_salable','thumbnail'))
                ->addAttributeToFilter('status', 1)
                ->addAttributeToFilter('type_id',array('in'=> 'simple'))
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