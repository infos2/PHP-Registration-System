<?php
$message = "";
$msg = preg_replace('#[^a-z 0-9.:_()]#i', '', $_GET['msg']);
if($msg == "activation_failure"){
	$message = '<h2>Activation Error</h2> Sorry there seems to have been an issue activating your account at this time. We have already notified ourselves of this issue and we will contact you via email when we have identified the issue.';
} else if($msg == "activation_success"){
	$message = '<h2>Activation Success</h2> Your account is now activated. <a href="index.php">Click here to log in</a><p><b>This page will redirect in <span id="counter">10</span> second(s).</b></p>';
} else {
	$message = $msg;
}
?>
<div><?php echo $message; ?></div>

<script  type="text/javascript">
	function countdown() {
		var i = document.getElementById('counter');
			if (parseInt(i.innerHTML)<=0) {
				location.href = 'index.php';
			}
			i.innerHTML = parseInt(i.innerHTML)-1;
		}
		setInterval(function(){ countdown(); },1000);
</script>