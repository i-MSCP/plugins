(function () {
	jush.style('jush.css');
	var source = document.getElementById('source');
	var value = '';
	if (!source.value && location.hash) {
		source.value = location.hash.substr(1);
	}
	source.onkeyup = function highlight() {
		if (value == source.value) {
			return;
		}
		value = source.value;
		var result = document.getElementById('result');
		var language = source.form['language'].value;
		result.className = 'jush-' + language;
		result.innerHTML = jush.highlight(language, source.value);
	};
	source.onchange = source.onkeyup;
	source.form['language'].onchange = function () {
		value = '';
		source.onkeyup();
	}
	source.onkeyup();
})();
