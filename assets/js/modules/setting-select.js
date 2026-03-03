(function () {
  var SETTING_YEAR_CONFIRMED_ATTR = "data-setting-year-confirmed";
  var SETTING_STATUS_CONFIRMED_ATTR = "data-setting-status-confirmed";
  var SETTING_DUTY_CONFIRMED_ATTR = "data-setting-duty-confirmed";

  function getSwalInstance() {
    if (window.Swal && typeof window.Swal.fire === "function") {
      return window.Swal;
    }
    if (window.Sweetalert2 && typeof window.Sweetalert2.fire === "function") {
      return window.Sweetalert2;
    }
    return null;
  }

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

  function submitForm(form, confirmedAttr) {
    form.setAttribute(confirmedAttr, "1");

    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
      return;
    }
    form.submit();
  }

  function ensureSwalLoaded() {
    return new Promise(function (resolve) {
      if (getSwalInstance()) {
        resolve(true);
        return;
      }

      var existing = document.querySelector('script[data-swal-local="1"]');
      if (existing) {
        existing.addEventListener("load", function () {
          if (!window.Swal && window.Sweetalert2 && typeof window.Sweetalert2.fire === "function") {
            window.Swal = window.Sweetalert2;
          }
          resolve(!!getSwalInstance());
        });
        existing.addEventListener("error", function () {
          resolve(false);
        });
        return;
      }

      var script = document.createElement("script");
      script.src = "assets/js/vendor/sweetalert2.all.min.js";
      script.async = true;
      script.setAttribute("data-swal-local", "1");
      script.addEventListener("load", function () {
        if (!window.Swal && window.Sweetalert2 && typeof window.Sweetalert2.fire === "function") {
          window.Swal = window.Sweetalert2;
        }
        resolve(!!getSwalInstance());
      });
      script.addEventListener("error", function () {
        resolve(false);
      });
      document.head.appendChild(script);
    });
  }

  function confirmSettingSave(form, confirmedAttr, title, message) {
    var showSwalConfirm = function () {
      var swal = getSwalInstance();

      if (!swal) {
        return;
      }

      swal.fire({
        icon: "warning",
        title: title,
        text: message,
        showCancelButton: true,
        confirmButtonText: "ยืนยัน",
        cancelButtonText: "ยกเลิก",
        reverseButtons: true,
      }).then(function (result) {
        if (result && result.isConfirmed) {
          submitForm(form, confirmedAttr);
        }
      });
    };

    if (
      window.AppAlerts &&
      typeof window.AppAlerts.confirm === "function" &&
      getSwalInstance()
    ) {
      window.AppAlerts
        .confirm(message, {
          title: title,
          confirmButtonText: "ยืนยัน",
          cancelButtonText: "ยกเลิก",
        })
        .then(function (approved) {
          if (approved) {
            submitForm(form, confirmedAttr);
          }
        });
      return;
    }

    if (getSwalInstance()) {
      showSwalConfirm();
      return;
    }

    ensureSwalLoaded().then(function (ready) {
      if (ready && getSwalInstance()) {
        showSwalConfirm();
        return;
      }
    });
  }

  function confirmYearSave(form) {
    confirmSettingSave(
      form,
      SETTING_YEAR_CONFIRMED_ATTR,
      "ยืนยันการบันทึก",
      "ยืนยันการบันทึกปีสารบรรณใช่หรือไม่?"
    );
  }

  function confirmStatusSave(form) {
    confirmSettingSave(
      form,
      SETTING_STATUS_CONFIRMED_ATTR,
      "ยืนยันการบันทึก",
      "ยืนยันการบันทึกสถานะของระบบใช่หรือไม่?"
    );
  }

  function confirmDutySave(form) {
    confirmSettingSave(
      form,
      SETTING_DUTY_CONFIRMED_ATTR,
      "ยืนยันการบันทึก",
      "ยืนยันการบันทึกการปฏิบัติราชการของผู้บริหารใช่หรือไม่?"
    );
  }

  document.addEventListener(
    "submit",
    function (event) {
      var form = event.target;
      if (!form || !form.classList) {
        return;
      }
      if (
        !form.classList.contains("setting-year-form") &&
        !form.classList.contains("setting-status-form") &&
        !form.classList.contains("setting-duty-form")
      ) {
        return;
      }
      var wrapper = form.querySelector(".custom-select-setting-wrapper");
      syncFromWrapper(wrapper);

      var isYearForm = form.classList.contains("setting-year-form");
      var isStatusForm = form.classList.contains("setting-status-form");
      var isDutyForm = form.classList.contains("setting-duty-form");

      if (!isYearForm && !isStatusForm && !isDutyForm) {
        return;
      }

      if (
        (isYearForm && form.getAttribute(SETTING_YEAR_CONFIRMED_ATTR) === "1") ||
        (isStatusForm && form.getAttribute(SETTING_STATUS_CONFIRMED_ATTR) === "1") ||
        (isDutyForm && form.getAttribute(SETTING_DUTY_CONFIRMED_ATTR) === "1")
      ) {
        if (isYearForm) {
          form.removeAttribute(SETTING_YEAR_CONFIRMED_ATTR);
        }
        if (isStatusForm) {
          form.removeAttribute(SETTING_STATUS_CONFIRMED_ATTR);
        }
        if (isDutyForm) {
          form.removeAttribute(SETTING_DUTY_CONFIRMED_ATTR);
        }
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      if (isYearForm) {
        confirmYearSave(form);
        return;
      }

      if (isStatusForm) {
        confirmStatusSave(form);
        return;
      }

      if (isDutyForm) {
        confirmDutySave(form);
      }
    },
    true
  );
})();
