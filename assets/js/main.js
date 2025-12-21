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

// Show password FEATURE FUTURE
// document.addEventListener("DOMContentLoaded", function () {
//   const eyeicon = document.getElementById("eyeicon");
//   const password = document.getElementById("password-toggle");

//   if(!eyeicon || !password){
//     return ;
//   }

//   eyeicon.onclick = function () {
//     if (password.type === "password") {
//       password.type = "text";
//       eyeicon.classList.remove("fa-eye-slash");
//       eyeicon.classList.add("fa-eye");
//     } else {
//       password.type = "password";
//       eyeicon.classList.remove("fa-eye");
//       eyeicon.classList.add("fa-eye-slash");
//     }
//   };
// });

//Calendar
// class Calendar {
//   constructor(config) {
//     this.monthYear = document.querySelector(config.monthYearSelector);
//     this.dates = document.querySelector(config.datesSelector);
//     this.prevBtn = document.querySelector(config.prevBtnSelector);
//     this.nextBtn = document.querySelector(config.nextBtnSelector);
//     this.locale = config.locale || "th-TH";

//     this.currentDate = new Date();

//     this.attachEvents();
//     this.updateCalendar();
//   }

//   attachEvents() {
//     if (this.prevBtn) {
//       this.prevBtn.addEventListener("click", () => {
//         this.currentDate.setMonth(this.currentDate.getMonth() - 1);
//         this.updateCalendar();
//       });
//     }

//     if (this.nextBtn) {
//       this.nextBtn.addEventListener("click", () => {
//         this.currentDate.setMonth(this.currentDate.getMonth() + 1);
//         this.updateCalendar();
//       });
//     }
//   }

//   updateCalendar() {
//     const year = this.currentDate.getFullYear();
//     const month = this.currentDate.getMonth();
//     const firstDay = new Date(year, month, 1);
//     const lastDay = new Date(year, month + 1, 0);

//     const totalDay = lastDay.getDate();
//     const firstDayIndex = firstDay.getDay();
//     const lastDayIndex = lastDay.getDay();

//     const monthYearString = this.currentDate.toLocaleString(this.locale, {
//       month: "long",
//       year: "numeric",
//     });

//     if (this.monthYear) {
//       this.monthYear.textContent = monthYearString;
//     }

//     const fragment = document.createDocumentFragment();

//     for (let i = 0; i < firstDayIndex; i++) {
//       fragment.appendChild(this.createDateCell("", true));
//     }

//     for (let i = 1; i <= totalDay; i++) {
//       const date = new Date(year, month, i);
//       const isToday = date.toDateString() === new Date().toDateString();
//       fragment.appendChild(this.createDateCell(i, false, isToday));
//     }

//     for (let i = lastDayIndex + 1; i <= 6; i++) {
//       fragment.appendChild(this.createDateCell("", true));
//     }

//     if (this.dates) {
//       this.dates.innerHTML = "";
//       this.dates.appendChild(fragment);
//     }
//   }

//   createDateCell(text, inactive = false, active = false) {
//     const div = document.createElement("div");
//     div.classList.add("date");
//     if (inactive) div.classList.add("inactive");
//     if (active) div.classList.add("active");
//     div.textContent = text;
//     return div;
//   }
// }

// const calendar1 = new Calendar({
//   monthYearSelector: "#month-year",
//   datesSelector: "#dates-calendar",
//   prevBtnSelector: "#prev-btn",
//   nextBtnSelector: "#next-btn",
//   locale: "th-TH",
// });

// document.addEventListener("DOMContentLoaded", function () {
//   const toggleBtn = document.getElementById("toggleNewsBtn");
//   const closeBtn = document.getElementById("closeNewsBtn");
//   const notificationSection = document.getElementById("notificationSection");

//   if (toggleBtn && notificationSection) {
//     toggleBtn.addEventListener("click", function () {
//       notificationSection.classList.add("active");
//     });
//   }

//   if (closeBtn && notificationSection) {
//     closeBtn.addEventListener("click", function () {
//       notificationSection.classList.remove("active");
//     });
//   }

// });
const mockEvents = {
  "2026-1-5": [
    { type: "car", title: "ขอใช้รถตู้", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "room", title: "ประชุมครู", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." }
  ],
  "2025-12-20": [
    { type: "car", title: "ขอใช้รถตู้", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "ขอใช้รถตู้", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "ขอใช้รถตู้", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "ขอใช้รถตู้", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "jjjjjjj", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "aaaaaaa", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "vvvvvvv", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "car", title: "aaaaaaa", time: "08.30-12.00", detail: "พานักเรียนไปแข่งทักษะ", count: "-", owner: "ครูสมชาย" },
    { type: "room", title: "ประชุมครู", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." },
    { type: "room", title: "ประชุมครู", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." },
    { type: "room", title: "ประชุมครู", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." },
    { type: "room", title: "oooooo", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." },
    { type: "room", title: "oooooo", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." },
    { type: "room", title: "pppppp", time: "09.00-12.00", detail: "ประชุมประจำเดือน", count: "50", owner: "ผอ." }
  ],
  "2026-1-22": [
    { type: "car", title: "รถกระบะ", time: "08.30-16.30", detail: "ขนย้ายอุปกรณ์", count: "-", owner: "ลุงแดง" }
  ],
  "2026-1-24": [
    { type: "other", title: "วันหยุดกรณีพิเศษ", time: "ทั้งวัน", detail: "-", count: "-", owner: "-" }
  ],
  "2026-1-14": [
    { type: "room", title: "หอประชุม", time: "08.00-16.00", detail: "กิจกรรมวันวาเลนไทน์", count: "500", owner: "สภานักเรียน" }
  ]
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
            if(e.target === this.modalOverlay) this.closeModal();
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

      fragment.appendChild(this.createDateCell(i, false, isToday, dayEvents, eventKey));
    }

    for (let i = lastDayIndex + 1; i <= 6; i++) {
      fragment.appendChild(this.createDateCell("", true));
    }

    if (this.dates) {
      this.dates.innerHTML = "";
      this.dates.appendChild(fragment);
    }
  }

  createDateCell(text, inactive = false, active = false, events = null, dateKey = null) {
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

        events.forEach(ev => {
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
      if(!this.modalOverlay) return;

      const dateObj = new Date(dateKey);
      const monthsTh = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
      const splitDate = dateKey.split('-');
      const thaiDate = `วันที่ ${splitDate[2]} ${monthsTh[splitDate[1]-1]} ${parseInt(splitDate[0]) + 543}`;
      
      this.modalTitle.innerText = thaiDate;

      this.roomTableBody.innerHTML = "";
      this.carTableBody.innerHTML = "";
      this.roomSection.classList.add("hidden");
      this.carSection.classList.add("hidden");
      this.noEventMessage.classList.add("hidden");

      let hasRoomData = false;
      let hasCarData = false;

      events.forEach(ev => {
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
          this.noEventMessage.innerHTML = "<ul>" + events.map(e => `<li>${e.title}</li>`).join("") + "</ul>";
      }

      this.modalOverlay.classList.remove("hidden");
  }

  closeModal() {
      if(this.modalOverlay) {
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
  events: mockEvents
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