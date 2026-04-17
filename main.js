/* ============================================================
   main.js — Glow Beauty
   Features: Animations, Search, Cart, Form Validation,
             Modals, Toast, PHP API Integration
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  document.body.classList.add('loaded');
  initAnimations();
  initDropdowns();
  initSearch();
  initCart();
  initFormValidation();
  initCheckout();
});

/* ════════════════════════════════
   0. DROPDOWNS (Me menu)
════════════════════════════════ */
function initDropdowns() {
  document.querySelectorAll('.dropdown-toggle').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      const isOpen = menu.classList.contains('open');
      document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
      if (!isOpen) menu.classList.add('open');
    });
  });
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
  });
}


/* ════════════════════════════════
   1. SCROLL REVEAL ANIMATIONS
════════════════════════════════ */
function initAnimations() {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  els.forEach(el => observer.observe(el));
}


/* ════════════════════════════════
   2. LIVE SEARCH
════════════════════════════════ */
function initSearch() {
  const input = document.getElementById('searchInput');
  if (!input) return;

  const items = document.querySelectorAll('.search-item');
  const noResults = document.getElementById('searchNoResults');

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    let count = 0;

    items.forEach(item => {
      const text = (item.dataset.title || item.textContent).toLowerCase();
      const tags = (item.dataset.tags || '').toLowerCase();
      const match = !q || text.includes(q) || tags.includes(q);
      item.style.display = match ? '' : 'none';
      if (match) count++;
    });

    if (noResults) noResults.style.display = (count === 0 && q) ? 'block' : 'none';
  });
}


/* ════════════════════════════════
   3. CART
════════════════════════════════ */
let cart = [];

try {
  cart = JSON.parse(localStorage.getItem('glowCart') || '[]');
} catch { cart = []; }

function initCart() {
  updateCartBadge();

  document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.product-card');
      if (!card) return;
      addToCart({
        id:    card.dataset.id,
        name:  card.dataset.name,
        price: parseFloat(card.dataset.price),
      });
    });
  });

  document.getElementById('cartIcon')?.addEventListener('click', openCart);
  document.getElementById('closeCart')?.addEventListener('click', closeCart);
  document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
}

function addToCart(product) {
  const existing = cart.find(i => i.id === product.id);
  if (existing) {
    existing.qty = (existing.qty || 1) + 1;
  } else {
    cart.push({ ...product, qty: 1 });
  }
  saveCart();
  updateCartBadge();
  animateBump();
  showToast('"' + product.name + '" added to cart! 🛒');
}

function removeFromCart(id) {
  cart = cart.filter(i => i.id !== id);
  saveCart();
  updateCartBadge();
  renderCartBody();
}

function saveCart() {
  localStorage.setItem('glowCart', JSON.stringify(cart));
}

function updateCartBadge() {
  const badge = document.getElementById('cartCount');
  if (!badge) return;
  const total = cart.reduce((s, i) => s + (i.qty || 1), 0);
  badge.textContent = total;
  badge.style.display = total > 0 ? 'inline' : 'none';
}

function openCart() {
  document.getElementById('cartPanel')?.classList.add('open');
  document.getElementById('cartOverlay')?.classList.add('active');
  document.body.style.overflow = 'hidden';
  renderCartBody();
}

function closeCart() {
  document.getElementById('cartPanel')?.classList.remove('open');
  document.getElementById('cartOverlay')?.classList.remove('active');
  document.body.style.overflow = '';
}

function renderCartBody() {
  const body   = document.getElementById('cartBody');
  const footer = document.getElementById('cartFooter');
  if (!body) return;

  if (!cart.length) {
    body.innerHTML = '<p class="cart-empty">Your cart is empty </p>';
    if (footer) footer.style.display = 'none';
    return;
  }

  const total = cart.reduce((s, i) => s + i.price * (i.qty || 1), 0);

  body.innerHTML = '<ul class="cart-list">' +
    cart.map(item =>
      '<li class="cart-item">' +
        '<span class="cart-item-name">' + item.name + '</span>' +
        '<span class="cart-item-qty">×' + (item.qty || 1) + '</span>' +
        '<span class="cart-item-price">₹' + (item.price * (item.qty || 1)).toFixed(0) + '</span>' +
        '<button class="cart-remove" data-id="' + item.id + '" title="Remove">✕</button>' +
      '</li>'
    ).join('') +
  '</ul>';

  body.querySelectorAll('.cart-remove').forEach(btn => {
    btn.addEventListener('click', () => removeFromCart(btn.dataset.id));
  });

  if (footer) {
    footer.style.display = 'block';
    const totalEl = document.getElementById('cartTotalAmt');
    if (totalEl) totalEl.textContent = '₹' + total.toFixed(0);
  }
}

function animateBump() {
  const icon = document.getElementById('cartIcon');
  if (!icon) return;
  icon.classList.add('bump');
  setTimeout(() => icon.classList.remove('bump'), 350);
}


/* ════════════════════════════════
   4. FORM VALIDATION
════════════════════════════════ */
function initFormValidation() {
  document.querySelectorAll('.validate-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      if (validateForm(form)) {
        form.dataset.valid = 'true';
        form.dispatchEvent(new CustomEvent('form:valid', { bubbles: true }));
      }
    });

    form.querySelectorAll('input, textarea').forEach(field => {
      field.addEventListener('blur',  () => validateField(field));
      field.addEventListener('input', () => {
        if (field.classList.contains('invalid')) validateField(field);
      });
    });
  });

  // Contact form → contact.php
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('form:valid', async () => {
      const btn     = contactForm.querySelector('button[type=submit]');
      const success = document.getElementById('contactSuccess');
      btn.textContent = 'Sending...';
      btn.disabled = true;

      try {
        const res = await fetch('contact.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name:    contactForm.querySelector('[name=name]').value,
            email:   contactForm.querySelector('[name=email]').value,
            message: contactForm.querySelector('[name=message]').value,
          })
        });
        const data = await res.json();
        if (data.success) {
          contactForm.reset();
          if (success) success.style.display = 'block';
          showToast('Message sent! ');
        } else {
          showToast('Could not send. Please try again.', 4000);
        }
      } catch {
        // Demo mode: show success even without PHP
        contactForm.reset();
        if (success) success.style.display = 'block';
        showToast('Message sent! ');
      } finally {
        btn.textContent = 'Send Message ';
        btn.disabled = false;
      }
    });
  }
}

function validateForm(form) {
  let valid = true;
  form.querySelectorAll('input, textarea').forEach(f => {
    if (!validateField(f)) valid = false;
  });
  return valid;
}

function validateField(field) {
  clearError(field);
  const val  = field.value.trim();
  const name = field.name || field.id || 'Field';

  if (field.hasAttribute('required') && !val)
    return showFieldError(field, cap(name) + ' is required.');

  if (field.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))
    return showFieldError(field, 'Enter a valid email address.');

  const min = field.dataset.min;
  if (min && val.length < parseInt(min))
    return showFieldError(field, cap(name) + ' must be at least ' + min + ' characters.');

  const matchId = field.dataset.match;
  if (matchId) {
    const target = document.getElementById(matchId);
    if (target && val !== target.value.trim())
      return showFieldError(field, 'Passwords do not match.');
  }

  if (field.type === 'tel' && val && !/^[\d\s\-\+\(\)]{7,15}$/.test(val))
    return showFieldError(field, 'Enter a valid phone number.');

  field.classList.add('valid');
  return true;
}

function showFieldError(field, msg) {
  field.classList.add('invalid');
  field.classList.remove('valid');
  let err = field.parentElement.querySelector('.field-error');
  if (!err) {
    err = document.createElement('span');
    err.className = 'field-error';
    field.insertAdjacentElement('afterend', err);
  }
  err.textContent = msg;
  return false;
}

function clearError(field) {
  field.classList.remove('invalid', 'valid');
  field.parentElement.querySelector('.field-error')?.remove();
}

function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }


/* ════════════════════════════════
   5. CHECKOUT MODAL
════════════════════════════════ */
function initCheckout() {
  document.getElementById('checkoutBtn')?.addEventListener('click', () => {
    closeCart();
    openModal(document.getElementById('checkoutModal'));
  });

  document.getElementById('closeCheckout')?.addEventListener('click', () => {
    closeModal(document.getElementById('checkoutModal'));
  });

  document.getElementById('checkoutModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal(e.currentTarget);
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m));
    }
  });

  const checkoutForm = document.getElementById('checkoutForm');
  if (checkoutForm) {
    checkoutForm.addEventListener('form:valid', async () => {
      const btn = checkoutForm.querySelector('button[type=submit]');
      btn.textContent = 'Placing Order...';
      btn.disabled = true;

      const orderData = {
        name:    checkoutForm.querySelector('[name=name]').value,
        email:   checkoutForm.querySelector('[name=email]').value,
        phone:   checkoutForm.querySelector('[name=phone]').value,
        address: checkoutForm.querySelector('[name=address]').value,
        cart:    cart,
        total:   cart.reduce((s, i) => s + i.price * (i.qty || 1), 0),
      };

      try {
        await fetch('order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(orderData),
        });
      } catch { /* demo mode */ }

      cart = [];
      saveCart();
      updateCartBadge();
      checkoutForm.style.display = 'none';
      document.getElementById('orderSuccess').style.display = 'block';
      showToast('Order placed! Thank you ', 4000);
    });
  }
}

function openModal(modal) {
  if (!modal) return;
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
  if (!modal) return;
  modal.classList.remove('active');
  document.body.style.overflow = '';
}


/* ════════════════════════════════
   6. TOAST NOTIFICATIONS
════════════════════════════════ */
function showToast(message, duration = 3000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = message;
  toast.style.cssText = 'opacity:0;transform:translateY(10px);transition:all 0.3s ease;';
  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// Expose for login.html
window.showToast = showToast;
