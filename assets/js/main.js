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

// Show password
document.addEventListener("DOMContentLoaded", function () {
  let eyeicon = document.getElementById("eyeicon");
  let password = document.getElementById("password-toggle");

  eyeicon.onclick = function () {
    if (password.type === "password") {
      password.type = "text";
      eyeicon.classList.remove("fa-eye-slash");
      eyeicon.classList.add("fa-eye");
    } else {
      password.type = "password";
      eyeicon.classList.remove("fa-eye");
      eyeicon.classList.add("fa-eye-slash");
    }
  };
});
