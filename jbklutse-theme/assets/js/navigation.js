/**
 * JBKlutse Theme - Navigation
 * Lightweight sticky header + smooth scroll
 */
(function () {
	'use strict';

	// Sticky header on scroll
	const header = document.querySelector('.wp-block-group.has-primary-background-color');
	if (header) {
		header.classList.add('jbk-sticky-header');
		let lastScroll = 0;

		window.addEventListener('scroll', function () {
			const currentScroll = window.pageYOffset;
			if (currentScroll > 50) {
				header.classList.add('scrolled');
			} else {
				header.classList.remove('scrolled');
			}
			lastScroll = currentScroll;
		}, { passive: true });
	}

	// Smooth scroll for anchor links
	document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
		anchor.addEventListener('click', function (e) {
			const target = document.querySelector(this.getAttribute('href'));
			if (target) {
				e.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});
	});

	// Lazy load images that aren't natively lazy loaded
	if ('IntersectionObserver' in window) {
		const imgObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					const img = entry.target;
					if (img.dataset.src) {
						img.src = img.dataset.src;
						img.removeAttribute('data-src');
					}
					imgObserver.unobserve(img);
				}
			});
		}, { rootMargin: '100px' });

		document.querySelectorAll('img[data-src]').forEach(function (img) {
			imgObserver.observe(img);
		});
	}
})();
