(function () {
  // Toggle 'scrolled' class based on scroll position
  function toggleScrolled() {
    const body = document.querySelector('body');
    const header = document.querySelector('#header');

    if (!body || !header) return;

    const isSticky = header.classList.contains('scroll-up-sticky') ||
                     header.classList.contains('sticky-top') ||
                     header.classList.contains('fixed-top');

    if (isSticky) {
      window.scrollY > 100 ? body.classList.add('scrolled') : body.classList.remove('scrolled');
    }
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  // Mobile Navigation Toggle
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function toggleMobileNav() {
    const body = document.querySelector('body');
    if (!body || !mobileNavToggleBtn) return;

    body.classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }

  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', toggleMobileNav);
  }

  // Close mobile nav when clicking a menu link
  document.querySelectorAll('#navmenu a').forEach(link => {
    link.addEventListener('click', () => {
      if (document.body.classList.contains('mobile-nav-active')) {
        toggleMobileNav();
      }
    });
  });

  // Dropdown Toggle in Navigation Menu
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function (e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      if (this.parentNode.nextElementSibling) {
        this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      }
      e.stopImmediatePropagation();
    });
  });

  // Scroll-to-top button
  const scrollTopBtn = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (!scrollTopBtn) return;
    window.scrollY > 100 ? scrollTopBtn.classList.add('active') : scrollTopBtn.classList.remove('active');
  }

  if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  window.addEventListener('scroll', toggleScrollTop);
  window.addEventListener('load', toggleScrollTop);

  // AOS Animation Initialization
  function aosInit() {
    if (typeof AOS !== 'undefined') {
      AOS.init({
        duration: 600,
        easing: 'ease-in-out-sine',
        offset: 50,
        once: true,
        mirror: false
      });
    }
  }
  
  window.addEventListener('load', aosInit);

  // Swiper Slider Initialization
  function initSwiper() {
    if (typeof Swiper === 'undefined') return;

    document.querySelectorAll('.init-swiper').forEach(swiperElement => {
      let configElement = swiperElement.querySelector('.swiper-config');
      if (!configElement) return;

      let config = JSON.parse(configElement.innerHTML.trim());

      if (swiperElement.classList.contains('swiper-tab')) {
        initSwiperWithCustomPagination(swiperElement, config);
      } else {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener('load', initSwiper);

  // PureCounter Initialization
  if (typeof PureCounter !== 'undefined') {
    new PureCounter();
  }

  // FAQ Toggle
  document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle').forEach(faqItem => {
    faqItem.addEventListener('click', () => {
      faqItem.parentNode.classList.toggle('faq-active');
    });
  });

  // Smooth Scroll to Section on Page Load with Hash
  window.addEventListener('load', () => {
    if (window.location.hash) {
      const section = document.querySelector(window.location.hash);
      if (section) {
        setTimeout(() => {
          let scrollMarginTop = parseInt(getComputedStyle(section).scrollMarginTop);
          window.scrollTo({ top: section.offsetTop - scrollMarginTop, behavior: 'smooth' });
        }, 100);
      }
    }
  });

  // ScrollSpy for Active Navigation Links
  const navMenuLinks = document.querySelectorAll('.navmenu a');

  function navMenuScrollSpy() {
    navMenuLinks.forEach(link => {
      if (!link.hash) return;
      const section = document.querySelector(link.hash);
      if (!section) return;

      const position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(activeLink => activeLink.classList.remove('active'));
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  document.addEventListener('scroll', navMenuScrollSpy);
  window.addEventListener('load', navMenuScrollSpy);

})();

$(document).ready(function () {
  $('.got-it-form').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const formData = form.serialize();
    const submitButton = form.find('.landing-btn');
    submitButton.prop('disabled', true);
    submitButton.html(
      '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...'
    );
    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      data: formData,
      success: function (res) {
        form[0].reset();
        submitButton.prop('disabled', false);
        submitButton.html('Submit');
        if (res.status && res.key) {
          Swal.fire({
            title: res.title || 'Success!',
            html: res.message ||
              'Your request has been submitted successfully. We will get back to you shortly!',
            icon: 'success',
            confirmButtonText: 'Thank You!',
            showCancelButton: true,
            cancelButtonText: 'Copy Key',
          }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel && res.key) {
              navigator.clipboard.writeText(res.key).then(() => {
                Swal.fire('Copied!', 'The key has been copied to your clipboard.', 'success');
              });
            }
          });
        } else if (res.status) {
          Swal.fire({
            title: res.title || 'Success!',
            html: res.message ||
              'Your request has been submitted successfully. We will get back to you shortly!',
            icon: 'success',
            confirmButtonText: 'Thank You!',
          });
        } else {
          Swal.fire({
            title: 'Error!',
            html: res.message ||
              'An error occurred while submitting your request. Please try again later.',
            icon: 'error',
            confirmButtonText: 'Try Again!'
          });
        }
      },
      error: function () {
        form[0].reset();
        submitButton.prop('disabled', false);
        submitButton.html('Submit');
        Swal.fire({
          title: 'Error!',
          text: 'Unable to process your request at the moment. Please check your internet connection or try again later.',
          icon: 'error',
          confirmButtonText: 'Try Again'
        });
      }
    });
  });
  $(document).on('click', '.show-modal-popup', function () {
    var data_type = $(this).attr('data-type') || '-';
    var clickedBtn = $(this);
    var tempBtnText = clickedBtn.html();
    clickedBtn.attr('disabled', true).addClass('disabled').html(tempBtnText + ' <i class="fa-solid fa-arrows-rotate fa-spin ms-2"></i>');
    $.ajax({
      url: window.location.origin + '/modal/popup/show',
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      data: { 'data_type': data_type },
      success: function (response) {
        var popModal = '#show-popup-modal';
        if (response.modal == "hide") {
          $(popModal).modal('hide');
        }
        if (response.status) {
          $(popModal + '-heading').html(response.heading);
          $(popModal + '-tagline').html(response.tagline);
          $(popModal + '-content').html(response.content);
          $(popModal + '-size').removeClass('modal-sm modal-md modal-lg modal-xl').addClass(response.size);
          if (response.modal == "show") {
            $(popModal).modal('show');
          }
        } else {
          warningToast('Form Error!', 'Invalid form data.', 5000);
        }
        clickedBtn.removeAttr('disabled').removeClass('disabled').html(tempBtnText);
      }.bind(this),
      error: function (xhr, status, error) {
        $('#show-popup-modal').modal('hide');
        Swal.fire({
          title: 'Error!',
          text: 'Unable to process your request at the moment. Please check your internet connection or try again later.',
          icon: 'error',
          confirmButtonText: 'Thank You!'
        });
        clickedBtn.removeAttr('disabled').removeClass('disabled').html(tempBtnText);
      }.bind(this)
    });
  });
  $(document).on('submit', '#show-popup-modal-form', function (e) {
    e.preventDefault();
    var submitBtn = $(this).find('[type="submit"]');
    var tempBtnText = submitBtn.html();
    submitBtn.attr('disabled', true).addClass('disabled').html(tempBtnText + ' <i class="fa-solid fa-arrows-rotate fa-spin"></i>');
    var formData = new FormData(this);
    $.ajax({
      url: window.location.origin + '/modal/popup/save',
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      data: formData,
      contentType: false,
      cache: false,
      processData: false,
      success: function (response) {
          console.log(response);
        if (response.target) {
          let target = $('<a>', {
            href: 'javascript:void(0)',
            class: 'd-none show-modal-popup',
            'data-type': response.target
          });
          $('body').append(target);
          target.trigger('click').remove();
        }
        if (response.redirect_url) {
          setTimeout(() => {
            window.location.href = response.redirect_url;
          }, 10000); 
        }

        if (response.modal == "hide") {
          $('#show-popup-modal').modal('hide');
        }
        if (response.info) {
          if (response.status) {
            Swal.fire({
              title: response.title || 'Success!',
              text: response.message ||
                'Your request has been submitted successfully. We will get back to you shortly!',
              icon: 'success',
              confirmButtonText: 'Thank You!'
            }).then(() => {
              if (response.download) {
                window.location.href = response.download; // Trigger file download
              }
            });
          } else {
            Swal.fire({
              title: response.title || 'Error!',
              text: response.message ||
                'Something went wrong. Please try again.',
              icon: 'error',
              confirmButtonText: 'Try Again!'
            });
          }
        }
        submitBtn.removeAttr('disabled').removeClass('disabled').html(tempBtnText);
      }.bind(this),
      error: function (xhr, status, error) {
        Swal.fire({
          title: 'Error!',
          text: 'Unable to process your request at the moment. Please check your internet connection or try again later.',
          icon: 'error',
          confirmButtonText: 'Thank You!'
        });
        submitBtn.removeAttr('disabled').removeClass('disabled').html(tempBtnText);
      }.bind(this)
    });
  });
});
