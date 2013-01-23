<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo $title_for_layout; ?></title>
		<link rel="stylesheet" href="<?php echo Configure::read('static_assets_host'); ?>/css/main.css?3"/>
		<!--[if lt IE 9]>
		<script>
			document.createElement('header');
			document.createElement('nav');
			document.createElement('section');
			document.createElement('article');
			document.createElement('aside');
			document.createElement('footer');
			document.createElement('hgroup');
		</script>
		<![endif]-->
		<script src="<?php echo Configure::read('static_assets_host'); ?>/js/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="<?php echo Configure::read('static_assets_host'); ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script src="<?php echo Configure::read('static_assets_host'); ?>/js/jquery-history.js" type="text/javascript"></script>
		<script src="<?php echo Configure::read('static_assets_host'); ?>/js/swfobject.js" type="text/javascript"></script>
		<script src="<?php echo Configure::read('static_assets_host'); ?>/js/main.js?1" type="text/javascript"></script>
	<?php
		echo $scripts_for_layout;
	?>
	</head>
	<body>
		<div class="container_24">
			<div class="navigation">
				<?php echo $this->element('navigation_summary', array('navigation_summary' => $navigation_summary)); ?>
			</div>
			<div class="main">
				<?php echo $content_for_layout; ?>
			</div>
		</div>
	</body>
</html>
