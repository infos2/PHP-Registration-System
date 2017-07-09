<?php
session_start();
// If user is logged in, header them away
if(isset($_SESSION["username"])){
	header("location: message.php?msg=User is already registered. Please logout to register another account.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Successful Registration</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  </head>

  <body>

<!--breadcrumbs start-->
<div class="breadcrumbs">
	<div class="container">
		<div class="row">
			<div class="col-lg-4 col-sm-4">
				<h1>Registration Successful!</h1>
			</div>
		</div>
	</div>
</div>
<!--breadcrumbs end-->
	
<div class="container">
	<h3>Welcome to Example.com!</h3>
	<p>You have been sent a welcome email with a link to login your account. </p>	
	<p>Thanks for joining us!</p>
	<p>Sincerely,</p>
	<p>Example.com Team</p><br />
	<p><b>This page will redirect in <span id="counter">15</span> second(s).</b></p>
	<br /><br />
</div>
    <!--container end-->
<script type="text/javascript">
	function countdown() {
		var i = document.getElementById('counter');
			if (parseInt(i.innerHTML)<=0) {
				location.href = 'home_page.php';
			}
		i.innerHTML = parseInt(i.innerHTML)-1;
	}
	setInterval(function(){ countdown(); },1000);
	</script>
  </body>
</html>