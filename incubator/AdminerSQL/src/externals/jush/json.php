<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>JSON</title>
<body>

<form action="" method="post">
<p><textarea name="json" rows="10" cols="50"><?php
if (isset($_POST["json"])) {
	echo htmlspecialchars($_POST["json"]);
}
?></textarea>
<p><input type="submit" value="JSON">
</form>

<pre><code class="language-js"><?php
if (isset($_POST["json"])) {
	echo htmlspecialchars(str_replace('\\/', '/', json_encode(json_decode($_POST["json"]), JSON_PRETTY_PRINT)));
}
?></code></pre>

<script type="text/javascript" src="jush.js"></script>
<script type="text/javascript">
jush.style('jush.css');
jush.highlight_tag('code');
</script>
