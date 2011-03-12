The Widget API
##############

The Widget API that comes with the CMS [Publishr](http://www.wdpublisher.com) provides features to
create widgets on the server side, make them come to life on the client side, fetch widgets from
remote locations and append them to the document while taking care of the required CSS and
Javascript assets.

A [widget](http://en.wikipedia.org/wiki/GUI_widget) is a reusable element of a graphical user
interface that displays an information arrangement and provides standardized data manipulation. In
web applications, widgets are usually composed of multiple HTML elements that _come to life_
with the magic of Javascript.


Supporting features
===================

The Widget API is an ensemble of techniques, guidelines and conventions, mostly from the
[WdCore](https://github.com/Weirdog/WdCore) framework (the RESTful API used to fetch widgets), the
[WdElements](https://github.Weirdog/WdElements) framework (used as the base class to create
widgets, or compose them) and the [MooTools](http://mootools.net) framework.


The elementsready event
-----------------------

We usualy turn HTML elements into widgets when the `domready` event is fired, but because we often
load widgets *after* the event has been fired (e.g. pop adjust widgets), we need another event
that can be fired at various times during the lifetime of the document.

The custom event `elementsready` is fired on the document object when new widgets are added
to the document, and they need to come to life. Because the event can be fired multiple times
during the lifetime of the document, one should use the [`store()`](http://mootools.net/docs/core/Element/Element#Element:store)
and [`retrieve()`](http://mootools.net/docs/core/Element/Element#Element:retrieve) methods to check
if an element has already been made a widget.

The `elementsready` event is first fired when the `domready` event is fired.


Auto-constructors
-----------------

By matching the Javascript constructor to a CSS class, the Widget API is capable of turning HTML
elements into splendid widgets all by itself. When the `elementsready` event is fired on the
document object, the Widget API traverses all the constructors defined in the `Widget` variable,
and for each one of them map their name to a CSS class and create widgets for the matching
elements.

As an example, the `Widget.AdjustNode` constructor is mapped to the ".widget-adjust-node" css
class, and all matching elements are turned into widgets using the constructor. Thus, all we need
to do for the magic to happen is to add widget constructors to the `Widget` variable, which also
serves as a namespace:

	Widget.AdjustNode = new Class({...});


The Request.Element Javascript class
------------------------------------

Elements are usually loaded using a `Request.Element` instance, which extends the `Request.JSON`
class to support the loading of single HTML elements. The class overrides the `onSuccess` method
to create a DOM element from the result of the response and update the document with its assets.

Here are the modified arguments of the `onSuccess` event:

1. `element` (object) - The DOM element created from the HTML result of the remote request.
2. `response` (object) - The JSON decoded response to the request.
3. `text` (string) - The raw response to the request.


The Request.Widget Javascript class
-----------------------------------

Widgets are usually loaded using a `Request.Widget` instance, which extends the `Request.Element`
and provides a simpler mean to load widgets:

	function callback(el, response)
	{
		console.log('loaded element: %a, response: %a', el, response);
	}

	new Request.Widget('adjust-thumbnail', callback).get({ selected: 190 });


The /api/widgets/:class RESTful route
-------------------------------------

Widgets can also be requested using the "`/api/widgets/:class`" API route. The route triggers an
operation that creates the requested widget and returns its HTML code along with its required CSS
and Javascript assets.

As an example, the "`/api/widgets/adjust-thumbail`" route returns the HTML representation of an
instance of the `WdAdjustThumbnailWidget` class, along with its assets.

Additional routes are available to return parts of the widget, its results set for example, or a
popup version of the widget:

	/api/widgets/adjust-thumbnail/results?selected=190&page=2&search=keywords
	/api/widgets/adjust-thumbnail/popup?selected=190


The Document.updateAssets method
--------------------------------

Because we often load widgets after the document was created or rendered, the document needs to be
updated with the CSS and Javascript files required by the loaded widgets. The
`Document.updateAssets()` method updates the document with the required CSS and Javascript
files. A callback function is called when the files have been loaded:

	function assetsLoaded()
	{
		alert('Assets have been loaded !');
	};

	Document.updateAssets
	(
		{
			css: [ '/public/widget.css' ],
			js: [ '/public/widget.js' ]
		},
		
		assetsLoaded
	);
	
	

Creating a widget class
=======================

A widget is usually an instance of the `Widget` class. There are many classes available, most of
them come with a module and are often used to edit a data type of their module. As an example,
the PopImage widget is used to select an image from the "resources.images" module. One can create
such a widget with the following code:

	$widget = new WdPopImageWidget
	(
		array
		(
			WdForm::T_LABEL => 'Illustration image',
			
			'name' => 'imageid' 
		)
	);


Required assets
---------------
	
When the widget is created it adds its required assets to the global document object:

	$document->css->add('public/pop-image.css');
	$document->js->add('public/pop-image.js');
	
One can retrieve the assets using the `get_assets()` method:

	$assets = document->get_assets();



Loading widgets using the RESTful API
=====================================

Widgets are often loaded through the RESTful API of the [WdCore](https://github.com/Weirdog/WdCore)
framework. For example, the `AdjustImage` widget:

	var widget;

	new Request.Widget
	(
		'adjust-image', function(el, response)
		{
			widget = el;

			document.appendChild(el);
		}
	)
	.get({selected: 190});

For adjust elements, the '/results' route can be used to update the widget results:

	Request.Element
	({
		url: '/api/widgets/adjust-image/results',
		
		onSuccess: function(el, response)
		{
			el.replaces(widget.getElement('.results'));
		}
	})
	.get({selected: 190, page: 2, search: "core"})

On the server side, the widget type "`adjust-image`" is translated to the PHP class name
WdAdjustImageWidget. If the widget HTML code is requested, the string version of the instance
is returned as the result of the operation. In addition to the result, the `assets` property of
the response might contain the CSS and Javascript files that are required for the widget to
operate.