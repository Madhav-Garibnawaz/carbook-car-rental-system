<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carbook - Terms & FAQ</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body {
  background: #f8f9fb;
  font-family: 'Poppins', sans-serif;
  color: #444;
}

/* HERO FULL WIDTH */
.hero {
  width: 100%;
  height: 420px;
  background: url('images/bg_3.jpg') center/cover no-repeat;
  position: relative;
}

.hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.65);
}

.hero-content {
  position: absolute;
  bottom: 50px;
  left: 8%;
  color: #fff;
  z-index: 2;
}

.hero h1 {
  font-size: 44px;
  font-weight: 600;
}

.hero p {
  opacity: 0.9;
}

/* CONTAINER */
.main-container {
  max-width: 1100px;
  margin: auto;
  margin-top: -60px;
}

/* CARD BOX */
.section-box {
  background: #fff;
  padding: 35px;
  border-radius: 14px;
  box-shadow: 0 10px 35px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}

/* HEADINGS */
.section-box h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}

/* TEXT */
.section-box p {
  font-size: 15px;
  line-height: 1.9;
  margin-bottom: 12px;
}

/* ACCORDION */
.accordion-button {
  font-weight: 500;
}

.accordion-button:not(.collapsed) {
  background: #111;
  color: #fff;
}

/* BUTTON */
.btn-custom {
  background: #111;
  color: #fff;
  padding: 12px 25px;
  border-radius: 8px;
  text-decoration: none;
}

.btn-custom:hover {
  background: #000;
}

</style>
</head>

<body>

<!-- HERO -->
<div class="hero">
  <div class="hero-content">
    <h1>Terms, Conditions & FAQ</h1>
    <p>Everything you need to know before booking your ride with Carbook</p>
  </div>
</div>

<div class="main-container">

<!-- TERMS -->
<div class="section-box">

<h2><i class="fa fa-file-contract"></i> Terms & Conditions</h2>

<p><b>1. Booking & Approval:</b> All bookings placed on Carbook are subject to administrative approval. A booking request does not guarantee vehicle availability until it is confirmed by our system.</p>

<p><b>2. User Eligibility:</b> Users must hold a valid driving license and provide authentic identity verification. Fake or invalid documents will result in account suspension.</p>

<p><b>3. Payment Policy:</b> Payment must be completed after approval. Carbook supports secure online payments. Failure to pay within the given time may cancel your booking.</p>

<p><b>4. Security Deposit:</b> A refundable deposit is applicable depending on vehicle type. Refunds are processed within 3–5 business days after inspection.</p>

<p><b>5. Driver Earnings:</b> Drivers receive a fixed 10% commission per completed booking. The remaining amount supports platform maintenance, insurance, and operations.</p>

<p><b>6. Cancellation Policy:</b> Free cancellation is allowed before approval. After approval, cancellation fees may apply based on timing.</p>

<p><b>7. Late Return Charges:</b> Late returns are charged on an hourly or daily basis depending on delay duration.</p>

<p><b>8. Fuel Policy:</b> Vehicles must be returned with the same fuel level as provided. Differences may result in additional charges.</p>

<p><b>9. Damage & Liability:</b> Users are responsible for any physical or mechanical damage during the rental period.</p>

<p><b>10. Prohibited Use:</b> Vehicles must not be used for illegal activities, racing, or commercial misuse.</p>

<p><b>11. Insurance:</b> All vehicles are insured; however, deductibles may apply in case of accidents.</p>

<p><b>12. Account Termination:</b> Carbook reserves the right to suspend accounts violating policies.</p>

</div>

<!-- FAQ -->
<div class="section-box">

<h2><i class="fa fa-question-circle"></i> Frequently Asked Questions</h2>

<div class="accordion" id="faq">

<!-- Q1 -->
<div class="accordion-item">
<button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#q1">
How does Carbook work?
</button>
<div id="q1" class="accordion-collapse collapse show">
<div class="accordion-body">
You select a car, request booking, wait for admin approval, then complete payment to confirm your ride.
</div>
</div>
</div>

<!-- Q2 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q2">
When is payment required?
</button>
<div id="q2" class="accordion-collapse collapse">
<div class="accordion-body">
Payment is only required after booking approval. No upfront payment is needed before confirmation.
</div>
</div>
</div>

<!-- Q3 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q3">
Is the security deposit refundable?
</button>
<div id="q3" class="accordion-collapse collapse">
<div class="accordion-body">
Yes, it is refunded after vehicle inspection if no damage or policy violations are found.
</div>
</div>
</div>

<!-- Q4 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q4">
What documents are required?
</button>
<div id="q4" class="accordion-collapse collapse">
<div class="accordion-body">
A valid driving license, ID proof, and sometimes address verification are required.
</div>
</div>
</div>

<!-- Q5 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q5">
Can I cancel my booking?
</button>
<div id="q5" class="accordion-collapse collapse">
<div class="accordion-body">
Yes. Cancellation is free before approval. Charges apply after approval.
</div>
</div>
</div>

<!-- Q6 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q6">
Are drivers verified?
</button>
<div id="q6" class="accordion-collapse collapse">
<div class="accordion-body">
Yes, all drivers undergo verification and training before onboarding.
</div>
</div>
</div>

<!-- Q7 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q7">
What if the car breaks down?
</button>
<div id="q7" class="accordion-collapse collapse">
<div class="accordion-body">
Contact support immediately. Replacement or assistance will be provided.
</div>
</div>
</div>

<!-- Q8 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q8">
Can I extend my booking?
</button>
<div id="q8" class="accordion-collapse collapse">
<div class="accordion-body">
Yes, subject to availability. Additional charges will apply.
</div>
</div>
</div>

<!-- Q9 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q9">
What happens in case of accident?
</button>
<div id="q9" class="accordion-collapse collapse">
<div class="accordion-body">
Inform support immediately. Insurance will be processed as per policy terms.
</div>
</div>
</div>

<!-- Q10 -->
<div class="accordion-item">
<button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q10">
Is there a mileage limit?
</button>
<div id="q10" class="accordion-collapse collapse">
<div class="accordion-body">
Some vehicles have daily mileage limits. Extra usage may incur charges.
</div>
</div>
</div>

</div>

</div>

</div>

<?php include('footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>