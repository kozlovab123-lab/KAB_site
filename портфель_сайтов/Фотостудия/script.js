(function () {
  'use strict';

  /* Hero slider */
  var heroSlider = document.getElementById('heroSlider');
  if (heroSlider) {
    var slides = heroSlider.querySelectorAll('.slider__slide');
    var prevBtn = document.querySelector('.hero .slider__btn--prev');
    var nextBtn = document.querySelector('.hero .slider__btn--next');
    var dotsContainer = document.querySelector('.hero .slider__dots');
    var current = 0;
    var autoplayTimer;

    slides.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.className = 'slider__dot' + (i === 0 ? ' slider__dot--active' : '');
      dot.setAttribute('aria-label', 'Кадр ' + (i + 1));
      dot.addEventListener('click', function () {
        goTo(i);
        resetAutoplay();
      });
      dotsContainer.appendChild(dot);
    });

    var dots = dotsContainer.querySelectorAll('.slider__dot');

    function goTo(index) {
      slides[current].classList.remove('slider__slide--active');
      dots[current].classList.remove('slider__dot--active');
      current = (index + slides.length) % slides.length;
      slides[current].classList.add('slider__slide--active');
      dots[current].classList.add('slider__dot--active');
    }

    function next() {
      goTo(current + 1);
    }

    function prev() {
      goTo(current - 1);
    }

    function startAutoplay() {
      autoplayTimer = setInterval(next, 5000);
    }

    function resetAutoplay() {
      clearInterval(autoplayTimer);
      startAutoplay();
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); resetAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { next(); resetAutoplay(); });

    startAutoplay();
  }

  /* Portfolio lightbox */
  var lightbox = document.getElementById('lightbox');
  var lightboxImg = lightbox && lightbox.querySelector('.lightbox__img');
  var lightboxCaption = lightbox && lightbox.querySelector('.lightbox__caption');
  var closeBtn = lightbox && lightbox.querySelector('.lightbox__close');

  document.querySelectorAll('.portfolio-card').forEach(function (card) {
    card.addEventListener('click', function () {
      var img = card.querySelector('img');
      var title = card.querySelector('h3');
      if (!lightbox || !img) return;
      lightboxImg.src = img.src.replace(/w=\d+/, 'w=1600');
      lightboxImg.alt = img.alt;
      lightboxCaption.textContent = title ? title.textContent : '';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    });
  });

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.hidden = true;
    document.body.style.overflow = '';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
  });

  /* Contact form */
  var contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = contactForm.querySelector('button[type="submit"]');
      var originalText = btn.textContent;
      btn.textContent = 'Заявка отправлена ✓';
      btn.disabled = true;
      setTimeout(function () {
        btn.textContent = originalText;
        btn.disabled = false;
        contactForm.reset();
      }, 3000);
    });
  }

  /* Header scroll */
  var header = document.querySelector('.header');
  window.addEventListener('scroll', function () {
    if (!header) return;
    header.style.background = window.scrollY > 60
      ? 'rgba(10, 10, 10, 0.96)'
      : 'rgba(10, 10, 10, 0.88)';
  });
})();
