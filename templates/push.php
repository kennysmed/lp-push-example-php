<!DOCTYPE html>
<html>
<head>
	<title>Push button</title>
</head>
<body>

	<?php if ($pushed) { %>
		<p><?php echo $subscribed_count; ?> subscriber(s) greeted. <?php echo $unsubscribed_count; ?> unsubscribed.</p>
	<<?php } ?>>

	<form method=POST>
		<p>Click to send a greeting to all subscribed Little Printers: <input type="submit" value="Say hello"></p>
	</form>

</body>
</html>
