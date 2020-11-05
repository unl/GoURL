<?php
require_once __DIR__ . '/../../config.inc.php';
$institutionPart = !empty(goController::$institution) ? ' | ' . goController::$institution : '';
$appName = !empty(goController::$appName) ? goController::$appName : 'short url';
?>
<!doctype html>
<html lang="en">
<head>
	<title>Page Not Found<?php echo $institutionPart; ?></title>
</head>
<body>
	<h1>Something's wrong here&hellip;</h1>
	<p>Uh no, we could not find a link for the <?php echo $appName; ?> you clicked.</p>
</body>
</html>
