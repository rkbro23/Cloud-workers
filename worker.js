export default {
  async fetch(request) {
    const url = new URL(request.url);

    // ✅ M3U (FAST STREAM)
    if (url.pathname === "/sports") {
      const res = await fetch("https://pub-2df68c4371434fb1ac616d7b3241d950.r2.dev/sports.m3u");

      return new Response(res.body, {
        headers: {
          "Content-Type": "application/vnd.apple.mpegurl",
          "Cache-Control": "public, max-age=3600"
        }
      });
    }

    // 😈 BASE URL (TROLL HTML PAGE)
    if (url.pathname === "/") {
      return new Response(`
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RK IPTV | Nice Try 😂</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    background: #0f0c29;
    background: linear-gradient(to right, #24243e, #302b63, #0f0c29);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    overflow: hidden;
  }

  /* Tap to Enter Overlay */
  #overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    cursor: pointer;
    transition: opacity 0.5s ease;
  }

  #overlay h2 {
    color: #ff3366;
    font-size: 2.2rem;
    animation: pulse 1.5s infinite;
  }

  #overlay p {
    margin-top: 10px;
    font-size: 16px;
    color: #00ffcc;
  }

  /* Main Glassmorphism Container */
  .container {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 40px 30px;
    border-radius: 20px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    max-width: 420px;
    width: 90%;
    display: none; /* Hidden till user clicks overlay */
    animation: fadeIn 1s ease-in-out;
  }

  img {
    width: 130px;
    height: 130px;
    border-radius: 50%; /* Circle Image */
    object-fit: cover;
    box-shadow: 0 0 30px rgba(0, 255, 204, 0.6);
    margin-bottom: 20px;
    border: 4px solid #00ffcc;
    animation: float 3s ease-in-out infinite;
  }

  h1 {
    font-size: 22px;
    font-weight: 800;
    color: #00ffcc;
    margin-bottom: 12px;
    text-shadow: 0 0 10px rgba(0, 255, 204, 0.3);
  }

  p {
    font-size: 15px;
    color: #d1d1d1;
    margin-bottom: 30px;
    line-height: 1.5;
  }

  .btn {
    display: inline-block;
    padding: 14px 35px;
    background: linear-gradient(45deg, #ff3366, #ff6699);
    color: #fff;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 800;
    font-size: 16px;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 51, 102, 0.4);
  }

  .btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 25px rgba(255, 51, 102, 0.7);
  }

  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }

  @keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
  }
</style>
</head>

<body>

<div id="overlay" onclick="startExperience()">
  <h2>Aagaye Playlist Churaane? 🕵️‍♂️</h2>
  <p>👆 Tap to prove you are a 'Hacker'</p>
</div>

<div class="container" id="main-content">
  <img src="https://i.ibb.co/Df9JDrhr/IMG-20260328-035948-914.jpg" alt="logo">
  
  <h1>Beta, Tumse Na Ho Payega! 🤡</h1>
  <p>Inspect Element kholne se koi Coder nahi ban jata lala.<br>M3U link dhoondhte dhoondhte zindagi nikal jayegi. Chup-chaap Telegram join kar lo!</p>
  
  <a class="btn" href="https://t.me/iptvm3y">ASLI JUGGAAD YAHAN HAI 🚀</a>
</div>

<audio id="bg-audio" loop>
  <source src="https://pub-2df68c4371434fb1ac616d7b3241d950.r2.dev/chup.mp3" type="audio/mpeg">
</audio>

<script>
  function startExperience() {
    // Play the troll audio
    const audio = document.getElementById("bg-audio");
    audio.play();
    
    // Hide overlay & Show content smoothly
    const overlay = document.getElementById("overlay");
    const mainContent = document.getElementById("main-content");
    
    overlay.style.opacity = '0';
    setTimeout(() => {
      overlay.style.display = 'none';
      mainContent.style.display = 'block';
    }, 500);

    // Stop devs from right-clicking (Thoda aur pareshan karne ke liye)
    document.addEventListener('contextmenu', event => event.preventDefault());
  }
</script>

</body>
</html>
      `, {
        headers: { "Content-Type": "text/html" }
      });
    }

    return new Response("Access Denied", { status: 403 });
  }
};
