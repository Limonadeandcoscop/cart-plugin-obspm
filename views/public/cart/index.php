<?php
/**
 * Admin index view.
 *
 * @package Cart
 * @subpackage Views
 * @copyright Copyright (c) 2009-2011 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

$head = array(
	'title' => __('Your cart'),
	'body_class' => 'primary'
);
echo head($head);
?>

<div id="primary" class="cart-content">
	<h1><?php echo __('Your cart') ?></h1>
	<?php if (count($cart) !== 0): ?>
	<div id="cart-message" class="<?php echo implode(',', $messenger['class']); ?>"><?php echo implode(',', $messenger['message']); ?></div>
	<form id="cart-form" action="<?php echo absolute_url('cart/cart/form/id/'); ?>">
		<button type="submit" name="type" value="pdf"><?php echo __("Generate PDF"); ?></button>
		<button type="submit" name="type" value="csv"><?php echo __("Generate CSV"); ?></button>
		<button type="submit" name="type" value="mail"><?php echo __("Send by mail"); ?></button>
		<ul data-cart-list-items>
			<?php foreach($cart as $c): ?>
				<?php $item = $c['item']; ?>
				<li class="item <?php echo $item->id; ?>" data-cart-description-id="<?php echo $item->id; ?>">
					<input type="checkbox" name="items[]" value="<?php echo $item->id; ?>"/>
					<?php echo link_to_item('', '', 'show', $item); ?>
					<a
						href="<?php echo url('cart/cart/remove?item_id='.$item->id) ?>"
						data-cart
						data-cart-action="remove"
						data-cart-item-id="<?php echo $item->id; ?>"
						class="remove"
						><?php echo __('Remove from cart') ?>
					</a>
					<a class="pdf" target="_blank" href="<?php echo url('cart/cart/form/id/?type=pdf&items[]='.$item->id) ?>"><?php echo __("Generate PDF"); ?></a>
					<a class="csv" target="_blank" href="<?php echo url('cart/cart/form/id/?type=csv&items[]='.$item->id) ?>"><?php echo __("Generate CSV"); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</form>
	<a class="empty" href="<?php echo url('cart/cart/empty') ?>"><?php echo __('Empty cart'); ?></a>
	<?php else: ?>
	<p><?php echo __('No items in your cart') ?></p>
	<?php endif; ?>
</div>

<?php echo foot(); ?>
