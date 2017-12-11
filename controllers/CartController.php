<?php

class Cart_CartController extends Omeka_Controller_AbstractActionController {

    /**
     * @var Omeka_Controller_Action_Helper_FlashMessenger
     */
    private $_flashMessenger;

    public function init() {
        $this->_helper->db->setDefaultModelName('Cart');

        // Disable view rendering
        $this->_helper->viewRenderer->setNoRender(true);

        // FlashMessenger
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');

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

        $flash = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        $messenger = array(
            'class' => [],
            'message' => []
        );
        if ($flash->hasMessages()) {
            foreach ($flash->getMessages() as $status => $messages) {
            array_push($messenger['class'], $status);
                foreach ($messages as $message) {
                array_push($messenger['message'], $message);
                }
            }
        }

        $this->view->cart = $cart;
        $this->view->messenger = $messenger;
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

        if (!$cart->isValid($_GET)) {
            $error = (string)$cart->getErrors();
        } else { // Add item to the cart
            $cart->save(false);
        }

        // Response
        $this->_formatResponse(
            $this->getParam('format'),
            __('Notice successfuly added to your cart'),
            isset($error) ? $error : null
        );
    }


    /**
     * Remove an item from the cart (call via Ajax)
     *
     * @return JSON The number of item in the connected user's cart
     */
    public function removeAction() {
        if (!$item_id = $this->getParam('item_id')) {
            $error = __('Item ID is required');
        } else {
            $item           = get_record_by_id('Item', $item_id);
            $cartTable      = get_db()->getTable('Cart');
            $isInTheCart    = $cartTable->itemIsInTheCart($item);
            if (!$isInTheCart) {
                $error = __("The Item isn't in the user's cart");
            }
        }

        // If no error, delete item in user cart
        if (!isset($error)) {
            $cartTable->removeItemFromTheCart($item);
        }

        // Response
        $this->_formatResponse(
            $this->getParam('format'),
            __('Notice successfuly removed from your cart'),
            isset($error) ? $error : null
        );
    }


    /**
     * Empty the cart
     *
     * @return true
     */
    public function emptyAction() {

        $cartTable = get_db()->getTable('Cart');
        $cartTable->emptyUserCart();
        $this->_flashMessenger->addMessage( __('Successfully emptied cart'), 'success');
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


    /**
     * Generate PDF for items
     *
     * @return void
     */
    public function pdfAction()
    {
        // Retrieve comma separated item IDs
        $itemsIds = explode(',', $this->getParam('id'));

        // Get "Cart" table
        $cartTable = get_db()->getTable('Cart');

        // Populate array with item objects
        $items = array();
        foreach($itemsIds as $itemId) {
            $item = get_record_by_id("Item", $itemId);
            $item->note = $cartTable->getNoteOfItem($item);
            $items[] = $item;
        }

        // Call PDF helper
        $pdf = new Cart_Pdf($items);
    }
    
    private function _formatResponse($format, $message, $error) {
        if ($format === 'json') {
            // Retrieve the cart of current user
            $cartTable = get_db()->getTable('Cart');
            $cartOfUser = $cartTable->getCartOfUser();

            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $json = array('items' => array_map(function($i) { return $i->item_id; }, $cartOfUser));
            if (isset($error)) {
                $json['error'] = $error;
            } else {
                $json['message'] = $message;
            }
            echo json_encode($json); // Print JSON like : {"items": [123,456,...]}
        } else {
            if ( isset($error)) {
                $message = $error;
                $status = 'error';
            } else {
                $message = $message;
                $status = 'success';
            }
            $this->_flashMessenger->addMessage($message, $status);
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }

}

