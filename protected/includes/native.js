String.extend
({
	/*
	Property: left
		Returns a sub-string of the specified length from the left of the passed string. If the sub-string is shorter than the specified length, the sub-string is padded to the right with the filling character which is a blank space by default.
	
	Arguments:
		length - the length of the resulting string
		pad - optional, if the string is smaller than requested length, the result is padded to the right with the 'pad' character

	Returns:
		A string of 'length' length.

	Example:
		> '123456'.left(3) 		// -> 123
		> '123456'.left(8, '+') // -> 123456++
	*/

	left: function(length, pad)
	{
		var pad = $defined(pad) ? pad : ' ';
		
		if (this.length > length)
		{
			return this.substr(0, length);
		}
		else if (this.length < length)
		{
			var str = this;
			var d = length - this.length;
			
			while (d--)
			{
				str += pad;
			}
			
			return str;
		}

		return this;
	},

	/*
	Property: right
		Returns a sub-string of the specified length from the right of the passed string. If the sub-string is shorter than the specified length, the sub-string is padded to the left with the filling character which is a blank space by default.

	Arguments:
		length - the length of the resulting string
		pad - optional, if the string is smaller than requested length, the result is padded to the left with the 'pad' character

	Returns:
		A string of 'length' length.

	Example:
		> '123456'.left(3) 		// -> 456
		> '123456'.left(8, '+') // -> ++123456
	*/

	right: function(length, pad)
	{
		var pad = $defined(pad) ? pad : ' ';
		
		if (this.length > length)
		{
			return this.substr(this.length - length, length);
		}
		else if (this.length < length)
		{
			var str = this;
			var d = length - this.length;
			
			while (d--)
			{
				str = pad + str;
			}
			
			return str;
		}

		return this;
	},
	
	/*
	Property: center
		Center the passed string in a string of the specified length. If the string is longer the the specified length, the string is padded with the filling character, which by default is a blank space.

	Arguments:
		length - the length of the resulting string
		pad - optional, if the string is smaller than requested length, the result is padded to the left and the right with the 'pad' character

	Returns:
		A string of 'length' length.

	Example:
		> 'abc'.center(6) // -> ' abc  '
		> 'abc'.center(6, '+') // -> '+abc++'
		> 'abc'.center(4, '+') // -> 'abc+'
		> '123456'.(3) // '234'
	*/

	center: function(length, pad)
	{
		pad = $defined(pad) ? pad : ' ';
		
		if (this.length > length)
		{
			return this.substr((this.length - length) / 2, length);
		}
		else if (this.length < length)
		{
			var str = this;
			
			var l = ((length - this.length) / 2).toFixed();
			
			while (--l)
			{
				str = pad + str;
			}
			
			while (str.length < length)
			{
				str += pad;
			}
			
			return str;
		}

		return this;
	},

	/*
	Property: copies
		Returns a string made of copies of the passed string. The number of copies might be zero, in which case an empty string is returned.

	Arguments:
		number - number of copies

	Returns:
		A copied string.

	Example:
		> 'abc'.copies(6) // -> 'abcabcabcabc'
		> 'abc'.copies(0) // -> ''
	*/

	copies: function(copies)
	{
		var str = '';
		
		while (copies--)
		{
			str += this;
		}
		
		return str;
	}
});

Number.extend
({
			  
	left: function(length, pad)
	{
		pad = $defined(pad) ? pad : '0';
		
		return (this.toString()).left(length, pad);
	},
	
	right: function(length, pad)
	{
		pad = $defined(pad) ? pad : '0';

		return (this.toString()).right(length, pad);
	}
});