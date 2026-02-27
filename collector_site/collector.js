
(function () {
  'use strict';
  const ENDPOINT = 'https://collector.mrxijian.site/cgi-bin/collect.cgi';

  const MOUSE_THROTTLE_MS = 250;
  const SCROLL_THROTTLE_MS = 250;
  const IDLE_THRESHOLD_MS = 2000;
  const IDLE_CHECK_MS = 500;

  function getSessionId() {
    let sid = sessionStorage.getItem('_collector_sid');
    if (!sid) {
      sid = Math.random().toString(36).substring(2) + Date.now().toString(36);
      sessionStorage.setItem('_collector_sid', sid);
    }
    return sid;
  }

  function getNetworkInfo() {
    if (!('connection' in navigator)) return {};
    const conn = navigator.connection;
    return {
      effectiveType: conn.effectiveType,
      downlink: conn.downlink,
      rtt: conn.rtt,
      saveData: conn.saveData
    };
  }

  function getTechnographics() {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,

      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,

      screenWidth: window.screen.width,
      screenHeight: window.screen.height,
      pixelRatio: window.devicePixelRatio,

      cores: navigator.hardwareConcurrency || 0,
      memory: navigator.deviceMemory || 0,

      network: getNetworkInfo(),

      colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
  }

  // round a number to two decimal places
  function round(n) {
    return Math.round(n * 100) / 100;
  }

  function getNavigationTiming() {
    const entries = performance.getEntriesByType('navigation');
    if (!entries.length) return {};

    const n = entries[0];

    return {
      dnsLookup: round(n.domainLookupEnd - n.domainLookupStart),
      tcpConnect: round(n.connectEnd - n.connectStart),
      tlsHandshake: n.secureConnectionStart > 0 ? round(n.connectEnd - n.secureConnectionStart) : 0,
      ttfb: round(n.responseStart - n.requestStart),
      download: round(n.responseEnd - n.responseStart),
      domInteractive: round(n.domInteractive - n.fetchStart),
      domComplete: round(n.domComplete - n.fetchStart),
      loadEvent: round(n.loadEventEnd - n.fetchStart),
      fetchTime: round(n.responseEnd - n.fetchStart),
      transferSize: n.transferSize,
      headerSize: n.transferSize - n.encodedBodySize
    };
  }

  function getResourceSummary() {
    const resources = performance.getEntriesByType('resource');

    const summary = {
      script:         { count: 0, totalSize: 0, totalDuration: 0 },
      link:           { count: 0, totalSize: 0, totalDuration: 0 },
      img:            { count: 0, totalSize: 0, totalDuration: 0 },
      font:           { count: 0, totalSize: 0, totalDuration: 0 },
      fetch:          { count: 0, totalSize: 0, totalDuration: 0 },
      xmlhttprequest: { count: 0, totalSize: 0, totalDuration: 0 },
      other:          { count: 0, totalSize: 0, totalDuration: 0 }
    };

    resources.forEach((r) => {
      const type = summary[r.initiatorType] ? r.initiatorType : 'other';
      summary[type].count++;
      summary[type].totalSize += r.transferSize || 0;
      summary[type].totalDuration += r.duration || 0;
    });

    return {
      totalResources: resources.length,
      byType: summary
    };
  }

  function send(payload) {
    const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });

    if (navigator.sendBeacon) {
      navigator.sendBeacon(ENDPOINT, blob);
    } else {
      fetch(ENDPOINT, { method: 'POST', body: blob, keepalive: true }).catch(() => {});
    }


    window.dispatchEvent(new CustomEvent('collector:payload', { detail: payload }));
  }

  function basePayload(kind) {
    return {
      kind, 
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      timestamp: new Date().toISOString(),
      session: getSessionId(),
      page: {
        path: window.location.pathname,
        host: window.location.host
      }
    };
  }

  function collectStatic() {
    const payload = basePayload('static');
    payload.technographics = getTechnographics();

    payload.capabilities = {
      jsEnabled: true,
      imagesEnabled: true,
      cssEnabled: true
    };

    send(payload);
  }

  function collectPerformance() {
    const payload = basePayload('performance');
    payload.timing = getNavigationTiming();
    payload.resources = getResourceSummary();

    if (payload.timing && typeof payload.timing.loadEvent === 'number') {
      payload.totalLoadTimeMs = Math.round(payload.timing.loadEvent);
    }

    send(payload);
  }

  function collectEnter() {
    const payload = basePayload('enter');
    send(payload);
  }

  function collectLeave() {
    const payload = basePayload('leave');
    send(payload);
  }

  //collect acitivity
  let lastMouseSent = 0;
  let lastScrollSent = 0;

  function collectActivity(type, data) {
    const payload = basePayload('activity');
    payload.activity = { type, ...data };
    send(payload);
  }

  //idle 2 seconds
  let lastActivityAt = Date.now();
  let idleActive = false;
  let idleStartAt = null;

  function markActive() {
    const now = Date.now();
    lastActivityAt = now;

    if (idleActive) {
      idleActive = false;
      const idleEnd = now;
      collectActivity('idle_end', {
        idleStartAt,
        idleEndAt: idleEnd,
        idleDurationMs: idleEnd - idleStartAt
      });
    }
  }

  function idleLoop() {
    const now = Date.now();
    if (!idleActive && (now - lastActivityAt >= IDLE_THRESHOLD_MS)) {
      idleActive = true;
      idleStartAt = lastActivityAt;
      collectActivity('idle_start', { idleStartAt });
    }
  }

  function collectError(type, data) {
    const payload = basePayload('error');
    payload.error = { type, ...data };
    send(payload);
  }

  window.addEventListener('load', () => {
    collectEnter();

    setTimeout(() => {
      collectStatic();
      collectPerformance();
    }, 0);
  });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
      collectLeave();
    }
  });

  document.addEventListener('mousemove', (e) => {
    markActive();
    const now = Date.now();
    if (now - lastMouseSent < MOUSE_THROTTLE_MS) return;
    lastMouseSent = now;

    collectActivity('mousemove', { x: e.clientX, y: e.clientY });
  }, { passive: true });

  document.addEventListener('click', (e) => {
    markActive();
    collectActivity('click', { x: e.clientX, y: e.clientY, button: e.button });
  }, { passive: true });

  window.addEventListener('scroll', () => {
    markActive();
    const now = Date.now();
    if (now - lastScrollSent < SCROLL_THROTTLE_MS) return;
    lastScrollSent = now;

    collectActivity('scroll', { scrollX: window.scrollX, scrollY: window.scrollY });
  }, { passive: true });

  document.addEventListener('keydown', (e) => {
    markActive();
    collectActivity('keydown', { key: e.key, code: e.code || null });
  });

  // JS errors
  window.addEventListener('error', (ev) => {
    markActive();
    collectError('error', {
      message: ev.message || 'error',
      filename: ev.filename || null,
      lineno: ev.lineno || null,
      colno: ev.colno || null
    });
  });

  window.addEventListener('unhandledrejection', (ev) => {
    markActive();
    collectError('unhandledrejection', { reason: String(ev.reason) });
  });

  
  setInterval(idleLoop, IDLE_CHECK_MS);

  window.__collector = {
    getSessionId,
    getNetworkInfo,
    getTechnographics,
    getNavigationTiming,
    getResourceSummary,
    collectStatic,
    collectPerformance,
    collectEnter,
    collectLeave
  };

})();