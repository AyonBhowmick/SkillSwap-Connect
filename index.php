<?php
// index.php
?>

<!DOCTYPE html>
<html>

<head>
    <title>SkillSwap - Learn Skills Through Exchange</title>

    <style>
        /* ===== RESET ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
            line-height: 1.6;
        }

        /* ===== HEADER ===== */
        .header {
            background: #fff;
            padding: 15px 30px;
            border-bottom: 1px solid #ddd;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #2c59ff;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav a {
            text-decoration: none;
            color: #222;
            padding: 6px 10px;
            border-radius: 5px;
        }

        .nav a:hover {
            background: #eee;
            color: #2c59ff;
        }

        /* ===== DROPDOWN ===== */
        .demo-dropdown {
            position: relative;
        }

        .demo-btn {
            background: #2c59ff;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        .demo-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            min-width: 200px;
        }

        .demo-dropdown-content a {
            display: block;
            padding: 10px;
            color: #222;
            border-bottom: 1px solid #eee;
            text-decoration: none;
        }

        .demo-dropdown:hover .demo-dropdown-content {
            display: block;
        }

        /* ===== MAIN ===== */
        .main {
            margin-top: 100px;
        }

        /* ===== HERO (FULL WIDTH – DESKTOP FIX) ===== */
        .hero {
            background: #2c59ff;
            color: white;
            padding: 70px 20px;
            text-align: center;
            margin-bottom: 40px;
        }

        .hero h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .hero p {
            font-size: 18px;
        }

        .big-button {
            margin-top: 15px;
            padding: 12px 18px;
            border: none;
            background: white;
            color: #2c59ff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        /* ===== SECTIONS (DESKTOP WIDTH CONTROL) ===== */
        .section {
            max-width: 1200px;
            margin: 0 auto 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
        }

        .section h2 {
            margin-bottom: 15px;
        }

        /* ===== FLEX SECTIONS ===== */
        .steps,
        .features,
        .users {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        /* ===== SOFT CARD LOOK (NO HARD BORDER) ===== */
        .step,
        .feature,
        .user-type {
            background: #fafafa;
            padding: 30px;
            border-radius: 12px;
            flex: 1;
        }

        .step-number {
            background: #2c59ff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-weight: bold;
        }

        /* ===== DEMO LINKS ===== */
        .demo-links {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 15px;
        }

        .demo-link {
            background: #2c59ff;
            color: white;
            text-decoration: none;
            padding: 22px;
            border-radius: 12px;
            display: flex;
            gap: 15px;
        }

        /* ===== LOGIN ===== */
        .login-box {
            max-width: 500px;
            margin: 0 auto;
            background: #fafafa;
            padding: 30px;
            border-radius: 12px;
        }

        .login-box input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .login-btn {
            padding: 10px 16px;
            border: none;
            background: #2c59ff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        .signup-link {
            text-align: center;
            margin-top: 10px;
        }

        .signup-link a {
            color: #2c59ff;
            text-decoration: none;
            font-weight: bold;
        }

        /* ===== QUICK ACCESS ===== */
        .quick-buttons {
            display: flex;
            gap: 15px;
            margin-top: 12px;
        }

        .quick-btn {
            padding: 10px 16px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
        }

        .learner-btn { background: #2c59ff; }
        .mentor-btn { background: #28a745; }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 18px;
            background: white;
            border-top: 1px solid #ddd;
            margin-top: 40px;
        }
        /* Sign Up button style */
.signup-btn {
    background: #2c59ff;
    color: white !important;
    padding: 7px 12px;
    border-radius: 6px;
    font-weight: bold;
}

.signup-btn:hover {
    background: #1f46d8;
}

    </style>
</head>

<body>

    <!-- HEADER -->
<div class="header">
    <div class="logo">SkillSwap</div>
    <div class="nav">
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="#how">How it Works</a>
    <a href="login.php">Login</a>
    <a href="browser.php">Browse Skills</a>


        <!-- NEW Sign Up Button -->
        <a href="signup.php" class="signup-btn">Sign Up</a>
        <!-- <div class="demo-dropdown">
            <button class="demo-btn">View Dashboards →</button>
            <div class="demo-dropdown-content">
                <a href="/PHP/learner.php">Learner Dashboard</a>
                <a href="/PHP/mentor.php"> Mentor Dashboard</a>

                <a href="/HTML/admin_dashboard.html">Admin Dashboard</a> -->
            <!-- </div>  -->
        </div>
    </div>
</div>

    <!-- MAIN -->
    <div class="main" id="home">

        <div class="hero">
            <h1>Learn Skills by Exchanging Skills</h1>
            <p>Teach what you know, learn what you don't. It's that simple!</p>
           <button class="big-button" onclick="window.location.href='signup.php'">

                Start Learning
            </button>
        </div>

        <div class="section" id="how">
            <h2>How SkillSwap Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Sign Up</h3>
                    <p>Create your free account</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>List Your Skills</h3>
                    <p>Add skills you can teach and want to learn</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Connect & Learn</h3>
                    <p>Find matches and start exchanging</p>
                </div>
            </div>
        </div>

        <div class="section" id="features">
            <h2>Why Choose SkillSwap?</h2>
            <div class="features">
                <div class="feature">
                    <h3>Learn for Free</h3>
                    <p>Exchange skills instead of paying money</p>
                </div>
                <div class="feature">
                    <h3>Safe Payments</h3>
                    <p>Secure payment options for premium lessons.</p>
                </div>
                <div class="feature">
                    <h3>Community</h3>
                    <p>Connect with mentors and learners.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Who Can Join?</h2>
            <div class="users">
                <div class="user-type">
                    <h3>Learner</h3>
                    <ul>
                        <li>Request skills to learn.</li>
                        <li>Browse mentors.</li>
                        <li>Track progress.</li>
                    </ul>
                </div>
                <div class="user-type">
                    <h3>Mentor</h3>
                    <ul>
                        <li>Teach your skills.</li>
                        <li>Earn money.</li>
                        <li>Help others learn.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section" id="demo">
            <h2>See Our Platform in Action.</h2>
            <div class="demo-links">
                <a href="/PHP/learner.php" class="demo-link">
                    <h3>Learner Dashboard.</h3>
                </a>
                <a href="/HTML/mentor_dashboard.html" class="demo-link">
                    <h3>Mentor Dashboard.</h3>
                </a>
            </div>
        </div>

    </div>

    <div class="footer">
        <p>© 2026 SkillSwap. Learn. Teach. Grow.</p>
    </div>

    <script>
        function showLoginMessage() {
            alert('Login functionality would connect to backend. For demo, use Quick Access buttons below.');
        }
    </script>

</body>

</html>
