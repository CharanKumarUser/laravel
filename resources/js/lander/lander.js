import Swiper from 'swiper';
import { Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';

Swiper.use([Pagination, Autoplay]);

function initSwiper() {
  document.querySelectorAll('.init-swiper').forEach((swiperElement) => {
    const paginationEl = swiperElement.querySelector('.swiper-pagination');
    if (!paginationEl) {
      console.warn('Swiper: pagination element missing inside', swiperElement);
    }

    const swiper = new Swiper(swiperElement, {
      loop: true,
      speed: 600,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false, // keep autoplay after user interaction
        pauseOnMouseEnter: true,     // pause when hovering (optional)
      },
      slidesPerView: 'auto',
      pagination: {
        el: paginationEl,
        type: 'bullets',
        clickable: true,
      },
      breakpoints: {
        320: {
          slidesPerView: 2,
          spaceBetween: 40,
        },
        480: {
          slidesPerView: 3,
          spaceBetween: 60,
        },
        640: {
          slidesPerView: 4,
          spaceBetween: 80,
        },
        992: {
          slidesPerView: 5,
          spaceBetween: 120,
        },
      },
    });

    // Ensure autoplay is running (manual start if needed)
    if (swiper.autoplay && !swiper.autoplay.running) {
      swiper.autoplay.start();
    }
  });
}

window.addEventListener('load', initSwiper);
export default initSwiper;
