jQuery(document).ready(function(jQuery) {
  // Init action/detection on script end

  var MESSAGE_TYPE_ERROR = 'error';
  var MESSAGE_TYPE_SUCCESS = 'success';

  // Call cart route, based on element attributes
  // Ajax call take element href with addtionnal param format=json
  // Automatic  add/remove class loading on element (for css)
  function callCart(element) {
    jQuery.ajax({
      beforeSend: function(event) { 
        clearMessage(); 
        jQuery(element).addClass('loading');
      },
      complete: function(event) {
        jQuery(element).removeClass('loading');
      },
      data: {
        format: 'json'
      },
      error: function(event) { displayMessage('JSON error', MESSAGE_TYPE_ERROR); },
      success: function(data) { onSuccess(data, element); },
      type: 'GET',
      url: jQuery(element).attr('href')
    })
  }

  function clearMessage() {
    jQuery('#cart-message')
      .removeClass()
      .css('opacity', '0')
      .text();
  }

  // On ajax error
  // TODO: Display error
  function displayMessage(message, type) {
    jQuery('#cart-message')
      .addClass(type)
      .text(message)
      .animate({opacity: 1}, hideMessage);
  }

  // Hide message, after xxx ms
  function hideMessage() {
    setTimeout(function() {
      jQuery('#cart-message').animate({opacity: 0}, clearMessage);
    }, 2000);
  }

  // On cart action, disable default event (browser redirection), and call cart route
  function onCartAction(event) {
    event.preventDefault();
    callCart(jQuery(this));
  }

  // On ajax success
  function onSuccess(data, element) {
    if (data.error) {
      return displayMessage(data.error, MESSAGE_TYPE_ERROR);
    }
    if (data.message) {
      displayMessage(data.message, MESSAGE_TYPE_SUCCESS);
    }
    var currentId = jQuery(element).data('cart-item-id');
    updateBadge(data.items.length);

    if (!updateCartListView(currentId)) {
      updateButtons(currentId, data.items);
    }
  }

  // In admin bar, update items number in cart link
  function updateBadge(number) {
    jQuery('.view-cart-link span').html(number);
  }

  function updateCartListView(currentId) {
    var descriptionContainer = jQuery('li[data-cart-description-id=' + currentId + ']').first();
    if (descriptionContainer.length !== 0) {
      descriptionContainer.remove();
      if (jQuery('[data-cart-list-items').children().length === 0) {

      }
      return true;
    }

    return false;
  }

  // Update data-cart link state
  function updateButtons(currentId, cartIds) {
    var isInCart = cartIds.indexOf(parseInt(currentId, 10)) !== -1;
    if (isInCart) {
      jQuery('a[data-cart-action="remove"]').removeClass('hide');
      jQuery('a[data-cart-action="add"]').addClass('hide');
    } else {
      jQuery('a[data-cart-action="remove"]').addClass('hide');
      jQuery('a[data-cart-action="add"]').removeClass('hide');
    }
  }

  // Detect click on all link  with data-cart attributes
  jQuery('a[data-cart]').click(onCartAction);

  // On load, if message not empty, hide it
  if (jQuery('#cart-message').text().length !== 0) {
    hideMessage();
  }
});
