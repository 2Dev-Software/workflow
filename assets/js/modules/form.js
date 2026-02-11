(function () {
  function findFieldWrapper(input) {
    return input.closest('.c-field') || input.parentElement;
  }

  function setError(input, message) {
    var wrapper = findFieldWrapper(input);
    if (!wrapper) {
      return;
    }
    wrapper.classList.add('c-field--error');
    var error = wrapper.querySelector('.c-field__error');
    if (!error) {
      error = document.createElement('div');
      error.className = 'c-field__error';
      wrapper.appendChild(error);
    }
    error.textContent = message;
  }

  function clearError(input) {
    var wrapper = findFieldWrapper(input);
    if (!wrapper) {
      return;
    }
    wrapper.classList.remove('c-field--error');
    var error = wrapper.querySelector('.c-field__error');
    if (error) {
      error.remove();
    }
  }

  function validate(form) {
    var valid = true;
    var errors = [];
    var required = form.querySelectorAll('[required]');
    required.forEach(function (input) {
      if (!input.value.trim()) {
        setError(input, 'กรุณากรอกข้อมูล');
        valid = false;
        errors.push('กรุณากรอกข้อมูลให้ครบถ้วน');
      } else {
        clearError(input);
      }
    });

    var summary = form.querySelector('.c-form__summary');
    if (!valid) {
      if (!summary) {
        summary = document.createElement('div');
        summary.className = 'c-form__summary';
        form.prepend(summary);
      }
      summary.textContent = errors[0];
    } else if (summary) {
      summary.remove();
    }

    return valid;
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.hasAttribute('data-validate')) {
      return;
    }
    if (!validate(form)) {
      event.preventDefault();
    }
  });
})();
