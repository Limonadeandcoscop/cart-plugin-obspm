<?php
/**
 * Admin index view.
 *
 * @package Cart
 * @subpackage Views
 * @copyright Copyright (c) 2009-2011 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

$head = array('title'      => __('Your cart'),
              'body_class' => 'primary');
echo head($head);
?>

<div id="primary" class="cart-content">

<h1><?php echo __('Your cart') ?></h1>

<?php if (count($cart)): ?>
	<ul>
		<?php foreach($cart as $c): ?>
			<?php $item = $c['item']; ?>
			<li class="item <?php echo $item->id; ?>">
				<?php echo link_to_item('', '', 'show', $item); ?>
				<a
					href="<?php echo url('cart/cart/remove?item_id='.$item->id) ?>"
					data-cart
					data-cart-action="remove"
					data-cart-item-id="<?php echo $item->id; ?>"
					class="remove"
					><?php echo __('Remove from cart') ?>
				</a>
				<form action="<?php echo url('cart/cart/save-note') ?>" method="post" class="note">
					<textarea cols="80" rows="3" name="note"><?php echo $c->note; ?></textarea>
					<input type="hidden" name="cart_id" value="<?php echo $c->id ?>" />
					<input type="submit" value="<?php echo __('Save note'); ?>" />
				</form>
			 </li>
		<?php endforeach; ?>
		<a class="empty" href="<?php echo url('cart/cart/empty') ?>"><?php echo __('Empty cart'); ?></a>
	</ul>
<?php else: ?>
	<p><?php echo __('No items in your cart') ?></p>
<?php endif; ?>


</div>

<?php echo foot(); ?>
