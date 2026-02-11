(function () {
  window.App = window.App || {};

  function showError(input, message) {
    var group = input.closest('.form__group') || input.parentElement;
    if (!group) {
      return;
    }
    var existing = group.querySelector('.field-error');
    if (existing) {
      existing.textContent = message;
      return;
    }
    var error = document.createElement('span');
    error.className = 'field-error';
    error.textContent = message;
    group.appendChild(error);
  }

  function clearError(input) {
    var group = input.closest('.form__group') || input.parentElement;
    if (!group) {
      return;
    }
    var existing = group.querySelector('.field-error');
    if (existing) {
      existing.remove();
    }
  }

  function validateForm(form) {
    var isValid = true;
    var required = form.querySelectorAll('[required]');
    required.forEach(function (input) {
      if (!input.value.trim()) {
        showError(input, 'กรุณากรอกข้อมูล');
        isValid = false;
      } else {
        clearError(input);
      }
    });
    return isValid;
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-validate]')) {
      return;
    }
    if (!validateForm(form)) {
      event.preventDefault();
    }
  });

  window.App.autosaveDraft = function (form, callback) {
    if (!form) {
      return;
    }
    var timeout;
    form.addEventListener('input', function () {
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        if (callback) {
          callback(new FormData(form));
        }
      }, 1000);
    });
  };

  window.App.uploadWithProgress = function (url, file, onProgress) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.upload.onprogress = function (event) {
      if (event.lengthComputable && onProgress) {
        onProgress(Math.round((event.loaded / event.total) * 100));
      }
    };
    var formData = new FormData();
    formData.append('file', file);
    xhr.send(formData);
  };
})();
