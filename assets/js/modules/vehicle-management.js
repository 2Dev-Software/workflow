(function () {
  var root = document.querySelector("[data-vehicle-management]");
  if (!root) {
    return;
  }

  var CONFIRM_APPROVED_ATTR = "data-vehicle-confirm-approved";
  var alertsApi = window.AppAlerts || null;
  var openButtons = document.querySelectorAll("[data-vehicle-modal-open]");
  var closeButtons = document.querySelectorAll("[data-vehicle-modal-close]");
  var editButtons = document.querySelectorAll("[data-vehicle-edit]");
  var deleteButtons = document.querySelectorAll("[data-vehicle-delete-btn]");

  var memberModal = document.getElementById("vehicleMemberModal");
  var memberSearchForm = memberModal
    ? memberModal.querySelector("[data-member-search-form]")
    : null;
  var memberSearchInput = memberModal
    ? memberModal.querySelector("[data-member-search]")
    : null;
  var memberRows = memberModal
    ? Array.from(memberModal.querySelectorAll("[data-member-row]"))
    : [];
  var memberEmptyState = memberModal
    ? memberModal.querySelector("[data-member-empty]")
    : null;
  var memberCount = memberModal
    ? memberModal.querySelector("[data-member-count]")
    : null;

  var editModal = document.getElementById("vehicleEditModal");
  ["vehicleMemberConfirmModal", "vehicleMemberRemoveConfirmModal"].forEach(function (legacyId) {
    var legacyModal = document.getElementById(legacyId);
    if (legacyModal && legacyModal.parentNode) {
      legacyModal.parentNode.removeChild(legacyModal);
    }
  });
  document.querySelectorAll(".alert-overlay").forEach(function (overlay) {
    if (
      overlay.querySelector(
        "[data-vehicle-member-confirm='true'],[data-vehicle-member-cancel='true'],[data-vehicle-member-remove-confirm='true'],[data-vehicle-member-remove-cancel='true']"
      )
    ) {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }
  });

  function openModal(modalId) {
    if (!modalId) {
      return;
    }
    var modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove("hidden");
    }
  }

  function closeModal(modalId) {
    if (!modalId) {
      return;
    }
    var modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("hidden");
    }
  }

  function confirmAction(message, options) {
    var opts = options || {};
    if (alertsApi && typeof alertsApi.confirm === "function") {
      return alertsApi.confirm(message, {
        title: opts.title || "ยืนยันการทำรายการ",
        type: opts.type || "warning",
        confirmButtonText: opts.confirmButtonText || "ยืนยัน",
        cancelButtonText: opts.cancelButtonText || "ยกเลิก",
      });
    }
    return Promise.resolve(window.confirm((opts.title || "ยืนยันการทำรายการ") + "\n" + message));
  }

  function submitApprovedForm(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }
    form.setAttribute(CONFIRM_APPROVED_ATTR, "1");
    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
      return;
    }
    form.submit();
  }

  function updateMemberSearch() {
    if (!memberModal || !memberSearchInput) return;
    var query = memberSearchInput.value.trim().toLowerCase();
    var visibleCount = 0;

    memberRows.forEach(function (row) {
      var haystack = (row.getAttribute("data-member-search") || "").toLowerCase();
      var isMatch = query === "" || haystack.indexOf(query) !== -1;
      row.style.display = isMatch ? "" : "none";
      if (isMatch) {
        visibleCount += 1;
      }
    });

    if (memberCount) {
      memberCount.textContent =
        query === "" ? "ทั้งหมด " + visibleCount + " คน" : "พบ " + visibleCount + " คน";
    }

    if (memberEmptyState) {
      memberEmptyState.classList.toggle("hidden", visibleCount !== 0);
    }
  }

  function openMemberConfirm(form) {
    var row = form.closest("[data-member-row], tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    var message = "โปรดยืนยันการเพิ่ม " + name + " เป็นสมาชิกทีมผู้ดูแลยานพาหนะ";

    confirmAction(message, {
      title: "ยืนยันการเพิ่มสมาชิก",
      type: "warning",
      confirmButtonText: "ยืนยัน",
      cancelButtonText: "ยกเลิก",
    }).then(function (approved) {
      if (!approved) {
        return;
      }
      submitApprovedForm(form);
    });
  }

  function openMemberRemoveConfirm(form) {
    var row = form.closest("tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";
    var message = "โปรดยืนยันการลบ " + name + " ออกจากสมาชิกทีมผู้ดูแลยานพาหนะ";

    confirmAction(message, {
      title: "ยืนยันการลบสมาชิก",
      type: "danger",
      confirmButtonText: "ยืนยัน",
      cancelButtonText: "ยกเลิก",
    }).then(function (approved) {
      if (!approved) {
        return;
      }
      submitApprovedForm(form);
    });
  }

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var targetId = button.getAttribute("data-vehicle-modal-open");
      openModal(targetId);

      if (targetId === "vehicleMemberModal" && memberSearchInput) {
        memberSearchInput.value = "";
        updateMemberSearch();
        memberSearchInput.focus();
      }
    });
  });

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      closeModal(button.getAttribute("data-vehicle-modal-close"));
    });
  });

  document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
    overlay.addEventListener("click", function (event) {
      if (event.target === overlay) {
        overlay.classList.add("hidden");
      }
    });
  });

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.getAttribute(CONFIRM_APPROVED_ATTR) === "1") {
      form.removeAttribute(CONFIRM_APPROVED_ATTR);
      return;
    }
    if (form.matches("[data-member-remove-form]")) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openMemberRemoveConfirm(form);
    }
  });

  document.addEventListener("click", function (event) {
    var addMemberBtn = event.target.closest("[data-member-add-btn]");
    if (!addMemberBtn) return;
    event.preventDefault();
    var form = addMemberBtn.closest("form");
    if (form) {
      openMemberConfirm(form);
    }
  });

  editButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var row = button.closest("[data-vehicle-row]");
      if (!row || !editModal) {
        return;
      }
      var id = row.getAttribute("data-vehicle-id") || "";
      var type = row.getAttribute("data-vehicle-type") || "";
      var plate = row.getAttribute("data-vehicle-plate") || "";
      var brand = row.getAttribute("data-vehicle-brand") || "";
      var model = row.getAttribute("data-vehicle-model") || "";
      var color = row.getAttribute("data-vehicle-color") || "";
      var capacity = row.getAttribute("data-vehicle-capacity") || "4";
      var status = row.getAttribute("data-vehicle-status-label") || "";

      var idInput = editModal.querySelector("[data-vehicle-edit-id]");
      var typeInput = editModal.querySelector("[data-vehicle-edit-type]");
      var plateInput = editModal.querySelector("[data-vehicle-edit-plate]");
      var brandInput = editModal.querySelector("[data-vehicle-edit-brand]");
      var modelInput = editModal.querySelector("[data-vehicle-edit-model]");
      var colorInput = editModal.querySelector("[data-vehicle-edit-color]");
      var capacityInput = editModal.querySelector("[data-vehicle-edit-capacity]");
      var statusSelect = editModal.querySelector("[data-vehicle-edit-status]");

      if (idInput) {
        idInput.value = id;
      }
      if (typeInput) {
        typeInput.value = type;
      }
      if (plateInput) {
        plateInput.value = plate;
      }
      if (brandInput) {
        brandInput.value = brand;
      }
      if (modelInput) {
        modelInput.value = model;
      }
      if (colorInput) {
        colorInput.value = color;
      }
      if (capacityInput) {
        capacityInput.value = capacity || "4";
      }
      if (statusSelect && status) {
        statusSelect.value = status;
      }

      openModal("vehicleEditModal");
    });
  });

  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      var form = button.closest("[data-vehicle-delete-form]");
      if (!form) {
        return;
      }
      var row = button.closest("[data-vehicle-row]");
      var plate = row ? row.getAttribute("data-vehicle-plate") : "";
      var label = plate && plate.trim() !== "" ? plate.trim() : "ยานพาหนะนี้";

      confirmAction("โปรดยืนยันการลบ " + label + " ออกจากระบบ", {
        title: "ยืนยันการลบยานพาหนะ",
        type: "danger",
        confirmButtonText: "ยืนยันลบ",
        cancelButtonText: "ยกเลิก",
      }).then(function (approved) {
        if (!approved) {
          return;
        }
        if (typeof form.requestSubmit === "function") {
          form.requestSubmit();
          return;
        }
        form.submit();
      });
    });
  });

  var searchInput = root.querySelector("[data-vehicle-search-input]");
  var statusFilter = root.querySelector("[data-vehicle-status-filter]");
  var rows = Array.prototype.slice.call(
    root.querySelectorAll("[data-vehicle-row]")
  );
  var emptyRow = root.querySelector("[data-vehicle-empty]");

  function applyFilters() {
    if (!rows.length) {
      return;
    }
    var query = searchInput ? searchInput.value.trim().toLowerCase() : "";
    var status = statusFilter ? statusFilter.value : "all";
    var visibleCount = 0;

    rows.forEach(function (row) {
      var haystack = (row.getAttribute("data-vehicle-search") || "").toLowerCase();
      var rowStatus = row.getAttribute("data-vehicle-status") || "";
      var matchQuery = query === "" || haystack.indexOf(query) !== -1;
      var matchStatus = status === "all" || rowStatus === status;
      var isVisible = matchQuery && matchStatus;
      row.style.display = isVisible ? "" : "none";
      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyRow) {
      emptyRow.classList.toggle("hidden", visibleCount !== 0);
    }
  }

  if (searchInput) {
    searchInput.addEventListener("input", applyFilters);
    searchInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        applyFilters();
      }
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", applyFilters);
  }

  applyFilters();

  var initialModal = root.getAttribute("data-vehicle-open-modal") || "";
  if (initialModal) {
    openModal(initialModal);
    if (initialModal === "vehicleMemberModal" && memberSearchInput) {
      updateMemberSearch();
      memberSearchInput.focus();
    }
  }

  if (memberSearchForm && memberSearchInput) {
    memberSearchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      updateMemberSearch();
    });

    memberSearchInput.addEventListener("input", updateMemberSearch);
  }

  updateMemberSearch();
})();
