document.addEventListener("DOMContentLoaded", () => {
  // 1. Dynamic Typing Effect in Hero Section
  const roles = [
    "Full-Stack Web Developer",
    "BS Computer Science Student",
    "PHP & SQL Backend Developer",
    "Frontend UI Designer",
  ];
  let roleIndex = 0;
  let charIndex = 0;
  let isDeleting = false;
  const typingElement = document.getElementById("typing-text");

  function typeEffect() {
    const currentRole = roles[roleIndex];
    if (isDeleting) {
      typingElement.textContent = currentRole.substring(0, charIndex - 1);
      charIndex--;
    } else {
      typingElement.textContent = currentRole.substring(0, charIndex + 1);
      charIndex++;
    }

    let speed = isDeleting ? 50 : 100;

    if (!isDeleting && charIndex === currentRole.length) {
      speed = 1800; // Pause at end of word
      isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
      isDeleting = false;
      roleIndex = (roleIndex + 1) % roles.length;
      speed = 400;
    }

    setTimeout(typeEffect, speed);
  }
  typeEffect();

  // 2. Scroll Reveal Animation using IntersectionObserver
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("active");
        }
      });
    },
    { threshold: 0.15 },
  );

  document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));

  // 3. Auto-close Bootstrap Mobile Navbar on Link Click
  const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
  const navbarCollapse = document.getElementById("navbarNav");
  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (navbarCollapse.classList.contains("show")) {
        new bootstrap.Collapse(navbarCollapse).hide();
      }
    });
  });

  // 4. Interactive Project Category Filtering
  const filterButtons = document.querySelectorAll(".filter-btn");
  const projectItems = document.querySelectorAll(".project-item");

  filterButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Update active button styling
      filterButtons.forEach((b) => {
        b.classList.remove("btn-info", "text-dark", "fw-semibold", "active");
        b.classList.add("btn-outline-info");
      });
      btn.classList.remove("btn-outline-info");
      btn.classList.add("btn-info", "text-dark", "fw-semibold", "active");

      const filterValue = btn.getAttribute("data-filter");

      projectItems.forEach((item) => {
        if (
          filterValue === "all" ||
          item.getAttribute("data-category") === filterValue
        ) {
          item.style.display = "block";
        } else {
          item.style.display = "none";
        }
      });
    });
  });

  // 5. Asynchronous Form Submission via Fetch API to contact.php
  const contactForm = document.getElementById("contactForm");
  const formResponse = document.getElementById("formResponse");
  const submitBtn = document.getElementById("submitBtn");

  contactForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

    const formData = new FormData(contactForm);

    try {
      const response = await fetch("contact.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();

      if (result.status === "success") {
        formResponse.innerHTML = `<div class="alert alert-success py-2">${result.message}</div>`;
        contactForm.reset();
      } else {
        formResponse.innerHTML = `<div class="alert alert-danger py-2">${result.message}</div>`;
      }
    } catch (error) {
      formResponse.innerHTML = `<div class="alert alert-warning py-2">Note: Run this project on a PHP server (like XAMPP/WAMP) to process PHP form submissions.</div>`;
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML =
        '<i class="fas fa-paper-plane me-2"></i>Send Message';
    }
  });
});
