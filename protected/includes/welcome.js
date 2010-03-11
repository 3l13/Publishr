window.addEvent('domready', function()
{
	new Sortables
	(
		$('config-tabs')/*,
		
		{
	 
	 	
		initialize: function(){
			var step = 0;
			this.elements.each(function(element, i){
				var color = [step, 82, 87].hsbToRgb();
				element.setStyle('background-color', color);
				step = step + 35;
				element.setStyle('height', $random(40, 100));
			});
		}
	 
	}*/);

	//
	// admin tabs
	//
	
	/*
	admins = $$('#tabs-drag a');
	
	//console.info('admins: %a', admins);
	
	admins.each
	(
	 	function(drag)
		{
			new Drag.Move(drag, { droppables: $('tabs-drop') });
		}
	);
	*/
	
	var accordion = new Accordion
	(
	 	'#welcome h2',
		'#welcome .fold',
		
		{
			duration: 250,

			opacity: true,
			
			onActive: function(toggler, element)
			{
				toggler.removeClass('folded');
			},
			
			onBackground: function(toggler, element)
			{
				toggler.addClass('folded');
			}
		}
	);
});