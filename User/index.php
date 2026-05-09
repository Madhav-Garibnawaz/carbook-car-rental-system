<?php include 'header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  /* ── PREMIUM CAR CARDS ── */
.car-wrap {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  border: 1px solid rgba(0,0,0,0.06);
  transition: transform 0.32s cubic-bezier(.34,1.56,.64,1), box-shadow 0.32s ease;
  position: relative;
  margin-bottom: 30px;
  display: flex;
  flex-direction: column;
}
.car-wrap:hover {
  transform: translateY(-8px) scale(1.013);
  box-shadow: 0 20px 48px rgba(46,204,113,0.13), 0 6px 18px rgba(0,0,0,0.09);
}

/* Accent bar — shimmer on hover */
.car-wrap::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: #2ecc71;
  z-index: 3;
  overflow: hidden;
}
.car-wrap::after {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 3px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.7), transparent);
  z-index: 4;
  transition: left 0.55s ease;
}
.car-wrap:hover::after { left: 100%; }

/* Image container */
.car-wrap .img-wrap {
  position: relative;
  background: linear-gradient(160deg, #f0faf4 0%, #e8f8ef 100%);
  padding: 28px 20px 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  height: 200px;
}
.car-wrap .img-wrap::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 40px;
  background: linear-gradient(to top, #fff, transparent);
  pointer-events: none;
}
.car-wrap .img-wrap img,
.car-wrap .img.rounded {
  width: 100% !important;
  max-width: 260px !important;
  height: 130px !important;
  object-fit: contain !important;
  object-position: center !important;
  filter: drop-shadow(0 10px 18px rgba(0,0,0,0.14));
  transition: transform 0.35s cubic-bezier(.34,1.56,.64,1);
  position: relative;
  z-index: 1;
  border-radius: 0 !important;
  background: none !important;
}
.car-wrap:hover .img.rounded,
.car-wrap:hover .img-wrap img {
  transform: translateX(6px) scale(1.06);
}

/* Brand badge on image */
.car-wrap .brand-badge {
  position: absolute;
  top: 14px; left: 14px;
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(46,204,113,0.25);
  border-radius: 6px;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #27ae60;
  padding: 3px 9px;
  z-index: 2;
  backdrop-filter: blur(4px);
}

/* Price badge on image */
.car-wrap .price-badge {
  position: absolute;
  top: 14px; right: 14px;
  background: #2ecc71;
  border-radius: 8px;
  font-size: 0.72rem;
  font-weight: 800;
  color: #fff;
  padding: 4px 10px;
  z-index: 2;
  letter-spacing: 0.02em;
  box-shadow: 0 3px 10px rgba(46,204,113,0.35);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.car-wrap:hover .price-badge {
  transform: scale(1.07);
  box-shadow: 0 5px 16px rgba(46,204,113,0.45);
}

/* Card text body */
.car-wrap .text {
  padding: 16px 18px 18px !important;
  flex: 1;
  display: flex;
  flex-direction: column;
  border-top: 1px solid rgba(0,0,0,0.05);
}
.car-wrap .text h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 10px;
  letter-spacing: -0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Specs strip */
.car-specs-strip {
  display: flex;
  gap: 0;
  border: 1px solid rgba(0,0,0,0.07);
  border-radius: 9px;
  overflow: hidden;
  margin-bottom: 14px;
  background: #f8fffe;
}
.car-spec-item {
  flex: 1;
  text-align: center;
  padding: 7px 4px;
  border-right: 1px solid rgba(0,0,0,0.07);
  transition: background 0.18s;
}
.car-spec-item:last-child { border-right: none; }
.car-wrap:hover .car-spec-item { background: #f0faf4; }
.car-spec-val {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 1px;
}
.car-spec-lbl {
  display: block;
  font-size: 0.58rem;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* Book Now button */
.car-wrap .btn-primary {
  background: #2ecc71 !important;
  border: 2px solid #2ecc71 !important;
  border-radius: 10px !important;
  font-weight: 700 !important;
  font-size: 0.85rem !important;
  letter-spacing: 0.04em !important;
  padding: 11px 0 !important;
  transition: background 0.25s, border-color 0.25s, transform 0.22s cubic-bezier(.34,1.56,.64,1), box-shadow 0.25s !important;
  position: relative;
  overflow: hidden;
}
.car-wrap .btn-primary::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
  opacity: 0;
  transition: opacity 0.22s;
}
.car-wrap .btn-primary:hover {
  background: #27ae60 !important;
  border-color: #27ae60 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 22px rgba(46,204,113,0.35) !important;
}
.car-wrap .btn-primary:hover::before { opacity: 1; }
.car-wrap .btn-primary:active {
  transform: translateY(0) scale(0.98) !important;
}

/* Entrance animation */
.car-wrap.ftco-animate {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.36s ease, transform 0.36s ease, box-shadow 0.32s ease;
}
.car-wrap.ftco-animate.animate-in {
  opacity: 1;
  transform: translateY(0);
}
.car-wrap.ftco-animate.animate-in:hover {
  transform: translateY(-8px) scale(1.013);
}
.car-wrap.ftco-animate.exiting {
  opacity: 0;
  transform: translateY(14px) scale(0.97);
  pointer-events: none;
}
  /* ===== Section Background ===== */
  /* ── Brand filter bar ─────────────────────────── */
		.brand-filter-bar {
			display: flex;
			justify-content: center;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 36px;
		}
		.brand-filter-bar .filter-btn {
			font-size: 0.82rem;
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			padding: 7px 20px;
			border-radius: 3px;
			border: 2px solid #2ecc71;
			background: transparent;
			color: #2ecc71;
			cursor: pointer;
			transition: background 0.22s ease, color 0.22s ease, transform 0.15s ease;
			outline: none;
		}
		.brand-filter-bar .filter-btn:hover,
		.brand-filter-bar .filter-btn.active {
			background: #2ecc71;
			color: #fff;
		}
		.brand-filter-bar .filter-btn:active {
			transform: scale(0.95);
		}

		/* ── Card entrance / exit transitions ────────── */
		/* Only adds opacity + translateY on top of existing .car-wrap styles */
		.car-wrap.ftco-animate {
			opacity: 0;
			transform: translateY(28px);
			transition: opacity 0.36s ease, transform 0.36s ease, box-shadow 0.3s ease;
		}
		.car-wrap.ftco-animate.animate-in {
			opacity: 1;
			transform: translateY(0);
		}
		.car-wrap.ftco-animate.animate-in:hover {
			transform: translateY(-5px);
		}
		.car-wrap.ftco-animate.exiting {
			opacity: 0;
			transform: translateY(14px) scale(0.97);
			pointer-events: none;
		}

		/* ── Hidden column (filter hide) ─────────────── */
		.col-md-4.col-hidden {
			display: none !important;
		}

    /* ── Book Now Button Hover Effect ─────────────── */
.car-wrap .btn-primary {
    background: #2ecc71;
    border: 2px solid #2ecc71;
    transition: all 0.3s ease;
}

.car-wrap .btn-primary:hover {
    background: #27ae60;
    border-color: #27ae60;
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
}

</style>
    <div class="hero-wrap ftco-degree-bg" style="background-image: url('images/bg_1.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text justify-content-start align-items-center justify-content-center">
          <div class="col-lg-8 ftco-animate">
          	<div class="text w-100 text-center mb-md-5 pb-md-5">
	            <h1 class="mb-4">Fast &amp; Easy Way To Rent A Car</h1>
	            <p style="font-size: 18px;">Whether you need a quick ride or a long trip, choose from trusted drivers and well-maintained vehicles—all in one place.</p>
	            <a href="car.php" class="icon-wrap d-flex align-items-center mt-4 justify-content-center">
	            	<div class="icon d-flex align-items-center justify-content-center">
	            		<span class="ion-ios-play"></span>
	            	</div>
	            	<div class="heading-title ml-5">
		            	<span>Easy steps for renting a car</span>
	            	</div>
	            </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section class="ftco-section ftco-no-pt bg-light">
          <div class="container">
              <div class="row no-gutters">
                  <div class="col-md-12 featured-top">
                      <div class="row no-gutters">

                          <div class="col-md-12 d-flex align-items-center">
                              <div class="services-wrap rounded-right w-100">
                                  <h3 class="heading-section mb-4">Better Way to Rent Your Perfect Cars</h3>
                                  <div class="row d-flex mb-4">
                                      <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                                          <div class="services w-100 text-center">
                                              <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-route"></span></div>
                                              <div class="text w-100">
                                                  <h3 class="heading mb-2">Choose Your Pickup Location</h3>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                                          <div class="services w-100 text-center">
                                              <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-handshake"></span></div>
                                              <div class="text w-100">
                                                  <h3 class="heading mb-2">Select the Best Deal</h3>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col-md-4 d-flex align-self-stretch ftco-animate">
                                          <div class="services w-100 text-center">
                                              <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-rent"></span></div>
                                              <div class="text w-100">
                                                  <h3 class="heading mb-2">Reserve Your Rental Car</h3>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <p><a href="car.php" class="btn btn-primary py-3 px-4">Reserve Your Perfect Car</a></p>
                              </div>
                          </div>

                      </div>
                  </div>
              </div>
          </div>
    </section>

    <section class="ftco-section bg-light mb-0">
        <div class="container">
          <div class="row justify-content-center">
          <div class="col-md-12 heading-section text-center ftco-animate mb-0">
            <div class="brands-heading-wrap">
              <span class="subheading">Our Partners</span>
              <h2 class="mb-2">Featured Cars</h2>
            </div>
          </div>
        </div>
          <div class="brand-filter-bar text-center mb-4">

            <?php
            include("connect.php");
            $q = mysqli_query($con,"SELECT * FROM brand_master");
            
            while ($row = mysqli_fetch_assoc($q)) { ?>
  <a href="#" class="filter-btn btn btn-outline-primary"
     onclick="getCars(<?= $row['brand_id'] ?>); return false;">
    <?= htmlspecialchars($row['brand_name']) ?>
  </a>
<?php } ?>

            </div>
          <div class="row" id="carGrid">
            <?php
              include("connect.php");

              $brand_id = $_GET['brand_id'] ?? 'all';

              $sql = "SELECT c.*, b.brand_name, p.price_per_day
                      FROM car_master c
                      JOIN brand_master b ON c.brand_id = b.brand_id
                      LEFT JOIN car_pricing p ON p.car_id = c.car_id
                      WHERE c.is_enabled = 1 AND b.is_active = 1";

              if ($brand_id !== 'all') {
                  $brand_id = (int)$brand_id;
                  $sql .= " AND c.brand_id = $brand_id";
              }

              $sql .= " ORDER BY c.car_id DESC LIMIT 9";

              $q = mysqli_query($con, $sql);

             while ($row = mysqli_fetch_assoc($q)) {
  $fuel  = !empty($row['fuel_type'])        ? $row['fuel_type']        : '—';
  $seats = !empty($row['seating_capacity']) ? $row['seating_capacity'] : '—';
  $gear  = !empty($row['gear_type'])        ? $row['gear_type']        : '—';
?>
<div class="col-md-4 mb-4">
  <div class="car-wrap rounded ftco-animate animate-in">
    <div style="position:relative;background:linear-gradient(160deg,#f0faf4,#e8f8ef);padding:28px 20px 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;height:200px;">
      <span class="brand-badge"><?= htmlspecialchars($row['brand_name']) ?></span>
      <span class="price-badge">&#8377;<?= number_format($row['price_per_day'], 0) ?>/day</span>
      <img src="../Admin/pages/images/car_images/<?= htmlspecialchars($row['primary_image']) ?>"
           class="img rounded" alt="<?= htmlspecialchars($row['car_display_name']) ?>"
           style="position:relative;z-index:1" onerror="this.style.opacity='.2'">
      <div style="position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(to top,#fff,transparent);pointer-events:none"></div>
    </div>
    <div class="text">
      <h2 class="mb-0"><?= htmlspecialchars($row['car_display_name']) ?></h2>
      <div class="car-specs-strip">
        <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($seats) ?></span><span class="car-spec-lbl">Seats</span></div>
        <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($fuel) ?></span><span class="car-spec-lbl">Fuel</span></div>
        <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($gear) ?></span><span class="car-spec-lbl">Gear</span></div>
      </div>
      <p class="card-footer p-0 mt-auto">
        <a href="booking.php?car_id=<?= $row['car_id'] ?>&brand_id=<?= $row['brand_id'] ?>"
           class="btn btn-primary w-100 py-3 rounded">Book Now</a>
      </p>
    </div>
  </div>
</div>
<?php } ?>
          </div>
          <!-- </div>/.row -->

          <div class="row mt-0">
            <div class="col text-center">
                <a href="car.php" class="btn btn-outline-primary">View More Cars!.</a>
            </div>
          </div>

        </div>
    </section>

		<section class="ftco-section ftco-intro" style="background-image: url(images/bg_3.jpg);">
			<div class="overlay"></div>
			<div class="container">
				<div class="row justify-content-end">
					<div class="col-md-6 heading-section heading-section-white ftco-animate">
            <h2 class="mb-3">Do You Want To Earn With Us? So Don't Be Late.</h2>
            <a href="../Driver/register.php" class="btn btn-primary btn-lg">Become A Driver</a>
          </div>
				</div>
			</div>
		</section>


    <section class="ftco-section testimony-section bg-light">
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-7 text-center heading-section ftco-animate">
          	<span class="subheading">Testimonial</span>
            <h2 class="mb-3">Happy Clients</h2>
          </div>
        </div>
        <div class="row ftco-animate">
          <div class="col-md-12">
            <div class="carousel-testimony owl-carousel ftco-owl">
              <div class="item">
                <div class="testimony-wrap rounded text-center py-4 pb-5">
                  <div class="user-img mb-2" style="background-image: url(images/person_1.jpg)">
                  </div>
                  <div class="text pt-4">
                    <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                    <p class="name">Roger Scott</p>
                    <span class="position">Marketing Manager</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap rounded text-center py-4 pb-5">
                  <div class="user-img mb-2" style="background-image: url(images/person_2.jpg)">
                  </div>
                  <div class="text pt-4">
                    <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                    <p class="name">Roger Scott</p>
                    <span class="position">Interface Designer</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap rounded text-center py-4 pb-5">
                  <div class="user-img mb-2" style="background-image: url(images/person_3.jpg)">
                  </div>
                  <div class="text pt-4">
                    <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                    <p class="name">Roger Scott</p>
                    <span class="position">UI Designer</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap rounded text-center py-4 pb-5">
                  <div class="user-img mb-2" style="background-image: url(images/person_1.jpg)">
                  </div>
                  <div class="text pt-4">
                    <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                    <p class="name">Roger Scott</p>
                    <span class="position">Web Developer</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap rounded text-center py-4 pb-5">
                  <div class="user-img mb-2" style="background-image: url(images/person_1.jpg)">
                  </div>
                  <div class="text pt-4">
                    <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                    <p class="name">Roger Scott</p>
                    <span class="position">System Analyst</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section">
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-7 heading-section text-center ftco-animate">
          	<span class="subheading">Blog</span>
            <h2>Recent Blog</h2>
          </div>
        </div>
        <div class="row d-flex">
          <div class="col-md-4 d-flex ftco-animate">
          	<div class="blog-entry justify-content-end">
              <a href="blog-single.html" class="block-20" style="background-image: url('images/image_1.jpg');">
              </a>
              <div class="text pt-4">
              	<div class="meta mb-3">
                  <div><a href="#">Oct. 29, 2019</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <h3 class="heading mt-2"><a href="blog.php">Why Lead Generation is Key for Business Growth</a></h3>
                <p><a href="#" class="btn btn-primary">Read more</a></p>
              </div>
            </div>
          </div>
          <div class="col-md-4 d-flex ftco-animate">
          	<div class="blog-entry justify-content-end">
              <a href="blog-single.html" class="block-20" style="background-image: url('images/image_2.jpg');">
              </a>
              <div class="text pt-4">
              	<div class="meta mb-3">
                  <div><a href="#">Oct. 29, 2019</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <h3 class="heading mt-2"><a href="#">Why Lead Generation is Key for Business Growth</a></h3>
                <p><a href="blog.php" class="btn btn-primary">Read more</a></p>
              </div>
            </div>
          </div>
          <div class="col-md-4 d-flex ftco-animate">
          	<div class="blog-entry">
              <a href="blog-single.html" class="block-20" style="background-image: url('images/image_3.jpg');">
              </a>
              <div class="text pt-4">
              	<div class="meta mb-3">
                  <div><a href="#">Oct. 29, 2019</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <h3 class="heading mt-2"><a href="#">Why Lead Generation is Key for Business Growth</a></h3>
                <p><a href="blog.php" class="btn btn-primary">Read more</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>	

    <section class="ftco-counter ftco-section img bg-light" id="section-counter">
			<div class="overlay"></div>
    	<div class="container">
    		<div class="row">
          <div class="col-md-6 col-lg-3 justify-content-center counter-wrap ftco-animate">
            <div class="block-18">
              <div class="text text-border d-flex align-items-center">
                <strong class="number" data-number="21">0</strong>
                <span>Year <br>Experienced</span>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 justify-content-center counter-wrap ftco-animate">
            <div class="block-18">
              <div class="text text-border d-flex align-items-center">
                <strong class="number" data-number="87">0</strong>
                <span>Total <br>Cars</span>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 justify-content-center counter-wrap ftco-animate">
            <div class="block-18">
              <div class="text text-border d-flex align-items-center">
                <strong class="number" data-number="100">0</strong>
                <span>Happy <br>Customers</span>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 justify-content-center counter-wrap ftco-animate">
            <div class="block-18">
              <div class="text d-flex align-items-center">
                <strong class="number" data-number="67">0</strong>
                <span>Total <br>Branches</span>
              </div>
            </div>
          </div>
        </div>
    	</div>
    </section>	
    <script>
function getCars(brand_id) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');

    var grid = document.getElementById("carGrid");
    grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">Loading...</p></div>';

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            grid.innerHTML = this.responseText;

            // Force all injected cards visible — overrides ftco-animate opacity:0
            grid.querySelectorAll('.car-wrap').forEach(function(card) {
                card.style.setProperty('opacity', '1', 'important');
                card.style.setProperty('transform', 'none', 'important');
            });
        }
    };
    xmlhttp.open("GET", "getCars.php?brand_id=" + brand_id, true);
    xmlhttp.send();
}
</script>
    <?php include 'footer.php'; ?>
    
  </body>
</html>