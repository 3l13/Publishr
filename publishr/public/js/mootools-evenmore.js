var Dataset = new Class
({
	getDataset: function(el)
	{
		el = $(el);

		var attributes = el.attributes;
		var dataset = {};

		for (var i = 0, y = attributes.length ; i < y ; i++)
		{
			var attr = attributes[i];

			if (!attr.name.match(/^data-/))
			{
				continue;
			}

			var name = attr.name.substring(5).camelCase();

			dataset[name] = attr.value;
		}

		return dataset;
	}
});