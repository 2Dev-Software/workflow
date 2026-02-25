(function () {
  "use strict";

  var toArray = function (nodeList) {
    return Array.prototype.slice.call(nodeList || []);
  };

  var splitMemberIds = function (raw) {
    return String(raw || "")
      .split(",")
      .map(function (item) {
        return item.trim();
      })
      .filter(function (item) {
        return item !== "";
      });
  };

  var initSearchableSelect = function () {
    toArray(document.querySelectorAll("[data-select-searchable='1']")).forEach(function (wrapper) {
      var input = wrapper.querySelector("[data-select-search-input]");
      var select = wrapper.querySelector("select");

      if (!input || !select) {
        return;
      }

      var updateMultipleSummary = function () {
        var summary = document.querySelector(
          "[data-select-multiple-summary][data-select-target='" + select.id + "']"
        );

        if (!summary || !select.multiple) {
          return;
        }

        var selectedCount = toArray(select.options).filter(function (option) {
          return option.selected;
        }).length;

        summary.textContent = "เลือกแล้ว " + selectedCount + " รายการ";
      };

      input.addEventListener("input", function () {
        var keyword = input.value.trim().toLowerCase();
        toArray(select.options).forEach(function (option) {
          if (option.value === "") {
            return;
          }
          var text = String(option.textContent || "").toLowerCase();
          option.hidden = keyword !== "" && text.indexOf(keyword) === -1;
        });
      });

      select.addEventListener("change", updateMultipleSummary);
      updateMultipleSummary();
    });
  };

  var initOrdersCreate = function () {
    var root = document.querySelector("[data-orders-create]");

    if (!root) {
      return;
    }

    var input = root.querySelector("[data-orders-attachment-input]");
    var budget = root.querySelector("[data-orders-attachment-budget]");
    var hint = root.querySelector("[data-orders-attachment-hint]");
    var limit = parseInt(root.getAttribute("data-orders-attachment-limit") || "5", 10);

    if (!input || !budget || !hint || Number.isNaN(limit)) {
      return;
    }

    var existing = parseInt(budget.getAttribute("data-existing-count") || "0", 10);

    if (Number.isNaN(existing)) {
      existing = 0;
    }

    var render = function () {
      var selectedCount = input.files ? input.files.length : 0;
      var used = existing + selectedCount;
      budget.textContent = "ใช้แล้ว " + used + "/" + limit + " ไฟล์";

      if (selectedCount === 0) {
        hint.textContent = "";
        hint.classList.remove("is-danger");
        return;
      }

      if (used > limit) {
        hint.textContent = "ไฟล์เกินโควตา: ระบบอนุญาตสูงสุด " + limit + " ไฟล์";
        hint.classList.add("is-danger");
      } else {
        hint.textContent = "ไฟล์ใหม่ " + selectedCount + " ไฟล์ พร้อมบันทึก";
        hint.classList.remove("is-danger");
      }
    };

    input.addEventListener("change", render);
    render();
  };

  var initRecipientPicker = function () {
    var root = document.querySelector("[data-recipient-picker]");

    if (!root) {
      return;
    }

    var sendRoot = document.querySelector("[data-orders-send]");
    var sourceCountNode = sendRoot ? sendRoot.querySelector("[data-recipient-source-count]") : null;
    var uniqueCountNode = sendRoot ? sendRoot.querySelector("[data-recipient-unique-count]") : null;
    var warningNode = sendRoot ? sendRoot.querySelector("[data-recipient-warning]") : null;

    var calculate = function () {
      var checked = toArray(root.querySelectorAll("input[data-recipient-option]:checked"));
      var unique = new Set();

      checked.forEach(function (input) {
        var members = splitMemberIds(input.getAttribute("data-member-pids"));

        if (members.length === 0 && input.value) {
          members = [String(input.value)];
        }

        members.forEach(function (pid) {
          unique.add(pid);
        });
      });

      if (sourceCountNode) {
        sourceCountNode.textContent = String(checked.length);
      }

      if (uniqueCountNode) {
        uniqueCountNode.textContent = String(unique.size);
      }

      if (warningNode) {
        warningNode.textContent = unique.size === 0 ? "กรุณาเลือกผู้รับอย่างน้อย 1 คน" : "";
      }

      return {
        selectedSources: checked.length,
        uniqueRecipients: unique.size,
      };
    };

    toArray(root.querySelectorAll("[data-recipient-search]")).forEach(function (searchInput) {
      searchInput.addEventListener("input", function () {
        var section = searchInput.closest("[data-recipient-group]");
        var keyword = String(searchInput.value || "").trim().toLowerCase();

        if (!section) {
          return;
        }

        toArray(section.querySelectorAll("[data-recipient-item]")).forEach(function (item) {
          var searchText = String(item.getAttribute("data-search") || "").toLowerCase();
          var visible = keyword === "" || searchText.indexOf(keyword) !== -1;
          item.classList.toggle("is-hidden", !visible);
        });
      });
    });

    toArray(root.querySelectorAll("input[data-recipient-option]")).forEach(function (checkbox) {
      checkbox.addEventListener("change", calculate);
    });

    calculate();

    var form = document.querySelector("[data-orders-send-form]");

    if (!form) {
      return;
    }

    form.addEventListener("submit", function (event) {
      var summary = calculate();
      var orderNo = sendRoot ? String(sendRoot.getAttribute("data-order-no") || "") : "";
      var orderSubject = sendRoot ? String(sendRoot.getAttribute("data-order-subject") || "") : "";

      if (summary.uniqueRecipients <= 0) {
        event.preventDefault();
        if (warningNode) {
          warningNode.textContent = "กรุณาเลือกผู้รับอย่างน้อย 1 คน";
        }
        return;
      }

      var confirmMessage =
        "ยืนยันการส่งคำสั่ง\n" +
        "เลขที่: " + orderNo + "\n" +
        "เรื่อง: " + orderSubject + "\n" +
        "จำนวนผู้รับจริง: " + summary.uniqueRecipients + " คน";

      if (!window.confirm(confirmMessage)) {
        event.preventDefault();
      }
    });
  };

  var init = function () {
    initSearchableSelect();
    initOrdersCreate();
    initRecipientPicker();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
