(function (Drupal) {
  Drupal.AjaxCommands.prototype.gpcQuickAddSetAutocompleteValue = function (ajax, response) {
    if (!response.selector || typeof response.value !== 'string') {
      return;
    }

    const element = document.querySelector(response.selector);
    if (!element) {
      return;
    }

    element.value = response.value;
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  };
})(Drupal);
