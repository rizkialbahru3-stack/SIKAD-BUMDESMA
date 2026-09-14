import './bootstrap';

const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.querySelector('[data-nav]');

const publicHeader = document.querySelector('[data-public-header]');
const publicMenu = document.querySelector('[data-public-menu]');
const publicNavigation = document.querySelector('[data-public-nav]');

window.addEventListener('scroll', () => {
	publicHeader?.classList.toggle('scrolled', window.scrollY > 24);
}, { passive: true });

publicMenu?.addEventListener('click', () => {
	if (!publicNavigation) return;
	const isOpen = publicNavigation.classList.toggle('open');
	publicMenu.setAttribute('aria-expanded', String(isOpen));
});

publicNavigation?.querySelectorAll('a').forEach((link) => {
	link.addEventListener('click', () => {
		publicNavigation.classList.remove('open');
		publicMenu?.setAttribute('aria-expanded', 'false');
	});
});

menuToggle?.addEventListener('click', () => {
	if (!navigation) return;
	const isOpen = navigation.classList.toggle('open');
	menuToggle.setAttribute('aria-expanded', String(isOpen));
});

navigation?.querySelectorAll('a').forEach((link) => {
	link.addEventListener('click', () => {
		navigation.classList.remove('open');
		menuToggle?.setAttribute('aria-expanded', 'false');
	});
});

const revealObserver = new IntersectionObserver((entries, observer) => {
	entries.forEach((entry) => {
		if (entry.isIntersecting) {
			entry.target.classList.add('visible');
			observer.unobserve(entry.target);
		}
	});
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));

const year = document.querySelector('[data-year]');
if (year) year.textContent = new Date().getFullYear();

const carousel = document.querySelector('[data-carousel]');
if (carousel) {
	const slides = [...carousel.querySelectorAll('.carousel-slide')];
	const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
	if (!slides.length) {
		console.warn('Carousel tidak memiliki slide.');
	} else {
	let activeIndex = 0;
	let timer;

	const showSlide = (index) => {
		activeIndex = (index + slides.length) % slides.length;
		slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === activeIndex));
		dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === activeIndex));
	};
	const restartTimer = () => {
		window.clearInterval(timer);
		timer = window.setInterval(() => showSlide(activeIndex + 1), 6000);
	};

	carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => { showSlide(activeIndex - 1); restartTimer(); });
	carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => { showSlide(activeIndex + 1); restartTimer(); });
	dots.forEach((dot) => dot.addEventListener('click', () => { showSlide(Number(dot.dataset.carouselDot)); restartTimer(); }));
	let touchStart = 0;
	carousel.addEventListener('touchstart', (event) => { touchStart = event.changedTouches[0].screenX; }, { passive: true });
	carousel.addEventListener('touchend', (event) => {
		const distance = event.changedTouches[0].screenX - touchStart;
		if (Math.abs(distance) > 45) showSlide(activeIndex + (distance < 0 ? 1 : -1));
		restartTimer();
	}, { passive: true });
	restartTimer();
	}
}
