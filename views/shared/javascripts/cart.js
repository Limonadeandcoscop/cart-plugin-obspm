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

  // Hide message
  function clearMessage() {
    jQuery('#cart-message')
      .removeClass()
      .css('opacity', '0')
      .text();
  }

  // Display message
  // message: text to display
  // type: class added to div (success, error....)
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

  // Is cart page 
  function isCartView() {
    return jQuery('[data-cart-list-items]').length !== 0;
  }

  // On cart action, disable default event (browser redirection), and call cart route
  function onCartAction(event) {
    event.preventDefault();
    callCart(jQuery(this));
  }

  // On ajax success
  function onSuccess(data, element) {
    // If error, display it
    if (data.error) {
      return displayMessage(data.error, MESSAGE_TYPE_ERROR);
    }

    // Update badge count
    var currentId = jQuery(element).data('cart-item-id');
    updateBadge(data.items.length);

    if (isCartView()) {
      // If cart empty, no need to update view, reload page
      if (data.items.length === 0) {
        window.location.reload();
        return;
      }
  
      // Remove removed item
      var descriptionContainer = jQuery('li[data-cart-description-id=' + currentId + ']').first();
      if (descriptionContainer.length !== 0) {
          descriptionContainer.remove();
      }
    } else {
      updateButtons(currentId, data.items);
    }
  
    // Display message
    if (data.message) {
      displayMessage(data.message, MESSAGE_TYPE_SUCCESS);
    }
  }

  // On Cart Submit
  function onCartSubmit(event) {
    event.preventDefault();
    
    // If no notice selected, display error message
    if (jQuery('#cart-form input:checked').length === 0) {
      displayMessage('You should select at least one notice', MESSAGE_TYPE_ERROR);
      return;
    }

    var formData = {};
    var type = jQuery(this).attr('value');
    jQuery.each(jQuery('#cart-form').serializeArray(), function(i,o){
      if (formData[this.name]) {
        if (!formData[this.name].push) {
          formData[this.name] = [formData[this.name]];
        }
        formData[this.name].push(this.value || '');
      } else {
        formData[this.name] = this.value || '';
      }
    });

    // Submit form, in new window, with data
    var url = jQuery('#cart-form').attr('action') + '?type=' + type + '&' + jQuery('#cart-form').serialize();
    window.open(url);
  }

  // In admin bar, update items number in cart link
  function updateBadge(number) {
    jQuery('.view-cart-link span').html(number);
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

  // On form, detect submit and button click
  jQuery('#cart-form').submit(onCartSubmit);
  jQuery('#cart-form button').click(onCartSubmit);

  // On load, if message not empty, hide it
  if (jQuery('#cart-message').text().length !== 0) {
    hideMessage();
  }
});
