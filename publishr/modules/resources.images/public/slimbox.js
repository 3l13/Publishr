/*!
	Slimbox v1.7 - The ultimate lightweight Lightbox clone
	(c) 2007-2009 Christophe Beyls <http://www.digitalia.be>
	MIT-style license.
*/

var Slimbox = (function() {

	// Global variables, accessible to Slimbox only
	var win = window, ie6 = Browser.ie6, options, images, activeImage = -1, activeURL, prevImage, nextImage, compatibleOverlay, middle, centerWidth, centerHeight;

	// Preload images
	var preload = {}, preloadPrev = new Image(), preloadNext = new Image();

	// DOM elements
	var overlay, center, image, sizer, prevLink, nextLink, bottomContainer, bottom, caption, number;

	// Effects
	var fxOverlay, fxResize, fxImage, fxBottom;

	/*
		Initialization
	*/

	window.addEvent
	(
		'domready', function()
		{
			// Append the Slimbox HTML code at the bottom of the document
			$(document.body).adopt
			(
				$$
				(
					overlay = new Element("div", {id: "lbOverlay", events: {click: close}}),
					center = new Element("div", {id: "lbCenter"}),
					bottomContainer = new Element("div", {id: "lbBottomContainer"})
				)
				.setStyle("display", "none")
			);

			image = new Element("div", {id: "lbImage"}).inject(center).adopt
			(
				sizer = new Element("div", {styles: {position: "relative"}}).adopt
				(
					prevLink = new Element("a", {id: "lbPrevLink", href: "#", events: {click: previous}}),
					nextLink = new Element("a", {id: "lbNextLink", href: "#", events: {click: next}})
				)
			);

			bottom = new Element("div", {id: "lbBottom"}).inject(bottomContainer).adopt
			(
				new Element("a", {id: "lbCloseLink", href: "#", events: {click: close}}),
				caption = new Element("div", {id: "lbCaption"}),
				number = new Element("div", {id: "lbNumber"}),
				new Element("div", {styles: {clear: "both"}})
			);
		}
	);


	/*
		Internal functions
	*/

	function position() {
		var scroll = win.getScroll(), size = win.getSize();
		$$(center, bottomContainer).setStyle("left", scroll.x + (size.x / 2));
		if (compatibleOverlay) overlay.setStyles({left: scroll.x, top: scroll.y, width: size.x, height: size.y});
	}

	function setup(open) {
		["object", ie6 ? "select" : "embed"].forEach(function(tag) {
			Array.forEach(document.getElementsByTagName(tag), function(el) {
				if (open) el._slimbox = el.style.visibility;
				el.style.visibility = open ? "hidden" : el._slimbox;
			});
		});

		overlay.style.display = open ? "" : "none";

		var fn = open ? "addEvent" : "removeEvent";
		win[fn]("scroll", position)[fn]("resize", position);
		document[fn]("keydown", keyDown);
	}

	function keyDown(event) {
		var code = event.code;
		// Prevent default keyboard action (like navigating inside the page)
		return options.closeKeys.contains(code) ? close()
			: options.nextKeys.contains(code) ? next()
			: options.previousKeys.contains(code) ? previous()
			: false;
	}

	function previous() {
		return changeImage(prevImage);
	}

	function next() {
		return changeImage(nextImage);
	}

	function changeImage(imageIndex)
	{
		if (imageIndex >= 0)
		{
			activeImage = imageIndex;
			activeURL = images[imageIndex][0];
			prevImage = (activeImage || (options.loop ? images.length : 0)) - 1;
			nextImage = ((activeImage + 1) % images.length) || (options.loop ? 0 : -1);

			stop();
			center.className = "lbLoading";

			if (1)
			{
				var base = document.location.protocol + '//' + document.location.host;

				activeURL = activeURL.substring(base.length);

				//console.log('activeurl: %s', activeURL);

				var size = $(document.body).getSize();
				var maxw = size.x - 150;
				var maxh = size.y - 200;

				if (!maxw || maxw == 'NaN')
				{
					maxw = 800;
				}

				if (!maxh || maxh == 'NaN')
				{
					maxh = 600;
				}

				activeURL = '/api/thumbnailer/get?src=' + encodeURI(activeURL) + '&w=' + maxw + '&h=' + maxh + '&method=constrained&no-upscale=1&quality=90';
			}

			preload = new Image();
			preload.onload = animateBox;
			preload.src = activeURL;
		}

		return false;
	}

	function animateBox()
	{
		center.className = "";
		fxImage.set(0);
		image.setStyles({backgroundImage: "url(" + activeURL + ")", display: ""});
		sizer.setStyle("width", preload.width);
		$$(sizer, prevLink, nextLink).setStyle("height", preload.height);

		caption.set("html", images[activeImage][1] || "");
		number.set("html", (((images.length > 1) && options.counterText) || "").replace(/{x}/, activeImage + 1).replace(/{y}/, images.length));

		if (prevImage >= 0) preloadPrev.src = images[prevImage][0];
		if (nextImage >= 0) preloadNext.src = images[nextImage][0];

		var centerWidth = image.offsetWidth;
		var centerHeight = image.offsetHeight;
		var top = Math.max(0, middle - (centerHeight / 2)), check = 0;

		if (center.offsetHeight != centerHeight)
		{
			check = fxResize.start({height: centerHeight, top: top});
		}

		if (center.offsetWidth != centerWidth)
		{
			check = fxResize.start({width: centerWidth, marginLeft: -centerWidth/2});
		}

		function fn()
		{
			bottomContainer.setStyles
			({
				width: centerWidth,
				top: top + centerHeight,
				marginLeft: -centerWidth/2,
				visibility: "hidden",
				display: ""
			});

			fxImage.start(1);
		}

		check ? fxResize.chain(fn) : fn();
	}

	function animateCaption() {
		if (prevImage >= 0) prevLink.style.display = "";
		if (nextImage >= 0) nextLink.style.display = "";
		fxBottom.set(-bottom.offsetHeight).start(0);
		bottomContainer.style.visibility = "";
	}

	function stop() {
		preload.onload = function() {};
		preload.src = preloadPrev.src = preloadNext.src = activeURL;
		fxResize.cancel();
		fxImage.cancel();
		fxBottom.cancel();
		$$(prevLink, nextLink, image, bottomContainer).setStyle("display", "none");
	}

	function close() {
		if (activeImage >= 0) {
			stop();
			activeImage = prevImage = nextImage = -1;
			center.style.display = "none";
			fxOverlay.cancel().chain(setup).start(0);
		}

		return false;
	}


	/*
		API
	*/

	Element.implement({
		slimbox: function(_options, linkMapper) {
			// The processing of a single element is similar to the processing of a collection with a single element
			$$(this).slimbox(_options, linkMapper);

			return this;
		}
	});

	Elements.implement
	({
		/*
			options:	Optional options object, see Slimbox.open()
			linkMapper:	Optional function taking a link DOM element and an index as arguments and returning an array containing 2 elements:
					the image URL and the image caption (may contain HTML)
			linksFilter:	Optional function taking a link DOM element and an index as arguments and returning true if the element is part of
					the image collection that will be shown on click, false if not. "this" refers to the element that was clicked.
					This function must always return true when the DOM element argument is "this".
		*/

		slimbox: function(_options, linkMapper, linksFilter)
		{
			linkMapper = linkMapper || function(el) {
				return [el.href, el.title];
			};

			linksFilter = linksFilter || function() {
				return true;
			};

			var links = this;

			links.removeEvents("click").addEvent("click", function(ev) {
				ev.stop();
				// Build the list of images that will be displayed
				var filteredLinks = links.filter(linksFilter, this);
				return Slimbox.open(filteredLinks.map(linkMapper), filteredLinks.indexOf(this), _options);
			});

			return links;
		}
	});

	return {
		open: function(_images, startImage, _options)
		{
			options = Object.merge
			(
				{
					loop: false,				// Allows to navigate between first and last images
					overlayOpacity: 0.8,			// 1 is opaque, 0 is completely transparent (change the color in the CSS file)
					overlayFadeDuration: 400,		// Duration of the overlay fade-in and fade-out animations (in milliseconds)
					resizeDuration: 400,			// Duration of each of the box resize animations (in milliseconds)
					resizeTransition: false,		// false uses the mootools default transition
					initialWidth: 250,			// Initial width of the box (in pixels)
					initialHeight: 250,			// Initial height of the box (in pixels)
					imageFadeDuration: 400,			// Duration of the image fade-in animation (in milliseconds)
					captionAnimationDuration: 400,		// Duration of the caption animation (in milliseconds)
					counterText: "{x} / {y}",	// Translate or change as you wish, or set it to false to disable counter text for image groups
					closeKeys: [27, 88, 67],		// Array of keycodes to close Slimbox, default: Esc (27), 'x' (88), 'c' (67)
					previousKeys: [37, 80],			// Array of keycodes to navigate to the previous image, default: Left arrow (37), 'p' (80)
					nextKeys: [39, 78]			// Array of keycodes to navigate to the next image, default: Right arrow (39), 'n' (78)


		           /* weirdog */

		           ,mode: 'image',
		           width: null,
		           height: null

				},

				_options || {}
			);

			if (options.width)
			{
				options.initialWidth = options.width;
			}

			if (options.height)
			{
				options.initialHeight = options.height;
			}

			// Setup effects
			fxOverlay = new Fx.Tween(overlay, {property: "opacity", duration: options.overlayFadeDuration});
			fxResize = new Fx.Morph(center, Object.merge({duration: options.resizeDuration, link: "chain"}, options.resizeTransition ? {transition: options.resizeTransition} : {}));
			fxImage = new Fx.Tween(image, {property: "opacity", duration: options.imageFadeDuration, onComplete: animateCaption});
			fxBottom = new Fx.Tween(bottom, {property: "margin-top", duration: options.captionAnimationDuration});

			// The function is called for a single image, with URL and Title as first two arguments
			if (typeof _images == "string") {
				_images = [[_images, startImage]];
				startImage = 0;
			}

			middle = win.getScrollTop() + (win.getHeight() / 2);
			centerWidth = options.initialWidth;
			centerHeight = options.initialHeight;
			center.setStyles({top: Math.max(0, middle - (centerHeight / 2)), width: centerWidth, height: centerHeight, marginLeft: -centerWidth/2, display: ""});
			compatibleOverlay = ie6 || (overlay.currentStyle && (overlay.currentStyle.position != "fixed"));
			if (compatibleOverlay) overlay.style.position = "absolute";
			fxOverlay.set(0).start(options.overlayOpacity);
			position();
			setup(1);

			images = _images;
			options.loop = options.loop && (images.length > 1);
			return changeImage(startImage);
		}
	};

})();

Slimbox.scanPage = function()
{
	$$('a[rel^=lightbox]').slimbox
	(
		{
			loop: true
		},

		null, function(el)
		{
			return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
		}
	);

	/*
	$$('a[rel^=lightbox]').each
	(
		function(el)
		{
			var optionsPairs = el.rel.split(';');
			var mode = optionsPairs.shift();
			var options = {};

			for (var i=0 ; i < optionsPairs.length ; i++)
			{
				var pair = optionsPairs[i].split('=');

				options[pair[0]] = parseInt(pair[1]);
			}

			if (mode == 'lightbox[Mixed]')
			{
				options.mode = 'mixed';

				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						var w = options.width;
						var h = options.height;

						var overlay = new Element
						(
							'div',
							{
								styles:
								{
									position: 'fixed',
									top: 0,
									bottom: 0,
									left: 0,
									right: 0,
									'background-color': 'black'
								}
							}
						);

						var box = new Element
						(
							'iframe',
							{
								src: el.href,
								frameborder: 0,
								marginheight: 0,
								marginwidth: 0,
								styles:
								{
									position: 'fixed',
									top: '50%',
									left: '50%',
									width: w,
									height: h,
									'margin-left': -w / 2,
									'margin-top': -h / 2
								}
							}
						);

						overlay.get('tween', { property: 'opacity', duration: 'short' }).set('opacity', 0);;
						box.get('tween', { property: 'opacity', duration: 'long' }).set('opacity', 0);

						overlay.inject(document.body);
						box.inject(document.body);

						overlay.get('tween').start(.8);
						box.get('tween').start(1);

						overlay.addEvent
						(
							'click', function()
							{
								box.destroy();
								overlay.destroy();
							}
						);
					}
				);
			}
			else
			{
				el.slimbox
				(
					options, null, function(el)
					{
						return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
					}
				);
			}
		}
	);
	*/
};

window.addEvent('domready', Slimbox.scanPage);