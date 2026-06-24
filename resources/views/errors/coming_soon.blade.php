<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon | Chetan Imitation</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Public+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #131615;
            --card-bg: rgba(19, 22, 21, 0.6);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: #D5D5D5;
            --color-gold: #B4771E;
            --color-gold-light: rgba(180, 119, 30, 0.15);
            --color-gold-glow: rgba(180, 119, 30, 0.25);
            --accent-gradient: linear-gradient(135deg, #B4771E 0%, #8a5c16 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

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
            background: radial-gradient(circle, var(--color-gold-glow) 0%, transparent 70%);
            top: -5%;
            right: -5%;
            animation: orbFloat 18s infinite ease-in-out alternate;
        }

        .glow-orb-2 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--color-gold-glow) 0%, transparent 70%);
            bottom: -5%;
            left: -5%;
            animation: orbFloat 22s infinite ease-in-out alternate-reverse;
        }

        .grid-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.008) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.008) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 2;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
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
            background: linear-gradient(180deg, rgba(180, 119, 30, 0.03) 0%, transparent 100%);
            pointer-events: none;
        }

        .illustration-wrapper {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .coming-soon-svg {
            width: 140px;
            height: 140px;
        }

        .clock-hand {
            transform-origin: 50px 53px;
            animation: rotateClock 4s linear infinite;
        }

        .clock-hand-short {
            transform-origin: 50px 53px;
            animation: rotateClockSlow 48s linear infinite;
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

        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--color-gold);
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .main-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
            animation: textGlow 3s infinite alternate;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
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
            animation: glowGold 1.5s infinite alternate;
        }

        .light-warm:nth-child(2) { animation-delay: 0.3s; }
        .light-warm:nth-child(3) { animation-delay: 0.6s; }
        .light-warm:nth-child(4) { animation-delay: 0.9s; }
        .light-warm:nth-child(5) { animation-delay: 1.2s; }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-40px, 30px) scale(1.08); }
            100% { transform: translate(30px, -40px) scale(0.95); }
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes rotateClock {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        @keyframes rotateClockSlow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        @keyframes waveExpand {
            0%   { transform: scale(0.8); opacity: 0.9; }
            100% { transform: scale(2.3); opacity: 0; }
        }

        @keyframes textGlow {
            0%   { filter: drop-shadow(0 0 10px rgba(180, 119, 30, 0.3)); }
            100% { filter: drop-shadow(0 0 20px rgba(180, 119, 30, 0.5)); }
        }

        @keyframes glowGold {
            0%   { background-color: #272c3d; box-shadow: none; }
            100% { background-color: #B4771E; box-shadow: 0 0 12px #B4771E; }
        }

        @media (max-width: 576px) {
            .error-card    { padding: 2.5rem 1.5rem; }
            .main-title    { font-size: 2.5rem; }
            h1             { font-size: 1.3rem; }
            .description   { font-size: 0.95rem; margin-bottom: 2rem; }
        }
    </style>
</head>
<body>

    <div class="grid-bg"></div>
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="container">
        <div class="error-card">

            <div class="illustration-wrapper">
                <div class="pulse-wave"></div>

                <svg class="coming-soon-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Clock circle -->
                    <circle cx="50" cy="50" r="36" fill="url(#clockGrad)" stroke="#B4771E" stroke-width="2"/>

                    <!-- Tick marks -->
                    <line x1="50" y1="17" x2="50" y2="23" stroke="#B4771E" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="50" y1="77" x2="50" y2="83" stroke="#B4771E" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="17" y1="50" x2="23" y2="50" stroke="#B4771E" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="77" y1="50" x2="83" y2="50" stroke="#B4771E" stroke-width="2.5" stroke-linecap="round"/>

                    <!-- Minute hand (long) -->
                    <line class="clock-hand" x1="50" y1="50" x2="50" y2="24" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/>

                    <!-- Hour hand (short) -->
                    <line class="clock-hand-short" x1="50" y1="50" x2="50" y2="32" stroke="#B4771E" stroke-width="3" stroke-linecap="round"/>

                    <!-- Center dot -->
                    <circle cx="50" cy="50" r="3.5" fill="#B4771E"/>

                    <!-- Outer dashed ring -->
                    <circle cx="50" cy="50" r="45" stroke="#B4771E" stroke-width="1" stroke-dasharray="3 9" opacity="0.25">
                        <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="20s" repeatCount="indefinite"/>
                    </circle>

                    <defs>
                        <radialGradient id="clockGrad" cx="50%" cy="50%" r="50%">
                            <stop stop-color="rgba(180, 119, 30, 0.25)"/>
                            <stop offset="1" stop-color="rgba(180, 119, 30, 0.05)"/>
                        </radialGradient>
                    </defs>
                </svg>
            </div>

            <p class="brand-name">Chetan Imitation</p>
            <div class="main-title">Coming Soon</div>

            <h1>We're Working on Something Special</h1>
            <p class="description">
                Our website is currently under maintenance. We'll be back shortly with a refreshed experience. Thank you for your patience.
            </p>

            <div class="lights-row">
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-warm"></div>
                <div class="light-indicator light-warm"></div>
            </div>

        </div>
    </div>

</body>
</html>
