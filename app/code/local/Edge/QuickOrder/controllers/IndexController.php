<?php

class Edge_QuickOrder_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function suggestAction()
    {
        if (!$this->getRequest()->getParam('q', false)) {
            $this->getResponse()->setRedirect(Mage::getSingleton('core/url')->getBaseUrl());
        }

        $this->getResponse()->setBody($this->getLayout()->createBlock('edgequickorder/autocomplete')->toHtml());
    }

    public function addToCartAction() {

        if ($data = $this->getRequest()->getPost('product')) {
            $cart   = Mage::getSingleton('checkout/cart');

            foreach ($data as $row) {

                if (!isset($row['sku'])) {
                    continue;
                }

                $productId = Mage::getModel('catalog/product')->getIdBySku($row['sku']);

                if ($productId) {
                    $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($productId);

                    $attributeName = [];
                    $parent_id = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

                    if (!empty($parent_id)) {
                            $parent_product = Mage::getModel('catalog/product')
                                                   ->setStoreId(Mage::app()->getStore()->getId())
                                                   ->load($parent_id);

                            $productId = $parent_product->getId();
                            $confAttributes = $parent_product->getTypeInstance(true)->getConfigurableAttributesAsArray($parent_product);

                            foreach ($confAttributes as $conf) {
                                if (isset($product[$conf['attribute_code']])) {
                                    $attributeName[] = [$conf['attribute_id'] => $product[$conf['attribute_code']]];
                                }
                            }
                    }

                    $params = ['cart'            => 'add',
                               'product'         => $productId,
                               'related_product' => '',
                               'super_attribute' => $attributeName[0],
                               'qty'             => !isset($row['qty']) ? 1: $row['qty']];

                    try {

                        $cart->addProduct($product, $params);
                        if (!empty($params['related_product'])) {
                            $cart->addProductsByIds(explode(',', $params['related_product']));
                        }
                        $cart->save();

                        $message = $this->__('%s was successfully added to your shopping cart.', $product->getName());
                        Mage::getSingleton('checkout/session')->addSuccess($message);
                    }
                    catch (Mage_Core_Exception $e) {
                        if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                            Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                        } else {
                            $messages = array_unique(explode("\n", $e->getMessage()));
                            foreach ($messages as $message) {
                                Mage::getSingleton('checkout/session')->addError($message);
                            }
                        }
                    }
                    catch (Exception $e) {
                        Mage::getSingleton('checkout/session')->addException($e, $this->__('Can not add item to shopping cart'));
                    }
                }
            }

           Mage::getSingleton('checkout/session')->setCartWasUpdated(true);

        }
        $this->_redirect('checkout/cart');
    }

}