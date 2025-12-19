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
class Calendar {
  constructor(config) {
    this.monthYear = document.querySelector(config.monthYearSelector);
    this.dates = document.querySelector(config.datesSelector);
    this.prevBtn = document.querySelector(config.prevBtnSelector);
    this.nextBtn = document.querySelector(config.nextBtnSelector);
    this.locale = config.locale || "th-TH";

    this.currentDate = new Date();

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
      fragment.appendChild(this.createDateCell(i, false, isToday));
    }

    for (let i = lastDayIndex + 1; i <= 6; i++) {
      fragment.appendChild(this.createDateCell("", true));
    }

    if (this.dates) {
      this.dates.innerHTML = "";
      this.dates.appendChild(fragment);
    }
  }

  createDateCell(text, inactive = false, active = false) {
    const div = document.createElement("div");
    div.classList.add("date");
    if (inactive) div.classList.add("inactive");
    if (active) div.classList.add("active");
    div.textContent = text;
    return div;
  }
}

const calendar1 = new Calendar({
  monthYearSelector: "#month-year",
  datesSelector: "#dates-calendar",
  prevBtnSelector: "#prev-btn",
  nextBtnSelector: "#next-btn",
  locale: "th-TH",
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
