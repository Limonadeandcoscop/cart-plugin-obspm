<?php
/**
 * CartPlugin
 *
 * Enable cart functionality for Omeka items
 *
 * @copyright Copyright 2011-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CartPlugin
 */

define('CART_DIR', dirname(__FILE__));
require_once CART_DIR . '/controllers/CartController.php';

/**
 * The CartPlugin plugin.
 * @package Omeka\Plugins\CartPlugin
 */

/**
 * The Cart plugin.
 * @package Omeka\Plugins\Cart
 */
class CartPlugin extends Omeka_Plugin_AbstractPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Zend_Registry::get('bootstrap')->getResource('Helper');
    }
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'public_head',
        'public_items_show',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'public_navigation_admin_bar',
    );


    /**
     * Simply include JS & CSS files
     * CSS elements of this can be overloaded by your own CSS
     */
    public function hookPublicHead() {

        queue_css_file('cart');
        queue_js_file('cart');
    }

    /**
     * The install process
     */
    public function hookInstall()
    {
        $sql  = "
        CREATE TABLE IF NOT EXISTS `{$this->_db->Cart}` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(10) unsigned NOT NULL,
          `item_id` int(10) unsigned NOT NULL,
          `note` text,
          PRIMARY KEY (`id`),
          UNIQUE KEY `id` (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->query($sql);
    }

    /**
     * The uninstall process
     */
    public function hookUninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->Cart` ";
        $db->query($sql);
    }

    /**
     * Manage display of "add/remove to cart" links
     */
    public function hookPublicItemsShow($args) {

        // Only if user is logged in
        if (!$user = current_user()) return;

        // Retrieve the cart of current user
        $cartTable = get_db()->getTable('Cart');
        $isInTheCart = $cartTable->itemIsInTheCart($args['item']);

        // Call template witch displays "add/remove to cart" links on items
        echo get_view()->partial('links.phtml', array('item' => $args['item'], 'user' => $user, 'isInTheCart' => $isInTheCart));
    }


    /**
     * Add the cart link to the admin bar
     */
    public function filterPublicNavigationAdminBar($navLinks)
    {
        if(!current_user()) {
            return $navLinks;
        }

        // Retrieve the cart of current user
        $cartTable = get_db()->getTable('Cart');
        $cart = $cartTable->getCartOfUser();

        // Manage the admin bar links to place the cart link in second postion (i.e. $navLinks[0])
        foreach($navLinks as $key => $link) {
            $newNavLinks[$key+1] = $link;
        }
        $navLinks       = $newNavLinks;
        $navLinks[0]    = $navLinks[1];

        // Allow view to render html tags
        get_view()->setEscape('trim');

        // Build the link in admin bar
        $nb = count($cart);
        $navLinks[1] = array(
            'label'=> __('Cart')." <span>$nb</span>",
            'class' => 'view-cart-link',
            'uri' => url("cart/cart")
        );

        ksort($navLinks);

        return $navLinks;
    }

}




