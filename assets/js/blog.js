document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.blog-related').forEach(function (section) {
    var track = section.querySelector('[data-blog-related-track]');
    var previous = section.querySelector('[data-blog-related-prev]');
    var next = section.querySelector('[data-blog-related-next]');

    if (!track || !previous || !next) {
      return;
    }

    function scroll(direction) {
      var card = track.querySelector('.blog-card');
      var gap = parseFloat(window.getComputedStyle(track).columnGap || window.getComputedStyle(track).gap) || 16;
      var distance = card ? card.getBoundingClientRect().width + gap : track.clientWidth;
      track.scrollBy({ left: direction * distance, behavior: 'smooth' });
    }

    previous.addEventListener('click', function () { scroll(-1); });
    next.addEventListener('click', function () { scroll(1); });
  });
});
