(function ($) {
	
	// include jush.js here
	
	$.jush = jush;
	
	/** Highlight element content
	* @param [string]
	* @return jQuery
	* @this jQuery
	*/
	$.fn.jush = function (language) {
		return this.each(function () {
			var lang = language;
			var $this = $(this);
			if (!lang) {
				var match = /(^|\s)(?:jush-|language-)(\S+)/.exec($this.attr('class'));
				lang = (match ? match[2] : 'htm');
			}
			$this.html(jush.highlight(lang, $this.text()));
		});
	}
	
})(jQuery);
