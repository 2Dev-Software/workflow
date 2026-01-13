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

const mockEvents = {
  "2026-1-5": [
    {
      type: "car",
      title: "ขอใช้รถตู้",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "room",
      title: "ประชุมครู",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
  ],
  "2025-12-20": [
    {
      type: "car",
      title: "ขอใช้รถตู้",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "ขอใช้รถตู้",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "ขอใช้รถตู้",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "ขอใช้รถตู้",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "jjjjjjj",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "aaaaaaa",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "vvvvvvv",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "car",
      title: "aaaaaaa",
      time: "08.30-12.00",
      detail: "พานักเรียนไปแข่งทักษะ",
      count: "-",
      owner: "ครูสมชาย",
    },
    {
      type: "room",
      title: "ประชุมครู",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
    {
      type: "room",
      title: "ประชุมครู",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
    {
      type: "room",
      title: "ประชุมครู",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
    {
      type: "room",
      title: "oooooo",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
    {
      type: "room",
      title: "oooooo",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
    {
      type: "room",
      title: "pppppp",
      time: "09.00-12.00",
      detail: "ประชุมประจำเดือน",
      count: "50",
      owner: "ผอ.",
    },
  ],
  "2026-1-22": [
    {
      type: "car",
      title: "รถกระบะ",
      time: "08.30-16.30",
      detail: "ขนย้ายอุปกรณ์",
      count: "-",
      owner: "ลุงแดง",
    },
  ],
  "2026-1-24": [
    {
      type: "other",
      title: "วันหยุดกรณีพิเศษ",
      time: "ทั้งวัน",
      detail: "-",
      count: "-",
      owner: "-",
    },
  ],
  "2026-1-14": [
    {
      type: "room",
      title: "หอประชุม",
      time: "08.00-16.00",
      detail: "กิจกรรมวันวาเลนไทน์",
      count: "500",
      owner: "สภานักเรียน",
    },
  ],
};

class Calendar {
  constructor(config) {
    this.monthYear = document.querySelector(config.monthYearSelector);
    this.dates = document.querySelector(config.datesSelector);
    this.prevBtn = document.querySelector(config.prevBtnSelector);
    this.nextBtn = document.querySelector(config.nextBtnSelector);
    this.locale = config.locale || "th-TH";
    this.events = config.events || {};

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

    const monthYearString = this.currentDate.toLocaleString(this.locale, {
      month: "long",
      year: "numeric",
    });

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
      const dayEvents = this.events[eventKey] || null;

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
        if (ev.type === "car" && !hasCar) {
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

    this.roomTableBody.innerHTML = "";
    this.carTableBody.innerHTML = "";
    this.roomSection.classList.add("hidden");
    this.carSection.classList.add("hidden");
    this.noEventMessage.classList.add("hidden");

    let hasRoomData = false;
    let hasCarData = false;

    events.forEach((ev) => {
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
        this.roomTableBody.innerHTML += row;
      } else if (ev.type === "car") {
        hasCarData = true;
        const row = `
                  <tr>
                      <td>${ev.title}</td>
                      <td>${ev.time}</td>
                      <td>${ev.detail}</td>
                      <td>${ev.owner}</td>
                  </tr>
              `;
        this.carTableBody.innerHTML += row;
      }
    });

    if (hasRoomData) this.roomSection.classList.remove("hidden");
    if (hasCarData) this.carSection.classList.remove("hidden");

    if (!hasRoomData && !hasCarData) {
      this.noEventMessage.classList.remove("hidden");
      this.noEventMessage.innerHTML =
        "<ul>" + events.map((e) => `<li>${e.title}</li>`).join("") + "</ul>";
    }

    this.modalOverlay.classList.remove("hidden");
  }

  closeModal() {
    if (this.modalOverlay) {
      this.modalOverlay.classList.add("hidden");
    }
  }
}

const calendar1 = new Calendar({
  monthYearSelector: "#month-year",
  datesSelector: "#dates-calendar",
  prevBtnSelector: "#prev-btn",
  nextBtnSelector: "#next-btn",
  locale: "th-TH",
  events: mockEvents,
});

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
  let currentPage = 1;
  let rowsPerPage = 10;

  let allRowsData = [];
  let currentFilteredRows = [];

  const tableBody = document.getElementById("teacher-table-body");
  const searchInput = document.getElementById("search-input");
  const countText = document.getElementById("count-text");
  const paginationContainer = document.getElementById("pagination");

  const dropdownWrapper = document.querySelector(".custom-select-wrapper");
  const dropdownTrigger = document.querySelector(".custom-select-trigger");
  const dropdownOptions = document.querySelectorAll(".custom-option");
  const displayValue = document.getElementById("select-value");
  const hiddenSelect = document.getElementById("real-page-select");

  function initData() {
    if (!tableBody) return;

    const rows = Array.from(tableBody.querySelectorAll("tr"));

    allRowsData = rows.map((row) => ({
      element: row,
      text: row.innerText.toLowerCase().trim(),
    }));

    currentFilteredRows = [...allRowsData];
    updateTable();
  }

  //search-input-bar
  function handleSearch() {
    const keyword = searchInput.value.toLowerCase().trim();

    if (keyword === "") {
      currentFilteredRows = [...allRowsData];
    } else {
      currentFilteredRows = allRowsData.filter((item) =>
        item.text.includes(keyword)
      );
    }

    currentPage = 1;
    updateTable();
  }

  //table
  function updateTable() {
    tableBody.innerHTML = "";

    const totalItems = currentFilteredRows.length;

    let start = (currentPage - 1) * rowsPerPage;
    let end = start + rowsPerPage;

    if (rowsPerPage === "all") {
      start = 0;
      end = totalItems;
    }

    const rowsToDisplay = currentFilteredRows.slice(start, end);

    if (rowsToDisplay.length === 0) {
      tableBody.innerHTML =
        '<tr><td colspan="100%" style="text-align:center; padding: 20px;">ไม่พบข้อมูล</td></tr>';
    } else {
      const fragment = document.createDocumentFragment();
      rowsToDisplay.forEach((item) => {
        fragment.appendChild(item.element);
      });
      tableBody.appendChild(fragment);
    }

    if (countText) {
      const message = `จำนวน ${totalItems} รายชื่อ`;

      const pTag = countText.querySelector("p");
      if (pTag) {
        pTag.innerText = message;
      } else {
        countText.innerHTML = `<p>${message}</p>`;
      }
    }

    updatePagination(totalItems);
  }

  //pagination
  function updatePagination(totalItems) {
    paginationContainer.innerHTML = "";

    if (rowsPerPage === "all" || totalItems <= 0) return;

    const totalPages = Math.ceil(totalItems / rowsPerPage);

    if (totalPages <= 1) return;

    const prevBtn = createButton('<i class="fas fa-chevron-left"></i>', () =>
      changePage(currentPage - 1)
    );
    prevBtn.disabled = currentPage === 1;
    paginationContainer.appendChild(prevBtn);

    let startPage = 1,
      endPage = totalPages;

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
      paginationContainer.appendChild(createButton(1, () => changePage(1)));
      if (startPage > 2) paginationContainer.appendChild(createSpan("..."));
    }

    for (let i = startPage; i <= endPage; i++) {
      const btn = createButton(i, () => changePage(i));
      if (i === currentPage) btn.classList.add("active"); // ใส่ class active
      paginationContainer.appendChild(btn);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1)
        paginationContainer.appendChild(createSpan("..."));
      paginationContainer.appendChild(
        createButton(totalPages, () => changePage(totalPages))
      );
    }

    const nextBtn = createButton('<i class="fas fa-chevron-right"></i>', () =>
      changePage(currentPage + 1)
    );
    nextBtn.disabled = currentPage === totalPages;
    paginationContainer.appendChild(nextBtn);
  }

  function changePage(pageNum) {
    currentPage = pageNum;
    updateTable();
  }

  function createButton(content, onClick) {
    const btn = document.createElement("button");
    btn.innerHTML = content;
    btn.addEventListener("click", onClick);
    return btn;
  }

  function createSpan(text) {
    const span = document.createElement("span");
    span.innerText = text;
    span.style.padding = "0 5px";
    return span;
  }

  if (searchInput) {
    searchInput.addEventListener("input", handleSearch);
  }

  //custom-dropdown
  if (dropdownTrigger) {
    dropdownTrigger.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdownWrapper.classList.toggle("open");
    });

    dropdownOptions.forEach((option) => {
      option.addEventListener("click", function () {
        dropdownOptions.forEach((opt) => opt.classList.remove("selected"));
        this.classList.add("selected");
        displayValue.textContent = this.textContent;
        dropdownWrapper.classList.remove("open");

        const val = this.getAttribute("data-value");
        if (hiddenSelect) hiddenSelect.value = val;

        if (val === "all") {
          rowsPerPage = "all";
        } else {
          rowsPerPage = parseInt(val, 10);
        }

        currentPage = 1;
        updateTable();
      });
    });

    window.addEventListener("click", (e) => {
      if (dropdownWrapper && !dropdownWrapper.contains(e.target)) {
        dropdownWrapper.classList.remove("open");
      }
    });
  }

  initData();
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
  const mainProfilePic = document.getElementById("mainProfilePic");
  if (tempImageSrc) {
    if (mainProfilePic) {
      mainProfilePic.style.backgroundImage = `url('${tempImageSrc}')`;
      mainProfilePic.style.backgroundColor = "transparent";
    }
    closeImageModal();
  } else {
    alert("กรุณาเลือกรูปภาพก่อน");
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
  const mainSignatureImg = document.getElementById("mainSignatureImg");
  const noSignatureText = document.getElementById("noSignatureText");

  if (tempSignatureSrc) {
    if (mainSignatureImg) {
      mainSignatureImg.src = tempSignatureSrc;
      mainSignatureImg.classList.remove("hidden");
    }
    if (noSignatureText) {
      noSignatureText.style.display = "none";
    }
    closeSignatureModal();
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const phoneInput = document.getElementById("phoneInput");
  const modal = document.getElementById("confirmModal");
  const showPhone = document.getElementById("showPhone");
  const confirmBtn = document.getElementById("confirmBtn");
  const cancelBtn = document.getElementById("cancelBtn");

  if (phoneInput) {
    phoneInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        const phone = phoneInput.value.trim();
        if (phone) {
          if (showPhone) showPhone.textContent = phone;
          if (modal) modal.style.display = "flex";
        }
      }
    });
  }

  if (confirmBtn) {
    confirmBtn.addEventListener("click", function () {
      if (modal) modal.style.display = "none";
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      if (modal) modal.style.display = "none";
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
  
  const dutyCheckboxes = document.querySelectorAll('input[name="acting_duty"]');

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
        
        const selected = document.querySelector('input[name="acting_duty"]:checked');

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