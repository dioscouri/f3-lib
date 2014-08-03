<!DOCTYPE html>
<head>
    <base href="<?php echo $SCHEME . "://" . $HOST . $BASE . "/"; ?>" />
</head>
<body class="system-theme <?php echo !empty($body_class) ? $body_class : null; ?>">
    <tmpl type="view" /> 
</body>
</html>