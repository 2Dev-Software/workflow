(function () {
  var root = document.querySelector("[data-vehicle-management]");
  if (!root) {
    return;
  }

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

  var memberConfirmModal = document.getElementById("vehicleMemberConfirmModal");
  var memberConfirmMessage = memberConfirmModal
    ? memberConfirmModal.querySelector("[data-vehicle-member-confirm-message]")
    : null;
  var memberConfirmButton = memberConfirmModal
    ? memberConfirmModal.querySelector('[data-vehicle-member-confirm="true"]')
    : null;
  var memberCancelButton = memberConfirmModal
    ? memberConfirmModal.querySelector('[data-vehicle-member-cancel="true"]')
    : null;

  var memberRemoveConfirmModal = document.getElementById(
    "vehicleMemberRemoveConfirmModal"
  );
  var memberRemoveMessage = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        "[data-vehicle-member-remove-message]"
      )
    : null;
  var memberRemoveConfirmButton = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        '[data-vehicle-member-remove-confirm="true"]'
      )
    : null;
  var memberRemoveCancelButton = memberRemoveConfirmModal
    ? memberRemoveConfirmModal.querySelector(
        '[data-vehicle-member-remove-cancel="true"]'
      )
    : null;

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
  var pendingMemberForm = null;
  var pendingRemoveForm = null;

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
    pendingMemberForm = form;
    var row = form.closest("[data-member-row], tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";

    if (memberConfirmMessage) {
      memberConfirmMessage.textContent =
        "โปรดยืนยันการเพิ่ม " + name + " เป็นสมาชิกทีมผู้ดูแลยานพาหนะ";
    }
    if (memberConfirmModal) {
      memberConfirmModal.classList.remove("hidden");
    }
  }

  function openMemberRemoveConfirm(form) {
    pendingRemoveForm = form;
    var row = form.closest("tr");
    var nameEl = row ? row.querySelector("strong") : null;
    var name =
      nameEl && nameEl.textContent.trim() !== ""
        ? nameEl.textContent.trim()
        : "บุคลากรคนนี้";

    if (memberRemoveMessage) {
      memberRemoveMessage.textContent =
        "โปรดยืนยันการลบ " + name + " ออกจากสมาชิกทีมผู้ดูแลยานพาหนะ";
    }
    if (memberRemoveConfirmModal) {
      memberRemoveConfirmModal.classList.remove("hidden");
    }
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
    if (form.matches("[data-member-remove-form]")) {
      event.preventDefault();
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

  if (memberConfirmButton) {
    memberConfirmButton.addEventListener("click", function () {
      if (pendingMemberForm) {
        pendingMemberForm.submit();
      }
    });
  }

  if (memberCancelButton) {
    memberCancelButton.addEventListener("click", function () {
      if (memberConfirmModal) {
        memberConfirmModal.classList.add("hidden");
      }
      pendingMemberForm = null;
    });
  }

  if (memberRemoveConfirmButton) {
    memberRemoveConfirmButton.addEventListener("click", function () {
      if (pendingRemoveForm) {
        pendingRemoveForm.submit();
      }
    });
  }

  if (memberRemoveCancelButton) {
    memberRemoveCancelButton.addEventListener("click", function () {
      if (memberRemoveConfirmModal) {
        memberRemoveConfirmModal.classList.add("hidden");
      }
      pendingRemoveForm = null;
    });
  }

  if (memberConfirmModal) {
    memberConfirmModal.addEventListener("click", function (event) {
      if (event.target === memberConfirmModal) {
        memberConfirmModal.classList.add("hidden");
        pendingMemberForm = null;
      }
    });
  }

  if (memberRemoveConfirmModal) {
    memberRemoveConfirmModal.addEventListener("click", function (event) {
      if (event.target === memberRemoveConfirmModal) {
        memberRemoveConfirmModal.classList.add("hidden");
        pendingRemoveForm = null;
      }
    });
  }

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
