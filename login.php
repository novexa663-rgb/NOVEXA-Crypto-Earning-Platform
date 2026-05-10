<?php
require_once 'config.php';
if(isLoggedIn()) { header('Location: dashboard.php'); exit; }
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username/email or password';
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
<title>Login &#8212; NOVEXA</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#000;background:radial-gradient(ellipse at 50% 30%,#1a0035 0%,#0a0018 50%,#000 100%);color:#9CA3AF;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;overflow-x:hidden;}
canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
.auth-card{position:relative;z-index:2;width:100%;max-width:420px;background:rgba(10,0,24,.85);border:1px solid rgba(168,85,247,.2);padding:48px 36px;backdrop-filter:blur(20px);box-shadow:0 24px 64px rgba(124,58,237,.1),0 0 1px rgba(168,85,247,.3);animation:cardIn .7s cubic-bezier(0.34,1.56,0.64,1) both;overflow:hidden;}
.auth-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#ff00cc,#ff4d6d,#ff9900,#ffee00,#00ff99,#00ccff,#ff00cc);background-size:200% auto;animation:rainbowLine 3s linear infinite;}
.auth-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(168,85,247,.3),transparent);}
@keyframes cardIn{from{opacity:0;transform:translateY(30px) scale(.97)}to{opacity:1;transform:none}}
@keyframes rainbowLine{0%{background-position:0% center}100%{background-position:200% center}}
.logo{font-family:'Syne',sans-serif;font-size:2rem;font-weight:900;letter-spacing:6px;text-align:center;background:linear-gradient(90deg,#ff00cc,#ff4d6d,#ff9900,#ffee00,#00ff99,#00ccff,#ff00cc);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:rainbowLine 3s linear infinite;filter:drop-shadow(0 0 20px rgba(255,0,204,.4));margin-bottom:6px;}
.tagline{text-align:center;font-family:'JetBrains Mono',monospace;font-size:.55rem;letter-spacing:4px;color:rgba(168,85,247,.5);margin-bottom:8px;text-transform:uppercase;}
.divider{width:60px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,0,204,.4),rgba(0,204,255,.4),transparent);margin:0 auto 32px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-family:'JetBrains Mono',monospace;font-size:.6rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#9CA3AF;margin-bottom:6px;}
.form-input{width:100%;padding:13px 16px;background:rgba(255,255,255,.04);border:1px solid rgba(168,85,247,.15);color:#F9FAFB;font-family:'DM Sans',sans-serif;font-size:.88rem;outline:none;transition:all .3s;}
.form-input:focus{border-color:#A855F7;box-shadow:0 0 0 3px rgba(168,85,247,.08);background:rgba(168,85,247,.04);}
.form-input::placeholder{color:rgba(156,163,175,.25);}
.pwd-wrap{position:relative;}
.pwd-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#A855F7;font-family:'JetBrains Mono',monospace;font-size:.55rem;font-weight:700;letter-spacing:1px;cursor:pointer;transition:color .2s;}
.pwd-toggle:hover{color:#EC4899;}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#7C3AED,#A855F7);border:none;color:#fff;font-family:'Syne',sans-serif;font-size:.85rem;font-weight:700;letter-spacing:2px;cursor:pointer;clip-path:polygon(8px 0,100% 0,calc(100% - 8px) 100%,0 100%);margin-top:8px;transition:all .3s;position:relative;overflow:hidden;}
.btn:hover{filter:brightness(1.15);transform:translateY(-2px);box-shadow:0 12px 32px rgba(124,58,237,.3);}
.btn::after{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(45deg,transparent 40%,rgba(255,255,255,.08) 50%,transparent 60%);animation:shine 3s ease infinite;}
@keyframes shine{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.links{display:flex;justify-content:space-between;margin-top:20px;font-size:.82rem;}
.links a{color:#A855F7;text-decoration:none;transition:color .2s;}
.links a:hover{color:#EC4899;}
.error{background:rgba(239,68,68,.08);border-left:3px solid #EF4444;padding:12px;color:#EF4444;font-size:.83rem;margin-bottom:18px;animation:shake .4s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
::selection{background:rgba(168,85,247,.3);}
::-webkit-scrollbar{width:3px;}
::-webkit-scrollbar-thumb{background:linear-gradient(#7C3AED,#EC4899);}
@media(max-width:500px){
  .auth-card{padding:36px 24px;margin:0 8px;max-width:100%;}
  .form-input{font-size:.84rem;padding:12px 14px;}
  .logo{font-size:1.6rem;letter-spacing:4px;}
  .btn{font-size:.8rem;}
}
</style>
</head>
<body>
<canvas id="c"></canvas>
<div class="auth-card">
  <div class="logo">NOVEXA</div>
  <div class="tagline">&#10022; beyond the horizon &#10022;</div>
  <div class="divider"></div>
  <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Username or Email</label>
      <input type="text" name="username" class="form-input" placeholder="Enter username or email" required/>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="pwd-wrap">
        <input type="password" name="password" id="loginPwd" class="form-input" placeholder="Enter password" required style="padding-right:50px;"/>
        <button type="button" class="pwd-toggle" onclick="var p=document.getElementById('loginPwd');if(p.type==='password'){p.type='text';this.textContent='HIDE';}else{p.type='password';this.textContent='SHOW';}">SHOW</button>
      </div>
    </div>
    <button type="submit" class="btn">LOGIN &#8594;</button>
  </form>
  <div class="links">
    <a href="forgot_password.php">Forgot Password?</a>
    <a href="register.php">Create Account &#8594;</a>
  </div>
</div>
<script>
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
