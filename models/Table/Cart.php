<?php
/**
 * Cart
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Cart table.
 *
 * @package Omeka\Plugins\CollectionTree
 */
class Table_Cart extends Omeka_Db_Table
{
    /**
     * Return the cart of connected user or NULL if the user is not connected
     *
     * @return Array|null A record set
     */
    public function getCartOfUser()
    {
        if (!$user=current_user()) return false;

        return $this->findBy(array('user_id' => $user->id));
    }


    /**
     * Check if an item is in the cart of the current user
     *
     * @param Item $item The Item object
     * @return Item|null A record set
     */
    public function itemIsInTheCart($item)
    {
        if (!$user=current_user()) return false;

        if (!$item) return false;

        return $this->findBy(array('user_id' => $user->id, 'item_id' => $item->id));
    }


    /**
     * Remove an item from the a cart of connected user
     *
     * @param Item $item The Item object
     * @return true|false
     */
    public function removeItemFromTheCart($item)
    {
        if (!$user = current_user()) return false;

        if (!$item) return false;

        $itemInTheCart = $this->findBy(array('user_id' => $user->id, 'item_id' => $item->id));

        if($itemInTheCart) {
            return $itemInTheCart[0]->delete();
        }

    }

    /**
     * Empty the user cart
     *
     * @return true|false
     */
    public function emptyUserCart()
    {
        if (!$user = current_user()) return false;

        $sql = "DELETE FROM omeka_carts WHERE user_id = ".$user->id;
        $this->query($sql);
    }

    /**
     * Get note of an item
     *
     * @param Item $item The Item object
     * @return String The note
     */
    public function getNoteOfItem($item)
    {
        $isInTheCart = $this->itemIsInTheCart($item);
        return $isInTheCart[0]->note;
    }

}
