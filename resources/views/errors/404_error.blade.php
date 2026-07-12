<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Chetan Imitation</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Public+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            left: -5%;
            animation: orbFloat 18s infinite ease-in-out alternate;
        }

        .glow-orb-2 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--color-gold-glow) 0%, transparent 70%);
            bottom: -5%;
            right: -5%;
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

        .search-svg {
            width: 150px;
            height: 150px;
        }

        .search-circle {
            animation: searchPulse 2s infinite ease-in-out alternate;
        }

        .search-lens {
            animation: lensBlink 1.2s infinite steps(1);
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
            box-shadow: 0 4px 15px rgba(180, 119, 30, 0.3);
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
            box-shadow: 0 8px 25px rgba(180, 119, 30, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(5px);
        }

        .btn-secondary:hover {
            background: rgba(180, 119, 30, 0.1);
            border-color: rgba(180, 119, 30, 0.4);
            color: var(--color-gold);
            transform: translateY(-3px);
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

        @keyframes searchPulse {
            0% { transform: scale(1) translateY(0); filter: drop-shadow(0 0 5px rgba(180, 119, 30, 0.3)); }
            100% { transform: scale(1.03) translateY(-4px); filter: drop-shadow(0 0 15px rgba(180, 119, 30, 0.6)); }
        }

        @keyframes lensBlink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.4; }
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
                filter: drop-shadow(0 0 10px rgba(180, 119, 30, 0.3));
            }
            100% {
                filter: drop-shadow(0 0 20px rgba(180, 119, 30, 0.5));
            }
        }

        @keyframes glowGold {
            0% { background-color: #272c3d; box-shadow: none; }
            100% { background-color: #B4771E; box-shadow: 0 0 12px #B4771E; }
        }

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

    <div class="grid-bg"></div>

    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>

    <div class="container">
        <div class="error-card">
            <div class="illustration-wrapper">
                <div class="pulse-wave"></div>
                
                <svg class="search-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle class="search-circle" cx="42" cy="42" r="22" fill="url(#searchGrad)" stroke="#B4771E" stroke-width="3" stroke-linejoin="round"/>
                    
                    <path class="search-lens" d="M 58 58 L 78 78" stroke="#B4771E" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    
                    <circle cx="42" cy="42" r="10" fill="rgba(180, 119, 30, 0.15)" stroke="rgba(255, 255, 255, 0.4)" stroke-width="1"/>
                    
                    <circle cx="50" cy="50" r="42" stroke="#B4771E" stroke-width="1" stroke-dasharray="3 9" opacity="0.25">
                        <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="20s" repeatCount="indefinite"/>
                    </circle>
                    
                    <defs>
                        <radialGradient id="searchGrad" cx="50%" cy="50%" r="50%">
                            <stop stop-color="rgba(180, 119, 30, 0.35)"/>
                            <stop offset="1" stop-color="rgba(180, 119, 30, 0.08)"/>
                        </radialGradient>
                    </defs>
                </svg>
            </div>

            <div class="error-code">404</div>

            <h1>Page Not Found</h1>
            <p class="description">
                The page you are looking for doesn't exist or has been moved. Let us help you find your way back.
            </p>

            <div class="action-group">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fa-solid fa-house"></i> Back to Website
                </a>
                <button onclick="window.history.back()" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Go Back
                </button>
            </div>

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
