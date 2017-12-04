jQuery(document).ready(function($) {
  // Init action/detection on script end

  // Call cart route, based on element attributes
  // Ajax call take element href with addtionnal param format=json
  // Automatic  add/remove class loading on element (for css)
  function callCart(element) {
    $.ajax({
      beforeSend: function(event) { $(element).addClass('loading'); },
      complete: function(event) { $(element).removeClass('loading'); },
      data: {
        format: 'json'
      },
      error: function(event) { displayError('JSON error'); },
      success: function(data) { onSuccess(data, element); },
      type: 'GET',
      url: $(element).attr('href')
    })
  }

  // On ajax error
  // TODO: Display error
  function displayError(error) {
    console.log(error);
  }

  // On cart action, disable default event (browser redirection), and call cart route
  function onCartAction(event) {
    event.preventDefault();
    callCart($(this));
  }

  // On ajax success
  function onSuccess(data, element) {
    if (data.error) {
      return displayError(data.error);
    }

    updateBadge(data.items.length);
    updateButtons($(element).data('cart-item-id'), data.items);
  }

  // In admin bar, update items number in cart link
  function updateBadge(number) {
    $('.view-cart-link span').html(number);
  }

  // Update data-cart link state
  function updateButtons(currentId, cartIds) {
    var isInCart = cartIds.indexOf(parseInt(currentId, 10)) !== -1;
    if (isInCart) {
      $('a[data-cart-action="remove"]').removeClass('hide');
      $('a[data-cart-action="add"]').addClass('hide');
    } else {
      $('a[data-cart-action="remove"]').addClass('hide');
      $('a[data-cart-action="add"]').removeClass('hide');
    }
  }

  // Detect click on all link  with data-cart attributes
  $('a[data-cart]').click(onCartAction);
});
