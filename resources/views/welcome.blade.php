<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCOMM — Network Growth Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #070B14;
            color: #F8FAFC;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* ── Aurora Background ── */
        .aurora-bg {
            position: fixed; inset: 0; z-index: 0;
            overflow: hidden; pointer-events: none;
        }
        .aurora-blob {
            position: absolute; border-radius: 50%;
            filter: blur(90px); opacity: 0.12;
        }
        .blob-1 {
            width: 700px; height: 700px;
            background: radial-gradient(circle, #3B82F6 0%, #1D4ED8 60%, transparent 100%);
            top: -200px; left: -150px;
            animation: float1 22s ease-in-out infinite;
        }
        .blob-2 {
            width: 550px; height: 550px;
            background: radial-gradient(circle, #7C3AED 0%, #4C1D95 60%, transparent 100%);
            top: 25%; right: -100px;
            animation: float2 28s ease-in-out infinite;
        }
        .blob-3 {
            width: 450px; height: 450px;
            background: radial-gradient(circle, #06B6D4 0%, #0E7490 60%, transparent 100%);
            bottom: 5%; left: 15%;
            animation: float3 20s ease-in-out infinite;
        }
        .blob-4 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #8B5CF6 0%, #6D28D9 60%, transparent 100%);
            bottom: 35%; right: 15%;
            animation: float1 26s ease-in-out infinite reverse;
        }
        @keyframes float1 {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(60px,-50px) scale(1.06); }
            66%      { transform: translate(-40px,70px) scale(0.94); }
        }
        @keyframes float2 {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(-80px,55px) scale(1.08); }
            66%      { transform: translate(45px,-65px) scale(0.93); }
        }
        @keyframes float3 {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(55px,-55px) scale(1.1); }
        }

        /* ── Grid overlay ── */
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.018) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Glassmorphism ── */
        .glass {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            transition: background 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        .glass-hover:hover {
            background: rgba(255,255,255,0.07);
            border-color: rgba(255,255,255,0.14);
            transform: translateY(-3px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.45);
        }

        /* ── Gradient text ── */
        .grad-text {
            background: linear-gradient(135deg, #60A5FA 0%, #A78BFA 50%, #F472B6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Buttons ── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #3B82F6, #7C3AED);
            color: #fff; font-weight: 700; font-size: 15px;
            padding: 14px 28px; border-radius: 12px; border: none;
            cursor: pointer; text-decoration: none;
            position: relative; overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn-primary::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, #60A5FA, #A78BFA);
            opacity: 0; transition: opacity 0.25s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(59,130,246,0.45); }
        .btn-primary:hover::after { opacity: 1; }
        .btn-primary > * { position: relative; z-index: 1; }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.14);
            color: #CBD5E1; font-weight: 600; font-size: 15px;
            padding: 14px 28px; border-radius: 12px;
            cursor: pointer; text-decoration: none;
            transition: background 0.25s ease, border-color 0.25s ease, transform 0.25s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            backdrop-filter: blur(8px);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.11);
            border-color: rgba(255,255,255,0.28);
            transform: translateY(-2px);
        }

        /* ── Navbar ── */
        .navbar {
            position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
            z-index: 100; width: calc(100% - 48px); max-width: 1200px;
            padding: 12px 24px; border-radius: 20px;
            background: rgba(7,11,20,0.82);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.09);
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-link {
            color: #94A3B8; font-size: 14px; font-weight: 500;
            text-decoration: none; transition: color 0.2s ease;
        }
        .nav-link:hover { color: #F8FAFC; }

        /* ── Scroll reveal ── */
        .reveal {
            opacity: 0; transform: translateY(28px);
            transition: opacity 0.65s ease, transform 0.65s ease;
        }
        .reveal.in { opacity: 1; transform: translateY(0); }

        /* ── Badge ── */
        .badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(59,130,246,0.1);
            border: 1px solid rgba(59,130,246,0.28);
            color: #60A5FA; padding: 5px 14px; border-radius: 100px;
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.07em; text-transform: uppercase;
        }

        /* ── Glow dot ── */
        .glow-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #60A5FA; flex-shrink: 0;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%,100% { box-shadow: 0 0 0 0 rgba(96,165,250,0.5); }
            50%      { box-shadow: 0 0 0 5px rgba(96,165,250,0); }
        }

        /* ── Feature icon ── */
        .feat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px; flex-shrink: 0;
        }

        /* ── Bento grid ── */
        .bento { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
        .bento-wide { grid-column: span 2; }
        @media (max-width: 1024px) {
            .bento { grid-template-columns: repeat(2,1fr); }
            .bento-wide { grid-column: span 2; }
        }
        @media (max-width: 640px) {
            .bento { grid-template-columns: 1fr; }
            .bento-wide { grid-column: span 1; }
        }

        /* ── Steps ── */
        .steps-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 32px; position: relative; }
        @media (max-width: 768px) {
            .steps-grid { grid-template-columns: 1fr; gap: 24px; }
            .step-connector { display: none !important; }
        }

        /* ── Stats ── */
        .stats-grid { display: grid; grid-template-columns: repeat(3,1fr); }
        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: 1fr; }
            .stat-div { border-left: none !important; border-right: none !important; border-top: 1px solid rgba(255,255,255,0.07) !important; }
        }

        /* ── Stat number ── */
        .stat-num {
            font-size: clamp(2.8rem, 5vw, 4.2rem); font-weight: 900; line-height: 1;
            background: linear-gradient(135deg, #60A5FA, #A78BFA);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Step circle ── */
        .step-circle {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; color: #fff;
            position: relative; z-index: 1; flex-shrink: 0;
            margin: 0 auto 24px;
        }

        /* ── Glow line ── */
        .glow-line {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #3B82F6 30%, #7C3AED 70%, transparent 100%);
            animation: glow-sweep 4s ease-in-out infinite;
        }
        @keyframes glow-sweep { 0%,100% { opacity: 0.25; } 50% { opacity: 0.8; } }

        /* ── Particles ── */
        .particle {
            position: fixed; border-radius: 50%;
            pointer-events: none; z-index: 0;
            animation: ptc-rise linear infinite;
        }
        @keyframes ptc-rise {
            0%   { transform: translateY(110vh) translateX(0) scale(0); opacity: 0; }
            8%   { opacity: 1; }
            92%  { opacity: 0.8; }
            100% { transform: translateY(-10vh) translateX(var(--drift)) scale(1); opacity: 0; }
        }

        /* ── Chip ── */
        .chip {
            display: inline-block;
            background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.22);
            color: #93C5FD; padding: 3px 11px; border-radius: 100px;
            font-size: 12px; font-weight: 600;
        }

        /* ── Modal input ── */
        .modal-input {
            width: 100%; display: block;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px; padding: 12px 16px;
            color: #F8FAFC; font-size: 15px; outline: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: border-color 0.2s ease; margin-bottom: 16px;
        }
        .modal-input:focus { border-color: rgba(59,130,246,0.6); }
        .modal-input::placeholder { color: #475569; }

        /* ── Misc ── */
        @keyframes bounce-arr {
            0%,100% { transform: translateY(0) translateX(-50%); }
            50%      { transform: translateY(8px) translateX(-50%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .aurora-blob, .particle, .glow-line, .glow-dot { animation: none !important; }
            .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<!-- Aurora -->
<div class="aurora-bg" aria-hidden="true">
    <div class="aurora-blob blob-1"></div>
    <div class="aurora-blob blob-2"></div>
    <div class="aurora-blob blob-3"></div>
    <div class="aurora-blob blob-4"></div>
</div>
<div class="grid-overlay" aria-hidden="true"></div>

<!-- Navbar -->
<nav class="navbar" aria-label="Main navigation">
    <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#3B82F6,#7C3AED);border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </div>
        <span style="font-weight:800;font-size:16px;color:#F8FAFC;letter-spacing:-0.01em;">SOCOMM<span style="color:#60A5FA;">NGE</span></span>
    </a>
    <div style="display:flex;align-items:center;gap:24px;">
        <a href="#features" class="nav-link" id="nav-feat" style="display:none;">Features</a>
        <a href="#how-it-works" class="nav-link" id="nav-how" style="display:none;">How It Works</a>
        <a href="/admin" class="btn-primary" style="padding:9px 20px;font-size:13px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            <span>Admin Panel</span>
        </a>
    </div>
</nav>

<main style="position:relative;z-index:1;">

    <!-- ═══════════ HERO ═══════════ -->
    <section style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:140px 24px 100px;text-align:center;">
        <div style="max-width:820px;margin:0 auto;width:100%;">

            <div class="badge reveal" style="margin-bottom:28px;">
                <div class="glow-dot"></div>
                B2B Compensation Engine · Laravel Cloud
            </div>

            <h1 class="reveal" style="font-size:clamp(2.8rem,7.5vw,5.8rem);font-weight:900;line-height:1.04;letter-spacing:-0.035em;margin-bottom:28px;">
                The Engine Behind<br>
                <span class="grad-text">Every Payout</span>
            </h1>

            <p class="reveal" style="font-size:clamp(1rem,2vw,1.2rem);color:#94A3B8;line-height:1.75;max-width:580px;margin:0 auto 52px;">
                A parameter-driven compensation platform that calculates affiliate commissions, viral networks, and wallet movements — entirely from config. No hardcoded rules. Infinite tenants.
            </p>

            <div class="reveal" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:80px;">
                <a href="/admin" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Admin Panel</span>
                </a>
                <button class="btn-ghost" onclick="openModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Affiliate Login</span>
                </button>
            </div>

            <!-- Metric pills -->
            <div class="reveal" style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap;">
                <div class="glass" style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                    <div style="width:34px;height:34px;background:rgba(59,130,246,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div style="text-align:left;">
                        <div style="font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Commissions</div>
                        <div style="font-size:15px;font-weight:700;">Real-time</div>
                    </div>
                </div>
                <div class="glass" style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                    <div style="width:34px;height:34px;background:rgba(6,182,212,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22D3EE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div style="text-align:left;">
                        <div style="font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Ledger</div>
                        <div style="font-size:15px;font-weight:700;">Immutable</div>
                    </div>
                </div>
                <div class="glass" style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                    <div style="width:34px;height:34px;background:rgba(167,139,250,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <div style="text-align:left;">
                        <div style="font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Tenants</div>
                        <div style="font-size:15px;font-weight:700;">Multi-tenant</div>
                    </div>
                </div>
                <div class="glass" style="padding:14px 20px;display:flex;align-items:center;gap:12px;border-radius:12px;">
                    <div style="width:34px;height:34px;background:rgba(52,211,153,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div style="text-align:left;">
                        <div style="font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Affiliates</div>
                        <div style="font-size:15px;font-weight:700;">Unlimited</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="position:absolute;bottom:36px;left:50%;animation:bounce-arr 2.2s ease-in-out infinite;display:flex;flex-direction:column;align-items:center;gap:6px;color:#334155;" aria-hidden="true">
            <span style="font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Scroll</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </section>

    <!-- ═══════════ FEATURES ═══════════ -->
    <section id="features" style="padding:80px 24px;max-width:1200px;margin:0 auto;">
        <div class="glow-line" style="margin-bottom:80px;"></div>

        <div style="text-align:center;margin-bottom:64px;">
            <div class="badge reveal" style="margin-bottom:20px;">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Features
            </div>
            <h2 class="reveal" style="font-size:clamp(1.9rem,4vw,3rem);font-weight:800;letter-spacing:-0.025em;margin-bottom:16px;">Everything your network needs</h2>
            <p class="reveal" style="color:#94A3B8;font-size:1.05rem;max-width:520px;margin:0 auto;line-height:1.75;">Purpose-built for the complexity of multi-tier affiliate networks. Every edge case handled. Every payout precise.</p>
        </div>

        <div class="bento">
            <!-- Config-Driven — wide card -->
            <div class="glass glass-hover bento-wide reveal" style="padding:40px;cursor:default;">
                <div class="feat-icon" style="background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.22);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h3 style="font-size:1.35rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Config-Driven Plans</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.935rem;max-width:500px;margin-bottom:24px;">Every business rule lives in a versioned JSON plan config. Onboard a new company by creating a config file — not writing code. Tiers, caps, qualification thresholds, viral rules — all declarative.</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="chip">JSON Config</span>
                    <span class="chip">Versioned Plans</span>
                    <span class="chip">Zero Hardcoding</span>
                    <span class="chip">Idempotent Runs</span>
                </div>
            </div>

            <!-- Multi-Tenant -->
            <div class="glass glass-hover reveal" style="padding:36px;cursor:default;">
                <div class="feat-icon" style="background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.22);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Multi-Tenant Architecture</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.9rem;">Every company is fully isolated by <code style="background:rgba(255,255,255,0.07);padding:1px 6px;border-radius:4px;font-size:12px;color:#A78BFA;">company_id</code>. Each tenant gets their own branded affiliate portal, scoped plan config, and isolated commission history.</p>
            </div>

            <!-- Immutable Ledger -->
            <div class="glass glass-hover reveal" style="padding:36px;cursor:default;">
                <div class="feat-icon" style="background:rgba(6,182,212,0.12);border:1px solid rgba(6,182,212,0.22);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#22D3EE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Immutable Ledger</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.9rem;">Append-only movement ledger. Wallet balance is always <code style="background:rgba(255,255,255,0.07);padding:1px 6px;border-radius:4px;font-size:12px;color:#22D3EE;">SUM(movements)</code> — never stored as a mutable field. Full audit trail: credits, clawbacks, withdrawals.</p>
            </div>

            <!-- Real-Time Calculations -->
            <div class="glass glass-hover reveal" style="padding:36px;cursor:default;">
                <div class="feat-icon" style="background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.2);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FCD34D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Real-Time Calculations</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.9rem;">Run commission calculations on-demand or on schedule via artisan commands. Same company + same date always yields identical results. Designed to be re-run safely.</p>
            </div>

            <!-- Viral Commission Trees -->
            <div class="glass glass-hover reveal" style="padding:36px;cursor:default;">
                <div class="feat-icon" style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.2);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Viral Commission Trees</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.9rem;">Deep genealogy trees with the QVV algorithm. Multi-leg volume aggregation, large/small leg balancing, qualified viral volume across unlimited depth.</p>
            </div>

            <!-- Cap Enforcement -->
            <div class="glass glass-hover reveal" style="padding:36px;cursor:default;">
                <div class="feat-icon" style="background:rgba(251,113,133,0.1);border:1px solid rgba(251,113,133,0.2);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FB7185" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.015em;">Cap Enforcement</h3>
                <p style="color:#94A3B8;line-height:1.75;font-size:0.9rem;">Global and viral commission caps applied automatically per run. Configurable limits protect payout liability while remaining completely fair to your affiliate network.</p>
            </div>
        </div>
    </section>

    <!-- ═══════════ HOW IT WORKS ═══════════ -->
    <section id="how-it-works" style="padding:80px 24px;max-width:1100px;margin:0 auto;">
        <div class="glow-line" style="margin-bottom:80px;"></div>

        <div style="text-align:center;margin-bottom:64px;">
            <div class="badge reveal" style="margin-bottom:20px;">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Process
            </div>
            <h2 class="reveal" style="font-size:clamp(1.9rem,4vw,3rem);font-weight:800;letter-spacing:-0.025em;margin-bottom:16px;">From config to payout in 3 steps</h2>
            <p class="reveal" style="color:#94A3B8;font-size:1.05rem;max-width:500px;margin:0 auto;line-height:1.75;">A clean, predictable pipeline that turns plan configuration into accurate affiliate payouts — every time.</p>
        </div>

        <div class="steps-grid reveal">
            <div class="step-connector" style="position:absolute;top:27px;left:calc(16.666% + 28px);width:calc(66.666% - 56px);height:2px;background:linear-gradient(90deg,#3B82F6,#7C3AED);opacity:0.35;" aria-hidden="true"></div>

            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;">
                <div class="step-circle" style="background:linear-gradient(135deg,#3B82F6,#6366F1);">1</div>
                <div class="glass" style="padding:28px 24px;width:100%;">
                    <div style="font-size:1.1rem;font-weight:800;margin-bottom:10px;letter-spacing:-0.01em;">Configure</div>
                    <p style="color:#94A3B8;font-size:0.875rem;line-height:1.7;">Define your compensation plan in a JSON config. Set tiers, caps, qualification thresholds, and viral commission rules. One file per tenant.</p>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;">
                <div class="step-circle" style="background:linear-gradient(135deg,#6366F1,#7C3AED);">2</div>
                <div class="glass" style="padding:28px 24px;width:100%;">
                    <div style="font-size:1.1rem;font-weight:800;margin-bottom:10px;letter-spacing:-0.01em;">Calculate</div>
                    <p style="color:#94A3B8;font-size:0.875rem;line-height:1.7;">The engine evaluates affiliate qualification, applies the QVV algorithm, calculates direct and viral commissions, and enforces all caps automatically.</p>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;">
                <div class="step-circle" style="background:linear-gradient(135deg,#7C3AED,#06B6D4);">3</div>
                <div class="glass" style="padding:28px 24px;width:100%;">
                    <div style="font-size:1.1rem;font-weight:800;margin-bottom:10px;letter-spacing:-0.01em;">Credit</div>
                    <p style="color:#94A3B8;font-size:0.875rem;line-height:1.7;">Approved commissions are credited to affiliate wallets via the immutable movement ledger. Full audit trail. Zero data loss. Corrections are new entries, never overwrites.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ STATS ═══════════ -->
    <section style="padding:80px 24px;max-width:1100px;margin:0 auto;">
        <div class="glass reveal" id="stats-card">
            <div class="stats-grid">
                <div style="padding:56px 40px;text-align:center;">
                    <div class="stat-num" id="stat-1">0%</div>
                    <div style="color:#CBD5E1;margin-top:14px;font-size:1rem;font-weight:700;">Config Driven</div>
                    <div style="color:#475569;margin-top:8px;font-size:0.825rem;line-height:1.6;">Every rule lives in your plan config. No exceptions.</div>
                </div>
                <div class="stat-div" style="padding:56px 40px;text-align:center;border-left:1px solid rgba(255,255,255,0.07);border-right:1px solid rgba(255,255,255,0.07);">
                    <div class="stat-num" id="stat-2">0</div>
                    <div style="color:#CBD5E1;margin-top:14px;font-size:1rem;font-weight:700;">Hardcoded Rules</div>
                    <div style="color:#475569;margin-top:8px;font-size:0.825rem;line-height:1.6;">Business logic never baked into source code.</div>
                </div>
                <div style="padding:56px 40px;text-align:center;">
                    <div class="stat-num" id="stat-3" style="font-size:clamp(3rem,5.5vw,5rem);opacity:0;transform:scale(0.7);transition:opacity 0.6s ease,transform 0.6s ease;">∞</div>
                    <div style="color:#CBD5E1;margin-top:14px;font-size:1rem;font-weight:700;">Tenants Supported</div>
                    <div style="color:#475569;margin-top:8px;font-size:0.825rem;line-height:1.6;">Scale to any number of companies without new code.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ CTA BAND ═══════════ -->
    <section style="padding:40px 24px 100px;max-width:900px;margin:0 auto;">
        <div class="glass reveal" style="padding:64px 48px;text-align:center;background:linear-gradient(135deg,rgba(59,130,246,0.07),rgba(124,58,237,0.07));border-color:rgba(59,130,246,0.18);">
            <h2 style="font-size:clamp(1.7rem,3.5vw,2.6rem);font-weight:800;letter-spacing:-0.025em;margin-bottom:16px;">Ready to power your network?</h2>
            <p style="color:#94A3B8;font-size:1.05rem;line-height:1.75;max-width:460px;margin:0 auto 40px;">Access the admin panel to manage companies, plans, and commission runs — or send your affiliates straight to their portal.</p>
            <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                <a href="/admin" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                    <span>Go to Admin Panel</span>
                </a>
                <button class="btn-ghost" onclick="openModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Affiliate Login</span>
                </button>
            </div>
        </div>
    </section>

</main>

<!-- ═══════════ FOOTER ═══════════ -->
<footer style="position:relative;z-index:1;border-top:1px solid rgba(255,255,255,0.06);padding:36px 24px;">
    <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:28px;height:28px;background:linear-gradient(135deg,#3B82F6,#7C3AED);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            </div>
            <span style="font-weight:700;font-size:14px;color:#F8FAFC;">SOCOMM <span style="color:#60A5FA;">Network Growth Engine</span></span>
        </div>
        <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
            <a href="/admin" class="nav-link" style="font-size:13px;">Admin Panel</a>
            <a href="#features" class="nav-link" style="font-size:13px;">Features</a>
            <a href="#how-it-works" class="nav-link" style="font-size:13px;">How It Works</a>
        </div>
        <div style="color:#334155;font-size:12px;">&copy; {{ date('Y') }} SOCOMM. All rights reserved.</div>
    </div>
</footer>

<!-- ═══════════ AFFILIATE MODAL ═══════════ -->
<div id="company-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"
     style="display:none;position:fixed;inset:0;z-index:200;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);backdrop-filter:blur(10px);">
    <div class="glass" style="width:100%;max-width:420px;padding:40px;margin:24px;background:rgba(10,15,28,0.92);border-color:rgba(255,255,255,0.1);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 id="modal-title" style="font-size:1.2rem;font-weight:800;letter-spacing:-0.01em;">Affiliate Login</h3>
            <button onclick="closeModal()" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#94A3B8;transition:background 0.2s ease;" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <p style="color:#64748B;font-size:0.9rem;margin-bottom:28px;line-height:1.6;">Enter your company slug to be redirected to your affiliate portal.</p>
        <form onsubmit="handleCompanyLogin(event)">
            <label for="company-slug" style="display:block;font-size:11px;font-weight:700;color:#64748B;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.08em;">Company Slug</label>
            <input id="company-slug" class="modal-input" type="text" placeholder="e.g. socomm" required autocomplete="off" spellcheck="false">
            <p id="slug-error" style="display:none;color:#F87171;font-size:0.8rem;margin:-8px 0 12px;line-height:1.4;">Company not found. Please check the slug and try again.</p>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn-primary" style="flex:1;justify-content:center;">
                    <span>Go to Portal</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
                <button type="button" class="btn-ghost" onclick="closeModal()" style="padding:14px 18px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Responsive nav links
    function syncNav() {
        const vis = window.innerWidth >= 768 ? 'block' : 'none';
        document.getElementById('nav-feat').style.display = vis;
        document.getElementById('nav-how').style.display  = vis;
    }
    syncNav();
    window.addEventListener('resize', syncNav);

    // ── Scroll reveal
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); revealObs.unobserve(e.target); } });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

    // ── Counters
    let countersRan = false;
    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
    function countUp(el, target, suffix, ms) {
        const t0 = performance.now();
        (function tick(now) {
            const p = Math.min((now - t0) / ms, 1);
            el.textContent = Math.round(target * easeOutCubic(p)) + suffix;
            if (p < 1) requestAnimationFrame(tick);
        })(t0);
    }

    new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !countersRan) {
            countersRan = true;
            countUp(document.getElementById('stat-1'), 100, '%', 1600);
            // stat-2 = 0, already set in HTML
            setTimeout(() => {
                const s3 = document.getElementById('stat-3');
                s3.style.opacity = '1';
                s3.style.transform = 'scale(1)';
            }, 350);
        }
    }, { threshold: 0.4 }).observe(document.getElementById('stats-card'));

    // ── Modal
    const modal = document.getElementById('company-modal');
    function openModal() {
        modal.style.display = 'flex';
        document.getElementById('slug-error').style.display = 'none';
        setTimeout(() => document.getElementById('company-slug').focus(), 50);
    }
    function closeModal() { modal.style.display = 'none'; }
    async function handleCompanyLogin(e) {
        e.preventDefault();
        const slug = document.getElementById('company-slug').value.trim();
        if (!slug) return;

        const errorEl = document.getElementById('slug-error');
        const btn = e.target.querySelector('button[type="submit"]');
        const btnSpan = btn.querySelector('span');
        errorEl.style.display = 'none';
        btn.disabled = true;
        btnSpan.textContent = 'Checking...';

        try {
            const res = await fetch('/api/company/' + encodeURIComponent(slug) + '/check');
            const data = await res.json();
            if (data.exists) {
                window.location.href = '/' + data.slug + '/affiliate/login';
            } else {
                errorEl.style.display = 'block';
                btn.disabled = false;
                btnSpan.textContent = 'Go to Portal';
            }
        } catch {
            errorEl.style.display = 'block';
            btn.disabled = false;
            btnSpan.textContent = 'Go to Portal';
        }
    }
    document.getElementById('company-slug').addEventListener('input', () => {
        document.getElementById('slug-error').style.display = 'none';
    });
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.style.display === 'flex') closeModal(); });

    // ── Floating particles
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const colors = ['rgba(59,130,246,0.55)','rgba(124,58,237,0.55)','rgba(6,182,212,0.45)','rgba(167,139,250,0.45)'];
        for (let i = 0; i < 22; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const sz = Math.random() * 2.5 + 1;
            Object.assign(p.style, {
                width: sz + 'px', height: sz + 'px',
                left: Math.random() * 100 + '%',
                background: colors[Math.floor(Math.random() * colors.length)],
                '--drift': ((Math.random() - 0.5) * 180) + 'px',
                animationDuration: (Math.random() * 18 + 14) + 's',
                animationDelay: (Math.random() * -25) + 's',
            });
            document.body.appendChild(p);
        }
    }
</script>
</body>
</html>
