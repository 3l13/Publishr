window.addEvent
(
	'domready', function()
	{
		return;
		
		function layout()
		{
			$$('div.view-selector').each
			(
				function(selector)
				{
					var coords = selector.getCoordinates();
					
					//console.log('selector width: %d', coords.width);
					
					var w = Math.floor(coords.width / 4);
					
					selector.getElements('ul').each
					(
						function(el)
						{
							var parent = el.getParent();
							
							el.setStyles
							({
								position: parent == selector ? '' : 'absolute',
								left: parent == selector ? 0 : el.getCoordinates(parent).right,
								top: 0,
								width: w
							});
						}
					);
				}
			);
		}
		
		layout();
		
		
		function closeFocus(ul)
		{
			ul.getElements('li.focus').each
			(
				function(el)
				{
					el.toggleClass('focus');
					
					var sub = el.getElement('ul');
					
					if (sub)
					{
						closeFocus(sub);
					}
				}
			);
		}
		
		$$('div.view-selector').each
		(
			function(selector)
			{
				var selectorCoords = selector.getCoordinates();
				
				function toggleNode(el)
				{
					var sub = el.getElement('ul');
					
					if (!sub)
					{
						return;
					}
					
					var parent = el.getParent();
					var coords = parent.getCoordinates(selector);
					
					//console.log('sub: %a, coords: %a (right: %d, parent: %a)', sub, coords, coords.right, parent);

					//closeFocus(selector);
					
					el.toggleClass('focus');
					
					sub.setStyles
					({
						top: 0,
						left: coords.width + 1,
						position: 'absolute',
						width: 290
					});
				}
				
				
				
				
				
				selector.getElements('ul.categories > li').each
				(
					function(category)
					{
						//console.log('category: ', category);
						
						category.addEvent
						(
							'click', function(ev)
							{
								if (ev.target != category)
								{
									return;
								}
								
								ev.stop();
								
								toggleNode(category);
							}
						);
					}
				);
				
				selector.getElements('ul.modules > li').each
				(
					function(module)
					{
						module.addEvent
						(
							'click', function(ev)
							{
								if (ev.target != module)
								{
									return;
								}
								
								ev.stop();
								
								toggleNode(module);
							}
						);
					}
				);
				
				selector.getElements('ul.types > li').each
				(
					function(type)
					{
						type.addEvent
						(
							'click', function(ev)
							{
								if (ev.target != type)
								{
									return;
								}
								
								ev.stop();
								
								toggleNode(type);
							}
						);
					}
				);
			}
		);
	}
);