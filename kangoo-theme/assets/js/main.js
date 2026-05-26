document.addEventListener('DOMContentLoaded', function () {
  const mainBtn = document.querySelector('.single_add_to_cart_button');
  const stickyBtn = document.getElementById('sticky-add-btn');
  const sticky = document.querySelector('.sticky-add');

  if (mainBtn && stickyBtn) {
    stickyBtn.addEventListener('click', function () {
      mainBtn.click();
    });
  }

  if (sticky && mainBtn) {
    const observer = new IntersectionObserver(
      ([entry]) => {
        sticky.style.transform = entry.isIntersecting ? 'translateY(100%)' : 'translateY(0)';
      },
      { threshold: 0 }
    );

    observer.observe(mainBtn);
  }

  const variationForm = document.querySelector('.variations_form');
  const mainImage = document.getElementById('product-main-image');
  const thumbs = document.querySelectorAll('.product-thumb');

  if (variationForm && mainImage && window.jQuery) {
    const originalSrc = mainImage.getAttribute('src');
    const originalAlt = mainImage.getAttribute('alt');

    jQuery(variationForm).on('found_variation', function (event, variation) {
      if (!variation || !variation.image || !variation.image.src) {
        return;
      }

      mainImage.setAttribute('src', variation.image.src);
      mainImage.setAttribute('alt', variation.image.alt || originalAlt);

      thumbs.forEach(function (thumb) {
        thumb.classList.toggle('is-active', thumb.dataset.image === variation.image.src);
      });
    });

    jQuery(variationForm).on('reset_image', function () {
      mainImage.setAttribute('src', originalSrc);
      mainImage.setAttribute('alt', originalAlt);

      thumbs.forEach(function (thumb, index) {
        thumb.classList.toggle('is-active', index === 0);
      });
    });
  }

  if (mainImage && thumbs.length) {
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        const newSrc = this.dataset.image;
        const newAlt = this.dataset.alt || mainImage.getAttribute('alt');

        if (!newSrc) {
          return;
        }

        mainImage.setAttribute('src', newSrc);
        mainImage.setAttribute('alt', newAlt);

        thumbs.forEach(function (item) {
          item.classList.remove('is-active');
        });

        this.classList.add('is-active');
      });
    });
  }
	
});

document.addEventListener('DOMContentLoaded', function () {

  const drawer = document.getElementById('cart-drawer');
  const overlay = drawer?.querySelector('.cart-drawer__overlay');
  const closeBtn = drawer?.querySelector('.cart-drawer__close');

  function openDrawer() {
    drawer.classList.add('is-open');
    document.body.classList.add('no-scroll');
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
  }

  // Close events
  closeBtn?.addEventListener('click', closeDrawer);
  overlay?.addEventListener('click', closeDrawer);

  // 🔥 KEY PART: detect add to cart after reload
  const params = new URLSearchParams(window.location.search);

  if (params.has('add-to-cart')) {
    openDrawer();
  }

});
  
document.addEventListener('DOMContentLoaded', function () {

  const addBtn = document.querySelector('.single_add_to_cart_button');

  if (addBtn) {
    addBtn.addEventListener('click', function () {
      sessionStorage.setItem('cart_open', '1');
    });
  }

});
  
window.addEventListener('load', function () {

  const drawer = document.getElementById('cart-drawer');
  if (!drawer) return;

  const shouldOpen = sessionStorage.getItem('cart_open');

  if (shouldOpen === '1') {

    drawer.classList.add('is-open');

    // clear flag so it doesn't reopen again
    sessionStorage.removeItem('cart_open');

  }

});

document.addEventListener('DOMContentLoaded', function () {

  const drawer = document.getElementById('cart-drawer');
  const overlay = document.querySelector('.cart-drawer__overlay');
  const closeBtn = document.querySelector('.cart-drawer__close');

  if (!drawer) return;

  function closeDrawer() {
    drawer.classList.remove('is-open');
  }

  if (overlay) overlay.addEventListener('click', closeDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

});
  
document.addEventListener('DOMContentLoaded', function () {

  const cartTrigger = document.getElementById('header-cart-trigger');
  const drawer = document.getElementById('cart-drawer');

  if (cartTrigger && drawer) {
    cartTrigger.addEventListener('click', function (e) {
      e.preventDefault();
      drawer.classList.add('is-open');
    });
  }

});