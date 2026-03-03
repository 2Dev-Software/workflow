(function () {
  "use strict";

  var CONFIRM_APPROVED_ATTR = "data-confirm-approved";

  function getSwalInstance() {
    if (window.Swal && typeof window.Swal.fire === "function") {
      return window.Swal;
    }
    if (window.Sweetalert2 && typeof window.Sweetalert2.fire === "function") {
      return window.Sweetalert2;
    }
    return null;
  }

  function hasSwal() {
    return getSwalInstance() !== null;
  }

  function normalizeType(type) {
    var value = String(type || "").trim().toLowerCase();
    if (value === "danger") return "error";
    if (value === "warning") return "warning";
    if (value === "success") return "success";
    return "info";
  }

  function fire(options) {
    var opts = options || {};
    var swal = getSwalInstance();
    var icon = normalizeType(opts.type || opts.icon || "info");
    var title = String(opts.title || "");
    var text = String(opts.message || opts.text || "");
    var showConfirmButton = opts.showConfirmButton !== false;
    var confirmButtonText = String(opts.confirmButtonText || "ตกลง");

    if (!swal) {
      if (title && text) {
        window.console && console.warn && console.warn(title + ": " + text);
      } else if (title) {
        window.console && console.warn && console.warn(title);
      } else if (text) {
        window.console && console.warn && console.warn(text);
      }
      return Promise.resolve({ isConfirmed: true, isDismissed: false });
    }

    return swal.fire({
      icon: icon,
      title: title || undefined,
      text: text || undefined,
      showConfirmButton: showConfirmButton,
      confirmButtonText: confirmButtonText,
      showCancelButton: opts.showCancelButton === true,
      cancelButtonText: String(opts.cancelButtonText || "ยกเลิก"),
      reverseButtons: opts.reverseButtons !== false,
      allowOutsideClick: opts.allowOutsideClick !== false,
      allowEscapeKey: opts.allowEscapeKey !== false,
      timer: Number.isFinite(Number(opts.timer)) ? Number(opts.timer) : undefined,
      timerProgressBar: opts.timerProgressBar === true,
    });
  }

  function confirm(message, options) {
    var opts = options || {};
    var text = String(message || "");
    var title = String(opts.title || "ยืนยันการทำรายการ");

    if (!hasSwal()) {
      var fallbackMessage = title;
      if (text !== "") {
        fallbackMessage += "\n" + text;
      }
      return Promise.resolve(window.confirm(fallbackMessage));
    }

    return fire({
      type: opts.type || "warning",
      title: title,
      message: text,
      showConfirmButton: true,
      confirmButtonText: opts.confirmButtonText || "ยืนยัน",
      showCancelButton: true,
      cancelButtonText: opts.cancelButtonText || "ยกเลิก",
      allowOutsideClick: opts.allowOutsideClick === true,
      allowEscapeKey: opts.allowEscapeKey === true,
    }).then(function (result) {
      return !!(result && result.isConfirmed);
    });
  }

  function readAlertPayload(el) {
    if (!el) {
      return null;
    }
    var raw = el.getAttribute("data-app-alert");
    if (!raw) {
      return null;
    }

    try {
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== "object") {
        return null;
      }
      return parsed;
    } catch (error) {
      return null;
    }
  }

  function firePayload(payload) {
    if (!payload) {
      return Promise.resolve();
    }

    var hideButton = payload.hide_button === true;
    var delayMs = Number(payload.delay_ms || 0);
    var redirect = String(payload.redirect || "");
    var showConfirmButton = !hideButton;
    var timer = hideButton && delayMs > 0 ? delayMs : undefined;

    return fire({
      type: payload.type || "info",
      title: payload.title || "",
      message: payload.message || "",
      showConfirmButton: showConfirmButton,
      confirmButtonText: payload.button_label || "ยืนยัน",
      timer: timer,
      timerProgressBar: timer !== undefined,
      allowOutsideClick: !hideButton,
      allowEscapeKey: !hideButton,
    }).then(function () {
      if (redirect !== "") {
        var waitMs = hideButton && delayMs > 0 ? 0 : Math.max(delayMs, 0);
        window.setTimeout(function () {
          window.location.href = redirect;
        }, waitMs);
      }
    });
  }

  function consumePayloadElement(el) {
    if (!el) return;
    var payload = readAlertPayload(el);
    if (!payload) return;
    firePayload(payload);
    if (el.parentNode) {
      el.parentNode.removeChild(el);
    }
  }

  function bindServerAlertPayloads(scope) {
    var root = scope || document;
    var nodes = root.querySelectorAll("[data-app-alert]");
    nodes.forEach(function (node) {
      consumePayloadElement(node);
    });
  }

  function bindConfirmHandlers() {
    document.addEventListener(
      "click",
      function (event) {
        var trigger = event.target && event.target.closest ? event.target.closest("[data-confirm]") : null;
        if (!trigger) {
          return;
        }

        if (trigger.getAttribute(CONFIRM_APPROVED_ATTR) === "1") {
          trigger.removeAttribute(CONFIRM_APPROVED_ATTR);
          return;
        }

        var message = String(trigger.getAttribute("data-confirm") || "").trim();
        if (message === "") {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        confirm(message, {
          title: trigger.getAttribute("data-confirm-title") || "ยืนยันการทำรายการ",
          confirmButtonText: trigger.getAttribute("data-confirm-ok") || "ยืนยัน",
          cancelButtonText: trigger.getAttribute("data-confirm-cancel") || "ยกเลิก",
        }).then(function (approved) {
          if (!approved) {
            return;
          }

          if (trigger.tagName === "A") {
            var href = trigger.getAttribute("href");
            if (href && href !== "#") {
              window.location.href = href;
            }
            return;
          }

          var form = trigger.closest("form");
          if (!form) {
            return;
          }

          trigger.setAttribute(CONFIRM_APPROVED_ATTR, "1");
          if (typeof form.requestSubmit === "function") {
            form.requestSubmit(trigger);
          } else {
            form.submit();
          }
        });
      },
      true
    );

    document.addEventListener(
      "submit",
      function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
          return;
        }

        var submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        var submitterMessage = submitter ? String(submitter.getAttribute("data-confirm") || "").trim() : "";
        var formMessage = String(form.getAttribute("data-confirm") || "").trim();
        var message = submitterMessage !== "" ? submitterMessage : formMessage;

        if (message === "" && submitter) {
          return;
        }

        if (message === "") {
          return;
        }

        if (form.getAttribute(CONFIRM_APPROVED_ATTR) === "1") {
          form.removeAttribute(CONFIRM_APPROVED_ATTR);
          return;
        }

        if (submitter && submitter.getAttribute(CONFIRM_APPROVED_ATTR) === "1") {
          submitter.removeAttribute(CONFIRM_APPROVED_ATTR);
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        confirm(message, {
          title: (submitter && submitter.getAttribute("data-confirm-title")) || form.getAttribute("data-confirm-title") || "ยืนยันการทำรายการ",
          confirmButtonText: (submitter && submitter.getAttribute("data-confirm-ok")) || form.getAttribute("data-confirm-ok") || "ยืนยัน",
          cancelButtonText: (submitter && submitter.getAttribute("data-confirm-cancel")) || form.getAttribute("data-confirm-cancel") || "ยกเลิก",
        }).then(function (approved) {
          if (!approved) {
            return;
          }

          form.setAttribute(CONFIRM_APPROVED_ATTR, "1");

          if (typeof form.requestSubmit === "function") {
            if (submitter) {
              form.requestSubmit(submitter);
            } else {
              form.requestSubmit();
            }
            return;
          }

          form.submit();
        });
      },
      true
    );
  }

  function bindMutationObserver() {
    if (typeof MutationObserver !== "function") {
      return;
    }
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!(node instanceof Element)) {
            return;
          }
          if (node.hasAttribute("data-app-alert")) {
            consumePayloadElement(node);
            return;
          }
          bindServerAlertPayloads(node);
        });
      });
    });
    observer.observe(document.documentElement || document.body, {
      childList: true,
      subtree: true,
    });
  }

  window.AppAlerts = {
    fire: fire,
    confirm: confirm,
    firePayload: firePayload,
    consumePayloadElement: consumePayloadElement,
  };

  // Replace native alert with SweetAlert2 for all legacy call sites.
  window.alert = function (message) {
    fire({
      type: "warning",
      title: "แจ้งเตือน",
      message: String(message || ""),
      showConfirmButton: true,
      confirmButtonText: "ตกลง",
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      bindServerAlertPayloads(document);
      bindConfirmHandlers();
      bindMutationObserver();
    });
  } else {
    bindServerAlertPayloads(document);
    bindConfirmHandlers();
    bindMutationObserver();
  }
})();
