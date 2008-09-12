$.server({
	url: 'http://wikipedia.org',
	data: {param1: 'foo'},
	})
// $.serverGet()
// $.serverPost()
	.find('.class')
		.appendTo('.other-place').end()
	.find('.other-place')
//		.client(true)	// return full document 
//		.client('json') .client('string') .client('string', true)
		.client(function(response){	// return actuall stack as DOM
			response.appendTo('.my-target');
		})