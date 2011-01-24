/**
 * The "elementsready" event is sent for elements to be initialized, to become alive thanks to the
 * magic of Javascript. This event is usually fired when new elements are added to the DOM.
 */

window.addEvent
(
	'domready', function()
	{
		document.fireEvent('elementsready', { target: $(document.body) });
	}
);