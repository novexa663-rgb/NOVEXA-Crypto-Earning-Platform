<?php
require_once 'config.php';
if(isLoggedIn() && !isset($_GET['ref'])) { header('Location: dashboard.php'); exit; }
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim(($_POST['country_code'] ?? '') . ($_POST['mobile'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $sponsorCode = trim($_POST['sponsor_id'] ?? '');
    $wallet = trim($_POST['wallet_address'] ?? '');
    $walletSol = trim($_POST['wallet_sol'] ?? '');
    $walletBnb = trim($_POST['wallet_bnb'] ?? '');
    $position = $_POST['position'] ?? 'left';
    if($password !== $confirm) { $error = 'Passwords do not match'; }
    elseif(strlen($password) < 6) { $error = 'Password must be at least 6 characters'; }
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) { $error = 'Email already exists'; }
        else {
            $sponsorId = null;
            if($sponsorCode) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$sponsorCode]);
                $sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
                if($sponsor) { $sponsorId = $sponsor['id']; }
                else { $error = 'Invalid referral code'; }
            }
            if(!$error) {
                $username = 'NX' . mt_rand(100000, 999999);
                $refCode = generateReferralCode();
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, mobile, password, sponsor_id, referral_code, wallet_address_usdt, wallet_address_sol, wallet_address_bnb, binary_position) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$fullname, $username, $email, $mobile, $hashedPass, $sponsorId, $refCode, $wallet, $walletSol, $walletBnb, $position]);
                $newUserId = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO wallet_balances (user_id) VALUES (?)")->execute([$newUserId]);
                $pdo->prepare("INSERT INTO binary_tree (user_id, parent_id, position) VALUES (?,?,?)")->execute([$newUserId, $sponsorId, $position]);
                if($sponsorId) {
                    $pdo->prepare("INSERT INTO referrals (sponsor_id, referred_id) VALUES (?,?)")->execute([$sponsorId, $newUserId]);
                    addNotification($sponsorId, 'New Referral!', "$fullname joined using your referral code.", 'income');
                }
                $_SESSION['reg_success'] = true;
                $_SESSION['reg_name'] = $fullname;
                $_SESSION['reg_username'] = $username;
                $_SESSION['reg_refcode'] = $refCode;
                $_SESSION['reg_email'] = $email;
                header('Location: register.php'); exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta property="og:title" content="NOVEXA"/>
<meta property="og:image" content="https://novexa.org.in/share-card.jpg"/>
<meta property="og:image:width" content="1200"/>
<meta property="og:image:height" content="630"/>
<meta property="og:type" content="website"/>
<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:image" content="https://novexa.org.in/share-card.jpg"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Register — NOVEXA</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<style>
:root{--v:#7C3AED;--v2:#A855F7;--pink:#EC4899;--bg:#020008;--border:rgba(168,85,247,.15);--text:#9CA3AF;--white:#F9FAFB;--green:#10B981;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.auth-card{width:100%;max-width:480px;background:rgba(255,255,255,.02);border:1px solid var(--border);padding:40px;position:relative;overflow:hidden;backdrop-filter:blur(16px);animation:authIn .6s ease;}
.auth-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--v),var(--pink),var(--v2));}
@keyframes authIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.logo{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;letter-spacing:5px;text-align:center;background:linear-gradient(135deg,var(--v2),var(--pink));-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:8px;}
.tagline{text-align:center;font-family:'JetBrains Mono',monospace;font-size:.6rem;letter-spacing:3px;color:var(--text);margin-bottom:32px;}
.form-group{margin-bottom:14px;}
.form-label{display:block;font-family:'JetBrains Mono',monospace;font-size:.62rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--text);margin-bottom:5px;}
.form-input{width:100%;padding:12px 16px;background:rgba(255,255,255,.03);border:1px solid var(--border);color:var(--white);font-family:'DM Sans',sans-serif;font-size:.86rem;outline:none;transition:all .3s;}
.form-input:focus{border-color:var(--v2);box-shadow:0 0 0 3px rgba(168,85,247,.1);}
.form-input::placeholder{color:rgba(156,163,175,.3);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,var(--v),var(--v2));border:none;color:#fff;font-family:'Syne',sans-serif;font-size:.85rem;font-weight:700;letter-spacing:2px;cursor:pointer;clip-path:polygon(8px 0,100% 0,calc(100% - 8px) 100%,0 100%);margin-top:8px;transition:all .3s;position:relative;overflow:hidden;}
.btn:hover{filter:brightness(1.15);transform:translateY(-1px);box-shadow:0 8px 24px rgba(124,58,237,.2);}
.btn::after{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(45deg,transparent 40%,rgba(255,255,255,.08) 50%,transparent 60%);animation:shine 3s ease infinite;}
@keyframes shine{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.links{text-align:center;margin-top:18px;font-size:.82rem;}
.links a{color:var(--v2);text-decoration:none;}
.links a:hover{color:var(--pink);}
.error{background:rgba(239,68,68,.08);border-left:3px solid #EF4444;padding:12px;color:#EF4444;font-size:.83rem;margin-bottom:16px;}
.glow{position:fixed;width:300px;height:300px;border-radius:50%;pointer-events:none;}
::selection{background:rgba(168,85,247,.3);}
::-webkit-scrollbar{width:3px;}
::-webkit-scrollbar-thumb{background:linear-gradient(var(--v),var(--pink));}
@media(max-width:500px){.grid-2{grid-template-columns:1fr;}.auth-card{padding:28px 20px;margin:0 10px;}.form-input{font-size:.84rem;padding:11px 14px;}}
</style>
</head>
<body>
<div class="glow" style="top:10%;left:5%;background:radial-gradient(circle,rgba(124,58,237,.08),transparent 70%);"></div>
<div class="glow" style="bottom:10%;right:5%;background:radial-gradient(circle,rgba(236,72,153,.06),transparent 70%);"></div>

<div class="auth-card">
  <div class="logo">NOVEXA</div>
  <div class="tagline">&#9670; CREATE YOUR ACCOUNT &#9670;</div>

  <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input type="text" name="full_name" class="form-input" placeholder="Enter full name" required/>
    </div>
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="you@email.com" required/>
    </div>
    <div class="form-group">
      <label class="form-label">Mobile Number</label>
      <div style="display:flex;gap:0;">
        <select name="country" id="countrySelect" onchange="var c=this.options[this.selectedIndex].getAttribute('data-code')||'';document.getElementById('codeDisplay').textContent=c;document.getElementById('countryCodeHidden').value=c;" style="flex:1;padding:12px 10px;background:rgba(255,255,255,.03);border:1px solid rgba(168,85,247,.15);color:#F9FAFB;font-size:.82rem;font-family:'DM Sans',sans-serif;outline:none;border-right:none;">
          <option value="" data-code="">&#8212; Select Country &#8212;</option>
          <option value="AU" data-code="+61">Australia</option>
          <option value="BD" data-code="+880">Bangladesh</option>
          <option value="BR" data-code="+55">Brazil</option>
          <option value="CA" data-code="+1">Canada</option>
          <option value="CN" data-code="+86">China</option>
          <option value="EG" data-code="+20">Egypt</option>
          <option value="FR" data-code="+33">France</option>
          <option value="DE" data-code="+49">Germany</option>
          <option value="GH" data-code="+233">Ghana</option>
          <option value="IN" data-code="+91">India</option>
          <option value="ID" data-code="+62">Indonesia</option>
          <option value="IQ" data-code="+964">Iraq</option>
          <option value="JP" data-code="+81">Japan</option>
          <option value="KE" data-code="+254">Kenya</option>
          <option value="KW" data-code="+965">Kuwait</option>
          <option value="MY" data-code="+60">Malaysia</option>
          <option value="MX" data-code="+52">Mexico</option>
          <option value="NP" data-code="+977">Nepal</option>
          <option value="NG" data-code="+234">Nigeria</option>
          <option value="OM" data-code="+968">Oman</option>
          <option value="PK" data-code="+92">Pakistan</option>
          <option value="PH" data-code="+63">Philippines</option>
          <option value="QA" data-code="+974">Qatar</option>
          <option value="RU" data-code="+7">Russia</option>
          <option value="SA" data-code="+966">Saudi Arabia</option>
          <option value="SG" data-code="+65">Singapore</option>
          <option value="ZA" data-code="+27">South Africa</option>
          <option value="KR" data-code="+82">South Korea</option>
          <option value="LK" data-code="+94">Sri Lanka</option>
          <option value="TH" data-code="+66">Thailand</option>
          <option value="TR" data-code="+90">Turkey</option>
          <option value="AE" data-code="+971">UAE</option>
          <option value="GB" data-code="+44">United Kingdom</option>
          <option value="US" data-code="+1">United States</option>
          <option value="VN" data-code="+84">Vietnam</option>
        </select>
        <span id="codeDisplay" style="padding:12px 10px;background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.15);color:#A855F7;font-size:.82rem;font-weight:700;font-family:'JetBrains Mono',monospace;min-width:55px;text-align:center;border-right:none;display:flex;align-items:center;justify-content:center;"></span>
        <input type="hidden" name="country_code" id="countryCodeHidden"/>
        <input type="text" name="mobile" class="form-input" placeholder="Number" style="border-left:none;flex:1;"/>
      </div>
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="Min 6 characters" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div style="position:relative;">
          <input type="password" name="confirm_password" id="regConf" class="form-input" placeholder="Confirm" required style="padding-right:50px;"/>
          <button type="button" onclick="var p=document.getElementById('regConf');if(p.type==='password'){p.type='text';this.textContent='HIDE';}else{p.type='password';this.textContent='SHOW';}" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#A855F7;font-family:'JetBrains Mono',monospace;font-size:.6rem;font-weight:700;letter-spacing:1px;cursor:pointer;">SHOW</button>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Sponsor / Referral Code</label>
      <input type="text" name="sponsor_id" class="form-input" placeholder="e.g. NVX3A8F2B (optional)" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>"/>
    </div>
    <div class="form-group">
      <label class="form-label">Position (Binary Tree)</label>
      <div style="display:flex;gap:12px;">
        <label style="flex:1;display:flex;align-items:center;gap:8px;padding:12px 16px;background:rgba(255,255,255,.03);border:1px solid rgba(168,85,247,.15);cursor:pointer;transition:all .3s;" id="posLeft" onclick="selectPos('left')">
          <input type="radio" name="position" value="left" <?= ($_GET['pos'] ?? '') === 'left' ? 'checked' : '' ?> style="accent-color:#A855F7;"/>
          <span style="color:#F9FAFB;font-size:.88rem;">Left</span>
        </label>
        <label style="flex:1;display:flex;align-items:center;gap:8px;padding:12px 16px;background:rgba(255,255,255,.03);border:1px solid rgba(168,85,247,.15);cursor:pointer;transition:all .3s;" id="posRight" onclick="selectPos('right')">
          <input type="radio" name="position" value="right" <?= ($_GET['pos'] ?? '') === 'right' ? 'checked' : '' ?> style="accent-color:#EC4899;"/>
          <span style="color:#F9FAFB;font-size:.88rem;">Right</span>
        </label>
      </div>
      <script>
      function selectPos(p){
        document.getElementById('posLeft').style.borderColor=p==='left'?'#A855F7':'rgba(168,85,247,.15)';
        document.getElementById('posLeft').style.background=p==='left'?'rgba(168,85,247,.1)':'rgba(255,255,255,.03)';
        document.getElementById('posRight').style.borderColor=p==='right'?'#EC4899':'rgba(168,85,247,.15)';
        document.getElementById('posRight').style.background=p==='right'?'rgba(236,72,153,.1)':'rgba(255,255,255,.03)';
      }
      if(document.querySelector('input[name=position]:checked')){selectPos(document.querySelector('input[name=position]:checked').value);}
      </script>
    </div>
    <div class="form-group">
      <label class="form-label">Wallet Address (USDT BEP20)</label>
      <input type="text" name="wallet_address" class="form-input" placeholder="Your USDT BEP20 wallet address"/>
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Wallet Address (SOL)</label>
        <input type="text" name="wallet_sol" class="form-input" placeholder="Your Solana wallet address"/>
      </div>
      <div class="form-group">
        <label class="form-label">Wallet Address (BNB)</label>
        <input type="text" name="wallet_bnb" class="form-input" placeholder="Your BNB wallet address"/>
      </div>
    </div>
    <div class="form-group" style="margin-top:4px;">
      <label style="color:#9CA3AF;font-size:.82rem;display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" name="accept_policy" required style="accent-color:#A855F7;width:16px;height:16px;"/>
        I accept the <a href="#" style="color:#A855F7;">Privacy Policy</a> &amp; Terms
      </label>
    </div>
    <button type="submit" class="btn">CREATE ACCOUNT &#8594;</button>
  </form>
  <div class="links">Already have an account? <a href="login.php">Login &#8594;</a></div>
</div>

<?php if(isset($_SESSION['reg_success']) && $_SESSION['reg_success']): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="background:linear-gradient(180deg,#0D0025,#020008);border:1px solid rgba(168,85,247,.3);max-width:440px;width:100%;padding:40px 32px;text-align:center;position:relative;overflow:hidden;">
    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#7C3AED,#EC4899,#F59E0B);"></div>
    <div style="font-size:3rem;margin-bottom:16px;">&#127881;</div>
    <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:900;color:#F9FAFB;letter-spacing:2px;margin-bottom:8px;">CONGRATULATIONS!</h1>
    <p style="color:#A855F7;font-size:.9rem;font-weight:600;margin-bottom:24px;">Your Registration is Successful</p>
    <div style="background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.15);padding:20px;text-align:left;margin-bottom:24px;">
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;font-size:.8rem;">Name</span><span style="color:#F9FAFB;font-size:.8rem;font-weight:600;"><?= htmlspecialchars($_SESSION['reg_name']) ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;font-size:.8rem;">User ID</span><span style="color:#F9FAFB;font-size:.8rem;font-weight:600;"><?= $_SESSION['reg_username'] ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;font-size:.8rem;">Referral Code</span><span style="color:#EC4899;font-size:.8rem;font-weight:600;"><?= $_SESSION['reg_refcode'] ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;"><span style="color:#9CA3AF;font-size:.8rem;">Email</span><span style="color:#F9FAFB;font-size:.8rem;font-weight:600;"><?= htmlspecialchars($_SESSION['reg_email']) ?></span></div>
    </div>
    <a href="login.php" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;font-family:'Syne',sans-serif;font-size:.85rem;font-weight:700;letter-spacing:2px;text-decoration:none;clip-path:polygon(8px 0,100% 0,calc(100% - 8px) 100%,0 100%);">LOGIN NOW &#8594;</a>
    <p style="color:rgba(156,163,175,.3);font-size:.65rem;margin-top:20px;letter-spacing:2px;">NOVEXA &#8212; BEYOND THE HORIZON</p>
  </div>
</div>
<?php
unset($_SESSION['reg_success'], $_SESSION['reg_name'], $_SESSION['reg_username'], $_SESSION['reg_refcode'], $_SESSION['reg_email']);
endif; ?>
</body>
</html>
