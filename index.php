<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssTracker — Get Productive</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .landing-hero-card {
      background: var(--teal);
      border-radius: var(--r-xl);
      padding: 36px 40px;
      margin-bottom: 36px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 16px 48px rgba(30,61,58,.35);
      text-align: left;
    }
    .landing-hero-card::after {
      content: '';
      position: absolute;
      right: -30px; bottom: -30px;
      width: 140px; height: 140px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .landing-hero-card::before {
      content: '';
      position: absolute;
      right: 40px; bottom: 40px;
      width: 70px; height: 70px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .hero-card-eyebrow {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .12em;
      color: var(--orange);
      margin-bottom: 12px;
    }
    .hero-card-title {
      font-size: clamp(26px, 4vw, 40px);
      font-weight: 900;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 8px;
    }
    .hero-card-title span { color: var(--orange); }
    .hero-card-sub {
      font-size: 14px;
      color: rgba(255,255,255,.6);
      font-weight: 500;
      margin-bottom: 28px;
      max-width: 320px;
    }
    .progress-ring-wrap {
      position: absolute;
      right: 40px; top: 50%;
      transform: translateY(-50%);
    }
    .progress-ring { transform: rotate(-90deg); }
    .ring-bg  { fill: none; stroke: rgba(255,255,255,.12); stroke-width: 8; }
    .ring-fill {
      fill: none;
      stroke: var(--orange);
      stroke-width: 8;
      stroke-linecap: round;
      stroke-dasharray: 250;
      stroke-dashoffset: 50; 
    }
    .ring-label {
      position: absolute;
      inset: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
      font-weight: 900;
      color: #fff;
    }
    @media (max-width: 480px) {
      .landing-hero-card { padding: 24px 22px; }
      .progress-ring-wrap { display: none; }
      .hero-card-title { font-size: 26px; }
      .schedule-grid { grid-template-columns: 1fr !important; }
    }
    .schedule-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 36px;
    }
    .schedule-tile {
      background: var(--surface);
      border: 2px solid var(--border);
      border-radius: var(--r);
      padding: 20px;
      box-shadow: var(--shadow-xs);
      transition: transform .2s, box-shadow .2s;
    }
    .schedule-tile:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .schedule-tile.teal-tile {
      background: var(--teal);
      border-color: transparent;
      box-shadow: 0 8px 28px rgba(30,61,58,.3);
    }
    .schedule-tile.orange-tile {
      background: var(--orange-pale);
      border-color: transparent;
    }
    .tile-title {
      font-size: 14px;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 4px;
      line-height: 1.3;
    }
    .teal-tile .tile-title { color: #fff; }
    .tile-time {
      font-size: 11.5px;
      color: var(--muted);
      font-weight: 600;
    }
    .teal-tile .tile-time { color: rgba(255,255,255,.55); }
    .tile-more {
      font-size: 12px;
      color: rgba(255,255,255,.7);
      margin-top: 6px;
      font-weight: 700;
    }
  </style>
</head>
<body class="auth-body">
  <div class="landing-wrap">

    <div class="landing-brand">
      <div class="logo">AssTracker</div>
      <div class="logo-sub">Universal Assignment Tracker</div>
    </div>

    <div class="landing-hero-card">
      <div class="hero-card-eyebrow">Your productivity dashboard</div>
      <div class="hero-card-title">Let's become<br>more <span>Productive</span></div>
      <div class="hero-card-sub">Track every task, deadline & priority — all in one beautiful place.</div>
      <a href="login.php" class="btn btn-primary">Get Started</a>
      <div class="progress-ring-wrap">
        <div style="position:relative;width:80px;height:80px;">
          <svg width="80" height="80" class="progress-ring">
            <circle class="ring-bg"   cx="40" cy="40" r="35"/>
            <circle class="ring-fill" cx="40" cy="40" r="35"/>
          </svg>
          <div class="ring-label">95%</div>
        </div>
      </div>
    </div>

    <p class="hero-sub" style="text-align:left;margin:0 0 18px">Today's Schedule</p>

    <div class="schedule-grid">
      <div class="schedule-tile">
        <div class="tile-title">Board & List Views</div>
        <div class="tile-time">Kanban + Table layout</div>
      </div>
      <div class="schedule-tile orange-tile">
        <div class="tile-title">Priority Levels</div>
        <div class="tile-time">Low · Medium · High · Urgent</div>
      </div>
      <div class="schedule-tile orange-tile">
        <div class="tile-title">Deadline Tracking</div>
        <div class="tile-time">Overdue alerts built-in</div>
      </div>
      <div class="schedule-tile teal-tile">
        <div class="tile-title" style="font-size:13px">Your goals start here ✦</div>
        <div class="tile-more">Stay ahead every day</div>
      </div>
    </div>

    <div class="feature-pills">
      <span class="pill">Firebase sync</span>
      <span class="pill">Team assignments</span>
      <span class="pill">7 categories</span>
      <span class="pill">Stats dashboard</span>
    </div>
  </div>
</body>
</html>