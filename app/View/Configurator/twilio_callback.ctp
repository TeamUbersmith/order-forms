<<?php echo '?'; ?>xml version="1.0" encoding="UTF-8"?>
<Response>
	<Say>Fraud verification for first name <?php echo $first_name; ?>.</Say>
	<Dial callerId="<?php echo $number; ?>" IfMachine="Hangup">
		<Number><?php echo $verification_phone_number; ?></Number>
		<Say>Please wait while we connect you with a Internap representative.</Say>
	</Dial>
</Response><?php exit; ?>
