(function () {
  'use strict';

  var storageKey = 'gpc-theme';
  var root = document.documentElement;
  var media = window.matchMedia('(prefers-color-scheme: dark)');
  var drawerModeQuery = window.matchMedia('(min-width: 64rem)');
  var drawerOpenTrigger = null;
  var mobileDrawerOpen = false;

  function getStoredTheme() {
    try {
      return window.localStorage.getItem(storageKey);
    }
    catch (error) {
      return null;
    }
  }

  function setStoredTheme(theme) {
    try {
      window.localStorage.setItem(storageKey, theme);
    }
    catch (error) {
      return null;
    }
  }

  function resolveSystemTheme() {
    return media.matches ? 'dark' : 'light';
  }

  function applyTheme(theme, source) {
    root.dataset.theme = theme;
    root.dataset.themeSource = source;
    root.style.colorScheme = theme;

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
      button.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      button.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      button.setAttribute('data-current-theme', theme);
    });
  }

  function syncTheme() {
    var storedTheme = getStoredTheme();
    if (storedTheme === 'light' || storedTheme === 'dark') {
      applyTheme(storedTheme, 'manual');
      return;
    }

    applyTheme(resolveSystemTheme(), 'system');
  }

  function toggleTheme(event) {
    event.preventDefault();
    var nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    setStoredTheme(nextTheme);
    applyTheme(nextTheme, 'manual');
  }

  function isDesktopDrawer() {
    return drawerModeQuery.matches;
  }

  function applyDrawerState() {
    var desktopDrawer = isDesktopDrawer();
    var open = desktopDrawer || mobileDrawerOpen;

    root.dataset.drawerMode = desktopDrawer ? 'desktop' : 'mobile';
    root.dataset.drawerOpen = mobileDrawerOpen ? 'true' : 'false';

    if (document.body) {
      document.body.classList.toggle('is-drawer-open', !desktopDrawer && mobileDrawerOpen);
    }

    document.querySelectorAll('[data-drawer]').forEach(function (drawer) {
      drawer.dataset.open = open ? 'true' : 'false';
      drawer.dataset.mode = desktopDrawer ? 'desktop' : 'mobile';
      drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    document.querySelectorAll('[data-drawer-toggle]').forEach(function (button) {
      button.setAttribute('aria-expanded', !desktopDrawer && mobileDrawerOpen ? 'true' : 'false');
    });

    if (!desktopDrawer && !mobileDrawerOpen && drawerOpenTrigger) {
      drawerOpenTrigger.focus();
      drawerOpenTrigger = null;
    }
  }

  function syncDrawerMode() {
    if (isDesktopDrawer()) {
      mobileDrawerOpen = false;
    }

    applyDrawerState();
  }

  function toggleDrawer(event, trigger) {
    event.preventDefault();
    if (isDesktopDrawer()) {
      return;
    }

    drawerOpenTrigger = trigger;
    var drawerId = trigger.getAttribute('data-drawer-target');
    var drawer = drawerId ? document.getElementById(drawerId) : null;
    var isOpen = mobileDrawerOpen;

    mobileDrawerOpen = !isOpen;
    applyDrawerState();
    if (!isOpen && drawer) {
      var focusTarget = drawer.querySelector('[data-drawer-close], a, button, input, select, textarea, summary, [tabindex]:not([tabindex="-1"])');
      if (focusTarget) {
        window.setTimeout(function () {
          focusTarget.focus();
        }, 0);
      }
    }
  }

  syncTheme();
  syncDrawerMode();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      syncTheme();
      syncDrawerMode();
    }, { once: true });
  }

  if (typeof drawerModeQuery.addEventListener === 'function') {
    drawerModeQuery.addEventListener('change', syncDrawerMode);
  }
  else if (typeof drawerModeQuery.addListener === 'function') {
    drawerModeQuery.addListener(syncDrawerMode);
  }

  var onMediaChange = function () {
    if (root.dataset.themeSource !== 'manual') {
      applyTheme(resolveSystemTheme(), 'system');
    }
  };

  if (typeof media.addEventListener === 'function') {
    media.addEventListener('change', onMediaChange);
  }
  else if (typeof media.addListener === 'function') {
    media.addListener(onMediaChange);
  }

  document.addEventListener('click', function (event) {
    var themeToggle = event.target.closest('[data-theme-toggle]');
    if (themeToggle) {
      toggleTheme(event);
      return;
    }

    var drawerToggle = event.target.closest('[data-drawer-toggle]');
    if (drawerToggle) {
      toggleDrawer(event, drawerToggle);
      return;
    }

    var drawerClose = event.target.closest('[data-drawer-close]');
    if (drawerClose) {
      event.preventDefault();
      mobileDrawerOpen = false;
      applyDrawerState();
      return;
    }

    if (!isDesktopDrawer() && event.target.closest('[data-drawer][data-open="true"] a')) {
      mobileDrawerOpen = false;
      applyDrawerState();
      return;
    }

    if (!isDesktopDrawer() && event.target.closest('[data-drawer-backdrop]')) {
      mobileDrawerOpen = false;
      applyDrawerState();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (!isDesktopDrawer() && event.key === 'Escape' && mobileDrawerOpen) {
      mobileDrawerOpen = false;
      applyDrawerState();
      return;
    }

    if (!isDesktopDrawer() && event.key === 'Tab' && mobileDrawerOpen) {
      var drawer = document.querySelector('[data-drawer][data-open="true"]');
      if (!drawer) {
        return;
      }

      var focusable = drawer.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), summary, [tabindex]:not([tabindex="-1"])');
      if (!focusable.length) {
        return;
      }

      var first = focusable[0];
      var last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      }
      else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });
}());
