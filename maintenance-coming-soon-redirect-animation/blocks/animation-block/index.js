(function (wp) {
	'use strict';

	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var __ = wp.i18n.__;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;

	var data = window.wploti_animation_block || { animations: [], animationsUrl: '' };

	var animationOptions = data.animations.map(function (file) {
		return { label: file, value: file };
	});

	function AnimationPreview(props) {
		var playerRef = useRef();

		useEffect(function () {
			var player = playerRef.current;

			if (player && player.load) {
				player.load(props.src);
			}
		}, [props.src]);

		return el('lottie-player', {
			ref: playerRef,
			src: props.src,
			autoplay: props.autoplay ? 'true' : undefined,
			loop: props.loop ? true : undefined,
			style: { width: props.width, height: props.height },
		});
	}

	registerBlockType('wploti/animation', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var previewSrc = data.animationsUrl + attributes.animation;

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Animation Settings', 'maintenance-coming-soon-redirect-animation') },
						el(SelectControl, {
							label: __('Animation', 'maintenance-coming-soon-redirect-animation'),
							value: attributes.animation,
							options: animationOptions,
							onChange: function (value) {
								setAttributes({ animation: value });
							},
						}),
						el(ToggleControl, {
							label: __('Autoplay', 'maintenance-coming-soon-redirect-animation'),
							checked: attributes.autoplay,
							onChange: function (value) {
								setAttributes({ autoplay: value });
							},
						}),
						el(ToggleControl, {
							label: __('Loop', 'maintenance-coming-soon-redirect-animation'),
							checked: attributes.loop,
							onChange: function (value) {
								setAttributes({ loop: value });
							},
						}),
						el(TextControl, {
							label: __('Width', 'maintenance-coming-soon-redirect-animation'),
							value: attributes.width,
							onChange: function (value) {
								setAttributes({ width: value });
							},
						}),
						el(TextControl, {
							label: __('Height', 'maintenance-coming-soon-redirect-animation'),
							value: attributes.height,
							onChange: function (value) {
								setAttributes({ height: value });
							},
						})
					)
				),
				el(AnimationPreview, {
					src: previewSrc,
					autoplay: attributes.autoplay,
					loop: attributes.loop,
					width: attributes.width,
					height: attributes.height,
				})
			);
		},
		save: function () {
			// Rendered dynamically via render_callback in PHP.
			return null;
		},
	});
})(window.wp);
