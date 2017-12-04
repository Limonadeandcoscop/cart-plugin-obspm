<?php

class Cart_CartController extends Omeka_Controller_AbstractActionController {

    public function init() {
        $this->_helper->db->setDefaultModelName('Cart');

        // Disable view rendering
        $this->_helper->viewRenderer->setNoRender(true);

        // Redirect to homepage if the user is not logged in
        if(!current_user()) {
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }

    /**
     * Display cart's content
     *
     * @return HTML
     */
    public function indexAction() {

        // Enable view rendering for this page
        $this->_helper->viewRenderer->setNoRender(false);

        // Retrieve the cart of current user
        $cartTable = get_db()->getTable('Cart');
        $cart = $cartTable->getCartOfUser();

        $items = array();

        // For each item in cart, get more information from element_texts table
        foreach($cart as $c) {
            $c['item'] =  get_record_by_id('Item', $c['item_id']);
        }

        $this->view->cart = $cart;
    }


    /**
     * Add an item in the cart (call via Ajax)
     * TODO?: Manage javascript failure with redirection to page item with success/fail message
     *
     * @return JSON The number of item in the connected user's cart
     */
    public function addAction() {

        // Validate the cart
        $cart = new Cart();
        $cart->setPostData($_GET);

        $user = current_user();
        $cart->user_id = $user->id;

        // Prepare JSON response
        $json = array();

        if ($cart->isValid($_GET)) {

            // Add tje item in the cart
            $cart->save(false);

            // Retrieve the cart of current user
            $cartTable = get_db()->getTable('Cart');
            $cart = $cartTable->getCartOfUser();

            // Return item_ids array
            $json['items'] = array_map(function($i) { return $i->item_id; }, $cart);

        } else {
            $json['error'] = (string)$cart->getErrors();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        echo json_encode($json); // Print JSON like : {"items": [123,456,...]}
    }


    /**
     * Save a note
     */
    public function saveNoteAction() {

        if (!($cart_id = $this->getParam('cart_id')) || !($note = $this->getParam('note')))
            $this->_helper->redirector->gotoUrl('cart/cart');

        $cartTable = get_db()->getTable('Cart');
        $cart = $cartTable->findBy(array('id' => $cart_id))[0];
        $cart->saveNote($note);
        $this->_helper->redirector->gotoUrl('cart/cart');
    }


    /**
     * Remove an item from the cart (call via Ajax)
     *
     * @return JSON The number of item in the connected user's cart
     */
    public function removeAction() {

        $json = array();

        if (!$item_id = $this->getParam('item_id')) {

            $json['error'] = __('Item ID is required');

        } else {

            $item           = get_record_by_id('Item', $item_id);
            $cartTable      = get_db()->getTable('Cart');
            $isInTheCart    = $cartTable->itemIsInTheCart($item);
            if(!$isInTheCart)
                $json['error'] = __("The Item isn't in the user's cart");
        }

        if (!isset($json['error'])) {

            $item       = get_record_by_id('Item', $item_id);
            $cartTable  = get_db()->getTable('Cart');
            $cartTable->removeItemFromTheCart($item);
            $cartOfUser = $cartTable->getCartOfUser();

            // Return item_ids array
            $json['items'] = array_map(function($i) { return $i->item_id; }, $cartOfUser);
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        echo json_encode($json); // Print JSON like : {"items": [123,456,...]}
    }


    /**
     * Empty the cart
     *
     * @return true
     */
    public function emptyAction() {

        $cartTable = get_db()->getTable('Cart');
        $cartTable->emptyUserCart();
        $this->_helper->redirector->gotoUrl('cart/cart');

    }

    /**
     * Remove an item from the cart (call via Ajax)
     *
     * @return JSON The number of item in the connected user's cart
     */
    public function removeItemAction() {

        $id = $this->_getParam('id');
        $item = get_record_by_id('Item', $id);
        $cartTable  = get_db()->getTable('Cart');
        $cartTable->removeItemFromTheCart($item);
        $this->_helper->redirector->gotoUrl('cart/cart');
    }

}

