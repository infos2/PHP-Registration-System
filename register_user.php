<?php
session_start();

// If user is logged in, head them away.
if(isset($_SESSION["username"])){
	header("location: message.php?msg=User is already registered. Please logout to register another account.");
    exit();
}

// Ajax calls the NAME CHECK code to execute
if(isset($_POST["usernamecheck"])){
	include_once("db.php");  //Database connection
	$username = $_POST['usernamecheck'];
	$stmt = $db->stmt_init();
	$sql = "SELECT id FROM users_table WHERE username = ? LIMIT 1";
	if ($stmt->prepare($sql)){
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->fetch();		
		
		if (strlen($username) < 3 || strlen($username) > 16) {
	    echo '<strong style="color:#F00;">3 - 16 characters please</strong>';
	    exit();
		}
		if (is_numeric($username[0])) {
			echo '<strong style="color:#F00;">Usernames must begin with a letter</strong>';
			exit();
		}				
		
		$stmt->close();			
	}
	
	$sql_ucheck = "SELECT id FROM users_table WHERE username = ? LIMIT 1";
	$stmt = $db->stmt_init();
	$stmt = $db->prepare($sql_ucheck);
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->store_result();
	$results = $stmt->num_rows;
    $stmt->close();
	if($results < 1){
			//echo '<strong style="color:#009900;">' . $username . ' is available</strong>';
			exit();
		} else {
			echo '<strong style="color:#F00;">' . $username . ' is not an available User Name</strong>';
			exit();
		}			
}

// Ajax calls this EMAIL CHECK code to execute
if(isset($_POST["emailcheck"])){
	include_once("db.php");  //Database connection
	$email = $_POST['emailcheck'];
	$sql_email = "SELECT id FROM users_table WHERE email = ? LIMIT 1";
	$stmt = $db->stmt_init();
	$stmt = $db->prepare($sql_email);
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$stmt->store_result();
	$results = $stmt->num_rows;
    $stmt->close();
	//echo '<strong style="color:#F00;"># of rows: ' . $results . ' </strong>';
	if($results > 0){
			echo '<strong style="color:#F00;">' . $email . ' is already registered.</strong>';
			exit();
		} else {
			//echo '<strong style="color:#F00;">' . $email . ' is already registered.</strong>';
			exit();
		}	
}
?>
<?php
// Ajax calls this REGISTRATION code to execute
if(isset($_POST["u"])){
	// CONNECT TO THE DATABASE
	include_once("db.php");  //Database connection
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$fn = $_POST['fn'];
	$ln = $_POST['ln'];
	$u = $_POST['u'];
	$e = $_POST['e'];
	$p = $_POST['p'];
	$g = $_POST['g'];
	$c = $_POST['c'];	
	
	
	$uc = preg_match('@[A-Z]@', $p);
	$lc = preg_match('@[a-z]@', $p);
	$nm = preg_match('@[0-9]@', $p);
	
	// GET USER IP ADDRESS
	$ip = getenv('REMOTE_ADDR');
    
	// DUPLICATE DATA CHECKS FOR USERNAME AND EMAIL
	$sql = "SELECT id FROM users_table WHERE username = ? LIMIT 1";
	$stmt = $db->stmt_init();
	if ($stmt->prepare($sql)) {
		$stmt->bind_param("s", $u);
		$stmt->execute();
		$stmt->store_result();
		$u_check = $stmt->num_rows;
		$stmt->close();
	}
	$sql = "SELECT id FROM users_table WHERE email = ? LIMIT 1";
	$stmt = $db->stmt_init();
	if ($stmt->prepare($sql)) {
		$stmt->bind_param("s", $e);
		$stmt->execute();
		$stmt->store_result();
		$e_check = $stmt->num_rows;
		$stmt->close();
	}
	
	// FORM DATA ERROR HANDLING
	if($fn == "" || $ln == "" || $u == "" || $e == "" || $p == "" || $g == "" || $c == ""){
		echo "The form submission is missing values.";
        exit();
	} else if ($u_check > 0){ 
        echo "The username you entered is alreay taken";
        exit();
	} else if ($e_check > 0){ 
        echo "That email address is already in use in the system";
        exit();
	} else if (strlen($u) < 3 || strlen($u) > 16) {
        echo "Username must be between 3 and 16 characters";
        exit(); 
    } else if (is_numeric($u[0])) {
        echo 'Username cannot begin with a number';
        exit();
	} else if (!$uc || !$lc || !$nm){
		echo 'Password requires at least 1 uppercase, 1 lowercase, and 1 number.\nPlease try again.';
		exit();		
    } else {
	// END FORM DATA ERROR HANDLING
	    // Begin Insertion of data into the database
		// Hash the password and apply your own mysterious unique salt
		$p_hash = password_hash($p, PASSWORD_DEFAULT);  //password encryption; security
		
		//Saving default picture based on gender submitted
		//Default avatar in "img" folder on server
		$profile_pic = $row['avatar'];		
		if($g == "f"){
				$profile_pic = "default_woman.jpg";
			} else{
				$profile_pic = "default_man.jpg";
			}
			'<img src="img/'.$profile_pic.'" >';  //avatar image file location
			
		// Add user info into the database table for the main site table
		$sql = "INSERT INTO users_table (firstName, lastName, username, email, password, gender, country, avatar, ip, signup, lastlogin) VALUES(?,?,?,?,?,?,?,?,?,now(),now())";
		$stmt = $db->stmt_init();
		$stmt = $db->prepare($sql);
		$stmt->bind_param("sssssssss", $fn, $ln, $u, $e, $p_hash, $g, $c, $profile_pic, $ip);
		$stmt->execute();
		$stmt->close();
		
		$uid = mysqli_insert_id($db);
					
		// Create directory(folder) to hold each user's files(pics, MP3s, etc.)
		if (!file_exists("user/$u")) {
			mkdir("user/$u", 0755);
		}	
		
		//Assigning default profile picture
		//$image = "default_man.jpg";
		copy('img/'.$profile_pic.'', 'user/'.$u.'/'.$profile_pic.''); //Placing the appropriate default avatar picture into the user's folder for their avatar to load.
		
		// Email the user 
		$to = "$e";							 
		$from = "no_reply@example.com"; 
		$subject = 'Thanks for registering!';
		$message = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Thanks for registering!</title></head><body style="margin:0px; font-family:Tahoma, Geneva, sans-serif;"><div style="padding:40px; font-size:24px;"><a href="http://www.example.com"><img src="your_image.jpg" alt="Your_Website" width="200px" height="50px"></a><br /><br />Thanks for Registering!</div><div style="padding:24px;font-size:17px;">Hello '.$fn.' '.$ln.',<br /><br />Thank you for joining our website!<br/><br/>Here are your account details: <br /><br /><u>Name</u>: '.$fn.' '.$ln.' <br /><u>User Name</u>: '.$u.'<br /><br />Click the link below to login to your account:<br /><br /><a href="http://www.example.com?id='.$uid.'">Login Now</a><br /><br /></div></body></html>';
		$headers = "From: $from\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\n";
		mail($to, $subject, $message, $headers);
		echo "signup_success"; 
		exit();
	}
	exit();
}
?>

<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Developed By M Abdur Rokib Promy">
    <meta name="author" content="cosmic">
    <meta name="keywords" content="Bootstrap 3, Template, Theme, Responsive, Corporate, Business">
	<link rel="shortcut icon" href="Your_Icon.jpg">

    <title>Register | Example.com</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  
	<script src='https://www.google.com/recaptcha/api.js'></script>
	<style>
	form#form-signin{
		max-width: 630px;
	}
	.pull-right {
		float: right!important;
	}
	</style>
	<script src="js/main.js"></script>
	<script src="js/ajax.js"></script>
	
	<script>
		function restrict(elem){
			var tf = _(elem);
			var rx = new RegExp;
			if(elem == "email"){
				rx = /[' "]/gi;
			} else if(elem == "username"){
				rx = /[^a-z_-0-9]/gi;
			}
			tf.value = tf.value.replace(rx, "");
		}
		function emptyElement(x){
			_(x).innerHTML = "";
		}
		function checkusername(){
			var u = _("username").value;
			if(u != ""){
				var ajax = ajaxObj("POST", "register_user.php");
				ajax.onreadystatechange = function() {
					if(ajaxReturn(ajax) == true) {
						_("unamestatus").innerHTML = ajax.responseText;
					}
				}
				ajax.send("usernamecheck="+u);
			}
		}

		function checkemail(){
			var e = _("email").value;
			if(e != ""){
				var ajax = ajaxObj("POST", "register_user.php");
				ajax.onreadystatechange = function() {
					if(ajaxReturn(ajax) == true) {
						_("emailstatus").innerHTML = ajax.responseText;
					}
				}
				ajax.send("emailcheck="+e);
			}
		}
	</script>
</head>
<body>
	<!--container start-->
	<div class="registration-bg">
	<div class="container">
		<form class="form-signin-register wow fadeInUp" name="signupform" id="signupform" onsubmit="return false;">
			<h2 class="form-signin-heading">Register Now</h2>
			<div class="login-wrap">
                <p>Enter personal details</p>
                <input id="firstName" type="text" class="form-control" placeholder="First Name" autofocus>
				<input id="lastName" type="text" class="form-control" placeholder="Last Name">
                <input id="email" onfocus="emptyElement('status')" onblur="checkemail()" onkeyup="restrict('email')" maxlength="88" type="text" class="form-control" placeholder="Email"><span id="emailstatus"></span>
				<select id="gender" onfocus="emptyElement('status')" class="form-control">
					<option value="">Select Gender</option>
					<option value="m">Male</option>
					<option value="f">Female</option>
				</select>
<br />
				<select id="country" onfocus="emptyElement('status')" class="form-control">
					<option value="" label="Select Country" selected="selected">Select Country</option>
					<option value="Afghanistan" label="Afghanistan">Afghanistan</option>
					<option value="Albania" label="Albania">Albania</option>
					<option value="Algeria" label="Algeria">Algeria</option>
					<option value="American Samoa" label="American Samoa">American Samoa</option>
					<option value="Andorra" label="Andorra">Andorra</option>
					<option value="Angola" label="Angola">Angola</option>
					<option value="Anguilla" label="Anguilla">Anguilla</option>
					<option value="Antarctica" label="Antarctica">Antarctica</option>
					<option value="Antigua and Barbuda" label="Antigua and Barbuda">Antigua and Barbuda</option>
					<option value="Argentina" label="Argentina">Argentina</option>
					<option value="Armenia" label="Armenia">Armenia</option>
					<option value="Aruba" label="Aruba">Aruba</option>
					<option value="Australia" label="Australia">Australia</option>
					<option value="Austria" label="Austria">Austria</option>
					<option value="Azerbaijan" label="Azerbaijan">Azerbaijan</option>
					<option value="Bahamas" label="Bahamas">Bahamas</option>
					<option value="Bahrain" label="Bahrain">Bahrain</option>
					<option value="Bangladesh" label="Bangladesh">Bangladesh</option>
					<option value="Barbados" label="Barbados">Barbados</option>
					<option value="Belarus" label="Belarus">Belarus</option>
					<option value="Belgium" label="Belgium">Belgium</option>
					<option value="Belize" label="Belize">Belize</option>
					<option value="Benin" label="Benin">Benin</option>
					<option value="Bermuda" label="Bermuda">Bermuda</option>
					<option value="Bhutan" label="Bhutan">Bhutan</option>
					<option value="Bolivia" label="Bolivia">Bolivia</option>
					<option value="Bosnia and Herzegovina" label="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
					<option value="Botswana" label="Botswana">Botswana</option>
					<option value="Bouvet Island" label="Bouvet Island">Bouvet Island</option>
					<option value="Brazil" label="Brazil">Brazil</option>
					<option value="British Antarctic Territory" label="British Antarctic Territory">British Antarctic Territory</option>
					<option value="British Indian Ocean Territory" label="British Indian Ocean Territory">British Indian Ocean Territory</option>
					<option value="British Virgin Islands" label="British Virgin Islands">British Virgin Islands</option>
					<option value="Brunei" label="Brunei">Brunei</option>
					<option value="Bulgaria" label="Bulgaria">Bulgaria</option>
					<option value="Burkina Faso" label="Burkina Faso">Burkina Faso</option>
					<option value="Burundi" label="Burundi">Burundi</option>
					<option value="Cambodia" label="Cambodia">Cambodia</option>
					<option value="Cameroon" label="Cameroon">Cameroon</option>
					<option value="Canada" label="Canada">Canada</option>
					<option value="Canton and Enderbury Islands" label="Canton and Enderbury Islands">Canton and Enderbury Islands</option>
					<option value="Cape Verde" label="Cape Verde">Cape Verde</option>
					<option value="Cayman Islands" label="Cayman Islands">Cayman Islands</option>
					<option value="Central African Republic" label="Central African Republic">Central African Republic</option>
					<option value="Chad" label="Chad">Chad</option>
					<option value="Chile" label="Chile">Chile</option>
					<option value="China" label="China">China</option>
					<option value="Christmas Island" label="Christmas Island">Christmas Island</option>
					<option value="Cocos [Keeling] Islands" label="Cocos [Keeling] Islands">Cocos [Keeling] Islands</option>
					<option value="Colombia" label="Colombia">Colombia</option>
					<option value="Comoros" label="Comoros">Comoros</option>
					<option value="Congo - Brazzaville" label="Congo - Brazzaville">Congo - Brazzaville</option>
					<option value="Congo - Kinshasa" label="Congo - Kinshasa">Congo - Kinshasa</option>
					<option value="Cook Islands" label="Cook Islands">Cook Islands</option>
					<option value="Costa Rica" label="Costa Rica">Costa Rica</option>
					<option value="Croatia" label="Croatia">Croatia</option>
					<option value="Cuba" label="Cuba">Cuba</option>
					<option value="Cyprus" label="Cyprus">Cyprus</option>
					<option value="Czech Republic" label="Czech Republic">Czech Republic</option>
					<option value="Côte d’Ivoire" label="Côte d’Ivoire">Côte d’Ivoire</option>
					<option value="Denmark" label="Denmark">Denmark</option>
					<option value="Djibouti" label="Djibouti">Djibouti</option>
					<option value="Dominica" label="Dominica">Dominica</option>
					<option value="Dominican Republic" label="Dominican Republic">Dominican Republic</option>
					<option value="Dronning Maud Land" label="Dronning Maud Land">Dronning Maud Land</option>
					<option value="East Germany" label="East Germany">East Germany</option>
					<option value="Ecuador" label="Ecuador">Ecuador</option>
					<option value="Egypt" label="Egypt">Egypt</option>
					<option value="El Salvador" label="El Salvador">El Salvador</option>
					<option value="Equatorial Guinea" label="Equatorial Guinea">Equatorial Guinea</option>
					<option value="Eritrea" label="Eritrea">Eritrea</option>
					<option value="Estonia" label="Estonia">Estonia</option>
					<option value="Ethiopia" label="Ethiopia">Ethiopia</option>
					<option value="Falkland Islands" label="Falkland Islands">Falkland Islands</option>
					<option value="Faroe Islands" label="Faroe Islands">Faroe Islands</option>
					<option value="Fiji" label="Fiji">Fiji</option>
					<option value="Finland" label="Finland">Finland</option>
					<option value="France" label="France">France</option>
					<option value="French Guiana" label="French Guiana">French Guiana</option>
					<option value="French Polynesia" label="French Polynesia">French Polynesia</option>
					<option value="French Southern Territories" label="French Southern Territories">French Southern Territories</option>
					<option value="French Southern and Antarctic Territories" label="French Southern and Antarctic Territories">French Southern and Antarctic Territories</option>
					<option value="Gabon" label="Gabon">Gabon</option>
					<option value="Gambia" label="Gambia">Gambia</option>
					<option value="Georgia" label="Georgia">Georgia</option>
					<option value="Germany" label="Germany">Germany</option>
					<option value="Ghana" label="Ghana">Ghana</option>
					<option value="Gibraltar" label="Gibraltar">Gibraltar</option>
					<option value="Greece" label="Greece">Greece</option>
					<option value="Greenland" label="Greenland">Greenland</option>
					<option value="Grenada" label="Grenada">Grenada</option>
					<option value="Guadeloupe" label="Guadeloupe">Guadeloupe</option>
					<option value="Guam" label="Guam">Guam</option>
					<option value="Guatemala" label="Guatemala">Guatemala</option>
					<option value="Guernsey" label="Guernsey">Guernsey</option>
					<option value="Guinea" label="Guinea">Guinea</option>
					<option value="Guinea-Bissau" label="Guinea-Bissau">Guinea-Bissau</option>
					<option value="Guyana" label="Guyana">Guyana</option>
					<option value="Haiti" label="Haiti">Haiti</option>
					<option value="Heard Island and McDonald Islands" label="Heard Island and McDonald Islands">Heard Island and McDonald Islands</option>
					<option value="Honduras" label="Honduras">Honduras</option>
					<option value="Hong Kong SAR China" label="Hong Kong SAR China">Hong Kong SAR China</option>
					<option value="Hungary" label="Hungary">Hungary</option>
					<option value="Iceland" label="Iceland">Iceland</option>
					<option value="India" label="India">India</option>
					<option value="Indonesia" label="Indonesia">Indonesia</option>
					<option value="Iran" label="Iran">Iran</option>
					<option value="Iraq" label="Iraq">Iraq</option>
					<option value="Ireland" label="Ireland">Ireland</option>
					<option value="Isle of Man" label="Isle of Man">Isle of Man</option>
					<option value="Israel" label="Israel">Israel</option>
					<option value="Italy" label="Italy">Italy</option>
					<option value="Jamaica" label="Jamaica">Jamaica</option>
					<option value="Japan" label="Japan">Japan</option>
					<option value="Jersey" label="Jersey">Jersey</option>
					<option value="Johnston Island" label="Johnston Island">Johnston Island</option>
					<option value="Jordan" label="Jordan">Jordan</option>
					<option value="Kazakhstan" label="Kazakhstan">Kazakhstan</option>
					<option value="Kenya" label="Kenya">Kenya</option>
					<option value="Kiribati" label="Kiribati">Kiribati</option>
					<option value="Kuwait" label="Kuwait">Kuwait</option>
					<option value="Kyrgyzstan" label="Kyrgyzstan">Kyrgyzstan</option>
					<option value="Laos" label="Laos">Laos</option>
					<option value="Latvia" label="Latvia">Latvia</option>
					<option value="Lebanon" label="Lebanon">Lebanon</option>
					<option value="Lesotho" label="Lesotho">Lesotho</option>
					<option value="Liberia" label="Liberia">Liberia</option>
					<option value="Libya" label="Libya">Libya</option>
					<option value="Liechtenstein" label="Liechtenstein">Liechtenstein</option>
					<option value="Lithuania" label="Lithuania">Lithuania</option>
					<option value="Luxembourg" label="Luxembourg">Luxembourg</option>
					<option value="Macau SAR China" label="Macau SAR China">Macau SAR China</option>
					<option value="Macedonia" label="Macedonia">Macedonia</option>
					<option value="Madagascar" label="Madagascar">Madagascar</option>
					<option value="Malawi" label="Malawi">Malawi</option>
					<option value="Malaysia" label="Malaysia">Malaysia</option>
					<option value="Maldives" label="Maldives">Maldives</option>
					<option value="Mali" label="Mali">Mali</option>
					<option value="Malta" label="Malta">Malta</option>
					<option value="Marshall Islands" label="Marshall Islands">Marshall Islands</option>
					<option value="Martinique" label="Martinique">Martinique</option>
					<option value="Mauritania" label="Mauritania">Mauritania</option>
					<option value="Mauritius" label="Mauritius">Mauritius</option>
					<option value="Mayotte" label="Mayotte">Mayotte</option>
					<option value="Metropolitan France" label="Metropolitan France">Metropolitan France</option>
					<option value="Mexico" label="Mexico">Mexico</option>
					<option value="Micronesia" label="Micronesia">Micronesia</option>
					<option value="Midway Islands" label="Midway Islands">Midway Islands</option>
					<option value="Moldova" label="Moldova">Moldova</option>
					<option value="Monaco" label="Monaco">Monaco</option>
					<option value="Mongolia" label="Mongolia">Mongolia</option>
					<option value="Montenegro" label="Montenegro">Montenegro</option>
					<option value="Montserrat" label="Montserrat">Montserrat</option>
					<option value="Morocco" label="Morocco">Morocco</option>
					<option value="Mozambique" label="Mozambique">Mozambique</option>
					<option value="Myanmar [Burma]" label="Myanmar [Burma]">Myanmar [Burma]</option>
					<option value="Namibia" label="Namibia">Namibia</option>
					<option value="Nauru" label="Nauru">Nauru</option>
					<option value="Nepal" label="Nepal">Nepal</option>
					<option value="Netherlands" label="Netherlands">Netherlands</option>
					<option value="Netherlands Antilles" label="Netherlands Antilles">Netherlands Antilles</option>
					<option value="Neutral Zone" label="Neutral Zone">Neutral Zone</option>
					<option value="New Caledonia" label="New Caledonia">New Caledonia</option>
					<option value="New Zealand" label="New Zealand">New Zealand</option>
					<option value="Nicaragua" label="Nicaragua">Nicaragua</option>
					<option value="Niger" label="Niger">Niger</option>
					<option value="Nigeria" label="Nigeria">Nigeria</option>
					<option value="Niue" label="Niue">Niue</option>
					<option value="Norfolk Island" label="Norfolk Island">Norfolk Island</option>
					<option value="North Korea" label="North Korea">North Korea</option>
					<option value="North Vietnam" label="North Vietnam">North Vietnam</option>
					<option value="Northern Mariana Islands" label="Northern Mariana Islands">Northern Mariana Islands</option>
					<option value="Norway" label="Norway">Norway</option>
					<option value="Oman" label="Oman">Oman</option>
					<option value="Pacific Islands Trust Territory" label="Pacific Islands Trust Territory">Pacific Islands Trust Territory</option>
					<option value="Pakistan" label="Pakistan">Pakistan</option>
					<option value="Palau" label="Palau">Palau</option>
					<option value="Palestinian Territories" label="Palestinian Territories">Palestinian Territories</option>
					<option value="Panama" label="Panama">Panama</option>
					<option value="Panama Canal Zone" label="Panama Canal Zone">Panama Canal Zone</option>
					<option value="Papua New Guinea" label="Papua New Guinea">Papua New Guinea</option>
					<option value="Paraguay" label="Paraguay">Paraguay</option>
					<option value="Peoples Democratic Republic of Yemen" label="Peoples Democratic Republic of Yemen">Peoples Democratic Republic of Yemen</option>
					<option value="Peru" label="Peru">Peru</option>
					<option value="Philippines" label="Philippines">Philippines</option>
					<option value="Pitcairn Islands" label="Pitcairn Islands">Pitcairn Islands</option>
					<option value="Poland" label="Poland">Poland</option>
					<option value="Portugal" label="Portugal">Portugal</option>
					<option value="Puerto Rico" label="Puerto Rico">Puerto Rico</option>
					<option value="Qatar" label="Qatar">Qatar</option>
					<option value="Romania" label="Romania">Romania</option>
					<option value="Russia" label="Russia">Russia</option>
					<option value="Rwanda" label="Rwanda">Rwanda</option>
					<option value="Réunion" label="Réunion">Réunion</option>
					<option value="Saint Barthélemy" label="Saint Barthélemy">Saint Barthélemy</option>
					<option value="Saint Helena" label="Saint Helena">Saint Helena</option>
					<option value="Saint Kitts and Nevis" label="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
					<option value="Saint Lucia" label="Saint Lucia">Saint Lucia</option>
					<option value="Saint Martin" label="Saint Martin">Saint Martin</option>
					<option value="Saint Pierre and Miquelon" label="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
					<option value="Saint Vincent and the Grenadines" label="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
					<option value="Samoa" label="Samoa">Samoa</option>
					<option value="San Marino" label="San Marino">San Marino</option>
					<option value="Saudi Arabia" label="Saudi Arabia">Saudi Arabia</option>
					<option value="Senegal" label="Senegal">Senegal</option>
					<option value="Serbia" label="Serbia">Serbia</option>
					<option value="Serbia and Montenegro" label="Serbia and Montenegro">Serbia and Montenegro</option>
					<option value="Seychelles" label="Seychelles">Seychelles</option>
					<option value="Sierra Leone" label="Sierra Leone">Sierra Leone</option>
					<option value="Singapore" label="Singapore">Singapore</option>
					<option value="Slovakia" label="Slovakia">Slovakia</option>
					<option value="Slovenia" label="Slovenia">Slovenia</option>
					<option value="Solomon Islands" label="Solomon Islands">Solomon Islands</option>
					<option value="Somalia" label="Somalia">Somalia</option>
					<option value="South Africa" label="South Africa">South Africa</option>
					<option value="South Georgia and the South Sandwich Islands" label="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>
					<option value="South Korea" label="South Korea">South Korea</option>
					<option value="Spain" label="Spain">Spain</option>
					<option value="Sri Lanka" label="Sri Lanka">Sri Lanka</option>
					<option value="Sudan" label="Sudan">Sudan</option>
					<option value="Suriname" label="Suriname">Suriname</option>
					<option value="Svalbard and Jan Mayen" label="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
					<option value="Swaziland" label="Swaziland">Swaziland</option>
					<option value="Sweden" label="Sweden">Sweden</option>
					<option value="Switzerland" label="Switzerland">Switzerland</option>
					<option value="Syria" label="Syria">Syria</option>
					<option value="São Tomé and Príncipe" label="São Tomé and Príncipe">São Tomé and Príncipe</option>
					<option value="Taiwan" label="Taiwan">Taiwan</option>
					<option value="Tajikistan" label="Tajikistan">Tajikistan</option>
					<option value="Tanzania" label="Tanzania">Tanzania</option>
					<option value="Thailand" label="Thailand">Thailand</option>
					<option value="Timor-Leste" label="Timor-Leste">Timor-Leste</option>
					<option value="Togo" label="Togo">Togo</option>
					<option value="Tokelau" label="Tokelau">Tokelau</option>
					<option value="Tonga" label="Tonga">Tonga</option>
					<option value="Trinidad and Tobago" label="Trinidad and Tobago">Trinidad and Tobago</option>
					<option value="Tunisia" label="Tunisia">Tunisia</option>
					<option value="Turkey" label="Turkey">Turkey</option>
					<option value="Turkmenistan" label="Turkmenistan">Turkmenistan</option>
					<option value="Turks and Caicos Islands" label="Turks and Caicos Islands">Turks and Caicos Islands</option>
					<option value="Tuvalu" label="Tuvalu">Tuvalu</option>
					<option value="U.S. Minor Outlying Islands" label="U.S. Minor Outlying Islands">U.S. Minor Outlying Islands</option>
					<option value="U.S. Miscellaneous Pacific Islands" label="U.S. Miscellaneous Pacific Islands">U.S. Miscellaneous Pacific Islands</option>
					<option value="U.S. Virgin Islands" label="U.S. Virgin Islands">U.S. Virgin Islands</option>
					<option value="Uganda" label="Uganda">Uganda</option>
					<option value="Ukraine" label="Ukraine">Ukraine</option>
					<option value="Union of Soviet Socialist Republics" label="Union of Soviet Socialist Republics">Union of Soviet Socialist Republics</option>
					<option value="United Arab Emirates" label="United Arab Emirates">United Arab Emirates</option>
					<option value="United Kingdom" label="United Kingdom">United Kingdom</option>
					<option value="United States of America" label="United States of America">United States of America</option>
					<option value="Unknown or Invalid Region" label="Unknown or Invalid Region">Unknown or Invalid Region</option>
					<option value="Uruguay" label="Uruguay">Uruguay</option>
					<option value="Uzbekistan" label="Uzbekistan">Uzbekistan</option>
					<option value="Vanuatu" label="Vanuatu">Vanuatu</option>
					<option value="Vatican City" label="Vatican City">Vatican City</option>
					<option value="Venezuela" label="Venezuela">Venezuela</option>
					<option value="Vietnam" label="Vietnam">Vietnam</option>
					<option value="Wake Island" label="Wake Island">Wake Island</option>
					<option value="Wallis and Futuna" label="Wallis and Futuna">Wallis and Futuna</option>
					<option value="Western Sahara" label="Western Sahara">Western Sahara</option>
					<option value="Yemen" label="Yemen">Yemen</option>
					<option value="Zambia" label="Zambia">Zambia</option>
					<option value="Zimbabwe" label="Zimbabwe">Zimbabwe</option>
					<option value="Åland Islands" label="Åland Islands">Åland Islands</option>
				</select>
							
<br /><br />
<p> Enter account details</p>
<input id="username" type="text" onfocus="emptyElement('status')" onblur="checkusername()" onkeyup="restrict('username')" maxlength="16" class="form-control" placeholder="User Name">
<span id="unamestatus"></span>

<input id="pass1" type="password" onfocus="emptyElement('status')" maxlength="100" class="form-control" placeholder="Password">
<input id="pass2" type="password" onfocus="emptyElement('status')" maxlength="100" class="form-control" placeholder="Re-type Password">
					
<div class="container">
	<!-- Trigger the modal with a link -->
	 <label class="checkbox">
		<input name="terms" type="checkbox" value="terms"> I agree to the <a href="#myModal" data-toggle="modal" data-target="#myModal">Terms and Conditions</a>
	</label>

<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
<div class="modal-dialog">
    
<!-- Modal content-->
	<div class="modal-content">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title">Terms and Conditions</h4>
		</div>
		<div class="modal-body" style="padding: 35px;">
			<ul style="list-style-type:disc">
				<li>Website Rules.</li>
			</ul>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		</div>
	</div>      
</div>
</div>  
</div>
<br />
<div class="g-recaptcha" data-callback="captchaCheck" data-sitekey="6LeB6wYUAAAAABwftb4pU4SLodZwsffuyiD8b41W"></div>
<br /><br />
<button id="signupbtn" onclick="signup();" class="btn btn-lg btn-login btn-block" disabled>Create Account</button>
<center><span style="font-weight:bold;" id="status"></span></center>
<br />
				<div class="registration">
					Already Registered ?
					<a class="" href="home_page.php">
						Login Here
					</a>
				</div>
			</div>
		</form>
	</div>
</div>
	
<!--container end-->
<?php
$curl = curl_init();
curl_setopt_array($curl, [
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => [
		'secret' => '6LeB6wYUAAAAAHLj6nkdYCo8rJdMUSFjwhLooU7Y',
		'response' => $_POST['g-recaptcha-response'],
	],
]);

$response = json_decode(curl_exec($curl));
?>
<script>
function captchaCheck(){
	$('#signupbtn').removeAttr('disabled');
}

</script>
<script>
function signup(){
	var fn = _("firstName").value; 
	var ln = _("lastName").value;
	var u = _("username").value;
	var e = _("email").value;
	var p1 = _("pass1").value;
	var p2 = _("pass2").value;
	var c = _("country").value;
	var g = _("gender").value;
	var status = _("status");
	var lc = (p1.match(/[a-z]/)) ? 1 : 0;
	var uc = (p1.match(/[A-Z]/)) ? 1 : 0;
	var nm = (p1.match(/[0-9]/)) ? 1 : 0;
	
	if(fn == "" || ln == "" || u == "" || e == "" || p1 == "" || p2 == "" || c == "" || g == ""){
		status.innerHTML = "Fill out all of the form data.";
	} else if(p1 != p2){
		status.innerHTML = "Your password fields do not match.";
	} else if(!document.forms[0].terms.checked){
		status.innerHTML = "Please agree to the terms of use.";
	} else if(!uc || !lc || !nm){
		status.innerHTML = "Password requires at least one uppercase letter, one lowercase letter, and one number.\n Please try again.";
	}  else {
		_("signupbtn").style.display = "none";
		status.innerHTML = 'please wait ...';
		var ajax = ajaxObj("POST", "register_user.php");
        ajax.onreadystatechange = function() {
	        if(ajaxReturn(ajax) == true) {
	            if(ajax.responseText != "signup_success"){
					status.innerHTML = ajax.responseText;
					_("signupbtn").style.display = "block";
				} else{
					window.location.assign("registration_success.php")
				}
	        }
        }
        ajax.send("fn="+fn+"&ln="+ln+"&u="+u+"&e="+e+"&p="+p1+"&c="+c+"&g="+g);
	}
}
</script>
  </body>
</html>

<!--<html>
	<head></head>
	<body></body>
</html>-->










