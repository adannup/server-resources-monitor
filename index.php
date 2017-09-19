<?php 
	include('controller.php');
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title></title>
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<div class="container">
		<div class="card">
			<ul>
				<?php	foreach ($monitor->results as $key => $value): ?>
			    	<li><?php echo $key; ?><small><?php echo $value; ?></small></li>
				<?php	endforeach	?>
			</ul>
		</div>

		<div class="graphics">
			<?php	foreach ($monitor->graphics as $key => $value): ?>
				<?php 
					if(isset($value['level'])){
						if($value['level'] == 'LOW'){
							$color = '#2196F3';
						}else{

						}
					}else{
						$color = '#212121';
					}
				?>
				<div class="bar" style="<?php echo 'height:'.$value['value'].'px; background:'.$color.';'; ?> "><p><?php echo $key; ?></p></div>
			<?php	endforeach	?>
		</div>
	</div>
</body>
</html>