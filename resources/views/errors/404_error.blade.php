<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Chetan Imitation</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />
    <!-- Load modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Public+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #080a0f;
            --card-bg: rgba(13, 18, 28, 0.5);
            --border-color: rgba(255, 255, 255, 0.07);
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --color-cool: #328693;
            --color-warm: #B4771E;
            --glow-color-1: rgba(50, 134, 147, 0.15);
            --glow-color-2: rgba(180, 119, 30, 0.15);
            --accent-gradient: linear-gradient(135deg, #328693 0%, #4ea3b0 50%, #B4771E 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Ambient Glow Backgrounds */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 1;
            pointer-events: none;
        }

        .glow-orb-1 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, var(--glow-color-1) 0%, transparent 70%);
            top: -10%;
            left: -10%;
            animation: orbFloat 15s infinite ease-in-out alternate;
        }

        .glow-orb-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--glow-color-2) 0%, transparent 70%);
            bottom: -10%;
            right: -10%;
            animation: orbFloat 20s infinite ease-in-out alternate-reverse;
        }

        /* Particle Stars Background */
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .star {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            opacity: 0.25;
            animation: twinkle 4s infinite ease-in-out;
        }

        .star:nth-child(1) { top: 15%; left: 10%; width: 2px; height: 2px; animation-delay: 0s; }
        .star:nth-child(2) { top: 25%; left: 80%; width: 3px; height: 3px; animation-delay: 1s; }
        .star:nth-child(3) { top: 70%; left: 15%; width: 1px; height: 1px; animation-delay: 2s; }
        .star:nth-child(4) { top: 80%; left: 85%; width: 2px; height: 2px; animation-delay: 0.5s; }
        .star:nth-child(5) { top: 40%; left: 50%; width: 3px; height: 3px; animation-delay: 1.5s; }
        .star:nth-child(6) { top: 60%; left: 70%; width: 2px; height: 2px; animation-delay: 2.5s; }
        .star:nth-child(7) { top: 10%; left: 60%; width: 1px; height: 1px; animation-delay: 3s; }
        .star:nth-child(8) { top: 85%; left: 40%; width: 2px; height: 2px; animation-delay: 1.8s; }

        /* Container & Glass Card */
        .container {
            width: 90%;
            max-width: 650px;
            z-index: 10;
            text-align: center;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02) 0%, transparent 100%);
            pointer-events: none;
        }

        /* SVG Animation */
        .illustration-wrapper {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .satellite-svg {
            width: 150px;
            height: 150px;
            animation: satelliteFloat 6s ease-in-out infinite;
        }

        .pulse-circle {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 1px solid rgba(50, 134, 147, 0.4);
            border-radius: 50%;
            animation: waves 4s infinite linear;
            z-index: -1;
        }

        .pulse-circle-2 {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 1px solid rgba(180, 119, 30, 0.3);
            border-radius: 50%;
            animation: waves 4s infinite linear;
            animation-delay: 2s;
            z-index: -1;
        }

        /* Error Code Styling */
        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
            animation: textGlow 3s infinite alternate;
        }

        .error-code::after {
            content: "404";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: blur(12px);
            opacity: 0.8;
            z-index: -1;
        }

        /* Typography */
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        .description {
            font-size: 1.05rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2.5rem;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Buttons & Actions */
        .action-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.95rem 1.8rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            outline: none;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 15px rgba(50, 134, 147, 0.3), 0 4px 15px rgba(180, 119, 30, 0.2);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: all 0.6s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(50, 134, 147, 0.4), 0 8px 25px rgba(180, 119, 30, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(5px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(50, 134, 147, 0.4);
            color: var(--color-cool);
            transform: translateY(-3px);
        }

        .btn-secondary:active {
            transform: translateY(-1px);
        }

        /* Animations Keyframes */
        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -20px) scale(1.1); }
            100% { transform: translate(-20px, 30px) scale(0.95); }
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.2); }
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes satelliteFloat {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(4deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        @keyframes waves {
            0% {
                transform: scale(0.8);
                opacity: 0.8;
            }
            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        @keyframes textGlow {
            0% {
                filter: drop-shadow(0 0 10px rgba(50, 134, 147, 0.3)) drop-shadow(0 0 20px rgba(180, 119, 30, 0.2));
            }
            100% {
                filter: drop-shadow(0 0 20px rgba(50, 134, 147, 0.5)) drop-shadow(0 0 35px rgba(180, 119, 30, 0.4));
            }
        }

        /* Mobile Optimization */
        @media (max-width: 576px) {
            .error-card {
                padding: 2.5rem 1.5rem;
            }
            .error-code {
                font-size: 6rem;
            }
            h1 {
                font-size: 1.6rem;
            }
            .description {
                font-size: 0.95rem;
                margin-bottom: 2rem;
            }
            .action-group {
                flex-direction: column;
                width: 100%;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Orbs -->
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <!-- Starry Particles -->
    <div class="stars">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="error-card">
            <!-- Animated SVG Centerpiece -->
            <div class="illustration-wrapper">
                <div class="pulse-circle"></div>
                <div class="pulse-circle-2"></div>
                
                <!-- Custom SVG Satellite using theme colors -->
                <svg class="satellite-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="16" fill="url(#satGrad)" stroke="rgba(255, 255, 255, 0.15)" stroke-width="2"/>
                    <!-- Rings -->
                    <circle cx="50" cy="50" r="28" stroke="#328693" stroke-width="1.5" stroke-dasharray="8 6" opacity="0.6"/>
                    <circle cx="50" cy="50" r="40" stroke="#B4771E" stroke-width="1.5" stroke-dasharray="4 8" opacity="0.4"/>
                    <!-- Satellite Panels -->
                    <rect x="22" y="47" width="10" height="6" rx="1" fill="#328693" stroke="rgba(255, 255, 255, 0.4)" stroke-width="0.5"/>
                    <rect x="68" y="47" width="10" height="6" rx="1" fill="#328693" stroke="rgba(255, 255, 255, 0.4)" stroke-width="0.5"/>
                    <line x1="32" y1="50" x2="34" y2="50" stroke="#ffffff" stroke-width="2"/>
                    <line x1="66" y1="50" x2="68" y2="50" stroke="#ffffff" stroke-width="2"/>
                    <!-- Signal Beams -->
                    <path d="M 50 24 Q 45 15 50 6" stroke="#328693" stroke-width="2" stroke-linecap="round" opacity="0.8">
                        <animate attributeName="stroke-dasharray" values="0,20; 20,0; 0,20" dur="3s" repeatCount="indefinite"/>
                    </path>
                    <path d="M 50 76 Q 55 85 50 94" stroke="#B4771E" stroke-width="2" stroke-linecap="round" opacity="0.8">
                        <animate attributeName="stroke-dasharray" values="0,20; 20,0; 0,20" dur="3s" repeatCount="indefinite" begin="1.5s"/>
                    </path>
                    <defs>
                        <linearGradient id="satGrad" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#111827"/>
                            <stop offset="1" stop-color="#0b0f19"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            <!-- Error code -->
            <div class="error-code">404</div>

            <!-- Header and message -->
            <h1>Lost in the Digital Void</h1>
            <p class="description">
                The page you are trying to reach has drifted out of orbit, or never existed in this dimension. Let's get you back on track.
            </p>

            <!-- Interactive Navigation -->
            <div class="action-group">
                <button onclick="window.history.back()" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left"></i> Go Back
                </button>
            </div>
        </div>
    </div>

</body>
</html>
