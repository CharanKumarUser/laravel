import $ from 'jquery';
/**
 * SystemTheme: Manages theme customization and UI interactions, including sidebar functionality
 */
const SystemTheme = {
  // Initialize DOM references and state
  init() {
    this.$wrapper = $('.main-wrapper');
    this.$sidebar = $('.sidebar');
    this.$sidebarBgContainer = $('#sidebarbgContainer');
    this.$body = $('body');
    this.$html = $('html');
    this.$pageWrapper = $('.page-wrapper');
    this.layoutMini = 0;
    this.developerMode = window.general?.developerMode || false;
    this.themeUrl = window.location.origin;
    this.themeSettingsTemplate = `
            <div class="sidebar-contact">
                <div class="toggle-theme" data-bs-toggle="offcanvas" data-bs-target="#theme-setting"><i class="fa fa-cog fa-w-16 fa-spin"></i></div>
            </div>
            <div class="sidebar-themesettings offcanvas offcanvas-end" id="theme-setting">
                <div class="offcanvas-header d-flex align-items-center justify-content-between bg-dark">
                    <div>
                        <h3 class="mb-1 text-white">Theme Customizer</h3>
                        <p class="text-light">Setup your Got-It's Look.</p>
                    </div>
                    <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center text-white" data-bs-dismiss="offcanvas"><i class="ti ti-x"></i></a>
                </div>
                <div class="themesettings-inner offcanvas-body">
                    <div class="accordion accordion-customicon1 accordions-items-seperate" id="settingtheme">
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#modesetting" aria-expanded="true">
                                    Color Mode
                                </button>
                            </h2>
                            <div id="modesetting" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row gx-3">
                                        <div class="col-6">
                                            <div class="theme-mode">
                                                <input type="radio" name="theme" id="lightTheme" value="light" checked>
                                                <label for="lightTheme" class="p-2 rounded fw-medium w-100">                            
                                                    <span class="avatar avatar-md d-inline-flex rounded me-2"><i class="ti ti-sun-filled"></i></span>Light Mode
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="theme-mode">
                                                <input type="radio" name="theme" id="darkTheme" value="dark">
                                                <label for="darkTheme" class="p-2 rounded fw-medium w-100">                         
                                                    <span class="avatar avatar-md d-inline-flex rounded me-2"><i class="ti ti-moon-filled"></i></span>Dark Mode
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarsetting" aria-expanded="true">
                                    Layout Width
                                </button>
                            </h2>
                            <div id="sidebarsetting" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="d-flex align-items-center">
                                        <div class="theme-width m-1 me-2">
                                            <input type="radio" name="width" id="fluidWidth" value="fluid" checked>
                                            <label for="fluidWidth" class="d-block rounded fs-12">Fluid Layout
                                            </label>
                                        </div>
                                        <div class="theme-width m-1">
                                            <input type="radio" name="width" id="boxWidth" value="box">
                                            <label for="boxWidth" class="d-block rounded fs-12">Boxed Layout
                                            </label>
                                        </div>
                                    </div>  
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#cardsetting" aria-expanded="true">
                                    Card Layout
                                </button>
                            </h2>
                            <div id="cardsetting" class="accordion-collapse collapse show">
                                <div class="accordion-body pb-0">
                                    <div class="row gx-3">
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="card" id="borderedCard" value="bordered" checked>
                                                <label for="borderedCard">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/bordered.svg" alt="img">
                                                    </span>                                     
                                                    <span class="layout-type">Bordered</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="card" id="borderlessCard" value="borderless">
                                                <label for="borderlessCard">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/borderless.svg" alt="img">
                                                    </span>                                    
                                                    <span class="layout-type">Borderless</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="card" id="shadowCard" value="shadow">
                                                <label for="shadowCard">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/shadow.svg" alt="img">
                                                    </span>                                    
                                                    <span class="layout-type">Only Shadow</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarcolorsetting" aria-expanded="true">
                                    Sidebar Color
                                </button>
                            </h2>
                            <div id="sidebarcolorsetting" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="d-flex align-items-center">
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="lightSidebar" value="light" checked>
                                            <label for="lightSidebar" class="d-block rounded mb-2"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="darkgreenSidebar" value="darkgreen">
                                            <label for="darkgreenSidebar" class="d-block rounded bg-darkgreen mb-2"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="nightblueSidebar" value="nightblue">
                                            <label for="nightblueSidebar" class="d-block rounded bg-nightblue mb-2"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="darkgraySidebar" value="darkgray">
                                            <label for="darkgraySidebar" class="d-block rounded bg-darkgray mb-2"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="royalblueSidebar" value="royalblue">
                                            <label for="royalblueSidebar" class="d-block rounded bg-royalblue mb-2"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-2">
                                            <input type="radio" name="sidebar" id="indigoSidebar" value="indigo">
                                            <label for="indigoSidebar" class="d-block rounded bg-indigo mb-2"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>    
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sizesetting" aria-expanded="true">
                                    Sidebar Size
                                </button>
                            </h2>
                            <div id="sizesetting" class="accordion-collapse collapse show">
                                <div class="accordion-body pb-0">
                                    <div class="row gx-3">
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="size" id="defaultSize" value="default" checked>
                                                <label for="defaultSize">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/default.svg" alt="img">
                                                    </span>                                     
                                                    <span class="layout-type">Default</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="size" id="compactSize" value="compact">
                                                <label for="compactSize">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/compact.svg" alt="img">
                                                    </span>                                    
                                                    <span class="layout-type">Compact</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="theme-layout mb-3">
                                                <input type="radio" name="size" id="hoverviewSize" value="hoverview">
                                                <label for="hoverviewSize">
                                                    <span class="d-block mb-2 layout-img">
                                                        <img src="${this.themeUrl}/treasury/pack/theme/img/hoverview.svg" alt="img">
                                                    </span>                                    
                                                    <span class="layout-type">Hover View</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#colorsetting" aria-expanded="true">
                                    Top Bar Color
                                </button>
                            </h2>
                            <div id="colorsetting" class="accordion-collapse collapse show">
                                <div class="accordion-body pb-1">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="whiteTopbar" value="white" checked>
                                            <label for="whiteTopbar" class="white-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="darkaquaTopbar" value="darkaqua">
                                            <label for="darkaquaTopbar" class="darkaqua-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="whiterockTopbar" value="whiterock">
                                            <label for="whiterockTopbar" class="whiterock-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="rockblueTopbar" value="rockblue">
                                            <label for="rockblueTopbar" class="rockblue-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="bluehazeTopbar" value="bluehaze">
                                            <label for="bluehazeTopbar" class="bluehaze-topbar"></label>
                                        </div>                   
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="orangeGradientTopbar" value="orangegradient">
                                            <label for="orangeGradientTopbar" class="orange-gradient-topbar"></label>
                                        </div>                   
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="purpleGradientTopbar" value="purplegradient">
                                            <label for="purpleGradientTopbar" class="purple-gradient-topbar"></label>
                                        </div>                   
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="blueGradientTopbar" value="bluegradient">
                                            <label for="blueGradientTopbar" class="blue-gradient-topbar"></label>
                                        </div>                   
                                        <div class="theme-colorselect mb-3 me-3">
                                            <input type="radio" name="topbar" id="maroonGradientTopbar" value="maroongradient">
                                            <label for="maroonGradientTopbar" class="maroon-gradient-topbar"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#topcolorsetting" aria-expanded="true">
                                    Top Bar Background
                                </button>
                            </h2>
                            <div id="topcolorsetting" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <h6 class="mb-1 fw-medium">Colors</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="whiteTopbarcolor" value="white" checked>
                                            <label for="whiteTopbarcolor" class="white-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="primaryTopbarcolor" value="primary">
                                            <label for="primaryTopbarcolor" class="primary-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="blackpearlTopbarcolor" value="blackpearl">
                                            <label for="blackpearlTopbarcolor" class="blackpearl-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="maroonTopbarcolor" value="maroon">
                                            <label for="maroonTopbarcolor" class="maroon-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="bluegemTopbarcolor" value="bluegem">
                                            <label for="bluegemTopbarcolor" class="bluegem-topbar"></label>
                                        </div>
                                        <div class="theme-colorselect m-1 me-3">
                                            <input type="radio" name="topbarcolor" id="fireflyTopbarcolor" value="firefly">
                                            <label for="fireflyTopbarcolor" class="firefly-topbar"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 			    
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarcolor" aria-expanded="true">
                                    Theme Colors
                                </button>
                            </h2>
                            <div id="sidebarcolor" class="accordion-collapse collapse show">
                                <div class="accordion-body pb-2">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="primaryColor" value="primary" checked>
                                            <label for="primaryColor" class="primary-clr"></label>
                                        </div>
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="brightblueColor" value="brightblue">
                                            <label for="brightblueColor" class="brightblue-clr"></label>
                                        </div>
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="lunargreenColor" value="lunargreen">
                                            <label for="lunargreenColor" class="lunargreen-clr"></label>
                                        </div>
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="lavendarColor" value="lavendar">
                                            <label for="lavendarColor" class="lavendar-clr"></label>
                                        </div>
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="magentaColor" value="magenta">
                                            <label for="magentaColor" class="magenta-clr"></label>
                                        </div>
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="chromeyellowColor" value="chromeyellow">
                                            <label for="chromeyellowColor" class="chromeyellow-clr"></label>
                                        </div>  
                                        <div class="theme-colorsset me-2 mb-2">
                                            <input type="radio" name="color" id="lavaredColor" value="lavared">
                                            <label for="lavaredColor" class="lavared-clr"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="accordion-item">
                            <h2 class="accordion-header m-0">
                                <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#loadersetting" aria-expanded="true">
                                    Preloader
                                </button>
                            </h2>
                            <div id="loadersetting" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="d-flex align-items-center">
                                        <div class="theme-width me-2">
                                            <input type="radio" name="loader" id="enableLoader" value="enable" checked>
                                            <label for="enableLoader" class="d-block rounded fs-12">With Preloader
                                            </label>
                                        </div>
                                        <div class="theme-width">
                                            <input type="radio" name="loader" id="disableLoader" value="disable">
                                            <label for="disableLoader" class="d-block rounded fs-12">Without Preloader
                                            </label>
                                        </div>
                                    </div>  
                                </div>
                            </div>
                        </div> 
                    </div> 
                </div>
                <div class="p-3 pt-0">
                    <div class="row gx-3">
                        <div class="col-6">
                            <a href="#" id="resetbutton" class="btn btn-light close-theme w-100"><i class="ti ti-restore me-1"></i>Reset</a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="btn btn-primary w-100" data-bs-dismiss="offcanvas"><i class="ti ti-check me-1"></i>Save Changes</a>
                        </div>
                    </div>
                </div>    
            </div>`;
    this.activeTab = null;
    this.setup();
  },
  // Core setup for all components
  setup() {
    try {
      if (!window.jQuery || !window.general) throw new Error('Missing dependencies: jQuery or window.general');
      this.$body.append(this.themeSettingsTemplate).append('<div class="sidebar-overlay"></div>');
      this.applyInitialTheme();
      this.bindEvents();
      this.setupComponents();
      window.general.log('SystemTheme initialized');
    } catch (e) {
      window.general?.error('Initialization failed:', e);
      window.general?.errorToast('Theme Error', 'Initialization failed');
    }
  },
  // Apply saved theme settings from localStorage
  applyInitialTheme() {
    const settings = {
      theme: 'light',
      sidebar: 'light',
      color: 'primary',
      topbar: 'white',
      topbarcolor: 'white',
      card: 'bordered',
      size: 'default',
      width: 'fluid',
      loader: 'enable'
    };
    $.each(settings, (key, val) => {
      const saved = localStorage.getItem(key === 'sidebar' ? 'sidebarTheme' : key) || val;
      this.$html.attr(`data-${key}`, saved);
      $(`#${saved}${key.charAt(0).toUpperCase() + key.slice(1)}`).prop('checked', true);
    });
    ['sidebarBg', 'topbarbg'].forEach(bg => {
      const val = localStorage.getItem(bg);
      if (val) {
        this.$body.attr(`data-${bg}`, val);
        $(`#${val}`).prop('checked', true);
      }
    });
  },
  // Bind all event listeners
  bindEvents() {
    const events = [
      { sel: '#mobile_btn', fn: () => this.toggleSidebar(true) },
      { sel: '.sidebar-overlay', fn: () => this.toggleSidebar(false) },
      { sel: '#toggle_btn', fn: () => this.toggleMiniSidebar() },
      { sel: '.hideset', fn: e => $(e.target).closest('.card').hide() },
      { sel: '.delete-set', fn: e => $(e.target).closest('.card').hide() },
      { sel: '.win-maximize, .btnFullscreen', fn: () => this.toggleFullscreen() },
      { sel: '#check_all', fn: () => $('.checkmail').trigger('click') },
      { sel: '#select-all2', fn: e => $('.form-check.form-check-md :checkbox').prop('checked', e.target.checked) },
      { sel: '#select-all', fn: e => $(':checkbox').prop('checked', e.target.checked) },
      { sel: '#collapse-header', fn: e => $(e.target).toggleClass('active') && this.$body.toggleClass('header-collapse') },
      { sel: '.inc, .dec', fn: e => this.updateInputValue(e.target) },
      { sel: '[data-bs-toggle="card-fullscreen"]', fn: e => $(e.target).closest('.card').toggleClass('card-fullscreen').removeClass('card-collapsed') },
      { sel: '[data-bs-toggle="card-remove"]', fn: e => $(e.target).closest('.card').remove() },
      { sel: '.rating-select', fn: e => $(e.target).find('i').toggleClass('ti-star ti-star-filled filled') },
      { sel: '.image-sign', fn: e => this.previewImage(e.target) },
      { sel: '.add-info-fieldset .wizard-next-btn', fn: e => this.advanceWizard(e.target) },
      { sel: '.themecolorset, .theme-layout', fn: e => $(e.target).addClass('active').siblings().removeClass('active') },
      { sel: '#resetbutton', fn: () => this.resetTheme() },
      { sel: '#dark-mode-toggle', fn: () => this.toggleDarkMode(true) },
      { sel: '#light-mode-toggle', fn: () => this.toggleDarkMode(false) }
    ];
    events.forEach(({ sel, fn }) => $(document).on('click', sel, fn));
    this.setupMenuEvents();
    this.setupThemeChangeEvents();
    this.setupAdditionalEvents();
  },
  // Setup additional event listeners (e.g., for plugins)
  setupAdditionalEvents() {
    $(window).on('resize', window.general.debounce(() => this.$pageWrapper.css('min-height', window.innerHeight), 100)).trigger('resize');
    this.$body.on('click', e => {
      if (this.$body.hasClass('mini-sidebar') && $('#toggle_btn').is(':visible')) {
        this.$body.toggleClass('expand-menu', $(e.target).closest('.sidebar, .header-left').length);
        $('.subdrop + ul').slideToggle(this.$body.hasClass('expand-menu') ? 350 : 0);
      }
    });
    // Stack menu
    $('.stack-menu .nav a').on('click', e => {
      e.preventDefault();
      const tab = $(e.target).attr('href');
      const $pane = $(tab);
      $pane.toggle(tab === this.activeTab);
      this.activeTab = $pane.is(':visible') ? tab : null;
      $('#myTabContent .tab-pane').not($pane).hide();
    });
    // Tooltips
    $('[data-bs-toggle="tooltip"]').each(function () {
      new bootstrap.Tooltip(this);
    });
  },
  // Scroll to active menu item
  scrollToActiveItem() {
    try {
      const $activeItem = $('.sidebar-menu ul li a.active');
      if ($activeItem.length) {
        // Highlight active item
        $activeItem.addClass('active-menu-item');
        
        // Expand parent submenu
        const $parentSubmenu = $activeItem.parents('li.submenu').children('a:first');
        $parentSubmenu.addClass('active-menu-item subdrop').next('ul').slideDown(350);
        
        // Get sidebar and active item measurements
        const $menu = $('.sidebar-menu');
        const $menuItem = $activeItem.closest('li');
        const menuHeight = $menu.height() || 0;
        const contentHeight = $menu[0]?.scrollHeight || 0;
        const itemOffset = $menuItem.offset().top - $menu.offset().top;
        const itemHeight = $menuItem.outerHeight() || 0;
        
        // Only scroll if sidebar is scrollable
        if (contentHeight > menuHeight && menuHeight > 0) {
          let scrollTo;
          // Check if item is near the bottom
          if (itemOffset + itemHeight > contentHeight - menuHeight) {
            // Scroll to make last item fully visible
            scrollTo = contentHeight - menuHeight;
          } else {
            // Center the item
            scrollTo = itemOffset - (menuHeight / 2) + (itemHeight / 2);
          }
          
          // Ensure scroll position is non-negative
          scrollTo = Math.max(0, scrollTo);
          
          // Perform smooth scroll
          $menu.animate({
            scrollTop: scrollTo
          }, 350, 'swing', () => {});
        }
      } else {
        window.general?.log('No active menu item found');
      }
    } catch (e) {
      window.general?.error('Failed to scroll to active menu item:', e);
    }
  },
  setupMenuEvents() {
    const handleMenuClick = (selector, context) => {
      $(selector).on('click', function (e) {
        if ($(this).parent().hasClass('submenu')) {
          e.preventDefault();
          const $this = $(this);
          const isActive = $this.hasClass('subdrop');
          $(`${context} li.submenu ul`).slideUp(250);
          $(`${context} li.submenu > a`).removeClass('subdrop');
          if (!isActive) {
            $this.next('ul').slideDown(350);
            $this.addClass('subdrop');
          }
        }
        // Highlight active menu item
        $(`${context} li a`).removeClass('active-menu-item');
        $(this).addClass('active-menu-item');
      });
    };
    
    // Scroll to active menu item on DOM ready (for redirects)
    $(document).ready(() => {
      setTimeout(() => {
        this.scrollToActiveItem();
      }, 100); // Minimal delay for initial rendering
    });

    // Scroll to active menu item on page load (for reloads)
    $(window).on('load', () => {
      setTimeout(() => {
        this.scrollToActiveItem();
      }, 500); // Delay for dynamic content
    });

    handleMenuClick('.sidebar-menu a', '.sidebar-menu');
    handleMenuClick('.sidebar-right a', '.sidebar-right');
  },
  // Setup all non-event-driven components
  setupComponents() {
    // Sticky sidebar
    if (window.innerWidth > 767 && $.fn.theiaStickySidebar) {
      $('.theiaStickySidebar').theiaStickySidebar({ additionalMarginTop: 30 });
    }
    // Loader
    setTimeout(() => $('#global-loader').fadeOut('slow'), 100);
    // Table responsive
    setTimeout(() => $('.table').parent().addClass('table-responsive'), 1000);
  },
  // Setup theme change event listeners
  setupThemeChangeEvents() {
    const selectors = ['theme', 'sidebar', 'color', 'LayoutTheme', 'topbar', 'topbarcolor', 'card', 'size', 'width', 'loader', 'sidebarbg', 'topbarbg'];
    selectors.forEach(name => $(`input[name="${name}"]`).on('change', () => this.handleThemeChange()));
  },
  // Toggle sidebar visibility
  toggleSidebar(open) {
    this.$wrapper.toggleClass('slide-nav', open);
    $('.sidebar-overlay').toggleClass('opened', open);
    this.$html.toggleClass('menu-opened', open);
    $('#task_window').removeClass('opened');
  },
  // Toggle mini sidebar mode
  toggleMiniSidebar() {
    const isMini = this.$body.hasClass('mini-sidebar');
    this.$body.toggleClass('mini-sidebar', !isMini);
    $('#toggle_btn').toggleClass('active', !isMini);
    $('.header-left').toggleClass('active', !isMini);
    localStorage.setItem('screenModeNightTokenState', isMini ? '' : 'night');
    setTimeout(() => {
      this.$body.toggleClass('mini-sidebar', !isMini);
      $('.header-left').toggleClass('active', !isMini);
    }, 100);
  },
  // Toggle fullscreen mode
  toggleFullscreen(elem = document.documentElement) {
    if (!document.fullscreenElement) {
      elem.requestFullscreen?.() || elem.msRequestFullscreen?.() || elem.mozRequestFullScreen?.() || elem.webkitRequestFullscreen?.(Element.ALLOW_KEYBOARD_INPUT);
    } else {
      document.exitFullscreen?.() || document.msExitFullscreen?.() || document.mozCancelFullScreen?.() || document.webkitExitFullscreen?.();
    }
  },
  // Update input value for increment/decrement
  updateInputValue(target) {
    const $input = $(target).parent().find('input');
    const delta = $(target).hasClass('inc') ? 1 : -1;
    $input.val(Math.max(0, parseInt($input.val()) + delta));
  },
  // Preview uploaded images
  previewImage(input) {
    const $frames = $(input).closest('.upload-pic').find('.frames').empty();
    $.each(input.files, (i, file) => {
      $('<img>', { src: URL.createObjectURL(file), width: 100, height: 100 }).appendTo($frames);
    });
  },
  // Advance wizard steps
  advanceWizard(btn) {
    const $current = $(btn).closest('fieldset').hide();
    $current.next().fadeIn('slow');
    $('.progress-bar-wizard .active').removeClass('active').addClass('activated').next().addClass('active');
  },
  // Toggle dark mode
  toggleDarkMode(enable) {
    this.$html.attr('data-theme', enable ? 'dark' : 'light');
    $('#dark-mode-toggle').toggleClass('activate', !enable);
    $('#light-mode-toggle').toggleClass('activate', enable);
    localStorage.setItem('darkMode', enable ? 'enabled' : '');
  },
  // Handle theme setting changes
  handleThemeChange() {
    const values = {
      theme: 'light',
      layout: 'default',
      card: 'bordered',
      size: 'default',
      width: 'fluid',
      loader: 'enable',
      color: 'primary',
      sidebarTheme: 'light',
      topbar: 'white',
      topbarcolor: 'white'
    };
    $.each(values, (key, def) => {
      values[key] = $(`input[name="${key === 'sidebarTheme' ? 'sidebar' : key}"]:checked`).val() || def;
      this.$html.attr(`data-${key}`, values[key]);
      localStorage.setItem(key === 'sidebarTheme' ? 'sidebarTheme' : key, values[key]);
    });
    ['sidebarbg', 'topbarbg'].forEach(bg => {
      const val = $(`input[name="${bg}"]:checked`).val();
      this.$body.toggleAttr(`data-${bg}`, !!val).attr(`data-${bg}`, val || '');
      localStorage.setItem(bg, val || '');
    });
    this.layoutMini = values.layout === 'mini' || values.size === 'compact' || values.width === 'box' ? 1 : 0;
    this.$body.removeClass('mini-sidebar menu-horizontal layout-box-mode expand-menu')
      .toggleClass('mini-sidebar', values.layout === 'mini' || values.size === 'compact')
      .toggleClass('menu-horizontal', values.layout.includes('horizontal'))
      .toggleClass('expand-menu', values.size === 'hoverview')
      .toggleClass('layout-box-mode mini-sidebar', values.width === 'box');
    if (values.width === 'box' && values.layout.includes('horizontal')) this.$body.removeClass('mini-sidebar');
    this.$sidebarBgContainer?.toggleClass('show', values.layout === 'box');
  },
  // Reset theme to default settings
  resetTheme() {
    const defaults = {
      theme: 'light',
      sidebarTheme: 'light',
      color: 'primary',
      layout: 'default',
      topbar: 'white',
      topbarcolor: 'white',
      card: 'bordered',
      size: 'default',
      width: 'fluid',
      loader: 'enable'
    };
    this.setThemeAttributes(defaults);
    this.$body.removeAttr('data-sidebarbg data-topbarbg');
    localStorage.removeItem('sidebarBg topbarbg');
    ['lightTheme', 'lightSidebar', 'primaryColor', 'defaultLayout', 'whiteTopbar', 'whiteTopbarcolor', 'borderedCard', 'defaultSize', 'fluidWidth', 'enableLoader']
      .forEach(id => $(`#${id}`).prop('checked', true));
    $('input[name="sidebarbg"]:checked, input[name="topbarbg"]:checked').prop('checked', false);
  },
  // Set theme attributes
  setThemeAttributes(attrs) {
    $.each(attrs, (key, val) => {
      this.$html.attr(`data-${key}`, val);
      localStorage.setItem(key === 'sidebarTheme' ? 'sidebarTheme' : key, val);
    });
  }
};
// Initialize on DOM ready
$(() => {
  try {
    SystemTheme.init();
    window.SystemTheme = SystemTheme;
  } catch (e) {
    window.general?.error('Failed to initialize SystemTheme:', e);
  }
});
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggle = document.getElementById('notification_popup');
    const cancelBtn = document.querySelector('.btn-cancel');

    // Prevent Bootstrap dropdown from closing when clicking inside
    document.querySelector('.notification_item .dropdown-menu').addEventListener('click', function (event) {
        event.stopPropagation();
    });

    // Close dropdown when clicking Cancel
    cancelBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggle);
        if (dropdownInstance) {
            dropdownInstance.hide();
        }
    });
});
export default SystemTheme;