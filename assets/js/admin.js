/* Trust Bar Admin JS */
(function ($) {
  'use strict';

  var cfg = window.TrustBarAdmin;

  // ─── Media uploader ───────────────────────────────────────────────
  var mediaFrame = null;
  var $currentLogoImg = null;

  function openMediaUploader() {
    if (mediaFrame) { mediaFrame.open(); return; }
    mediaFrame = wp.media({
      title:    cfg.strings.selectImage,
      button:   { text: cfg.strings.useImage },
      multiple: false,
      library:  { type: 'image' },
    });
    mediaFrame.on('select', function () {
      var attachment = mediaFrame.state().get('selection').first().toJSON();
      setLogoImage(attachment.id, attachment.url, attachment.filename || '');
    });
    mediaFrame.open();
  }

  function setLogoImage(attachmentId, url, filename) {
    $currentLogoImg = url;
    $('#tb-logo-attachment-id').val(attachmentId);
    $('#tb-logo-image-url').val(url);
    var $preview = $('#tb-upload-preview');
    $preview.html('<img src="' + escHtml(url) + '" alt="">');
    $('#tb-remove-image').show();
    // Auto-fill alt text if empty
    if (!$('#tb-logo-alt').val() && filename) {
      var base = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
      $('#tb-logo-alt').val(base);
    }
  }

  // ─── Logo Modal ───────────────────────────────────────────────────
  function openLogoModal(logoData) {
    var $modal = $('#tb-logo-modal');
    // Reset
    $('#tb-logo-form')[0].reset();
    $('#tb-upload-preview').html(
      '<span class="dashicons dashicons-format-image"></span><p>' + cfg.strings.uploading.replace('…','') + '…</p>'
    );
    $('#tb-remove-image').hide();

    if (logoData) {
      $('#tb-logo-id').val(logoData.id);
      $('#tb-logo-group-id').val(logoData.group_id);
      $('#tb-logo-title').val(logoData.title);
      $('#tb-logo-alt').val(logoData.alt_text);
      $('#tb-logo-link').val(logoData.link_url);
      $('#tb-logo-target').val(logoData.link_target || '_self');
      $('#tb-logo-active').prop('checked', logoData.is_active == 1);
      $('#tb-logo-attachment-id').val(logoData.attachment_id);
      $('#tb-logo-image-url').val(logoData.image_url);
      if (logoData.image_url) {
        $('#tb-upload-preview').html('<img src="' + escHtml(logoData.image_url) + '" alt="">');
        $('#tb-remove-image').show();
      }
    } else {
      var groupId = $('.tb-add-logo').data('group') || 1;
      $('#tb-logo-group-id').val(groupId);
    }

    $modal.fadeIn(200);
  }

  function closeModal(selector) {
    $(selector).fadeOut(200);
  }

  // ─── Process image ────────────────────────────────────────────────
  $('#tb-process-image').on('click', function () {
    var attachId = $('#tb-logo-attachment-id').val();
    if (!attachId) { alert('Please choose an image first.'); return; }
    var $btn = $(this).text(cfg.strings.processing).prop('disabled', true);
    $.post(cfg.ajaxUrl, {
      action:        'tb_process_img',
      nonce:         cfg.nonce,
      attachment_id: attachId,
      width:         320,
      height:        160,
      fit:           $('#tb-fit-mode').val(),
      trim:          $('#tb-trim-whitespace').is(':checked') ? 1 : 0,
    }, function (res) {
      $btn.text('Process Image').prop('disabled', false);
      if (res.success) {
        $('#tb-logo-image-url').val(res.data.url);
        $('#tb-upload-preview').html('<img src="' + escHtml(res.data.url) + '" alt="">');
      } else {
        alert(res.data || cfg.strings.error);
      }
    });
  });

  // ─── Save logo form ───────────────────────────────────────────────
  $('#tb-logo-form').on('submit', function (e) {
    e.preventDefault();
    var $spinner = $('.tb-modal-spinner').addClass('is-active');
    var data = {
      action:        'tb_save_logo',
      nonce:         cfg.nonce,
      id:            $('#tb-logo-id').val(),
      group_id:      $('#tb-logo-group-id').val(),
      title:         $('#tb-logo-title').val(),
      alt_text:      $('#tb-logo-alt').val(),
      link_url:      $('#tb-logo-link').val(),
      link_target:   $('#tb-logo-target').val(),
      attachment_id: $('#tb-logo-attachment-id').val(),
      image_url:     $('#tb-logo-image-url').val(),
      is_active:     $('#tb-logo-active').is(':checked') ? 1 : 0,
    };
    $.post(cfg.ajaxUrl, data, function (res) {
      $spinner.removeClass('is-active');
      if (res.success) {
        closeModal('#tb-logo-modal');
        location.reload(); // Refresh to show updated logo list & preview
      } else {
        alert(cfg.strings.error);
      }
    });
  });

  // ─── Delete logo ──────────────────────────────────────────────────
  $(document).on('click', '.tb-delete-logo', function () {
    if (!confirm(cfg.strings.confirmDelete)) return;
    var id = $(this).data('id');
    $.post(cfg.ajaxUrl, { action: 'tb_delete_logo', nonce: cfg.nonce, id: id }, function (res) {
      if (res.success) location.reload();
    });
  });

  // ─── Toggle logo active state ─────────────────────────────────────
  $(document).on('change', '.tb-logo-active', function () {
    var id    = $(this).data('id');
    var state = $(this).is(':checked') ? 1 : 0;
    var $item = $(this).closest('.tb-logo-item');
    $item.toggleClass('tb-inactive', !state);
    $.post(cfg.ajaxUrl, { action: 'tb_toggle_logo', nonce: cfg.nonce, id: id, is_active: state });
  });

  // ─── Edit logo ────────────────────────────────────────────────────
  $(document).on('click', '.tb-edit-logo', function () {
    var logo = $(this).data('logo');
    openLogoModal(logo);
  });

  // ─── Add logo ─────────────────────────────────────────────────────
  $(document).on('click', '.tb-add-logo', function () {
    openLogoModal(null);
  });

  // ─── Media library ────────────────────────────────────────────────
  $(document).on('click', '#tb-choose-image', openMediaUploader);

  $(document).on('click', '#tb-remove-image', function () {
    $('#tb-logo-attachment-id').val('');
    $('#tb-logo-image-url').val('');
    $('#tb-upload-preview').html(
      '<span class="dashicons dashicons-format-image"></span><p>Click to upload or drag an image here</p>'
    );
    $(this).hide();
  });

  // ─── Close modals ─────────────────────────────────────────────────
  $(document).on('click', '.tb-modal-close, .tb-modal-cancel, .tb-modal-overlay', function () {
    $(this).closest('.tb-modal').fadeOut(200);
  });

  // ─── Sortable drag-to-reorder ─────────────────────────────────────
  $('.tb-logo-list').sortable({
    handle:   '.tb-drag-handle',
    axis:     'y',
    update: function () {
      var ids      = [];
      var groupId  = $(this).data('group');
      $(this).find('.tb-logo-item').each(function () {
        ids.push($(this).data('id'));
      });
      $.post(cfg.ajaxUrl, { action: 'tb_reorder', nonce: cfg.nonce, group_id: groupId, ids: ids });
    },
  }).disableSelection();

  // ─── Settings form ────────────────────────────────────────────────
  $('#tb-settings-form').on('submit', function (e) {
    e.preventDefault();
    var $notice = $('#tb-save-notice').hide().removeClass('success error');
    var formData = $(this).serializeArray();
    var data = { action: 'tb_save_group', nonce: cfg.nonce };
    formData.forEach(function (field) { data[field.name] = field.value; });
    data['id'] = $(this).data('group');

    // Explicitly handle unchecked checkboxes (they don't appear in serializeArray)
    ['settings[carousel]','settings[carousel_auto]','settings[show_text]','settings[border]'].forEach(function (k) {
      if (!data[k]) data[k] = '0';
    });

    $.post(cfg.ajaxUrl, data, function (res) {
      if (res.success) {
        $notice.text(cfg.strings.saved).addClass('success').show();
        refreshPreview();
      } else {
        $notice.text(cfg.strings.error).addClass('error').show();
      }
      setTimeout(function () { $notice.fadeOut(); }, 3000);
    });
  });

  // ─── New group modal ──────────────────────────────────────────────
  $('#tb-add-group').on('click', function (e) {
    e.preventDefault();
    $('#tb-group-modal').fadeIn(200);
  });

  $('#tb-group-form').on('submit', function (e) {
    e.preventDefault();
    var name = $('#tb-new-group-name').val().trim();
    if (!name) return;
    $.post(cfg.ajaxUrl, { action: 'tb_save_group', nonce: cfg.nonce, name: name, settings: {} }, function (res) {
      if (res.success) {
        window.location.href = window.location.href.split('&group')[0] + '&group=' + res.data.id;
      }
    });
  });

  // ─── Delete group ─────────────────────────────────────────────────
  $(document).on('click', '.tb-delete-group', function () {
    if (!confirm(cfg.strings.confirmDeleteGroup)) return;
    var id = $(this).data('id');
    $.post(cfg.ajaxUrl, { action: 'tb_delete_group', nonce: cfg.nonce, id: id }, function (res) {
      if (res.success) window.location.href = window.location.href.split('&group')[0];
    });
  });

  // ─── Copy shortcode ───────────────────────────────────────────────
  $(document).on('click', '.tb-copy-shortcode', function () {
    var code = $(this).data('code');
    navigator.clipboard ? navigator.clipboard.writeText(code) : copyFallback(code);
    $(this).text('Copied!');
    var $btn = $(this);
    setTimeout(function () { $btn.text('Copy'); }, 2000);
  });

  // ─── Color mode active class ──────────────────────────────────────
  $(document).on('change', 'input[name="settings[color_mode]"]', function () {
    $('.tb-color-mode-option').removeClass('active');
    $(this).closest('.tb-color-mode-option').addClass('active');
  });

  // ─── Color pickers ────────────────────────────────────────────────
  $('.tb-color-picker').wpColorPicker();

  // ─── Live preview refresh (after settings save) ───────────────────
  function refreshPreview() {
    var groupId = $('#tb-settings-form').data('group');
    var $wrap   = $('#tb-preview-' + groupId);
    if (!$wrap.length) return;
    $wrap.css('opacity', 0.5);
    // Reload current page into preview via fetch
    fetch(window.location.href)
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var parser  = new DOMParser();
        var doc     = parser.parseFromString(html, 'text/html');
        var newPrev = doc.querySelector('#tb-preview-' + groupId);
        if (newPrev) {
          $wrap.html(newPrev.innerHTML);
          $wrap.css('opacity', 1);
          window.TrustBarInit && window.TrustBarInit();
        }
      }).catch(function () { $wrap.css('opacity', 1); });
  }

  // ─── Util ─────────────────────────────────────────────────────────
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function copyFallback(text) {
    var el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }

})(jQuery);
