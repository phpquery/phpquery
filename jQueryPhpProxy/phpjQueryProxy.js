jQuery.extend({
	phpQueryCfg: {
		// URL to phpjQueryProxy.php
		url: 'http://localhost/projekty/phpQuery/phpjQueryProxy/phpjQueryProxy.php',
		// asynchronous communication
		async: false
	},
	phpQuery: function(url){
		// this is cache object
		var objectCache = {};
		// dump all jQuery methods, but only once
		// $.each doesn't work ?
		for( var i in jQuery.fn) {
			// closure to preserve loop iterator in scope
			(function(){
				var name = i;
				// create dummy method
				objectCache[name] = function(){
					// create method data object
					var data = {
						method: name,
						arguments: []
					};
					// collect arguments
					$.each(arguments, function(k, v){
						data.arguments.push(v);
					});
					// push data into stack
					this.stack.push(data);
					// preserve chain
					return this;
				}
			})();
		}
		/**
		 * Fetches results from phpQuery.
		 * 
		 * @param {Function} callback	Optional. Turns on async request.
		 * First parameter for callback is usually an JSON array of mathed elements. Use $(result) to append it to DOM.
		 * It can also be a boolean value or string, depending on last method called.
		 */
		objectCache.get = function(callback){
//				console.log(this.stack.toSource());
			if ( typeof callback == 'function' )
				$.post(
					jQuery.phpQueryCfg.url,
					{data: this.stack.toSource()},
					callback
				);
			else {
				var result;
				$.ajax({
					type: 'POST',
					data: {data: this.stack.toSource()},
					async: false,
					url: jQuery.phpQueryCfg.url,
					success: function(response){
						result = response;
					}
				})
				return result;
			}
		}
		// replace method with new one, which only uses cache
		jQuery.phpQuery = function(url){
			// clone cache object
			var myCache = jQuery.extend({}, objectCache);
			myCache.stack = [url];
			return myCache;
		}
		return jQuery.phpQuery(url);
	}
});
$.phpQuery('http://meta20.net')
	.find('h3')
	.eq(3)
	.get(function(result){
		console.log( $(result) );
	});