// Apply saved theme settings from localStorage with nullish coalescing
const applyInitialTheme = () => {
    const html = document.documentElement;
    const settings = {
        'data-theme': localStorage.getItem('theme') ?? 'light',
        'data-sidebar': localStorage.getItem('sidebarTheme') ?? 'light',
        'data-color': localStorage.getItem('color') ?? 'primary',
        'data-topbar': localStorage.getItem('topbar') ?? 'white',
        'data-layout': localStorage.getItem('layout') ?? 'default',
        'data-topbarcolor': localStorage.getItem('topbarcolor') ?? 'white',
        'data-card': localStorage.getItem('card') ?? 'bordered',
        'data-size': localStorage.getItem('size') ?? 'default',
        'data-width': localStorage.getItem('width') ?? 'fluid',
        'data-loader': localStorage.getItem('loader') ?? 'enable'
    };

    Object.entries(settings).forEach(([key, value]) => html.setAttribute(key, value));
};

// Theme settings HTML template (Pickr-related elements removed)
const themeUrl = window.location.origin;
const themesettings = `
<div class="sidebar-contact ">
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
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#cardsetting" aria-expanded="true" aria-controls="collapsecustomicon1One">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/bordered.svg" alt="img">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/borderless.svg" alt="img">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/shadow.svg" alt="img">
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
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarsetting" aria-expanded="true">
                        Sidebar Color
                    </button>
                </h2>
                <div id="sidebarsetting" class="accordion-collapse collapse show">
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
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sizesetting" aria-expanded="true" aria-controls="collapsecustomicon1One">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/default.svg" alt="img">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/compact.svg" alt="img">
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
                                            <img src="${themeUrl}/treasury/pack/theme/img/hoverview.svg" alt="img">
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

// Main theme management class
class ThemeManager {
    constructor() {
        this.sidebarElement = document.querySelector('.sidebar');
        this.sidebarBgContainer = document.getElementById('sidebarbgContainer');
        this.layoutMini = 0;
    }

    initialize() {
        document.body.insertAdjacentHTML('beforeend', themesettings);
        this.setupEventListeners();
        this.applySavedSettings();
    }

    setupEventListeners() {
        const selectors = {
            theme: 'input[name="theme"]',
            sidebar: 'input[name="sidebar"]',
            color: 'input[name="color"]',
            layout: 'input[name="LayoutTheme"]',
            topbar: 'input[name="topbar"]',
            topbarcolor: 'input[name="topbarcolor"]',
            card: 'input[name="card"]',
            size: 'input[name="size"]',
            width: 'input[name="width"]',
            loader: 'input[name="loader"]',
            sidebarbg: 'input[name="sidebarbg"]',
            topbarbg: 'input[name="topbarbg"]'
        };

        Object.entries(selectors).forEach(([key, selector]) => {
            document.querySelectorAll(selector).forEach(radio => 
                radio.addEventListener('change', this[`handle${key.charAt(0).toUpperCase() + key.slice(1)}Change`].bind(this))
            );
        });

        document.getElementById('resetbutton')?.addEventListener('click', this.resetTheme.bind(this));
        this.setupDarkModeToggles();
    }

    setupDarkModeToggles() {
        const darkToggle = document.getElementById('dark-mode-toggle');
        const lightToggle = document.getElementById('light-mode-toggle');
        
        if (!darkToggle || !lightToggle) return;

        const isDark = localStorage.getItem('darkMode') === 'enabled';
        this.toggleDarkMode(isDark);

        darkToggle.addEventListener('click', () => this.toggleDarkMode(true));
        lightToggle.addEventListener('click', () => this.toggleDarkMode(false));
    }

    toggleDarkMode(enable) {
        const html = document.documentElement;
        const darkToggle = document.getElementById('dark-mode-toggle');
        const lightToggle = document.getElementById('light-mode-toggle');

        if (enable) {
            html.setAttribute('data-theme', 'dark');
            darkToggle.classList.remove('activate');
            lightToggle.classList.add('activate');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            html.setAttribute('data-theme', 'light');
            lightToggle.classList.remove('activate');
            darkToggle.classList.add('activate');
            localStorage.removeItem('darkMode');
        }
    }

    setThemeAttributes({ theme, sidebarTheme, color, layout, topbar, topbarcolor, card, size, width, loader }) {
        if (!this.sidebarElement) {
            console.error('Sidebar element not found');
            return;
        }

        const html = document.documentElement;
        const body = document.body;
        const attributes = { theme, sidebarTheme, color, layout, topbar, topbarcolor, card, size, width, loader };

        // Set data attributes
        Object.entries(attributes).forEach(([key, value]) => 
            html.setAttribute(`data-${key}`, value)
        );

        // Handle layout classes
        this.layoutMini = 0;
        body.classList.remove('mini-sidebar', 'menu-horizontal', 'layout-box-mode', 'expand-menu');

        if (layout === 'mini' || size === 'compact' || width === 'box') this.layoutMini = 1;

        if (layout === 'mini') body.classList.add('mini-sidebar');
        else if (layout.includes('horizontal')) body.classList.add('menu-horizontal');

        if (size === 'compact') body.classList.add('mini-sidebar');
        else if (size === 'hoverview') body.classList.add('expand-menu');

        if (width === 'box') body.classList.add('layout-box-mode', 'mini-sidebar');
        
        // Remove mini-sidebar for specific box layout combinations
        if (width === 'box' && layout.includes('horizontal')) {
            body.classList.remove('mini-sidebar');
        }

        // Update sidebar background visibility
        if (this.sidebarBgContainer) {
            this.sidebarBgContainer.classList.toggle('show', layout === 'box');
        }

        // Save to localStorage
        Object.entries(attributes).forEach(([key, value]) => 
            localStorage.setItem(key === 'sidebarTheme' ? 'sidebarTheme' : key, value)
        );
    }

    handleThemeChange = () => this.handleInputChange();
    handleSidebarChange = () => this.handleInputChange();
    handleColorChange = () => this.handleInputChange();
    handleLayoutChange = () => this.handleInputChange();
    handleTopbarChange = () => this.handleInputChange();
    handleTopbarcolorChange = () => this.handleInputChange();
    handleCardChange = () => this.handleInputChange();
    handleSizeChange = () => this.handleInputChange();
    handleWidthChange = () => this.handleInputChange();
    handleLoaderChange = () => this.handleInputChange();

    handleSidebarbgChange() {
        const sidebarBg = document.querySelector('input[name="sidebarbg"]:checked')?.value;
        if (sidebarBg) {
            document.body.setAttribute('data-sidebarbg', sidebarBg);
            localStorage.setItem('sidebarBg', sidebarBg);
        } else {
            document.body.removeAttribute('data-sidebarbg');
            localStorage.removeItem('sidebarBg');
        }
    }

    handleTopbarbgChange() {
        const topbarBg = document.querySelector('input[name="topbarbg"]:checked')?.value;
        if (topbarBg) {
            document.body.setAttribute('data-topbarbg', topbarBg);
            localStorage.setItem('topbarbg', topbarBg);
        } else {
            document.body.removeAttribute('data-topbarbg');
            localStorage.removeItem('topbarbg');
        }
    }

    handleInputChange() {
        const values = {
            theme: document.querySelector('input[name="theme"]:checked')?.value ?? 'light',
            layout: document.querySelector('input[name="LayoutTheme"]:checked')?.value ?? 'default',
            card: document.querySelector('input[name="card"]:checked')?.value ?? 'bordered',
            size: document.querySelector('input[name="size"]:checked')?.value ?? 'default',
            width: document.querySelector('input[name="width"]:checked')?.value ?? 'fluid',
            loader: document.querySelector('input[name="loader"]:checked')?.value ?? 'enable',
            color: document.querySelector('input[name="color"]:checked')?.value ?? 'primary',
            sidebarTheme: document.querySelector('input[name="sidebar"]:checked')?.value ?? 'light',
            topbar: document.querySelector('input[name="topbar"]:checked')?.value ?? 'white',
            topbarcolor: document.querySelector('input[name="topbarcolor"]:checked')?.value ?? 'white'
        };

        this.setThemeAttributes(values);
    }

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
        document.body.removeAttribute('data-sidebarbg');
        document.body.removeAttribute('data-topbarbg');
        
        ['lightTheme', 'lightSidebar', 'primaryColor', 'defaultLayout', 'whiteTopbar', 
         'whiteTopbarcolor', 'borderedCard', 'defaultSize', 'fluidWidth', 'enableLoader']
            .forEach(id => document.getElementById(id)?.setAttribute('checked', 'true'));

        ['sidebarbg', 'topbarbg'].forEach(name => {
            const checked = document.querySelector(`input[name="${name}"]:checked`);
            if (checked) checked.checked = false;
            localStorage.removeItem(name === 'sidebarbg' ? 'sidebarBg' : 'topbarbg');
        });
    }

    applySavedSettings() {
        const saved = {
            theme: localStorage.getItem('theme') ?? 'light',
            sidebarTheme: localStorage.getItem('sidebarTheme') ?? 'light',
            color: localStorage.getItem('color') ?? 'primary',
            layout: localStorage.getItem('layout') ?? 'default',
            topbar: localStorage.getItem('topbar') ?? 'white',
            topbarcolor: localStorage.getItem('topbarcolor') ?? 'white',
            card: localStorage.getItem('card') ?? 'bordered',
            size: localStorage.getItem('size') ?? 'default',
            width: localStorage.getItem('width') ?? 'fluid',
            loader: localStorage.getItem('loader') ?? 'enable'
        };

        this.setThemeAttributes(saved);

        // Apply saved background settings
        const sidebarBg = localStorage.getItem('sidebarBg');
        const topbarBg = localStorage.getItem('topbarbg');
        if (sidebarBg) document.body.setAttribute('data-sidebarbg', sidebarBg);
        if (topbarBg) document.body.setAttribute('data-topbarbg', topbarBg);

        // Set radio button states
        Object.entries(saved).forEach(([key, value]) => {
            const radio = document.getElementById(`${value}${key.charAt(0).toUpperCase() + key.slice(1)}`);
            if (radio) radio.checked = true;
        });

        if (sidebarBg) document.getElementById(sidebarBg)?.setAttribute('checked', 'true');
        if (topbarBg) document.getElementById(topbarBg)?.setAttribute('checked', 'true');
    }
}

// Initialize on DOM content loaded
document.addEventListener('DOMContentLoaded', () => {
    applyInitialTheme();
    const themeManager = new ThemeManager();
    themeManager.initialize();
});