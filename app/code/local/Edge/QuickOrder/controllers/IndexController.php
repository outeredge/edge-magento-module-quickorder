<?php

class Edge_QuickOrder_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
        }

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

            try {
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
                        if (!empty($row['super_attribute'])) {
                            $attributeName = [key($row['super_attribute']) => reset($row['super_attribute'])];
                        }

                        $params = ['cart'            => 'add',
                                   'product'         => $productId,
                                   'related_product' => '',
                                   'super_attribute' => $attributeName,
                                   'qty'             => !isset($row['qty']) ? 1: $row['qty']];

                        $this->getRequest()->setparam('qty', $params['qty']);

                        $cart->addProduct($product, $params);
                        if (!empty($params['related_product'])) {
                            $cart->addProductsByIds(explode(',', $params['related_product']));
                        }

                        $stockReturn = Mage::dispatchEvent('checkout_cart_add_product_complete',
                            array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                        );
                        $stockMessage = $stockReturn->getResponse()->getBody();

                        if (!empty($stockMessage)) {
                            Mage::getSingleton('checkout/session')->addNotice($stockMessage);
                        }

                        $message = $this->__('%s was successfully added to your shopping cart.', $product->getName());
                        Mage::getSingleton('checkout/session')->addSuccess($message);
                    }
                }

                $cart->save();
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);

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

        $this->_redirect('checkout/cart');
    }
}