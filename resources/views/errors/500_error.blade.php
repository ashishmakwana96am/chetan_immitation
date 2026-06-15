<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error | Chetan Imitation</title>
    <!-- Load modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Public+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #08090d;
            --card-bg: rgba(15, 16, 26, 0.5);
            --border-color: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --color-cool: #328693;
            --color-warm: #B4771E;
            --glow-color-1: rgba(180, 119, 30, 0.12);
            --glow-color-2: rgba(50, 134, 147, 0.12);
            --accent-gradient: linear-gradient(135deg, #B4771E 0%, #7b7f64 50%, #328693 100%);
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
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--glow-color-1) 0%, transparent 70%);
            top: -5%;
            right: -5%;
            animation: orbFloat 18s infinite ease-in-out alternate;
        }

        .glow-orb-2 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--glow-color-2) 0%, transparent 70%);
            bottom: -5%;
            left: -5%;
            animation: orbFloat 22s infinite ease-in-out alternate-reverse;
        }

        /* Scanlines & Grid effect */
        .grid-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.005) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.005) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 2;
            pointer-events: none;
        }

        .scanline {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(180, 119, 30, 0.04);
            z-index: 3;
            pointer-events: none;
            animation: scan 8s linear infinite;
        }

        /* Container & Glass Card */
        .container {
            width: 90%;
            max-width: 650px;
            z-index: 10;
            text-align: center;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
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

        .warning-svg {
            width: 140px;
            height: 140px;
        }

        .warning-triangle {
            animation: trianglePulse 2s infinite ease-in-out alternate;
        }

        .warning-exclamation {
            animation: blink 1.2s infinite steps(1);
        }

        .pulse-wave {
            position: absolute;
            width: 120px;
            height: 120px;
            border: 2px solid rgba(180, 119, 30, 0.3);
            border-radius: 50%;
            animation: waveExpand 3s infinite cubic-bezier(0.1, 0.8, 0.3, 1);
            z-index: -1;
        }

        .pulse-wave-2 {
            position: absolute;
            width: 120px;
            height: 120px;
            border: 2px solid rgba(50, 134, 147, 0.2);
            border-radius: 50%;
            animation: waveExpand 3s infinite cubic-bezier(0.1, 0.8, 0.3, 1);
            animation-delay: 1.5s;
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
            content: "500";
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
            box-shadow: 0 4px 15px rgba(180, 119, 30, 0.3), 0 4px 15px rgba(50, 134, 147, 0.2);
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
            box-shadow: 0 8px 25px rgba(180, 119, 30, 0.4), 0 8px 25px rgba(50, 134, 147, 0.3);
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
            border-color: rgba(180, 119, 30, 0.4);
            color: var(--color-warm);
            transform: translateY(-3px);
        }

        .btn-secondary:active {
            transform: translateY(-1px);
        }

        /* Console Lights Animation */
        .lights-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 2rem;
            opacity: 0.6;
        }

        .light-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #272c3d;
            position: relative;
        }

        .light-warm {
            animation: glowWarm 1.5s infinite alternate;
        }

        .light-cool {
            animation: glowCool 2s infinite alternate-reverse;
            animation-delay: 0.5s;
        }

        /* Animation Keyframes */
        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-40px, 30px) scale(1.08); }
            100% { transform: translate(30px, -40px) scale(0.95); }
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

        @keyframes scan {
            0% { top: -5%; }
            100% { top: 105%; }
        }

        @keyframes trianglePulse {
            0% { transform: scale(1) translateY(0); filter: drop-shadow(0 0 5px rgba(180, 119, 30, 0.4)); }
            100% { transform: scale(1.05) translateY(-4px); filter: drop-shadow(0 0 15px rgba(180, 119, 30, 0.8)); }
        }

        @keyframes blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.3; }
        }

        @keyframes waveExpand {
            0% {
                transform: scale(0.8);
                opacity: 0.9;
            }
            100% {
                transform: scale(2.3);
                opacity: 0;
            }
        }

        @keyframes textGlow {
            0% {
                filter: drop-shadow(0 0 10px rgba(180, 119, 30, 0.3)) drop-shadow(0 0 20px rgba(50, 134, 147, 0.2));
            }
            100% {
                filter: drop-shadow(0 0 20px rgba(180, 119, 30, 0.5)) drop-shadow(0 0 35px rgba(50, 134, 147, 0.4));
            }
        }

        @keyframes glowWarm {
            0% { background-color: #272c3d; box-shadow: none; }
            100% { background-color: #B4771E; box-shadow: 0 0 12px #B4771E; }
        }

        @keyframes glowCool {
            0% { background-color: #272c3d; box-shadow: none; }
            100% { background-color: #328693; box-shadow: 0 0 12px #328693; }
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

    <!-- Grid Layout Background -->
    <div class="grid-bg"></div>
    <div class="scanline"></div>

    <!-- Ambient Orbs -->
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <!-- Content -->
    <div class="container">
        <div class="error-card">
            <!-- Animated SVG Centerpiece -->
            <div class="illustration-wrapper">
                <div class="pulse-wave"></div>
                <div class="pulse-wave-2"></div>
                
                <!-- Custom SVG Warning Sign with theme colors -->
                <svg class="warning-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Triangle Warning Sign using warm theme color -->
                    <polygon class="warning-triangle" points="50,15 88,82 12,82" fill="url(#warningGrad)" stroke="#B4771E" stroke-width="2" stroke-linejoin="round"/>
                    
                    <!-- Exclamation point -->
                    <path class="warning-exclamation" d="M 50 35 L 50 58" stroke="#ffffff" stroke-width="5.5" stroke-linecap="round"/>
                    <circle class="warning-exclamation" cx="50" cy="71" r="3.5" fill="#ffffff"/>
                    
                    <!-- Outer Gear/Pulse rings using cool theme color -->
                    <circle cx="50" cy="53" r="45" stroke="#328693" stroke-width="1.5" stroke-dasharray="3 9" opacity="0.4">
                        <animateTransform attributeName="transform" type="rotate" from="0 50 53" to="360 50 53" dur="20s" repeatCount="indefinite"/>
                    </circle>
                    
                    <defs>
                        <linearGradient id="warningGrad" x1="50" y1="15" x2="50" y2="82" gradientUnits="userSpaceOnUse">
                            <stop stop-color="rgba(180, 119, 30, 0.4)"/>
                            <stop offset="1" stop-color="rgba(50, 134, 147, 0.15)"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            <!-- Error code -->
            <div class="error-code">500</div>

            <!-- Header and message -->
            <h1>Houston, We Have a Problem</h1>
            <p class="description">
                Our servers are experiencing an unexpected system overload. Our tech specialists are working to resolve the issue as we speak.
            </p>

            <!-- Interactive Navigation -->
            <div class="action-group">
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <i class="fa-solid fa-rotate-right"></i> Refresh Page
                </button>
            </div>

            <!-- Custom Blinking Theme Lights -->
            <div class="lights-row">
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-cool"></div>
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-cool"></div>
                <div class="light-indicator light-warm"></div>
            </div>
        </div>
    </div>

</body>
</html>
