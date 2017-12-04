<?php
/**
 * Cart
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * A Cart row.
 *
 * @package Omeka\Plugins\CollectionTree
 */
class Cart extends Omeka_Record_AbstractRecord
{
    public $item_id;
    public $user_id;

    /**
     * Validate the record.
     */
    protected function _validate()
    {
        $user = current_user();

    	if (!$this->item_id) {

    		$this->addError('Cart error', __('The item ID is required'));

    	} elseif (!$user) {

    		$this->addError('Cart error', __('The user must be logged in'));

    	} elseif ($this->item_id && $user->id) {

	    	$tableCart = $this->getTable('Cart');
	    	$result = $tableCart->findBy(array('item_id' => $this->item_id, 'user_id' => $user->id));
	    	if ($result) {
	    		$this->addError('Cart error', __('The user has already add this item to its cart'));
	    	};
	    }
    }

}
