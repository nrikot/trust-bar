/**
 * Trust Bar – Gutenberg Block
 */
(function (blocks, element, blockEditor, components, i18n) {
  var el        = element.createElement;
  var __        = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody  = components.PanelBody;
  var SelectControl = components.SelectControl;
  var RangeControl  = components.RangeControl;
  var ToggleControl = components.ToggleControl;
  var TextControl   = components.TextControl;
  var ServerSideRender = wp.serverSideRender || components.ServerSideRender;

  var groups = (window.TrustBarBlock && window.TrustBarBlock.groups) || [];
  var groupOptions = groups.map(function (g) {
    return { label: g.label, value: g.value };
  });

  blocks.registerBlockType('trust-bar/trust-bar', {
    title:       __('Trust Bar', 'trust-bar'),
    description: __('Display a logo trust bar / partner strip.', 'trust-bar'),
    icon:        'awards',
    category:    'widgets',
    keywords:    [__('logo'), __('trust'), __('brand'), __('partner')],

    attributes: {
      groupId:    { type: 'integer', default: groupOptions[0] ? groupOptions[0].value : 1 },
      colorMode:  { type: 'string',  default: '' },
      rows:       { type: 'integer', default: 0 },
      carousel:   { type: 'string',  default: '' },
      extraClass: { type: 'string',  default: '' },
    },

    edit: function (props) {
      var attrs  = props.attributes;
      var setAttr = props.setAttributes;

      var colorModeOptions = [
        { label: __('— Use group setting —', 'trust-bar'), value: '' },
        { label: __('Full Color', 'trust-bar'),            value: 'full' },
        { label: __('Grayscale', 'trust-bar'),             value: 'grayscale' },
        { label: __('Monochrome', 'trust-bar'),            value: 'mono' },
        { label: __('Dimmed', 'trust-bar'),                value: 'dimmed' },
      ];

      var carouselOptions = [
        { label: __('— Use group setting —', 'trust-bar'), value: '' },
        { label: __('Enabled', 'trust-bar'),               value: '1' },
        { label: __('Disabled', 'trust-bar'),              value: '0' },
      ];

      var rowOptions = [
        { label: __('— Use group setting —', 'trust-bar'), value: 0 },
      ];
      for (var r = 1; r <= 5; r++) {
        rowOptions.push({ label: String(r) + ' row' + (r > 1 ? 's' : ''), value: r });
      }

      return [
        el(InspectorControls, { key: 'inspector' },
          el(PanelBody, { title: __('Trust Bar Settings', 'trust-bar'), initialOpen: true },

            el(SelectControl, {
              label:    __('Group', 'trust-bar'),
              value:    attrs.groupId,
              options:  groupOptions,
              onChange: function (v) { setAttr({ groupId: parseInt(v, 10) }); },
            }),

            el(SelectControl, {
              label:    __('Color Mode Override', 'trust-bar'),
              value:    attrs.colorMode,
              options:  colorModeOptions,
              onChange: function (v) { setAttr({ colorMode: v }); },
            }),

            el(SelectControl, {
              label:    __('Rows Override', 'trust-bar'),
              value:    attrs.rows,
              options:  rowOptions,
              onChange: function (v) { setAttr({ rows: parseInt(v, 10) }); },
            }),

            el(SelectControl, {
              label:    __('Carousel Override', 'trust-bar'),
              value:    attrs.carousel,
              options:  carouselOptions,
              onChange: function (v) { setAttr({ carousel: v }); },
            }),

            el(TextControl, {
              label:    __('Extra CSS Class', 'trust-bar'),
              value:    attrs.extraClass,
              onChange: function (v) { setAttr({ extraClass: v }); },
            })
          )
        ),

        el(ServerSideRender, {
          key:   'ssr',
          block: 'trust-bar/trust-bar',
          attributes: attrs,
        })
      ];
    },

    save: function () {
      // Dynamic block; rendered server-side
      return null;
    },
  });

})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
