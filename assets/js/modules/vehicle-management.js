(function () {
  var root = document.querySelector("[data-vehicle-management]");
  if (!root) {
    return;
  }

  var openButtons = document.querySelectorAll("[data-vehicle-modal-open]");
  var closeButtons = document.querySelectorAll("[data-vehicle-modal-close]");
  var editButtons = document.querySelectorAll("[data-vehicle-edit]");
  var deleteButtons = document.querySelectorAll("[data-vehicle-delete-btn]");

  var editModal = document.getElementById("vehicleEditModal");
  var deleteModal = document.getElementById("vehicleDeleteConfirmModal");
  var deleteMessage = deleteModal
    ? deleteModal.querySelector("[data-vehicle-delete-message]")
    : null;
  var deleteConfirm = deleteModal
    ? deleteModal.querySelector("[data-vehicle-delete-confirm]")
    : null;
  var deleteCancel = deleteModal
    ? deleteModal.querySelector("[data-vehicle-delete-cancel]")
    : null;
  var pendingDeleteForm = null;

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

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      openModal(button.getAttribute("data-vehicle-modal-open"));
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
      if (!form || !deleteModal) {
        return;
      }
      pendingDeleteForm = form;
      var row = button.closest("[data-vehicle-row]");
      var plate = row ? row.getAttribute("data-vehicle-plate") : "";
      var label = plate && plate.trim() !== "" ? plate.trim() : "ยานพาหนะนี้";
      if (deleteMessage) {
        deleteMessage.textContent = "โปรดยืนยันการลบ " + label + " ออกจากระบบ";
      }
      openModal("vehicleDeleteConfirmModal");
    });
  });

  if (deleteConfirm) {
    deleteConfirm.addEventListener("click", function () {
      if (pendingDeleteForm) {
        pendingDeleteForm.submit();
      }
    });
  }

  if (deleteCancel) {
    deleteCancel.addEventListener("click", function () {
      closeModal("vehicleDeleteConfirmModal");
      pendingDeleteForm = null;
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener("click", function (event) {
      if (event.target === deleteModal) {
        closeModal("vehicleDeleteConfirmModal");
        pendingDeleteForm = null;
      }
    });
  }

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
  }
})();
