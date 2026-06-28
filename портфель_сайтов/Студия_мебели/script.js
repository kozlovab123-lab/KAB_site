var products = [
  {
    id: 1,
    title: 'Кухня «Линия»',
    category: 'kitchen',
    material: 'ldsp',
    color: 'white',
    size: 'medium',
    price: 248000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80',
    meta: 'ЛДСП · Белый · 3,2 м'
  },
  {
    id: 2,
    title: 'Кухня «Тёплый дуб»',
    category: 'kitchen',
    material: 'mdf',
    color: 'oak',
    size: 'large',
    price: 312000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1600489000022-4472b28adcb3?w=800&q=80',
    meta: 'МДФ · Дуб · 4,1 м'
  },
  {
    id: 3,
    title: 'Шкаф‑купе «Минимал»',
    category: 'wardrobe',
    material: 'ldsp',
    color: 'white',
    size: 'medium',
    price: 86000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800&q=80',
    meta: 'ЛДСП · Белый · 2,4 м'
  },
  {
    id: 4,
    title: 'Шкаф‑купе «Графит»',
    category: 'wardrobe',
    material: 'mdf',
    color: 'graphite',
    size: 'large',
    price: 124000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1595428774223-ef5262410880?w=800&q=80',
    meta: 'МДФ Soft-touch · Графит · 3,6 м'
  },
  {
    id: 5,
    title: 'Гардеробная «Компакт»',
    category: 'closet',
    material: 'ldsp',
    color: 'oak',
    size: 'compact',
    price: 98000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1631889992178-680e4c725b7f?w=800&q=80',
    meta: 'ЛДСП · Дуб · 1,8 м'
  },
  {
    id: 6,
    title: 'Гардеробная «Премиум»',
    category: 'closet',
    material: 'solid',
    color: 'walnut',
    size: 'large',
    price: 186000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1616485887694-80f9b199131e?w=800&q=80',
    meta: 'Массив · Орех · 3,8 м'
  },
  {
    id: 7,
    title: 'Стенка «Лофт»',
    category: 'living',
    material: 'mdf',
    color: 'graphite',
    size: 'large',
    price: 142000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800&q=80',
    meta: 'МДФ · Графит · 4,2 м'
  },
  {
    id: 8,
    title: 'ТВ‑зона «Сканди»',
    category: 'living',
    material: 'ldsp',
    color: 'oak',
    size: 'medium',
    price: 78000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1616047006789-4821670a8f8f?w=800&q=80',
    meta: 'ЛДСП · Дуб · 2,8 м'
  },
  {
    id: 9,
    title: 'Детская «Росток»',
    category: 'kids',
    material: 'ldsp',
    color: 'white',
    size: 'medium',
    price: 156000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1558449088-6556d5405c2b?w=800&q=80',
    meta: 'ЛДСП · Белый · 2,6 м'
  },
  {
    id: 10,
    title: 'Детская «Орех»',
    category: 'kids',
    material: 'solid',
    color: 'walnut',
    size: 'large',
    price: 198000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80',
    meta: 'Массив · Орех · 3,4 м'
  },
  {
    id: 11,
    title: 'Кухня «Уголок»',
    category: 'kitchen',
    material: 'ldsp',
    color: 'graphite',
    size: 'compact',
    price: 168000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&q=80',
    meta: 'ЛДСП · Графит · 1,9 м'
  },
  {
    id: 12,
    title: 'Шкаф‑купе «Зеркало»',
    category: 'wardrobe',
    material: 'mdf',
    color: 'white',
    size: 'compact',
    price: 72000,
    priceNote: 'от',
    image: 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=800&q=80',
    meta: 'МДФ · Белый · 1,6 м'
  }
];

var categoryLabels = {
  kitchen: 'Кухни',
  wardrobe: 'Шкафы‑купе',
  closet: 'Гардеробные',
  living: 'Гостиные',
  kids: 'Детская'
};

var filters = {
  category: 'all',
  material: 'all',
  color: 'all',
  size: 'all'
};

var priceRates = {
  kitchen: { ldsp: 52000, mdf: 68000, solid: 92000 },
  wardrobe: { ldsp: 28000, mdf: 36000, solid: 52000 },
  closet: { ldsp: 32000, mdf: 42000, solid: 58000 },
  living: { ldsp: 24000, mdf: 32000, solid: 48000 },
  kids: { ldsp: 38000, mdf: 48000, solid: 64000 }
};

function formatPrice(value) {
  return value.toLocaleString('ru-RU') + ' ₽';
}

function renderProducts() {
  var grid = document.getElementById('productGrid');
  var empty = document.getElementById('catalogEmpty');

  var filtered = products.filter(function (p) {
    if (filters.category !== 'all' && p.category !== filters.category) return false;
    if (filters.material !== 'all' && p.material !== filters.material) return false;
    if (filters.color !== 'all' && p.color !== filters.color) return false;
    if (filters.size !== 'all' && p.size !== filters.size) return false;
    return true;
  });

  if (filtered.length === 0) {
    grid.innerHTML = '';
    empty.hidden = false;
    return;
  }

  empty.hidden = true;
  grid.innerHTML = filtered.map(function (p) {
    return (
      '<article class="product-card" data-id="' + p.id + '">' +
        '<div class="product-card__img-wrap">' +
          '<img src="' + p.image + '" alt="' + p.title + '">' +
          '<span class="product-card__watermark" aria-hidden="true">КАБ‑студия</span>' +
        '</div>' +
        '<div class="product-card__body">' +
          '<span class="product-card__category">' + categoryLabels[p.category] + '</span>' +
          '<h3 class="product-card__title">' + p.title + '</h3>' +
          '<p class="product-card__meta">' + p.meta + '</p>' +
          '<div class="product-card__footer">' +
            '<span class="product-card__price">' + p.priceNote + ' ' + formatPrice(p.price) + '</span>' +
            '<a href="#contacts" class="product-card__link">Заказать →</a>' +
          '</div>' +
        '</div>' +
      '</article>'
    );
  }).join('');
}

function initFilters() {
  document.querySelectorAll('.category-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.category-btn').forEach(function (b) {
        b.classList.remove('category-btn--active');
      });
      btn.classList.add('category-btn--active');
      filters.category = btn.dataset.category;
      renderProducts();
    });
  });

  document.querySelectorAll('.filter-group__options').forEach(function (group) {
    var filterName = group.dataset.filter;
    group.querySelectorAll('.filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        group.querySelectorAll('.filter-btn').forEach(function (b) {
          b.classList.remove('filter-btn--active');
        });
        btn.classList.add('filter-btn--active');
        filters[filterName] = btn.dataset.value;
        renderProducts();
      });
    });
  });
}

function initSlider() {
  var slider = document.getElementById('heroSlider');
  if (!slider) return;

  var slides = slider.querySelectorAll('.slider__slide');
  var prevBtn = slider.querySelector('.slider__btn--prev');
  var nextBtn = slider.querySelector('.slider__btn--next');
  var dotsContainer = slider.querySelector('.slider__dots');
  var current = 0;
  var autoplayTimer;

  slides.forEach(function (_, i) {
    var dot = document.createElement('button');
    dot.className = 'slider__dot' + (i === 0 ? ' slider__dot--active' : '');
    dot.setAttribute('aria-label', 'Слайд ' + (i + 1));
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

  function resetAutoplay() {
    clearInterval(autoplayTimer);
    autoplayTimer = setInterval(function () {
      goTo(current + 1);
    }, 5000);
  }

  prevBtn.addEventListener('click', function () {
    goTo(current - 1);
    resetAutoplay();
  });

  nextBtn.addEventListener('click', function () {
    goTo(current + 1);
    resetAutoplay();
  });

  resetAutoplay();
}

function updateCalcPrice() {
  var form = document.getElementById('calcForm');
  var priceEl = document.getElementById('calcPrice');
  if (!form || !priceEl) return;

  var type = form.type.value;
  var length = parseFloat(form.length.value) || 0;
  var material = form.material.value;

  if (length <= 0 || !priceRates[type] || !priceRates[type][material]) {
    priceEl.textContent = '—';
    return;
  }

  var base = priceRates[type][material];
  var total = Math.round(base * length);
  priceEl.textContent = 'от ' + formatPrice(total);
}

function initCalculator() {
  var form = document.getElementById('calcForm');
  if (!form) return;

  form.addEventListener('input', updateCalcPrice);
  form.addEventListener('change', updateCalcPrice);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var success = document.createElement('p');
    success.className = 'form-success';
    success.textContent = 'Заявка принята. Перезвоним в течение 30 минут с точным расчётом.';
    form.replaceWith(success);
  });

  updateCalcPrice();
}

function initForms() {
  var contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var success = document.createElement('p');
      success.className = 'form-success';
      success.textContent = 'Заявка на замер принята. Свяжемся в течение 30 минут.';
      contactForm.replaceWith(success);
    });
  }
}

document.querySelectorAll('a[href^="#"]').forEach(function (link) {
  link.addEventListener('click', function (e) {
    var id = this.getAttribute('href');
    if (id === '#') return;
    var target = document.querySelector(id);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

renderProducts();
initFilters();
initSlider();
initCalculator();
initForms();
