<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: SAMEORIGIN"); 
header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once '../backends/config.php';
$conn = get_db_connection();
?>    
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iloilo Top Property Homes</title>
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<!-- AOS for scroll animations -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">

<style>
html, body {
    height: 100%;
    margin: 0;
}
.tempo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4); /* adjust opacity */
    z-index: 2;
}
.tempo-bg {
    background-image: url('../photo/news.png'); /* make sure this path is correct */
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px 0;
    position: relative;
}
</style>

</head>
<body>

<!-- TOP CONTACT -->
<div class="top-contact">
<div>ITPH.com.ph | (+63) 927 933 3923</div>
<div>
<i class="bi bi-facebook me-2"></i>
<i class="bi bi-instagram me-2"></i>
<i class="bi bi-tiktok"></i>
</div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">ITPH</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav me-3">
        <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link active-link" href="all_properties.php">Properties</a></li>
        <li class="nav-item"><a class="nav-link" href="contact_us.php">Contact Us</a></li>
        <li class="nav-item dropdown position-static">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">News &amp; Blogs</a>
            <div class="dropdown-menu w-100 p-4">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="bi bi-newspaper"></i> Latest News</h6>
                        <p class="small text-muted">Stay updated with the latest real estate updates and subdivision announcements.</p>
                        <a href="news.php" class="btn btn-outline-primary btn-sm">View News</a>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="bi bi-camera-video"></i> Vlogs</h6>
                        <p class="small text-muted">Watch property tours, real estate tips, and guides about buying homes and investing.</p>
                        <a href="vlogs.php" class="btn btn-outline-primary btn-sm">Watch Vlogs</a>
                    </div>
                </div>
            </div>
        </li>
      </ul>
      <?php if(isset($_SESSION['user_id'])): ?>
  <?php
    // Build initials for avatar
    $nav_fullname = $_SESSION['fullname'] ?? '';
    $nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($nav_fullname)))));
    $nav_initials = substr($nav_initials, 0, 2);
  ?>
  <div class="dropdown" style="display:flex; align-items:center; gap:10px;">
    <a href="account.php" title="My Account" style="
        width:38px; height:38px; border-radius:50%;
        background:var(--gold); color:#fff;
        font-size:0.75rem; font-weight:600; letter-spacing:0.05em;
        display:flex; align-items:center; justify-content:center;
        border:2px solid var(--gold-light); text-decoration:none;
        transition:transform .2s, box-shadow .2s;
    " onmouseover="this.style.transform='scale(1.07)';this.style.boxShadow='0 4px 14px rgba(191,161,88,0.35)'"
       onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
      <?= htmlspecialchars($nav_initials) ?>
    </a>
    <a href="log_out.php" title="Logout" style="color:#7a7a8a;font-size:1.1rem;transition:color .2s;"
       onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#7a7a8a'">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
<?php else: ?>
  <a href="login.php" class="btn btn-reserve">Log in</a>
<?php endif; ?>
    </div>
  </div>
</nav>


<div class="tempo-bg position-relative">
    <div class="tempo-overlay"></div>
</div>


<footer class="footer">
    <div class="container">
        <div class="row">
            <!-- About Section with Logo -->
            <div class="col-md-4 mb-4 text-center text-md-start">
                <div class="footer-logo-text mb-2">ITPH</div>
                <hr class="footer-divider">
                <p class="footer-about-text">
                    Bringing quality living closer to your future.
                    Iloilo Top Property Homes presents beautiful houses within well-planned subdivisions in Iloilo, providing a safe environment and modern living for homeowners
                </p>
            </div>
            <!-- Quick Links -->
            <div class="col-md-2 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../user_side/about_us.php">About Us</a></li>
                    <li><a href="../user_side/news.php">Latest News</a></li>
                    <li><a href="../user_side/vlogs.php">Vlogs</a></li>
                </ul>
            </div>

            <!-- Properties -->
            <div class="col-md-2 mb-4">
                <h6>Properties</h6>
                <ul class="list-unstyled">
                <li><a href="user_side/monticello.php">Monticello</a></li>
                
                    <li><a href="user_side/amani.php">Amani Homes</a></li>
                </ul>
            </div>

            <!-- Tools / Extras -->
            <div class="col-md-4 mb-4">
                <h6>Tools</h6>
                <ul class="list-unstyled">
                    <li><a href="../user_side/contact_us.php">Contact Us</a></li>
                    <li><a href="../user_side/reservation.php">Reserve Now</a></li>
                </ul>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="#top" class="back-to-top">
                    <span class="arrow">➤</span> Back to Top
                </a>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="row mt-3">
            <div class="col-12 text-center footer-contact">
                <span><i class="bi bi-geo-alt-fill"></i> Pavia, Iloilo City</span>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span><i class="bi bi-envelope-fill"></i> ITPH.com</span>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span><i class="bi bi-telephone-fill"></i> (+63) 912 345 6789</span>
            </div>
        </div>

        <!-- Social Icons -->
        <div class="row mt-2">
            <div class="col-12 text-center footer-social">
              <a href="#"><i class="bi bi-facebook"></i></a>
               
                <a href="#"><i class="bi bi-instagram"></i></a>
                
                <a href="#"><i class="bi bi-tiktok"></i></i></a>
            </div>
        </div>

        <hr class="footer-bottom-divider">

        <!-- Bottom Footer -->
        <div class="row">
            <div class="col-12 text-center bottom-footer">
                © 2026 IloIlo Top Property Homes. All rights reserved. 
                <a href="#">Privacy Policy</a> | 
                <a href="#">Terms and Conditions</a>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once:true });

// Scroll effect: hide top contact
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    const topContact = document.querySelector('.top-contact');
    if(window.scrollY > 50){ 
        navbar.classList.add('scrolled'); 
        topContact.classList.add('hidden'); 
    } else { 
        navbar.classList.remove('scrolled'); 
        topContact.classList.remove('hidden'); 
    }
});

// Chat Window Toggle
function toggleChat() {
    const chatWin = document.getElementById('chat-window');
    chatWin.style.display = (chatWin.style.display === 'flex') ? 'none' : 'flex';
}

// Send Message to PHP Backend
async function sendChat() {
    const inputField = document.getElementById('user-input-field');
    const chatMessages = document.getElementById('chat-messages');
    const message = inputField.value.trim();

    if (!message) return;

    // Display User Message
    chatMessages.innerHTML += `<div class="msg msg-user">${message}</div>`;
    inputField.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Display Loading Placeholder
    const loadingId = "loading-" + Date.now();
    chatMessages.innerHTML += `<div class="msg msg-bot" id="${loadingId}">Typing...</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;

    try {
        const formData = new FormData();
        formData.append('message', message);

        const response = await fetch('../backends/chat.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        document.getElementById(loadingId).innerText = data.reply;
    } catch (error) {
        document.getElementById(loadingId).innerText = "I'm sorry, I'm having trouble connecting. Please try again later.";
    }
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
</script>

</body>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/16/02/20260516020411-RS0TP9AJ.js" defer></script>
</html>