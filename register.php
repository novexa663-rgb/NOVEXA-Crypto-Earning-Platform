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
<title>Register &#8212; NOVEXA</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#000;background:radial-gradient(ellipse at 50% 30%,#1a0035 0%,#0a0018 50%,#000 100%);color:#9CA3AF;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;overflow-x:hidden;}
canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
.auth-card{position:relative;z-index:2;width:100%;max-width:460px;background:rgba(10,0,24,.85);border:1px solid rgba(168,85,247,.2);padding:36px 32px;backdrop-filter:blur(20px);box-shadow:0 24px 64px rgba(124,58,237,.1),0 0 1px rgba(168,85,247,.3);animation:cardIn .7s cubic-bezier(0.34,1.56,0.64,1) both;overflow:hidden;}
.auth-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#ff00cc,#ff4d6d,#ff9900,#ffee00,#00ff99,#00ccff,#ff00cc);background-size:200% auto;animation:rainbowLine 3s linear infinite;}
.auth-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.3),transparent);}
@keyframes cardIn{from{opacity:0;transform:translateY(30px) scale(.97)}to{opacity:1;transform:none}}
@keyframes rainbowLine{0%{background-position:0% center}100%{background-position:200% center}}
.logo{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:900;letter-spacing:6px;text-align:center;background:linear-gradient(90deg,#ff00cc,#ff4d6d,#ff9900,#ffee00,#00ff99,#00ccff,#ff00cc);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:rainbowLine 3s linear infinite;filter:drop-shadow(0 0 20px rgba(255,0,204,.4));margin-bottom:6px;}
.tagline{text-align:center;font-family:'JetBrains Mono',monospace;font-size:.55rem;letter-spacing:4px;color:rgba(168,85,247,.5);margin-bottom:28px;text-transform:uppercase;}
.divider{width:60px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,0,204,.4),rgba(0,204,255,.4),transparent);margin:0 auto 24px;}
.section-label{font-family:'JetBrains Mono',monospace;font-size:.55rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(168,85,247,.4);margin:20px 0 12px;padding-left:2px;}
.form-group{margin-bottom:12px;}
.form-label{display:block;font-family:'JetBrains Mono',monospace;font-size:.58rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9CA3AF;margin-bottom:5px;}
.form-input{width:100%;padding:11px 14px;background:rgba(255,255,255,.04);border:1px solid rgba(168,85,247,.15);color:#F9FAFB;font-family:'DM Sans',sans-serif;font-size:.84rem;outline:none;transition:all .3s;}
.form-input:focus{border-color:#A855F7;box-shadow:0 0 0 3px rgba(168,85,247,.08);background:rgba(168,85,247,.04);}
.form-input::placeholder{color:rgba(156,163,175,.25);}
select.form-input{cursor:pointer;appearance:none;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#7C3AED,#A855F7);border:none;color:#fff;font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;letter-spacing:2px;cursor:pointer;clip-path:polygon(8px 0,100% 0,calc(100% - 8px) 100%,0 100%);margin-top:12px;transition:all .3s;position:relative;overflow:hidden;}
.btn:hover{filter:brightness(1.15);transform:translateY(-2px);box-shadow:0 12px 32px rgba(124,58,237,.3);}
.btn::after{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(45deg,transparent 40%,rgba(255,255,255,.08) 50%,transparent 60%);animation:shine 3s ease infinite;}
@keyframes shine{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.links{text-align:center;margin-top:16px;font-size:.8rem;}
.links a{color:#A855F7;text-decoration:none;transition:color .2s;}
.links a:hover{color:#EC4899;}
.error{background:rgba(239,68,68,.08);border-left:3px solid #EF4444;padding:10px 14px;color:#EF4444;font-size:.8rem;margin-bottom:14px;animation:shake .4s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
.mobile-row{display:flex;gap:0;}
.mobile-row select{flex:1.2;border-right:none;font-size:.78rem;padding:11px 8px;}
.mobile-row .code-box{padding:11px 8px;background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.15);color:#A855F7;font-size:.78rem;font-weight:700;font-family:'JetBrains Mono',monospace;min-width:50px;text-align:center;display:flex;align-items:center;justify-content:center;border-left:none;border-right:none;}
.mobile-row input{flex:1;border-left:none;}
.pos-row{display:flex;gap:10px;}
.pos-label{flex:1;display:flex;align-items:center;gap:8px;padding:11px 14px;background:rgba(255,255,255,.03);border:1px solid rgba(168,85,247,.12);cursor:pointer;transition:all .3s;}
.pos-label:hover{border-color:rgba(168,85,247,.3);}
.pos-label.active-left{border-color:#A855F7;background:rgba(168,85,247,.08);}
.pos-label.active-right{border-color:#EC4899;background:rgba(236,72,153,.08);}
.pos-label span{color:#F9FAFB;font-size:.84rem;}
.policy{color:#9CA3AF;font-size:.78rem;display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:4px;}
.policy a{color:#A855F7;}
.pwd-wrap{position:relative;}
.pwd-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#A855F7;font-family:'JetBrains Mono',monospace;font-size:.55rem;font-weight:700;letter-spacing:1px;cursor:pointer;transition:color .2s;}
.pwd-toggle:hover{color:#EC4899;}
::selection{background:rgba(168,85,247,.3);}
::-webkit-scrollbar{width:3px;}
::-webkit-scrollbar-thumb{background:linear-gradient(#7C3AED,#EC4899);}
@media(max-width:500px){
  body{padding:12px;align-items:flex-start;padding-top:20px;}
  .grid-2{grid-template-columns:1fr;}
  .auth-card{padding:24px 18px;margin:0;max-width:100%;}
  .form-input{font-size:.82rem;padding:10px 12px;}
  .logo{font-size:1.4rem;letter-spacing:4px;}
  .tagline{font-size:.5rem;letter-spacing:3px;margin-bottom:20px;}
  .section-label{font-size:.5rem;margin:16px 0 10px;}
  .form-label{font-size:.55rem;}
  .mobile-row select{font-size:.72rem;padding:10px 6px;flex:1;}
  .mobile-row .code-box{min-width:45px;font-size:.72rem;padding:10px 6px;}
  .mobile-row input{font-size:.78rem;}
  .pos-row{gap:8px;}
  .pos-label{padding:10px 12px;}
  .pos-label span{font-size:.8rem;}
  .btn{padding:12px;font-size:.78rem;}
  .links{font-size:.76rem;}
  .divider{margin-bottom:18px;}
}
@media(max-width:360px){
  .auth-card{padding:20px 14px;}
  .logo{font-size:1.2rem;letter-spacing:3px;}
  .mobile-row select{font-size:.68rem;}
}
</style>
</head>
<body>
<canvas id="c"></canvas>
<div class="auth-card">
  <div class="logo">NOVEXA</div>
  <div class="tagline">&#10022; create your account &#10022;</div>
  <div class="divider"></div>
  <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <div class="section-label">&#9670; Personal Info</div>
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required/>
    </div>
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="you@email.com" required/>
    </div>
    <div class="form-group">
      <label class="form-label">Mobile Number</label>
      <div class="mobile-row">
        <select name="country" id="countrySelect" class="form-input" onchange="var c=this.options[this.selectedIndex].getAttribute('data-code')||'';document.getElementById('codeBox').textContent=c;document.getElementById('codeHidden').value=c;">
          <option value="" data-code="">Select Country</option>
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
        <span class="code-box" id="codeBox"></span>
        <input type="hidden" name="country_code" id="codeHidden"/>
        <input type="text" name="mobile" class="form-input" placeholder="Number"/>
      </div>
    </div>
    <div class="section-label">&#9670; Security</div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="Min 6 chars" required/>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="pwd-wrap">
          <input type="password" name="confirm_password" id="regConf" class="form-input" placeholder="Confirm" required style="padding-right:50px;"/>
          <button type="button" class="pwd-toggle" onclick="var p=document.getElementById('regConf');if(p.type==='password'){p.type='text';this.textContent='HIDE';}else{p.type='password';this.textContent='SHOW';}">SHOW</button>
        </div>
      </div>
    </div>
    <div class="section-label">&#9670; Referral</div>
    <div class="form-group">
      <label class="form-label">Sponsor / Referral Code</label>
      <input type="text" name="sponsor_id" class="form-input" placeholder="e.g. NVX3A8F2B (optional)" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>"/>
    </div>
    <div class="form-group">
      <label class="form-label">Position (Binary Tree)</label>
      <div class="pos-row">
        <label class="pos-label" id="posLeft" onclick="selectPos('left')">
          <input type="radio" name="position" value="left" <?= ($_GET['pos'] ?? '') === 'left' ? 'checked' : '' ?> style="accent-color:#A855F7;"/>
          <span>&#9664; Left</span>
        </label>
        <label class="pos-label" id="posRight" onclick="selectPos('right')">
          <input type="radio" name="position" value="right" <?= ($_GET['pos'] ?? '') === 'right' ? 'checked' : '' ?> style="accent-color:#EC4899;"/>
          <span>Right &#9654;</span>
        </label>
      </div>
    </div>
    <div class="section-label">&#9670; Wallet Addresses</div>
    <div class="form-group">
      <label class="form-label">USDT (BEP20)</label>
      <input type="text" name="wallet_address" class="form-input" placeholder="Your USDT BEP20 address"/>
    </div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">SOL (Solana)</label>
        <input type="text" name="wallet_sol" class="form-input" placeholder="Solana address"/>
      </div>
      <div class="form-group">
        <label class="form-label">BNB (BEP20)</label>
        <input type="text" name="wallet_bnb" class="form-input" placeholder="BNB address"/>
      </div>
    </div>
    <div class="form-group" style="margin-top:8px;">
      <label class="policy">
        <input type="checkbox" name="accept_policy" required style="accent-color:#A855F7;width:15px;height:15px;"/>
        I accept the <a href="#">Privacy Policy</a> &amp; Terms
      </label>
    </div>
    <button type="submit" class="btn">CREATE ACCOUNT &#8594;</button>
  </form>
  <div class="links">Already have an account? <a href="login.php">Login &#8594;</a></div>
</div>
<?php if(isset($_SESSION['reg_success']) && $_SESSION['reg_success']): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(10px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="background:linear-gradient(180deg,#0D0025,#020008);border:1px solid rgba(168,85,247,.3);max-width:420px;width:100%;padding:36px 28px;text-align:center;position:relative;overflow:hidden;animation:cardIn .5s ease;">
    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#ff00cc,#ff9900,#00ccff);background-size:200% auto;animation:rainbowLine 3s linear infinite;"></div>
    <div style="font-size:3rem;margin-bottom:14px;">&#127881;</div>
    <h1 style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:900;color:#F9FAFB;letter-spacing:2px;margin-bottom:8px;">CONGRATULATIONS!</h1>
    <p style="color:#A855F7;font-size:.85rem;font-weight:600;margin-bottom:20px;">Registration Successful</p>
    <div style="background:rgba(168,85,247,.06);border:1px solid rgba(168,85,247,.12);padding:16px;text-align:left;margin-bottom:20px;font-size:.8rem;">
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;">Name</span><span style="color:#F9FAFB;font-weight:600;"><?= htmlspecialchars($_SESSION['reg_name']) ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;">User ID</span><span style="color:#F9FAFB;font-weight:600;"><?= $_SESSION['reg_username'] ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(168,85,247,.08);"><span style="color:#9CA3AF;">Referral Code</span><span style="color:#EC4899;font-weight:600;"><?= $_SESSION['reg_refcode'] ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:7px 0;"><span style="color:#9CA3AF;">Email</span><span style="color:#F9FAFB;font-weight:600;"><?= htmlspecialchars($_SESSION['reg_email']) ?></span></div>
    </div>
    <a href="login.php" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;letter-spacing:2px;text-decoration:none;clip-path:polygon(8px 0,100% 0,calc(100% - 8px) 100%,0 100%);">LOGIN NOW &#8594;</a>
    <p style="color:rgba(156,163,175,.25);font-size:.6rem;margin-top:16px;letter-spacing:2px;">NOVEXA &#8212; BEYOND THE HORIZON</p>
  </div>
</div>
<?php unset($_SESSION['reg_success'], $_SESSION['reg_name'], $_SESSION['reg_username'], $_SESSION['reg_refcode'], $_SESSION['reg_email']); endif; ?>
<script>
function selectPos(p){
  document.getElementById('posLeft').className='pos-label'+(p==='left'?' active-left':'');
  document.getElementById('posRight').className='pos-label'+(p==='right'?' active-right':'');
}
if(document.querySelector('input[name=position]:checked')){selectPos(document.querySelector('input[name=position]:checked').value);}
var c=document.getElementById('c'),ctx=c.getContext('2d'),W,H;
function resize(){W=c.width=innerWidth;H=c.height=innerHeight;}
resize();window.addEventListener('resize',resize);
var cols=['#ff00cc','#ff4d6d','#ff9900','#00ff99','#00ccff','#cc00ff','#fff'];
var stars=Array.from({length:100},function(){return{x:Math.random()*2000,y:Math.random()*1200,r:Math.random()*1.4+.2,a:Math.random(),da:(Math.random()-.5)*.012,col:cols[Math.floor(Math.random()*cols.length)]}});
function draw(){ctx.clearRect(0,0,W,H);stars.forEach(function(s){s.a+=s.da;if(s.a<=0||s.a>=1)s.da*=-1;ctx.globalAlpha=Math.max(0,Math.min(1,s.a))*.7;ctx.fillStyle=s.col;ctx.beginPath();ctx.arc(s.x%W,s.y%H,s.r,0,Math.PI*2);ctx.fill();});requestAnimationFrame(draw);}
draw();
</script>
</body>
</html>
