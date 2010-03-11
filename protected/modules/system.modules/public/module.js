/*
window.addEvent('domready', function()
{
	//
	// theme selects
	//
	
	var selects = $$('table.resume select');
	
	if (selects)
	{
		selects.each
		(
			function(el)
			{
				new Wd.Elements.Select(el);
			}
		);
	}

	//
	// theme checkboxes
	//
	
	var checkboxes = $$('table.resume input[type="checkbox"]');
	
	checkboxes.each
	(
		function(box)
		{
			new Wd.Elements.Checkbox(box);
		}
	);
});
*/