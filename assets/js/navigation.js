/**
 * JBKlutse Theme - Navigation & UX Enhancements
 * Sticky header, smooth scroll, table of contents
 */
(function () {
	'use strict';

	// Sticky header on scroll
	var header = document.querySelector('.wp-block-group.has-primary-background-color');
	if (header) {
		header.classList.add('jbk-sticky-header');

		window.addEventListener('scroll', function () {
			if (window.pageYOffset > 50) {
				header.classList.add('scrolled');
			} else {
				header.classList.remove('scrolled');
			}
		}, { passive: true });
	}

	// Smooth scroll for anchor links
	document.addEventListener('click', function (e) {
		var link = e.target.closest('a[href^="#"]');
		if (!link) return;
		var target = document.querySelector(link.getAttribute('href'));
		if (target) {
			e.preventDefault();
			var offset = header ? header.offsetHeight + 16 : 16;
			var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
			window.scrollTo({ top: top, behavior: 'smooth' });
		}
	});

})();
