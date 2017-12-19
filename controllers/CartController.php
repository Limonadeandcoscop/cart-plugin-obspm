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

        // If message, display it, with status as div class
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
        $this->view->displayBibtext = plugin_is_active('Bibtex');
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
     * Add an item in the cart
     *
     * Return depends on format parameter
     * @return Redirect If no format, redirect to referrer with status message
     * @return JSON If format json, return cart information
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
     * Remove an item from the cart
     *
     * Return depends on format parameter
     * @return Redirect If no format, redirect to referrer with status message
     * @return JSON If format json, return cart information
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
     * Manage cart submission
     *
     * @return PDF If form action is pdf
     * @return PDCSVF If form action is csv
     * @return REDIRECT If form action is mail
     */
    public function formAction() {

        $itemsIds = $this->_getParam('items');

        if (!isset($itemsIds) || count($itemsIds) === 0) {
            $this->_flashMessenger->addMessage(__('You should select at least one notice'), 'error');
            $this->redirect($_SERVER['HTTP_REFERER']);
        }

        // Get "Cart" table
        $cartTable = get_db()->getTable('Cart');

        // Populate array with item objects
        $items = array();
        foreach($itemsIds as $itemId) {
            $item = get_record_by_id("Item", $itemId);
            $items[] = $item;
        }

        switch ($this->_getParam('type')) {
            case 'bibtex':
                $url = '/items/browse?output=bibtex&range=' . implode(',', $itemsIds);
                header('Location: ' . url($url));
                break;
            case 'csv':
                new Cart_CSV($items);
                break;
            case 'mail':
                $this->_redirecMailto($items);
                break;
            case 'pdf':
                new Cart_Pdf($items);
                break;
        }
    }

    /**
     * Redirect user with mailto:
     * Format mail body with cart items
     */
    private function _redirecMailto($items) {
        $subject = get_option('site_title');
        $body = array();
        $body[] = __('Bonjour');
        $body[] = __('J\'ai pensé que ce contenu, extrait de la bibliothèque numérique de l\'Observatoire de Paris, pourrait vous intéresser.');

        $newline = '%0D%0A';
        foreach($items as $item) {
            $list = [];
            $list[] = metadata($item, array('Dublin Core', 'Title')) . ': ';
            $list[] = absolute_url(
                array(
                    'controller' => 'items',
                    'module' => NULL,
                    'action' => 'show',
                    'id'=> $item->id
                )
            );
            $body[] = implode($newline, $list);
        }
        $body[] = __('A bientôt');
        $body[] = absolute_url('/');

        $mailto = 'mailto:?subject=' . $subject . '&body=' . implode($newline.$newline, $body);
        header('Location: ' . $mailto);
    }
    
    /**
     * Format response, depends on format value (empy or json for now)
     * 
     * @return Redirect If no format, redirect to referrer with status message
     * @return JSON If format json, return cart information
     */
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

