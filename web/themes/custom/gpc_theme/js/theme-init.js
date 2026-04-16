(function () {
  'use strict';

  var storageKey = 'gpc-theme';
  var root = document.documentElement;
  var media = window.matchMedia('(prefers-color-scheme: dark)');

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

  function setDrawerState(open) {
    root.dataset.drawerOpen = open ? 'true' : 'false';

    document.querySelectorAll('[data-drawer]').forEach(function (drawer) {
      drawer.dataset.open = open ? 'true' : 'false';
    });

    document.querySelectorAll('[data-drawer-toggle]').forEach(function (button) {
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function toggleDrawer(event, trigger) {
    event.preventDefault();
    var drawerId = trigger.getAttribute('data-drawer-target');
    var drawer = drawerId ? document.getElementById(drawerId) : null;
    var isOpen = root.dataset.drawerOpen === 'true';

    if (drawer) {
      drawer.dataset.open = isOpen ? 'false' : 'true';
    }

    setDrawerState(!isOpen);
  }

  syncTheme();
  setDrawerState(false);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      syncTheme();
      setDrawerState(root.dataset.drawerOpen === 'true');
    }, { once: true });
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

    if (event.target.closest('[data-drawer-backdrop]')) {
      setDrawerState(false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && root.dataset.drawerOpen === 'true') {
      setDrawerState(false);
    }
  });
}());
