<?php

class Edge_QuickOrder_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function suggestAction(){

		$params = $this->getRequest()->getParams();
		$str = strip_tags(trim($params['q']));
		$query = mysql_escape_string($str);

		$query = '%'.$query.'%';

		if($query==''){ return; }

		$data = array();
		$isCollection = false;
		$isSku = false;

		$visibility = $this->getConfig('visibility_filter');
		$visibility = explode(',',$visibility);

		$sort_column = $this->getConfig('sort_column');
		$limit =  $this->getConfig('number_result');

		$types = Mage::helper('quickorder/protected')->getProductTypes();
		$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$str);




		if($product){
					$isSku = true;
					$json = array();
					if(!$this->isProductAllowed($product)){ return; }
					$v = $product->getVisibility();
					if($product->getStatus()==1 && in_array($v,$visibility)){
						$imageUrl = $this->getImageUrl($product);
						$json['value'] = $product->getSku();
						$json['name'] = $product->getName();
						$json['image'] = $imageUrl;
						$json['is_sku'] = $isSku;
						$data[] =  $json;
						$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
						return;
					}
		}

		$collection = Mage::getModel('catalog/product')->getCollection()
								->addStoreFilter()
								->addFieldToFilter('name',array('like'=>$query))
								->addAttributeToSelect(array('sku','name','small_image','is_salable','image','thumbnail'))
								->addAttributeToFilter('visibility',array('in'=>$visibility))
								->addAttributeToFilter('type_id',array('in'=>$types))
								->addAttributeToSort($sort_column)
								->addAttributeToFilter('status',1)
								->setPageSize($limit);

		if($collection->count()>0){
			$isCollection = true;
		}else{
			$collection = Mage::getModel('catalog/product')->getCollection()
								->addStoreFilter()
								->addFieldToFilter('sku',array('like'=>$query))
								->addAttributeToSelect(array('name','sku','small_image','image','is_salable','thumbnail'))
								->addAttributeToFilter('visibility',array('in'=>$visibility))
								->addAttributeToFilter('type_id',array('in'=>$types))
								->addAttributeToFilter('status',1)
								->addAttributeToSort($sort_column)
								->setPageSize($limit);
			$isSku = true;
			$isCollection = true;
		}
		if($isCollection){

			foreach($collection as $_product){

					if(!$this->isProductAllowed($_product)){ continue; }
					$imageUrl = $this->getImageUrl($_product);
					$json = array();
					$json['value'] = $_product->getSku();
					$json['name'] = $_product->getName();
					$json['image'] = $imageUrl;
					$json['is_sku'] = $isSku;
					$data[] =  $json;
			}
		}


		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));

	}


    public function addToCardAction()
    {
        $data = $this->getRequest()->getPost();

        if (!empty($data)) {

            foreach ($data as $row) {
                if (isset($row['sku']) && isset($row['qty'])) {
                    $sku = $row['sku'];
                    $qty = $row['qty'];

                    $id = Mage::getModel('catalog/product')->getIdBySku($sku);

                    if (empty($id)) {
                        Mage::getSingleton('checkout/session')->addError("<strong>Product Not Added</strong><br />The SKU you entered ($sku) was not found.");
                    } else {
                        header('Location: /checkout/cart/add?product='.$id.'&qty='.$qty);
                    }

                }
            }

        }
        return;

    }

}