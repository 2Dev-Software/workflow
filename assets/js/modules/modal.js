(function () {
  window.App = window.App || {};

  function openModal(modal) {
    if (!modal) {
      return;
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(modal) {
    if (!modal) {
      return;
    }
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-modal-open],[data-modal-target]');
    if (trigger) {
      var selector = trigger.getAttribute('data-modal-open') || trigger.getAttribute('data-modal-target');
      var target = selector ? document.querySelector(selector) : null;
      openModal(target);
      return;
    }

    var closer = event.target.closest('[data-modal-close]');
    if (closer) {
      var modal = closer.closest('.c-modal, .modal');
      closeModal(modal);
    }
  });

  window.App.modal = {
    open: openModal,
    close: closeModal,
  };
})();
