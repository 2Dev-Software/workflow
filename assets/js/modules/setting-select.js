(function () {
  function extractNumber(text) {
    var match = String(text || "").match(/(\d{1,4})/);
    return match ? match[1] : "";
  }

  function setHiddenValue(form, value) {
    if (!value) {
      return;
    }
    var yearInput = form.querySelector('input[name="dh_year"]');
    if (yearInput) {
      yearInput.value = value;
    }
    var statusInput = form.querySelector('input[name="dh_status"]');
    if (statusInput) {
      statusInput.value = value;
    }
  }

  function syncFromWrapper(wrapper) {
    if (!wrapper) {
      return;
    }
    var form = wrapper.closest("form");
    if (!form) {
      return;
    }
    var selected = wrapper.querySelector(".custom-option.selected");
    var value = selected ? selected.getAttribute("data-value") : "";
    if (!value) {
      var triggerText = wrapper.querySelector(".select-text");
      value = triggerText ? extractNumber(triggerText.textContent) : "";
    }
    setHiddenValue(form, value);
  }

  document.addEventListener(
    "click",
    function (event) {
      var option = event.target.closest(
        ".custom-setting-options .custom-option"
      );
      if (!option) {
        return;
      }
      var wrapper = option.closest(".custom-select-setting-wrapper");
      if (!wrapper) {
        return;
      }
      var options = wrapper.querySelectorAll(".custom-option");
      options.forEach(function (item) {
        item.classList.remove("selected");
      });
      option.classList.add("selected");

      var triggerText = wrapper.querySelector(".select-text");
      if (triggerText) {
        triggerText.textContent = option.textContent;
      }

      var form = wrapper.closest("form");
      if (form) {
        setHiddenValue(form, option.getAttribute("data-value") || "");
      }
    },
    true
  );

  document.addEventListener(
    "submit",
    function (event) {
      var form = event.target;
      if (!form || !form.classList) {
        return;
      }
      if (
        !form.classList.contains("setting-year-form") &&
        !form.classList.contains("setting-status-form")
      ) {
        return;
      }
      var wrapper = form.querySelector(".custom-select-setting-wrapper");
      syncFromWrapper(wrapper);
    },
    true
  );
})();
