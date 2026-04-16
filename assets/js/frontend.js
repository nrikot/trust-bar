/**
 * Trust Bar – Frontend Carousel Engine
 * Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  function TrustBar(el) {
    this.el = el;
    this.track = el.querySelector('.tb-track');
    this.viewport = el.querySelector('.tb-viewport');
    this.items = Array.from(el.querySelectorAll('.tb-item'));
    this.prevBtn = el.querySelector('.tb-prev');
    this.nextBtn = el.querySelector('.tb-next');
    this.dotsWrap = el.querySelector('.tb-dots');
    this.rows = parseInt(el.dataset.rows, 10) || 1;
    this.carousel = el.dataset.carousel === '1';
    this.auto = el.dataset.auto === '1';
    this.speed = parseInt(el.dataset.speed, 10) || 4000;
    this.current = 0;
    this.timer = null;
    this.pages = [];
    this.mode = 'page';

    // Continuous carousel state (1-row only)
    this._tbOrigItemHTML = null;
    this._n = 0;
    this._baseWidth = 0; // distance between first logo of consecutive sequences (includes flex gap)
    this._itemStartRel = []; // start offsets of each logo within one sequence (px)
    this._deltaNext = []; // how far to move track when going to next logo (px)
    this._deltaPrev = []; // how far to move track when going to prev logo (px)
    this._transitionMs = 600;
    this._pxPerSec = 0;
    this._rafId = null;
    this._autoRunning = false;
    this._lastRafTs = 0;
    this.translateX = 0; // current translateX for continuous mode
    this.currentIndex = 0; // logo index for continuous mode
    this._autoRestartTimeout = null;

    this._init();
  }

  TrustBar.prototype = {
    _init: function () {
      var self = this;
      if (this.carousel && this.rows === 1) {
        this.mode = 'continuous';
        this._initContinuousCarousel();
        this._bindArrows();
        this._bindResize();
        this._bindHover();
        if (this.auto) this._startAuto();
        return;
      }

      this._buildPages();
      this._buildDots();
      this._bindArrows();
      this._bindDots();
      this._bindResize();
      this._bindHover();
      this.goTo(0, false);

      if (!this.carousel || this.pages.length <= 1) {
        this.el.classList.add('tb-no-carousel');
      } else if (this.auto) {
        this._startAuto();
      }
    },

    /**
     * Group items into pages based on how many fit in the viewport.
     * Recalculates on resize.
     */
    _buildPages: function () {
      var self = this;
      var vpWidth = this.viewport.offsetWidth;
      var style = getComputedStyle(this.el);
      var gap = parseFloat(style.getPropertyValue('--tb-gap')) || 32;
      var logoH = parseFloat(style.getPropertyValue('--tb-logo-height')) || 60;
      var logoMaxW =
        parseFloat(style.getPropertyValue('--tb-logo-max-width')) || 160;
      var rowsCount = this.rows;

      // Measure each item's rendered width (or estimate)
      var itemWidths = this.items.map(function (item) {
        var img = item.querySelector('img');
        if (img && img.naturalWidth && img.naturalHeight) {
          var ratio = img.naturalWidth / img.naturalHeight;
          return Math.min(ratio * logoH, logoMaxW);
        }
        return Math.min(item.offsetWidth || logoMaxW, logoMaxW);
      });

      // Items per page based on rows
      var perRow = this._itemsPerRow(vpWidth, itemWidths, gap, rowsCount);
      var perPage = perRow * rowsCount;
      if (perPage < 1) perPage = 1;

      this.pages = [];
      for (var i = 0; i < this.items.length; i += perPage) {
        this.pages.push(this.items.slice(i, i + perPage));
      }

      // Cap current page index
      if (this.current >= this.pages.length) {
        this.current = this.pages.length - 1;
      }
    },

    _itemsPerRow: function (vpWidth, itemWidths, gap, rows) {
      var count = 0;
      var used = 0;
      for (var i = 0; i < itemWidths.length; i++) {
        var needed = (count === 0 ? 0 : gap) + itemWidths[i];
        if (used + needed <= vpWidth) {
          used += needed;
          count++;
        } else {
          break;
        }
      }
      return Math.max(count, 1);
    },

    _buildDots: function () {
      var self = this;
      if (!this.dotsWrap) return;
      this.dotsWrap.innerHTML = '';
      if (!this.carousel || this.pages.length <= 1) return;
      this.pages.forEach(function (_, idx) {
        var btn = document.createElement('button');
        btn.className = 'tb-dot';
        btn.setAttribute('aria-label', 'Page ' + (idx + 1));
        btn.dataset.idx = idx;
        self.dotsWrap.appendChild(btn);
      });
    },

    _bindArrows: function () {
      var self = this;
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', function () {
          self._stopAuto();
          self.prev();
          if (self.mode === 'continuous') {
            if (self._autoRestartTimeout)
              clearTimeout(self._autoRestartTimeout);
            self._autoRestartTimeout = window.setTimeout(
              function () {
                self._autoRestartTimeout = null;
                self._restartAuto();
              },
              (self._transitionMs || 600) + 60,
            );
          } else {
            self._restartAuto();
          }
        });
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', function () {
          self._stopAuto();
          self.next();
          if (self.mode === 'continuous') {
            if (self._autoRestartTimeout)
              clearTimeout(self._autoRestartTimeout);
            self._autoRestartTimeout = window.setTimeout(
              function () {
                self._autoRestartTimeout = null;
                self._restartAuto();
              },
              (self._transitionMs || 600) + 60,
            );
          } else {
            self._restartAuto();
          }
        });
      }
    },

    _bindDots: function () {
      var self = this;
      if (!this.dotsWrap) return;
      this.dotsWrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.tb-dot');
        if (!btn) return;
        self._stopAuto();
        self.goTo(parseInt(btn.dataset.idx, 10));
        self._restartAuto();
      });
    },

    _bindResize: function () {
      var self = this;
      var t;
      window.addEventListener('resize', function () {
        clearTimeout(t);
        t = setTimeout(function () {
          if (self.mode === 'continuous') {
            var idx = self._getIndexFromTranslateX();
            self._rebuildContinuousCarousel(idx);
          } else {
            self._buildPages();
            self._buildDots();
            self.goTo(Math.min(self.current, self.pages.length - 1), false);
            if (!self.carousel || self.pages.length <= 1) {
              self.el.classList.add('tb-no-carousel');
            } else {
              self.el.classList.remove('tb-no-carousel');
            }
          }
        }, 200);
      });
    },

    _bindHover: function () {
      var self = this;
      this.el.addEventListener('mouseenter', function () {
        self._stopAuto();
      });
      this.el.addEventListener('mouseleave', function () {
        self._restartAuto();
      });
      this.el.addEventListener('focusin', function () {
        self._stopAuto();
      });
      this.el.addEventListener('focusout', function () {
        self._restartAuto();
      });
    },

    prev: function () {
      if (this.mode === 'continuous') {
        this._stepPrev();
        return;
      }
      this.goTo(this.current > 0 ? this.current - 1 : this.pages.length - 1);
    },

    next: function () {
      if (this.mode === 'continuous') {
        this._stepNext();
        return;
      }
      this.goTo(this.current < this.pages.length - 1 ? this.current + 1 : 0);
    },

    goTo: function (idx, animate) {
      if (idx < 0 || idx >= this.pages.length) return;
      var self = this;
      this.current = idx;

      // Show/hide items
      this.items.forEach(function (item) {
        item.style.display = 'none';
      });
      var page = this.pages[idx];
      if (page) {
        page.forEach(function (item) {
          item.style.display = '';
        });
      }

      // Slide transition on the track
      if (animate !== false) {
        this.track.style.transition = 'opacity var(--tb-transition) ease';
        this.track.style.opacity = '0';
        setTimeout(function () {
          self.track.style.opacity = '1';
        }, 50);
      }

      // Update dots
      var dots = this.dotsWrap ? this.dotsWrap.querySelectorAll('.tb-dot') : [];
      dots.forEach(function (d, i) {
        d.classList.toggle('active', i === idx);
      });

      // Update arrows
      if (this.prevBtn) this.prevBtn.disabled = this.pages.length <= 1;
      if (this.nextBtn) this.nextBtn.disabled = this.pages.length <= 1;
    },

    _startAuto: function () {
      if (!this.auto) return;

      if (this.mode === 'continuous') {
        if (this._n <= 1) return;
        this._autoRunning = true;
        this._lastRafTs =
          window.performance && window.performance.now
            ? window.performance.now()
            : Date.now();
        this.track.style.transition = 'none'; // avoid transform easing while auto-scrolling

        var self = this;
        var tick = function (ts) {
          if (!self._autoRunning) return;
          var now = ts || Date.now();
          var dt = now - self._lastRafTs;
          self._lastRafTs = now;

          // Move LEFT slowly (translateX decreases)
          var dx = (self._pxPerSec || 0) * (dt / 1000);
          self.translateX -= dx;

          // Seamless wrap without animation
          self._normalizeTranslateX();

          self.track.style.transform = 'translateX(' + self.translateX + 'px)';

          self._rafId = window.requestAnimationFrame(tick);
        };
        this._rafId = window.requestAnimationFrame(tick);
        return;
      }

      if (this.pages.length <= 1) return;
      var self = this;
      this.timer = setInterval(function () {
        self.next();
      }, this.speed);
    },

    _stopAuto: function () {
      if (this.mode === 'continuous') {
        this._autoRunning = false;
        if (this._rafId) {
          window.cancelAnimationFrame(this._rafId);
          this._rafId = null;
        }
        // Restore CSS transition for arrow stepping
        this.track.style.transition = '';
        return;
      }

      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
    },

    _restartAuto: function () {
      if (!this.auto) return;

      if (this.mode === 'continuous') {
        if (this._n <= 1) return;
        this._stopAuto();
        this._startAuto();
        return;
      }

      if (this.pages.length <= 1) return;
      this._stopAuto();
      this._startAuto();
    },

    // ─── Continuous carousel setup (1 row only) ──────────────────────
    _initContinuousCarousel: function () {
      var self = this;
      if (this.carousel !== true) return;

      // Capture original items once (for rebuilding on resize)
      if (!this._tbOrigItemHTML) {
        this._tbOrigItemHTML = this.items.map(function (it) {
          return it.outerHTML;
        });
      }

      // Rebuild track to original state (no clones, no hidden items)
      this.track.innerHTML = this._tbOrigItemHTML.join('');
      this.items = Array.from(this.track.querySelectorAll('.tb-item'));
      this._n = this.items.length;

      // Update transition duration from inline CSS var (--tb-transition)
      var css = getComputedStyle(this.el);
      var t = (css.getPropertyValue('--tb-transition') || '').trim();
      this._transitionMs = parseInt(t, 10);
      if (!this._transitionMs || isNaN(this._transitionMs))
        this._transitionMs = 600;

      // Prepare for next/prev one-logo stepping
      if (this._n <= 1) {
        this.currentIndex = 0;
        this.translateX = 0;
        this.track.style.transition = 'none';
        this.track.style.transform = 'translateX(0px)';
        this.el.classList.add('tb-no-carousel');
        if (this.prevBtn) this.prevBtn.disabled = true;
        if (this.nextBtn) this.nextBtn.disabled = true;
        this.track.style.transition = '';
        if (this.dotsWrap) this.dotsWrap.innerHTML = '';
        return;
      }

      this.el.classList.remove('tb-no-carousel');
      if (this.prevBtn) this.prevBtn.disabled = false;
      if (this.nextBtn) this.nextBtn.disabled = false;

      // Measure per-sequence offsets and distances
      var gapPx = parseFloat(css.getPropertyValue('--tb-gap')) || 0;
      var firstStart = this.items[0].offsetLeft;
      var last = this.items[this._n - 1];
      var lastRight = last.offsetLeft + last.offsetWidth;
      this._baseWidth = lastRight - firstStart + gapPx; // include inter-sequence flex gap

      // Start offsets of each logo within one sequence
      this._itemStartRel = [];
      for (var i = 0; i < this._n; i++) {
        this._itemStartRel.push(this.items[i].offsetLeft - firstStart);
      }

      // Precompute step deltas (px) for one-logo stepping
      this._deltaNext = [];
      this._deltaPrev = [];
      for (var j = 0; j < this._n; j++) {
        var nextIdx = (j + 1) % this._n;
        var deltaN;
        if (j === this._n - 1) {
          // last -> first of next sequence
          deltaN =
            this._itemStartRel[nextIdx] +
            this._baseWidth -
            this._itemStartRel[j];
        } else {
          deltaN = this._itemStartRel[nextIdx] - this._itemStartRel[j];
        }
        this._deltaNext.push(deltaN);

        var prevIdx = (j - 1 + this._n) % this._n;
        var deltaP;
        if (j === 0) {
          // first -> last of previous sequence (move right)
          deltaP = this._baseWidth - this._itemStartRel[prevIdx];
        } else {
          deltaP = this._itemStartRel[j] - this._itemStartRel[prevIdx];
        }
        this._deltaPrev.push(deltaP);
      }

      // Build clones to make wrap seamless in both directions
      // Track will be: [prepend clone] + [original sequence] + [append clone]
      var origNodes = this.items.slice();
      var prependFrag = document.createDocumentFragment();
      origNodes.forEach(function (node) {
        prependFrag.appendChild(node.cloneNode(true));
      });
      this.track.insertBefore(prependFrag, this.track.firstChild);

      var appendFrag = document.createDocumentFragment();
      origNodes.forEach(function (node) {
        appendFrag.appendChild(node.cloneNode(true));
      });
      this.track.appendChild(appendFrag);

      // Decide initial index (0 unless already set by _rebuildContinuousCarousel)
      if (typeof this.currentIndex !== 'number') this.currentIndex = 0;
      this.currentIndex = Math.max(0, Math.min(this.currentIndex, this._n - 1));

      // Initial position aligns the current logo at the viewport left edge
      // Middle sequence starts after one baseWidth (because of the prepend clone)
      this.translateX =
        -this._baseWidth - this._itemStartRel[this.currentIndex];
      this.track.style.transition = 'none';
      this.track.style.transform = 'translateX(' + this.translateX + 'px)';
      this.track.style.transition = '';

      // Auto speed: interpret data-speed as "interval between page switches" from old mode.
      // For 1-row continuous mode, scale it so movement is still very slow.
      var vpWidth = this.viewport.offsetWidth;
      var perRow = 0;
      var used = 0;
      for (var k = 0; k < this._n; k++) {
        var w =
          this.items[k].offsetWidth ||
          parseFloat(this.items[k].style.width) ||
          160;
        var needed = perRow === 0 ? w : gapPx + w;
        if (used + needed <= vpWidth) {
          used += needed;
          perRow++;
        } else {
          break;
        }
      }
      perRow = Math.max(perRow, 1);

      var logoIntervalMs = this.speed * perRow; // "very slow": one logo step per old-page interval scaled
      var avgStep = 0;
      for (var a = 0; a < this._n; a++) avgStep += this._deltaNext[a];
      avgStep = avgStep / this._n;
      this._pxPerSec =
        logoIntervalMs > 0 ? avgStep / (logoIntervalMs / 1000) : avgStep / 4;

      // Hide dots in continuous mode (they were page-based)
      if (this.dotsWrap) this.dotsWrap.innerHTML = '';
    },

    _rebuildContinuousCarousel: function (keepIndex) {
      if (this._n <= 1) {
        this._initContinuousCarousel();
        return;
      }
      if (typeof keepIndex !== 'number') keepIndex = 0;
      this.currentIndex = ((keepIndex % this._n) + this._n) % this._n;
      this._stopAuto();
      this._initContinuousCarousel();
      // _initContinuousCarousel uses this.currentIndex
    },

    _normalizeTranslateX: function () {
      if (!this._baseWidth) return;
      // Keep translateX within [-2*baseWidth, -baseWidth] for stability
      var minT = -2 * this._baseWidth;
      var maxT = -1 * this._baseWidth;
      while (this.translateX <= minT) this.translateX += this._baseWidth;
      while (this.translateX > maxT) this.translateX -= this._baseWidth;
    },

    _normalizeToMiddle: function () {
      // Snap immediately to the middle sequence for the currentIndex (no visible jump due to clones)
      this.track.style.transition = 'none';
      this.translateX =
        -this._baseWidth - this._itemStartRel[this.currentIndex];
      this.track.style.transform = 'translateX(' + this.translateX + 'px)';
      this.track.style.transition = '';
    },

    _getIndexFromTranslateX: function () {
      if (this.mode !== 'continuous') return this.current;
      if (!this._baseWidth || !this._itemStartRel || !this._itemStartRel.length)
        return 0;

      // viewportStartX is the x-coordinate inside the track currently visible at the viewport's left edge.
      var viewportStartX = -this.translateX;
      // Offset within the "middle sequence" (which starts at baseWidth)
      var offset = viewportStartX - this._baseWidth;

      // Normalize to [0, baseWidth)
      offset = ((offset % this._baseWidth) + this._baseWidth) % this._baseWidth;

      // Find the last item start <= offset
      var lo = 0;
      var hi = this._itemStartRel.length - 1;
      var ans = 0;
      while (lo <= hi) {
        var mid = (lo + hi) >> 1;
        if (this._itemStartRel[mid] <= offset) {
          ans = mid;
          lo = mid + 1;
        } else {
          hi = mid - 1;
        }
      }
      return ans;
    },

    _stepNext: function () {
      if (this._n <= 1) return;
      if (this._autoRestartTimeout) {
        clearTimeout(this._autoRestartTimeout);
        this._autoRestartTimeout = null;
      }

      var idx = this._getIndexFromTranslateX();
      var nextIdx = (idx + 1) % this._n;
      var delta = this._deltaNext[idx] || 0;
      var targetTranslate = this.translateX - delta;

      // Animate with CSS transform transition
      this.track.style.transition = '';
      this.translateX = targetTranslate;
      this.track.style.transform = 'translateX(' + this.translateX + 'px)';

      var self = this;
      window.setTimeout(function () {
        self.currentIndex = nextIdx;
        self._normalizeToMiddle();
      }, this._transitionMs + 10);
    },

    _stepPrev: function () {
      if (this._n <= 1) return;
      if (this._autoRestartTimeout) {
        clearTimeout(this._autoRestartTimeout);
        this._autoRestartTimeout = null;
      }

      var idx = this._getIndexFromTranslateX();
      var prevIdx = (idx - 1 + this._n) % this._n;
      var delta = this._deltaPrev[idx] || 0;
      var targetTranslate = this.translateX + delta;

      // Animate with CSS transform transition
      this.track.style.transition = '';
      this.translateX = targetTranslate;
      this.track.style.transform = 'translateX(' + this.translateX + 'px)';

      var self = this;
      window.setTimeout(function () {
        self.currentIndex = prevIdx;
        self._normalizeToMiddle();
      }, this._transitionMs + 10);
    },
  };

  // ─── Init all trust bars on the page ───
  function initAll() {
    document.querySelectorAll('.trust-bar-wrap').forEach(function (el) {
      if (!el._tbInstance) {
        el._tbInstance = new TrustBar(el);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }

  // Re-init after Gutenberg block live-render (editor)
  if (window.wp && window.wp.hooks) {
    window.wp.hooks.addAction('trust_bar.reinit', 'trust-bar', initAll);
  }

  // Expose for manual init (e.g., after AJAX page loads)
  window.TrustBarInit = initAll;
})();
