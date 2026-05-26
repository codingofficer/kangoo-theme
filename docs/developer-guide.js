(function () {
  const search = document.querySelector('#doc-search');
  const sections = Array.from(document.querySelectorAll('.doc-section'));
  const tocLinks = Array.from(document.querySelectorAll('.toc a'));
  const backToTop = document.querySelector('.back-to-top');

  function normalize(value) {
    return String(value || '').toLowerCase().trim();
  }

  function filterSections() {
    const query = normalize(search && search.value);

    sections.forEach((section) => {
      const haystack = normalize(section.textContent + ' ' + (section.dataset.search || ''));
      const visible = !query || haystack.includes(query);
      section.classList.toggle('is-hidden', !visible);
    });
  }

  function setActiveLink() {
    let activeId = '';
    const visibleSections = sections.filter((section) => !section.classList.contains('is-hidden'));

    visibleSections.forEach((section) => {
      const rect = section.getBoundingClientRect();

      if (rect.top <= 180) {
        activeId = section.id;
      }
    });

    tocLinks.forEach((link) => {
      link.classList.toggle('is-active', link.getAttribute('href') === '#' + activeId);
    });

    if (backToTop) {
      backToTop.classList.toggle('is-visible', window.scrollY > 680);
    }
  }

  if (search) {
    search.addEventListener('input', filterSections);
  }

  window.addEventListener('scroll', setActiveLink, { passive: true });
  window.addEventListener('resize', setActiveLink);

  tocLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (search && search.value) {
        search.value = '';
        filterSections();
      }
    });
  });

  if (backToTop) {
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  filterSections();
  setActiveLink();
})();
