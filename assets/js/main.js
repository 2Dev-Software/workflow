/**
 * Main Application Script
 * Level: Production
 * Structure: Module Pattern (IIFE)
 */
window.addEventListener("load", function () {
  const overlay = document.getElementById("preloader-overlay");

  overlay.classList.add("preloader-hidden");

  overlay.addEventListener("transitionend", function () {
    overlay.remove();
  });
});

const calendarFallbackEvents = {};

class Calendar {
  constructor(config) {
    this.monthYear = document.querySelector(config.monthYearSelector);
    this.dates = document.querySelector(config.datesSelector);
    this.prevBtn = document.querySelector(config.prevBtnSelector);
    this.nextBtn = document.querySelector(config.nextBtnSelector);
    this.locale = config.locale || "th-TH";
    this.events = config.events || {};
    this.mode = config.mode || "mixed";
    this.useThaiYear = Boolean(config.useThaiYear);

    this.currentDate = new Date();

    this.modalOverlay = document.getElementById("event-modal-overlay");
    this.closeModalBtn = document.getElementById("close-modal-btn");
    this.modalTitle = document.getElementById("modal-date-title");
    this.roomTableBody = document.getElementById("room-table-body");
    this.carTableBody = document.getElementById("car-table-body");
    this.roomSection = document.getElementById("room-booking-section");
    this.carSection = document.getElementById("car-booking-section");
    this.noEventMessage = document.getElementById("no-event-message");

    this.attachEvents();
    this.updateCalendar();
  }

  filterEvents(events) {
    if (!Array.isArray(events) || events.length === 0) {
      return null;
    }

    if (this.mode === "room") {
      const roomEvents = events.filter((ev) => ev.type === "room");
      return roomEvents.length > 0 ? roomEvents : null;
    }

    return events;
  }

  attachEvents() {
    if (this.prevBtn) {
      this.prevBtn.addEventListener("click", () => {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.updateCalendar();
      });
    }

    if (this.nextBtn) {
      this.nextBtn.addEventListener("click", () => {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.updateCalendar();
      });
    }

    if (this.closeModalBtn) {
      this.closeModalBtn.addEventListener("click", () => this.closeModal());
    }
    if (this.modalOverlay) {
      this.modalOverlay.addEventListener("click", (e) => {
        if (e.target === this.modalOverlay) this.closeModal();
      });
    }
  }

  updateCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const totalDay = lastDay.getDate();
    const firstDayIndex = firstDay.getDay();
    const lastDayIndex = lastDay.getDay();

    let monthYearString = this.currentDate.toLocaleString(this.locale, {
      month: "long",
      year: "numeric",
    });

    if (this.useThaiYear) {
      const monthsTh = [
        "มกราคม",
        "กุมภาพันธ์",
        "มีนาคม",
        "เมษายน",
        "พฤษภาคม",
        "มิถุนายน",
        "กรกฎาคม",
        "สิงหาคม",
        "กันยายน",
        "ตุลาคม",
        "พฤศจิกายน",
        "ธันวาคม",
      ];
      monthYearString = `${monthsTh[month]} ${year + 543}`;
    }

    if (this.monthYear) {
      this.monthYear.textContent = monthYearString;
    }

    const fragment = document.createDocumentFragment();

    for (let i = 0; i < firstDayIndex; i++) {
      fragment.appendChild(this.createDateCell("", true));
    }

    for (let i = 1; i <= totalDay; i++) {
      const date = new Date(year, month, i);
      const isToday = date.toDateString() === new Date().toDateString();

      const eventKey = `${year}-${month + 1}-${i}`;
      const dayEvents = this.filterEvents(this.events[eventKey] || null);

      fragment.appendChild(
        this.createDateCell(i, false, isToday, dayEvents, eventKey)
      );
    }

    for (let i = lastDayIndex + 1; i <= 6; i++) {
      fragment.appendChild(this.createDateCell("", true));
    }

    if (this.dates) {
      this.dates.innerHTML = "";
      this.dates.appendChild(fragment);
    }
  }

  createDateCell(
    text,
    inactive = false,
    active = false,
    events = null,
    dateKey = null
  ) {
    const div = document.createElement("div");
    div.classList.add("date");

    if (inactive) {
      div.classList.add("inactive");
      div.textContent = text;
      return div;
    }

    if (active) div.classList.add("active");

    div.innerHTML = `<span>${text}</span>`;

    if (events && events.length > 0) {
      div.classList.add("has-event");

      const iconContainer = document.createElement("div");
      iconContainer.classList.add("event-icons");

      let hasCar = false;
      let hasRoom = false;

      events.forEach((ev) => {
        if (this.mode !== "room" && ev.type === "car" && !hasCar) {
          iconContainer.innerHTML += `<i class="fa-solid fa-car"></i>`;
          hasCar = true;
        }
        if (ev.type === "room" && !hasRoom) {
          iconContainer.innerHTML += `<i class="fa-solid fa-building"></i>`;
          hasRoom = true;
        }
      });

      div.appendChild(iconContainer);

      div.addEventListener("click", () => {
        this.openModal(dateKey, events);
      });
    }

    return div;
  }

  openModal(dateKey, events) {
    if (!this.modalOverlay) return;

    const filteredEvents = this.filterEvents(events) || [];

    const dateObj = new Date(dateKey);
    const monthsTh = [
      "มกราคม",
      "กุมภาพันธ์",
      "มีนาคม",
      "เมษายน",
      "พฤษภาคม",
      "มิถุนายน",
      "กรกฎาคม",
      "สิงหาคม",
      "กันยายน",
      "ตุลาคม",
      "พฤศจิกายน",
      "ธันวาคม",
    ];
    const splitDate = dateKey.split("-");
    const thaiDate = `วันที่ ${splitDate[2]} ${monthsTh[splitDate[1] - 1]} ${
      parseInt(splitDate[0]) + 543
    }`;

    this.modalTitle.innerText = thaiDate;

    if (this.roomTableBody) this.roomTableBody.innerHTML = "";
    if (this.carTableBody) this.carTableBody.innerHTML = "";
    if (this.roomSection) this.roomSection.classList.add("hidden");
    if (this.carSection) this.carSection.classList.add("hidden");
    if (this.noEventMessage) this.noEventMessage.classList.add("hidden");

    let hasRoomData = false;
    let hasCarData = false;

    filteredEvents.forEach((ev) => {
      if (ev.type === "room") {
        hasRoomData = true;
        const row = `
                  <tr>
                      <td>${ev.title}</td>
                      <td>${ev.time}</td>
                      <td>${ev.detail}</td>
                      <td>${ev.count}</td>
                      <td>${ev.owner}</td>
                  </tr>
              `;
        if (this.roomTableBody) this.roomTableBody.innerHTML += row;
      } else if (this.mode !== "room" && ev.type === "car") {
        hasCarData = true;
        const row = `
                  <tr>
                      <td>${ev.title}</td>
                      <td>${ev.time}</td>
                      <td>${ev.detail}</td>
                      <td>${ev.owner}</td>
                  </tr>
              `;
        if (this.carTableBody) this.carTableBody.innerHTML += row;
      }
    });

    if (hasRoomData && this.roomSection) this.roomSection.classList.remove("hidden");
    if (hasCarData && this.carSection) this.carSection.classList.remove("hidden");

    if (!hasRoomData && !hasCarData) {
      if (this.noEventMessage) {
        this.noEventMessage.classList.remove("hidden");
        if (this.mode === "room") {
          this.noEventMessage.textContent = "ไม่มีรายการจองห้องในวันนี้";
        } else {
          this.noEventMessage.innerHTML =
            "<ul>" + events.map((e) => `<li>${e.title}</li>`).join("") + "</ul>";
        }
      }
    }

    this.modalOverlay.classList.remove("hidden");
  }

  closeModal() {
    if (this.modalOverlay) {
      this.modalOverlay.classList.add("hidden");
    }
  }
}

function resolveCalendarEvents() {
  if (typeof window !== "undefined" && window.roomBookingEvents) {
    return window.roomBookingEvents;
  }

  var dataEl = document.getElementById("roomBookingEventsData");
  if (dataEl) {
    var raw = dataEl.value || dataEl.textContent || "";
    if (raw) {
      try {
        var parsed = JSON.parse(raw);
        if (parsed && typeof parsed === "object") {
          if (typeof window !== "undefined") {
            window.roomBookingEvents = parsed;
          }
          return parsed;
        }
      } catch (error) {
        // ignore malformed data and fall back to empty events
      }
    }
  }

  return calendarFallbackEvents;
}

const calendarMode = document.body
  ? document.body.dataset.calendarMode || "mixed"
  : "mixed";
const calendarThaiYear =
  document.body && document.body.dataset.calendarThaiYear === "true";
const calendarEvents = resolveCalendarEvents();

const calendar1 = new Calendar({
  monthYearSelector: "#month-year",
  datesSelector: "#dates-calendar",
  prevBtnSelector: "#prev-btn",
  nextBtnSelector: "#next-btn",
  locale: "th-TH",
  events: calendarEvents,
  mode: calendarMode,
  useThaiYear: calendarThaiYear,
});

if (typeof window !== "undefined") {
  window.roomBookingCalendar = calendar1;
}

document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("toggleNewsBtn");
  const closeBtn = document.getElementById("closeNewsBtn");
  const notificationSection = document.getElementById("notificationSection");

  if (toggleBtn && notificationSection) {
    toggleBtn.addEventListener("click", function () {
      notificationSection.classList.add("active");
    });
  }

  if (closeBtn && notificationSection) {
    closeBtn.addEventListener("click", function () {
      notificationSection.classList.remove("active");
    });
  }
});

// dashboard-sidebar-toggle
document.addEventListener("DOMContentLoaded", () => {
  const BREAKPOINT_WIDTH = "1024px";
  const sidebar = document.querySelector(".sidebar");
  const closeBtn = document.querySelector("#btn-toggle");

  if (!sidebar || !closeBtn) {
    console.warn("Dashboard Sidebar or Toggle Button not found in DOM.");
    return;
  }

  const updateIconState = (isClosed) => {
    closeBtn.classList.remove("fa-angle-left", "fa-angle-right");

    if (isClosed) {
      closeBtn.classList.add("fa-angle-right");
    } else {
      closeBtn.classList.add("fa-angle-left");
    }
  };

  const handleToggleClick = () => {
    sidebar.classList.toggle("close");
    const isClosed = sidebar.classList.contains("close");
    updateIconState(isClosed);
  };

  const mediaQuery = window.matchMedia(`(min-width: ${BREAKPOINT_WIDTH})`);
  const handleScreenChange = (e) => {
    if (e.matches) {
      sidebar.classList.remove("close");
    } else {
      sidebar.classList.add("close");
    }

    updateIconState(sidebar.classList.contains("close"));
  };

  closeBtn.addEventListener("click", handleToggleClick);

  mediaQuery.addEventListener("change", handleScreenChange);

  handleScreenChange(mediaQuery);
});

//teacher-phone-directory
document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("teacher-table-body");
  if (!tableBody) return;

  const endpoint =
    tableBody.dataset.endpoint || "public/api/teacher-directory-api.php";
  const searchInput = document.getElementById("search-input");
  const paginationContainer = document.getElementById("pagination");
  const countText = document.getElementById("count-text");

  const dropdownWrapper = document.querySelector(".custom-select-wrapper");
  const dropdownTrigger = document.querySelector(".custom-select-trigger");
  const dropdownOptions = document.querySelectorAll(".custom-option");
  const displayValue = document.getElementById("select-value");
  const hiddenSelect = document.getElementById("real-page-select");

  const urlParams = new URLSearchParams(window.location.search);
  const initialPerPage =
    urlParams.get("per_page") || (hiddenSelect ? hiddenSelect.value : "10");

  const state = {
    page: Math.max(parseInt(urlParams.get("page") || "1", 10), 1),
    perPage: initialPerPage,
    query: urlParams.get("q") || (searchInput ? searchInput.value.trim() : ""),
  };

  const buildParams = () => {
    const params = new URLSearchParams();
    if (state.page > 1) params.set("page", String(state.page));
    if (state.perPage && state.perPage !== "10")
      params.set("per_page", state.perPage);
    if (state.query) params.set("q", state.query);
    return params;
  };

  const updateUrl = (params) => {
    const nextUrl = params.toString()
      ? `${window.location.pathname}?${params.toString()}`
      : window.location.pathname;
    window.history.replaceState({}, "", nextUrl);
  };

  const renderCount = (total) => {
    if (!countText) return;
    const pTag = countText.querySelector("p");
    const message = `จำนวน ${total} รายชื่อ`;
    if (pTag) {
      pTag.textContent = message;
    } else {
      countText.innerHTML = `<p>${message}</p>`;
    }
  };

  const renderRows = (rows) => {
    tableBody.innerHTML = "";
    if (!rows || rows.length === 0) {
      tableBody.innerHTML =
        '<tr><td colspan="3" style="text-align:center; padding: 20px;">ไม่พบข้อมูล</td></tr>';
      return;
    }

    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");

      const nameCell = document.createElement("td");
      nameCell.textContent = row.fName || "";
      tr.appendChild(nameCell);

      const deptCell = document.createElement("td");
      deptCell.textContent = row.department_name || "";
      tr.appendChild(deptCell);

      const phoneCell = document.createElement("td");
      phoneCell.textContent = row.telephone || "";
      tr.appendChild(phoneCell);

      fragment.appendChild(tr);
    });
    tableBody.appendChild(fragment);
  };

  const createButton = (label, page, isActive, isDisabled) => {
    const button = document.createElement("button");
    button.type = "button";
    button.setAttribute("data-page", String(page));
    button.innerHTML = label;
    if (isActive) button.classList.add("active");
    if (isDisabled) button.disabled = true;
    return button;
  };

  const createSpan = (text) => {
    const span = document.createElement("span");
    span.innerText = text;
    span.style.padding = "0 5px";
    return span;
  };

  const renderPagination = (totalPages, currentPage) => {
    if (!paginationContainer) return;
    paginationContainer.innerHTML = "";

    if (!totalPages || totalPages <= 1) return;

    const prevPage = Math.max(1, currentPage - 1);
    const nextPage = Math.min(totalPages, currentPage + 1);

    paginationContainer.appendChild(
      createButton(
        '<i class="fas fa-chevron-left"></i>',
        prevPage,
        false,
        currentPage === 1
      )
    );

    let startPage = 1;
    let endPage = totalPages;

    if (totalPages > 7) {
      if (currentPage <= 4) {
        endPage = 5;
      } else if (currentPage >= totalPages - 3) {
        startPage = totalPages - 4;
      } else {
        startPage = currentPage - 2;
        endPage = currentPage + 2;
      }
    }

    if (startPage > 1) {
      paginationContainer.appendChild(
        createButton("1", 1, currentPage === 1, false)
      );
      if (startPage > 2) paginationContainer.appendChild(createSpan("..."));
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationContainer.appendChild(
        createButton(String(i), i, i === currentPage, false)
      );
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationContainer.appendChild(createSpan("..."));
      }
      paginationContainer.appendChild(
        createButton(
          String(totalPages),
          totalPages,
          currentPage === totalPages,
          false
        )
      );
    }

    paginationContainer.appendChild(
      createButton(
        '<i class="fas fa-chevron-right"></i>',
        nextPage,
        false,
        currentPage === totalPages
      )
    );
  };

  const fetchDirectory = async () => {
    const params = buildParams();
    const requestUrl = params.toString()
      ? `${endpoint}?${params.toString()}`
      : endpoint;

    try {
      const response = await fetch(requestUrl, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
      }

      const payload = await response.json();
      const meta = payload.meta || {};

      state.page = Math.max(parseInt(meta.page || state.page, 10), 1);
      renderRows(payload.data || []);
      renderCount(Number(meta.total || 0));
      renderPagination(Number(meta.total_pages || 0), state.page);
      updateUrl(buildParams());
    } catch (error) {
      console.error(error);
    }
  };

  let searchTimer = null;

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      window.clearTimeout(searchTimer);
      const keyword = searchInput.value.trim();
      searchTimer = window.setTimeout(() => {
        state.query = keyword;
        state.page = 1;
        fetchDirectory();
      }, 400);
    });

    searchInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        window.clearTimeout(searchTimer);
        state.query = searchInput.value.trim();
        state.page = 1;
        fetchDirectory();
      }
    });
  }

  //custom-dropdown
  if (dropdownTrigger && dropdownWrapper) {
    dropdownTrigger.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdownWrapper.classList.toggle("open");
    });

    dropdownOptions.forEach((option) => {
      option.addEventListener("click", function () {
        dropdownOptions.forEach((opt) => opt.classList.remove("selected"));
        this.classList.add("selected");
        if (displayValue) displayValue.textContent = this.textContent;
        dropdownWrapper.classList.remove("open");

        const val = this.getAttribute("data-value") || "10";
        if (hiddenSelect) hiddenSelect.value = val;

        state.perPage = val;
        state.page = 1;
        fetchDirectory();
      });
    });

    window.addEventListener("click", (e) => {
      if (dropdownWrapper && !dropdownWrapper.contains(e.target)) {
        dropdownWrapper.classList.remove("open");
      }
    });
  }

  if (paginationContainer) {
    paginationContainer.addEventListener("click", (e) => {
      const target = e.target.closest("button[data-page]");
      if (!target || target.disabled) return;
      const page = parseInt(target.getAttribute("data-page") || "1", 10);
      state.page = Math.max(page, 1);
      fetchDirectory();
    });
  }

  fetchDirectory();
});

function openTab(tabName, evt) {
  const contents = document.getElementsByClassName("tab-content");
  Array.from(contents).forEach((content) => content.classList.remove("active"));

  const buttons = document.getElementsByClassName("tab-btn");
  Array.from(buttons).forEach((btn) => btn.classList.remove("active"));

  const selectedTab = document.getElementById(tabName);
  if (selectedTab) {
    selectedTab.classList.add("active");
  }

  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.add("active");
  }
}

let tempImageSrc = null;

function openImageModal() {
  const imageModal = document.getElementById("imageModal");
  if (imageModal) imageModal.classList.remove("hidden");
}

function closeImageModal() {
  const imageModal = document.getElementById("imageModal");
  const profileFileInput = document.getElementById("profileFileInput");
  const imagePreview = document.getElementById("imagePreview");
  const previewPlaceholder = document.getElementById("previewPlaceholder");

  if (imageModal) imageModal.classList.add("hidden");
  if (profileFileInput) profileFileInput.value = "";

  if (imagePreview) {
    imagePreview.src = "#";
    imagePreview.classList.add("hidden");
  }
  if (previewPlaceholder) previewPlaceholder.style.display = "block";

  tempImageSrc = null;
}

function previewProfileImage(input) {
  const imagePreview = document.getElementById("imagePreview");
  const previewPlaceholder = document.getElementById("previewPlaceholder");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      if (imagePreview) {
        imagePreview.src = e.target.result;
        imagePreview.classList.remove("hidden");
      }
      if (previewPlaceholder) previewPlaceholder.style.display = "none";
      tempImageSrc = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function confirmImageChange() {
  const profileForm = document.getElementById("profileImageForm");
  const profileInput = document.getElementById("profileFileInput");

  if (!profileInput || !profileInput.files || !profileInput.files[0]) {
    alert("กรุณาเลือกรูปภาพก่อน");
    return;
  }

  if (profileForm) {
    profileForm.submit();
  }
}

let tempSignatureSrc = null;

function handleSignatureSelect(input) {
  const signaturePreview = document.getElementById("signaturePreview");
  const signatureModal = document.getElementById("signatureModal");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      if (signaturePreview) {
        signaturePreview.src = e.target.result;
        signaturePreview.classList.remove("hidden");
      }
      tempSignatureSrc = e.target.result;
      if (signatureModal) signatureModal.classList.remove("hidden");
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function closeSignatureModal() {
  const signatureModal = document.getElementById("signatureModal");
  const signatureFileInput = document.getElementById("signatureFileInput");

  if (signatureModal) signatureModal.classList.add("hidden");
  if (signatureFileInput) signatureFileInput.value = "";
  tempSignatureSrc = null;
}

function confirmSignatureChange() {
  const signatureForm = document.getElementById("signatureUploadForm");
  const signatureInput = document.getElementById("signatureFileInput");

  if (!signatureInput || !signatureInput.files || !signatureInput.files[0]) {
    alert("กรุณาเลือกไฟล์ลายเซ็นก่อน");
    return;
  }

  if (signatureForm) {
    signatureForm.submit();
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const phoneInput = document.getElementById("phoneInput");
  const phoneForm = document.getElementById("phoneForm");
  const modal = document.getElementById("confirmModal");
  const showPhone = document.getElementById("showPhone");
  const confirmBtn = document.getElementById("confirmBtn");
  const cancelBtn = document.getElementById("cancelBtn");
  const tabButtons = document.querySelectorAll("[data-tab-target]");
  const profileOpenButtons = document.querySelectorAll(
    '[data-action="profile-image-open"]'
  );
  const profileFileButtons = document.querySelectorAll(
    '[data-action="profile-image-file-open"]'
  );
  const profileConfirmButton = document.querySelector(
    '[data-action="profile-image-confirm"]'
  );
  const profileCancelButton = document.querySelector(
    '[data-action="profile-image-cancel"]'
  );
  const signatureOpenButton = document.querySelector(
    '[data-action="signature-file-open"]'
  );
  const signatureCancelButton = document.querySelector(
    '[data-action="signature-cancel"]'
  );
  const profileFileInput = document.getElementById("profileFileInput");
  const signatureFileInput = document.getElementById("signatureFileInput");

  if (phoneInput) {
    phoneInput.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, "").slice(0, 10);
    });
    phoneInput.addEventListener("blur", function () {
      const phone = phoneInput.value.trim();

      if (phone) {
        if (showPhone) showPhone.textContent = phone;
        if (modal) modal.style.display = "flex";
      }
    });

    phoneInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        phoneInput.blur();
      }
    });
  }

  if (confirmBtn) {
    confirmBtn.addEventListener("click", function () {
      if (phoneForm) {
        phoneForm.submit();
      }
      if (modal) modal.style.display = "none";
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      if (modal) modal.style.display = "none";
    });
  }

  if (tabButtons.length > 0) {
    tabButtons.forEach((btn) => {
      btn.addEventListener("click", function (event) {
        const target = btn.getAttribute("data-tab-target");
        if (target) {
          openTab(target, event);
        }
      });
    });
  }

  profileOpenButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      openImageModal();
    });
  });

  profileFileButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      if (profileFileInput) {
        profileFileInput.click();
      }
    });
  });

  if (profileFileInput) {
    profileFileInput.addEventListener("change", function () {
      previewProfileImage(profileFileInput);
    });
  }

  if (profileConfirmButton) {
    profileConfirmButton.addEventListener("click", function () {
      confirmImageChange();
    });
  }

  if (profileCancelButton) {
    profileCancelButton.addEventListener("click", function () {
      closeImageModal();
    });
  }

  if (signatureOpenButton) {
    signatureOpenButton.addEventListener("click", function () {
      if (signatureFileInput) {
        signatureFileInput.click();
      }
    });
  }

  if (signatureFileInput) {
    signatureFileInput.addEventListener("change", function () {
      handleSignatureSelect(signatureFileInput);
    });
  }

  if (signatureCancelButton) {
    signatureCancelButton.addEventListener("click", function () {
      closeSignatureModal();
    });
  }

  const signatureModal = document.getElementById("signatureModal");

  window.addEventListener("click", function (e) {
    if (signatureModal && e.target === signatureModal) {
      closeSignatureModal();
    }
    const imageModal = document.getElementById("imageModal");
    if (imageModal && e.target === imageModal) {
      closeImageModal();
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const yearWrappers = document.querySelectorAll(".js-year-generator");

  if (yearWrappers.length > 0) {
    const date = new Date();
    const currentYearAD = date.getFullYear();
    const currentYearTH = currentYearAD + 543;
    const yearsToShow = [currentYearTH - 1, currentYearTH, currentYearTH + 1];

    yearWrappers.forEach((wrapper) => {
      const container = wrapper.querySelector(".options-container");
      const triggerText = wrapper.querySelector(".select-text");

      container.innerHTML = "";

      yearsToShow.forEach((year) => {
        const option = document.createElement("p");
        option.classList.add("custom-option");
        option.setAttribute("data-value", year);
        option.textContent = `ปีสารบรรณ ${year}`;

        if (year === currentYearTH) {
          option.classList.add("selected");
          if (triggerText) triggerText.textContent = `ปีสารบรรณ ${year}`;
        }

        container.appendChild(option);
      });
    });
  }

  const allSelectWrappers = document.querySelectorAll(
    ".custom-select-setting-wrapper"
  );

  allSelectWrappers.forEach((wrapper) => {
    const selectBox = wrapper.querySelector(".custom-setting-select");
    const triggerText = wrapper.querySelector(".select-text");
    const optionsContainer = wrapper.querySelector(".options-container");

    selectBox.addEventListener("click", function (e) {
      document
        .querySelectorAll(".custom-setting-select.open")
        .forEach((opened) => {
          if (opened !== selectBox) opened.classList.remove("open");
        });

      this.classList.toggle("open");
    });

    optionsContainer.addEventListener("click", function (e) {
      if (e.target.classList.contains("custom-option")) {
        e.stopPropagation();

        const siblings = optionsContainer.querySelectorAll(".custom-option");
        siblings.forEach((opt) => opt.classList.remove("selected"));

        e.target.classList.add("selected");

        if (triggerText) triggerText.textContent = e.target.textContent;

        const parentForm = wrapper.closest("form");
        const yearInput = parentForm
          ? parentForm.querySelector('input[name="dh_year"]')
          : null;
        if (yearInput) {
          yearInput.value = e.target.getAttribute("data-value") || "";
        }

        const statusInput = parentForm
          ? parentForm.querySelector('input[name="dh_status"]')
          : null;
        if (statusInput) {
          statusInput.value = e.target.getAttribute("data-value") || "";
        }

        selectBox.classList.remove("open");
      }
    });
  });

  window.addEventListener("click", function (e) {
    if (!e.target.closest(".custom-setting-select")) {
      document
        .querySelectorAll(".custom-setting-select.open")
        .forEach((box) => {
          box.classList.remove("open");
        });
    }
  });

  const saveButtons = document.querySelectorAll(".btn-save");

  saveButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      if (this.dataset.submit === "true") {
        return;
      }
      const parentContainer = this.closest(".setting-year-container");

      if (parentContainer) {
        const selectedOption = parentContainer.querySelector(
          ".custom-option.selected"
        );
        const title =
          parentContainer.querySelector(".setting-title").textContent;

        if (selectedOption) {
          const value = selectedOption.getAttribute("data-value");

          console.log(`กำลังบันทึกข้อมูล [${title}]:`, value);

          location.reload();
        } else {
          console.log(`กรุณาเลือกข้อมูลในหัวข้อ "${title}" ก่อนบันทึก`);
        }
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const dutyCheckboxes = document.querySelectorAll(
    'input[name="exec_duty_pid"]'
  );

  dutyCheckboxes.forEach((box) => {
    box.addEventListener("change", function () {
      if (this.checked) {
        dutyCheckboxes.forEach((otherBox) => {
          if (otherBox !== this) otherBox.checked = false;
        });
      }
    });
  });

  const btnSaveDuty = document.querySelector(".btn-save-duty");

  if (btnSaveDuty) {
    btnSaveDuty.addEventListener("click", function () {
      if (this.dataset.submit === "true") {
        return;
      }

      const selected = document.querySelector(
        'input[name="exec_duty_pid"]:checked'
      );

      if (selected) {
        const value = selected.value;

        const row = selected.closest("tr");
        const name = row.querySelector("td:first-child").textContent.trim();

        console.log(`กำลังบันทึกข้อมูล: ${name} (Value: ${value})`);
        console.log(`บันทึกสถานะผู้ปฏิบัติราชการ: "${name}" เรียบร้อยแล้ว`);

        location.reload();
      } else {
        console.log("กรุณาเลือกผู้ปฏิบัติราชการอย่างน้อย 1 ท่าน");
      }
    });
  }
});

const startDateInput = document.getElementById("startDate");
const endDateInput = document.getElementById("endDate");
const dayCountDisplay = document.querySelector("#dayCount [data-day-count]");
const startTimeInput = document.getElementById("startTime");
const endTimeInput = document.getElementById("endTime");
const vehicleForm = document.getElementById("vehicleReservationForm");
const departmentInput = document.getElementById("department");
const departmentWrapper = document.getElementById("dept-wrapper");
const departmentError = document.getElementById("departmentError");
const companionCountInput = document.getElementById("companionCount");
const passengerCountInput = document.getElementById("passengerCount");
const memberDropdown = document.getElementById("myDropdown");
const writeDateInput = document.getElementById("writeDate");

function setDepartmentError(isError) {
  if (!departmentWrapper || !departmentError) return;
  departmentWrapper.classList.toggle("is-invalid", isError);
  departmentWrapper.setAttribute("aria-invalid", isError ? "true" : "false");
  departmentError.classList.toggle("hidden", !isError);
}

function updateCompanionCount() {
  if (!memberDropdown || !companionCountInput) return;
  const checkedBoxes = memberDropdown.querySelectorAll(
    'input[type="checkbox"]:checked'
  );
  companionCountInput.value = String(checkedBoxes.length);
  if (passengerCountInput) {
    passengerCountInput.value = String(checkedBoxes.length + 1);
  }
}

function calculateDays() {
  if (!startDateInput || !endDateInput || !dayCountDisplay) return;

  const startDate = startDateInput.value;
  const endDate = endDateInput.value;

  if (startDate) {
    endDateInput.min = startDate;

    if (endDate && new Date(endDate) < new Date(startDate)) {
      endDateInput.value = "";
      dayCountDisplay.textContent = "-";
      return;
    }
  }

  if (startDate && endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = end - start;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

    if (diffDays > 0) {
      dayCountDisplay.textContent = diffDays + " วัน";
    } else {
      dayCountDisplay.textContent = "วันที่ไม่ถูกต้อง";
    }
  } else {
    dayCountDisplay.textContent = "-";
  }
}

function validateTimeRange() {
  if (!startTimeInput || !endTimeInput) return;

  const startTime = startTimeInput.value;
  const endTime = endTimeInput.value;
  const startDate = startDateInput ? startDateInput.value : "";
  const endDate = endDateInput ? endDateInput.value : "";
  const sameDay = startDate !== "" && endDate !== "" && startDate === endDate;

  if (sameDay && startTime) {
    endTimeInput.min = startTime;
  } else {
    endTimeInput.removeAttribute("min");
  }

  if (sameDay && startTime && endTime && endTime <= startTime) {
    endTimeInput.setCustomValidity("เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น");
  } else {
    endTimeInput.setCustomValidity("");
  }
}

if (startDateInput)
  startDateInput.addEventListener("change", function () {
    calculateDays();
    validateTimeRange();
  });
if (endDateInput)
  endDateInput.addEventListener("change", function () {
    calculateDays();
    validateTimeRange();
  });
if (startTimeInput) startTimeInput.addEventListener("change", validateTimeRange);
if (endTimeInput) endTimeInput.addEventListener("change", validateTimeRange);

if (vehicleForm) {
  vehicleForm.addEventListener("submit", function (e) {
    let hasError = false;

    if (departmentInput && departmentInput.value.trim() === "") {
      setDepartmentError(true);
      hasError = true;
    }

    validateTimeRange();
    if (endTimeInput && !endTimeInput.checkValidity()) {
      hasError = true;
    }

    if (hasError) {
      e.preventDefault();
      if (typeof vehicleForm.reportValidity === "function") {
        vehicleForm.reportValidity();
      }
    }
  });
}

if (departmentWrapper) {
  departmentWrapper.addEventListener("click", function (e) {
    if (e.target.closest(".custom-option")) {
      setDepartmentError(false);
    }
  });
}

if (memberDropdown) {
  memberDropdown.addEventListener("change", function (e) {
    if (e.target && e.target.matches('input[type="checkbox"]')) {
      updateCompanionCount();
    }
  });
  updateCompanionCount();
}

if (writeDateInput && !writeDateInput.value) {
  writeDateInput.value = new Date().toISOString().split("T")[0];
}

const fileInput = document.getElementById("attachment");
const attachmentList = document.getElementById("attachmentList");
const attachmentError = document.getElementById("attachmentError");
const MAX_ATTACHMENTS = 5;
const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;
const ALLOWED_ATTACHMENT_TYPES = [
  "application/pdf",
  "image/jpeg",
  "image/png",
];
let selectedAttachments = [];

function formatFileSize(size) {
  if (!size) return "0 KB";
  const kb = size / 1024;
  if (kb < 1024) return `${Math.ceil(kb)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

function setAttachmentError(message) {
  if (!attachmentError) return;
  attachmentError.textContent = message || "";
  attachmentError.classList.toggle("hidden", !message);
}

function syncAttachmentInput() {
  if (!fileInput) return;
  const dataTransfer = new DataTransfer();
  selectedAttachments.forEach((file) => dataTransfer.items.add(file));
  fileInput.files = dataTransfer.files;
}

function renderAttachmentList() {
  if (!attachmentList) return;
  attachmentList.innerHTML = "";

  if (selectedAttachments.length === 0) {
    const empty = document.createElement("p");
    empty.className = "attachment-empty";
    empty.textContent = "ยังไม่มีไฟล์แนบ";
    attachmentList.appendChild(empty);
    return;
  }

  selectedAttachments.forEach((file, index) => {
    const item = document.createElement("div");
    item.className = "attachment-item";

    const meta = document.createElement("div");
    meta.className = "attachment-meta";

    const name = document.createElement("span");
    name.className = "attachment-name";
    name.textContent = file.name;

    const size = document.createElement("span");
    size.className = "attachment-size";
    size.textContent = formatFileSize(file.size);

    meta.appendChild(name);
    meta.appendChild(size);

    const actions = document.createElement("div");
    actions.className = "attachment-actions";

    const viewBtn = document.createElement("button");
    viewBtn.type = "button";
    viewBtn.className = "attachment-action view";
    viewBtn.textContent = "ดูไฟล์";
    viewBtn.addEventListener("click", () => {
      const url = URL.createObjectURL(file);
      window.open(url, "_blank", "noopener");
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    });

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "attachment-action remove";
    removeBtn.textContent = "ลบไฟล์";
    removeBtn.addEventListener("click", () => {
      selectedAttachments = selectedAttachments.filter((_, i) => i !== index);
      syncAttachmentInput();
      renderAttachmentList();
      setAttachmentError("");
    });

    actions.appendChild(viewBtn);
    actions.appendChild(removeBtn);

    item.appendChild(meta);
    item.appendChild(actions);
    attachmentList.appendChild(item);
  });
}

function addAttachments(files) {
  if (!files || files.length === 0) return;
  const existingKeys = new Set(
    selectedAttachments.map(
      (file) => `${file.name}-${file.size}-${file.lastModified}`
    )
  );

  let hasInvalid = false;
  let hitLimit = false;

  Array.from(files).forEach((file) => {
    const key = `${file.name}-${file.size}-${file.lastModified}`;
    if (existingKeys.has(key)) {
      return;
    }

    if (!ALLOWED_ATTACHMENT_TYPES.includes(file.type)) {
      hasInvalid = true;
      return;
    }

    if (file.size > MAX_ATTACHMENT_SIZE) {
      hasInvalid = true;
      return;
    }

    if (selectedAttachments.length >= MAX_ATTACHMENTS) {
      hitLimit = true;
      return;
    }

    selectedAttachments.push(file);
    existingKeys.add(key);
  });

  if (hitLimit) {
    setAttachmentError(`แนบได้สูงสุด ${MAX_ATTACHMENTS} ไฟล์`);
  } else if (hasInvalid) {
    setAttachmentError("รองรับเฉพาะ PDF, JPG, PNG ขนาดไม่เกิน 10MB");
  } else {
    setAttachmentError("");
  }

  syncAttachmentInput();
  renderAttachmentList();
}

if (fileInput) {
  fileInput.addEventListener("change", function () {
    addAttachments(this.files);
  });
}

calculateDays();
validateTimeRange();
renderAttachmentList();

document.addEventListener("DOMContentLoaded", function () {
  const wrappers = document.querySelectorAll(".custom-select-wrapper");

  wrappers.forEach((wrapper) => {
    const trigger = wrapper.querySelector(".custom-select-trigger");
    const options = wrapper.querySelectorAll(".custom-option");
    const hiddenInput = wrapper.querySelector('input[type="hidden"]');
    const valueDisplay = wrapper.querySelector(".select-value");

    if (trigger) {
      trigger.addEventListener("click", function (e) {
        document.querySelectorAll(".custom-select-wrapper").forEach((w) => {
          if (w !== wrapper) w.classList.remove("open");
        });
        wrapper.classList.toggle("open");
        e.stopPropagation();
      });
    }

    options.forEach((option) => {
      option.addEventListener("click", function (e) {
        valueDisplay.textContent = this.textContent;

        if (hiddenInput) {
          hiddenInput.value = this.getAttribute("data-value");
        }

        options.forEach((opt) => opt.classList.remove("selected"));
        this.classList.add("selected");

        wrapper.classList.remove("open");
        e.stopPropagation();
      });
    });
  });

  window.addEventListener("click", function () {
    document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
      wrapper.classList.remove("open");
    });
  });
});

function openDropdown() {
  document.getElementById("myDropdown").classList.add("show");
}

function filterDropdown() {
  let input = document.getElementById("searchInput");
  let filter = input.value.toUpperCase();
  let dropdown = document.getElementById("myDropdown");
  let items = dropdown.getElementsByClassName("dropdown-item");

  for (let i = 0; i < items.length; i++) {
    let txtValue = items[i].innerText || items[i].textContent;
    if (txtValue.toUpperCase().indexOf(filter) > -1) {
      items[i].style.display = "";
    } else {
      items[i].style.display = "none";
    }
  }
}

document.addEventListener("click", function (e) {
  if (!e.target.closest(".go-with-dropdown")) {
    const dropdown = document.getElementById("myDropdown");
    if (dropdown && dropdown.classList.contains("show")) {
      dropdown.classList.remove("show");

      const checkedBoxes = dropdown.querySelectorAll(
        'input[type="checkbox"]:checked'
      );
      const searchInput = document.getElementById("searchInput");
      if (searchInput) {
        if (checkedBoxes.length > 0) {
          searchInput.value = `จำนวน ${checkedBoxes.length} รายชื่อ`;
        } else {
          searchInput.value = ""; 
        }
      }
    }
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("focus", function () {
      if (this.value.startsWith("จำนวน")) {
        this.value = "";
        filterDropdown(); 
      }
    });

    searchInput.addEventListener("blur", function () {
       setTimeout(() => {
        const dropdown = document.getElementById("myDropdown");
        if (dropdown && !dropdown.classList.contains("show")) {
             const checkedBoxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
             if (checkedBoxes.length > 0) {
                 this.value = `จำนวน ${checkedBoxes.length} รายชื่อ`;
             }
        }
       }, 200);
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("memberModal");
  const btnShow = document.querySelector(".show-member");
  const spanClose = document.getElementsByClassName("close-modal")[0];
  const listContainer = document.getElementById("selectedMemberList");

  function openModal() {
    modal.style.display = "flex";

    setTimeout(() => {
      modal.classList.add("show");
    }, 10);
  }

  function closeModal() {
    modal.classList.remove("show");

    setTimeout(() => {
      modal.style.display = "none";
    }, 300);
  }

  btnShow.addEventListener("click", function (e) {
    e.preventDefault();

    listContainer.innerHTML = "";
    const checkedBoxes = document.querySelectorAll(
      '#myDropdown input[type="checkbox"]:checked'
    );

    if (checkedBoxes.length > 0) {
      const ul = document.createElement("ul");
      checkedBoxes.forEach(function (checkbox) {
        const nameText = checkbox.nextElementSibling.innerText;
        const li = document.createElement("li");
        li.textContent = nameText;
        ul.appendChild(li);
      });
      listContainer.appendChild(ul);
    } else {
      listContainer.innerHTML =
        '<p style="text-align:center; color:#FF5050;">ยังไม่ได้เลือกรายชื่อผู้เดินทาง</p>';
    }

    openModal();
  });

  spanClose.onclick = function () {
    closeModal();
  };

  window.onclick = function (event) {
    if (event.target == modal) {
      closeModal();
    }
  };
});
