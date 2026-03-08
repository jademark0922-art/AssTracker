<?php
require 'auth.php';
if (!empty($_SESSION['uid'])) redirect('dashboard.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssTracker — Log In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAEAAQADASIAAhEBAxEB/8QAHAABAQACAwEBAAAAAAAAAAAAAAYDBQIEBwEI/8QAShAAAgEDAQMGCgcFBwEJAAAAAAECAwQRBQYSIRMUMUFRkgcWMjRTVGFzsdEiZHGBk6PhCBUjUlVEY3KClKHBFyQ2QmZ1kaWz4//EABoBAQADAQEBAAAAAAAAAAAAAAABAgMEBQf/xAAuEQEAAQMCAwYGAgMAAAAAAAAAAQIDERIxBAVBFCEiUWGRExVSscHwU9EjofH/2gAMAwEAAhEDEQA/APxkAAB2dOsbvULlW9nRlWqtN4TSwl1tvgjb7L7M19XjzmtOVvaJ4Ut3MqnHio/78e3t449GsrS2sqCoWlCFGmuqKxl4xl9r4Li+JaIy5rvExR3R3ykNM2F8qWp3nsjG3+7jvSX28MfeUVrs7oltvcnptCW9jPKJ1Ojs3s4+42gLYhw1Xq6t5cacIU6cadOEYQikoxisJJdCSOQBLMAAAAAAAAAAAAAAAAAAAAADjUhCpTlTqQjOEk1KMllNPpTRyAGrutndEud3lNNoR3c45NOn09u7jP3k7qewvky0y89ko3H38d6K+zhj7y2BGIaU3q6dpeN6jY3en3Lt7yjKjVSTw2nlPrTXBnWPZr20tr2g6F3QhWpvqks4eMZXY+L4riec7UbM19IjzmjOVxaN4ct3EqfHgpf7ce3s4ZrMYd1riYr7p7pT4AKukKDYzQVq9zKtc7ytKLW8llco/wCVP49fFduVqNNsq+oX1Kzt1F1arwt54S4Zbf2JNnrtha0bKyo2lBYp0oqK4LL9rx1vpftLRGXNxN3RGI3llpwhTpxp04RhCKSjGKwkl0JI5AF3mgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABxqQhUpyp1IRnCSalGSymn0po5ADzLbPQVpFzGtbbztKze6nl8m/wCVv4dfB9mXPns1/a0b2yrWldZp1YuL4LK9qz1rpXtPItSsq+n31WzuFFVaTw915T4ZTX2pplJjD0uHu64xO8K7wZWPnOpyl/cQin9kpN8P8OOPaWxq9krfm2zdjT39/NJVM4x5b3sfdnBtC0bOG9VqrmQAEswAAAAAAAAAAVvg72MqbU1q1atcTtbG3lFTnGnmVRvi4xb4J46XxxmPB5Pa9F0DRtFilpmm29tJRceUjHNRpvLTm8yaz2vqXYNltLjouztjpiUFKhRSqbkm4uo+M2m+OHJt/f1GzPjfPOeX+YX6oiqYtxPdH5nzzv37bQ+o8o5RZ4KzTM05rnefxHlj/YADwHtAAA6WraTpmrUeS1Kwt7qKjKMXUgnKCl07r6YvguKw+CPH/CXsHHQKMtW02rOpYTrbsqLi3K3T6PpccxzlZeGsxXFvJ7aYry2o3lnWtLmG/Rr05U6kctb0ZLDWVxXBnsco51xHLbsTTVM0daekx+J9Xl8z5VZ463MVREVdJ65/MPyyDsanaVNP1G5sK0oSq21adGbg8xbi2njPVwOufaaaoqiKo2l8pqpmmZidwAFkAAAAAAAABE+E6x821OMv7icW/tlFrh/izx7C2NXtbb852bvqe/uYpOpnGfIe9j78YInZpZq01xLZU4Qp0406cIwhFJRjFYSS6EkcgCWYAAAB29Ps3dNtycYR6Xjp9iCJnDqApKNCjRX8KnGPVnHH/wBzIThn8RLgqAMHxPRLgqAMHxPR7toOo09W0Wz1KluKNzRjUcYz31BtcY562nlP2o7p5XsFtXDR4zsdRlWnaTadKUfpKi2/pcOndec8OzgnlnqFtXoXNGNe2rU61KWd2dOSlF4eODR8U53yi7y3iKqZjwTPhnpMf3HV9a5RzS1zCxFUT4ojvjrE/wBSyAA8Z6wAABxrVadGjOtWqQp0qcXKc5yxGKXFtt9CFWpClTlVqzjCnBOUpSeFFLpbfUjzvb7a+hdW1TStKq1GnPdr14tKM444xi+lpvpfDo60z0uVcqv8yvxbtx3dZ6RDz+Zcys8BZm5cnv6R1mXkus3n7x1e91Dk+S51cVK25vZ3d6TeM9eMnUKgH3Ci3FFMU07Q+Q1Xpqqmqd5S4KgFsK/E9EuCoOFWlSqrFSEZfaugYPiJoHe1Kx5BcrTbcG+jHknRIaROQABIcakIVKcqdSEZwkmpRkspp9KaOQAAAAAABSWtLkbeFLh9FccdvWTZUEwyuAALMgAAAAAM1pd3VnUdW0ua1vUa3XKlNxbXZldXBGEFaqYqjFUZhamqaZzE4lsP35rf9Y1D/Uz+Y/fmt/1jUP8AUz+ZrwY9j4f+OPaGvar/ANc+8th+/Nb/AKxqH+pn8x+/Nb/rGof6mfzNeB2Ph/449oO1X/rn3l2Ly+vb3c55eXFzuZ3eVque7npxl8OhHXANqKKaI00xiGVVdVc5qnMgALKgAAAAD5OKnBwksxksNE1Ug6dSUHjMW08FMTl553W95L4kS1tsQAKtQAAAAAAAAqCXKgmGVzoAAsyAAAB8nJQg5yeIxWWz5TnCoswnGS6MxeQOQAAAAAAAAAAAAAAAAAAE5eed1veS+JRk5eed1veS+JEtLe7EACrYAAAAAAAAKglyoJhlc6AALMnq/wCzjoGmanrOoarfUOXr6byLtoy4wjOe/wDTa65LcWOzOelJr38/IGl7Ya7sts1rFvolKrQepSo0quoRzm3SVR7kWvJnJN4lnKUZY48Y+yeCLwwafrui1rXae4p2mrWFtOvVq7v0bqlTi5SqRil5aim5QS44zFYyo0q3d/D1UxTENX+1XtXzTR7TZC1n/Gv8XN3w6KMZfQjxjj6U45ymmuTw+EjwfSbKrShC7rUZwjVi3QlKLSnHLi5Rz0rMXHK61JdRsdRr6t4R/CPUq0aX/btYu1GlBreVGHCMVJxjlxhBLMt3oi2+s9B8OWj2Wga7oujadDctbPRqVKGUk5YqVcylhJOUnlt44tt9Yhnemaomp58ASW0Fpd1dXrzpW1acHu4lGm2n9FFpnDKxai7ViZwrQQfML71K5/Cl8hzC+9SufwpfIrr9HV2Kn6/33XgIPmF96lc/hS+Q5hfepXP4UvkNfodip+v9914CD5hfepXP4UvkOYX3qVz+FL5DX6HYqfr/AH3XgIPmF96lc/hS+Q5hfepXP4UvkNfodip+v9914CD5hfepXP4UvkOYX3qVz+FL5DX6HYqfr/fdeAg+YX3qVz+FL5Fpp0ZQ0+2jKLjJUopprDTwiaassL/DxaiJirLsE5eed1veS+JRk5eed1veS+JMsre7EACrYAAAAAAAAKglyoJhlc6AALMl94H9a2dtamqbO7U21Oppmtwp0p1Kr/h05Qct3e64puWVNNbrSfDyoxPhA2NoaFtPTsdD1nT9Y0+8nizr07yk3DL8is08Qaz5TxFrjwxJR64Iw1i7inGHpn7O2i6Ns9rOpa3tLrGiW19b4tbSlPUKE93eipTqJptPhKMVKMvSRZpfDDtRZbV7X8906nNWtvQjbU6k+DrKMpS38dMU3J4T44Sbw3hRoERgquzVTpAASyAAAAAAAAAAAAAAAACcvPO63vJfEoycvPO63vJfEiWlvdiABVsAAAAAAAAFQS5UEwyudAAFmQCh2C2R1PbDWVY2K5OhTxK5uZRzChB9b7ZPDxHr9iTa9K/6Df8Amv8A+P8A/wBCMxDSm1XVGYh4oD0Lwo+DW32I2Sq6zV2k5xVlVhQt6HMXDlKkuON7fljEVKXFYe7jpaPL9OvatzNwlSjw4uSbSXsGYKrdVO7vAAlmAAAAAAAAAAAAAAAAE5eed1veS+JRk5eed1veS+JEtLe7EACrYAAAAAAAAKglyoJhlc6AALMnqfgN2k0nZTQdqNZ1m45K3p81jGMeM6s3y2IQXXJ4f2YbbSTa940XVNP1rSrfVNLuqd1Z3MN+lVg+El8U08pp8U008NH5X2V8H1ztxoetV9MruOqabyMrehOSVOupcpvQy/Jl9FYfR1Pg96Oo2F282m2Aqanp1rylOFaFWlVta8cO3uN1xjVUZJ7s4ySymsSSw1wTjSd3dZr00xnZu/2kdq/GHbuel2882Oi71tDh5VZtctLjFNcYqGMtfw8ryjWa9stV2Ut9It7uM4Xt9p0L25pzTTpSnOooww0mmoxjlPOJb3HGDJ4BtkPGzbu351Q5TTNOxdXe9DMJ4f0KbzFxe9Lpi8ZjGeOguP2lv+/Vl/6ZT/8AtqiN1LkZomqXl5pdS13mV7Utua8puY+lymM5SfRj2m6NZfaJaXl1O4q1KynPGVGSxwWOz2Fpz0ZWJtxV/k2dDxn+o/m/oPGf6j+b+h2PFux9Lc96PyHi3Y+lue9H5FfE6tXCeX3dfxn+o/m/oPGf6j+b+h2PFux9Lc96PyHi3Y+lue9H5DxGrhPL7uv4z/Ufzf0HjP8AUfzf0Ox4t2PpbnvR+Q8W7H0tz3o/IeI1cJ5fd1/Gf6j+b+g8Z/qP5v6HY8W7H0tz3o/IeLdj6W570fkPEauE8vu6/jP9R/N/QeM/1H839DseLdj6W570fkPFux9Lc96PyHiNXCeX3dfxn+o/m/ob61q8vbUq27u8pBSxnOMrJqfFux9Lc96PyNvb0o0aFOjFtxhFRTfThLBNOerC/NmYj4cOZOXnndb3kviUZOXnndb3kviTLK3uxAAq2AAAAAAAACoJcqCYZXOgACzJtNltf1PZrWaWq6VX5OvT4Si+MKsH0wmuuLx8GsNJrJtvr3jdr1trWp6RplO5p4Vfm8KkFdxWMRqfTzwSxvJqWOGeEcacDC0V1RGIlY7Gbf3mx9K9o6DoWiW1O7rKrNOnVm1iKioqTqbzisOSTbw5yxhPBMatqN7q2pV9R1G5nc3VeW9UqT6W/gklhJLgkklwOqBgmuqYxMgACoAAAAAAAAAAAAAAAATl553W95L4lGTl553W95L4kS0t7sQAKtgAAAAAAAAqCXKgmGVzoAAsyAaXaj+z/wCb/g1FKhXqx3qVGpNZxmMWyk14nDttcHFdEVzVhYgjatGrSxytKdPPRvRayb7ZrzGfvX8EIqzOEXuEi3RrirLaAAu4wAAAAAAAAAAAAAAAAnLzzut7yXxKMnLzzut7yXxIlpb3YgAVbAAAA405wqU41Kc4zhJJxlF5TT6GmcgAAAFQS5UEwyudAAFmTS7Uf2f/ADf8GbZrzGfvX8EcNo6NWryHJUp1Mb2d2LeOg1HNLv1Wv+GzOZxVl6luim5w8UTOP+tptR/Z/wDN/wAGbZrzGfvX8EaXml36rX/DZvdn6dSlZTjVpyg+UbxJY6kKe+rKL9NNHD6InLYm80uvQhYU4zrU4yWcpySfSzRgvMZcVi9NmrVEKbnNt6xS76HObb1il30TIK6HV8wq8lNzm29Ypd9DnNt6xS76JkDQfMKvJTc5tvWKXfQ5zbesUu+iZA0HzCryU3Obb1il30Oc23rFLvomQNB8wq8lNzm29Ypd9DnNt6xS76JkDQfMKvJTc5tvWKXfRPXbUrqtKLTTnJprr4mIE004YX+Jm9ERMBOXnndb3kviUZOXnndb3kviTLK3uxAAq2ADjUnCnTlUqTjCEU3KUnhJLpbYGt2SuOc7N2NTc3MUlTxnPkPdz9+Mm0InwZX3nOmSj/fwkl9kZJ8f8OOHaWxEbNL1OmuYAASzCloVFVowqLH0lng849hNHe0y9VBOnVcnB9GP/CTEqV05hugfISjOKlCSlF9DTyj6WYAAAAAAAAAAAAAAAAAAAAAAAfG1FNtpJcW2AbUU22klxbZNVp8pVnUxjek3jsybHU76M4OhQk+nEpLoa7EawrMtqKcd4ACGgava245ts3fVNzfzSdPGceW93P3ZybQifCdfebaZGP8Afzk19sYpcf8AFnh2ETs0s06q4hI6be19PvqV5buKq0nlbyynww0/tTaPXbC6o3tlRu6DzTqxUlxWV7HjrXQ/aeMlBsZry0i5lRud52lZreay+Tf8yXx6+C7MOsTh3cTa1xmN4emg405wqU41Kc4zhJJxlF5TT6Gmci7zQAAcqc503mE5RfRmLwc+c3Hp6vfZiARhl5zcenq99jnNx6er32YgDEMvObj09Xvsc5uPT1e+zEAYhl5zcenq99jnNx6er32YgDEMvObj09Xvsc5uPT1e+zEAYhl5zcenq99jnNx6er32YgDEMvObj09Xvsc5uPT1e+zEAYhl5zcenq99jnNx6er32YgDEMvObj09Xvsc5uPT1e+zEAYhl5zcenq99nCpUqVMcpUnPHRvPODiAYAAEgBxqThTpyqVJxhCKblKTwkl0tsDFf3VGysq13XeKdKLk+Ky/Ys9b6F7TyLUr2vqF9VvLhxdWq8vdWEuGEl9iSRuNs9eWr3MaNtvK0ot7reVyj/ma+HXxfbhTxSZy9Lh7WiMzvIACrpUGy+01fSI82rQlcWjeVHexKnx4uP+/Dt7OOfRrK7tr2gq9pXhWpvri84eM4fY+K4PieMnZ06+u9PuVcWdaVGqk1lJPKfU0+DLROHNd4aK++O6XsgInTNuvKjqdn7Yyt/u4bsn9vHP3FFa7RaJc73J6lQju4zyjdPp7N7GfuLZhw1Wa6d4bQHGnOFSnGpTnGcJJOMovKafQ0zkSzAAAAAAAAAAAAAAAAAAAAAAA41Jwp05VKk4whFNylJ4SS6W2ByBq7raLRLbd5TUqEt7OOTbqdHbu5x95O6nt15MdMs/bKVx9/Ddi/s45+4jMNKbNdW0K+9u7ayoOvd14Uaa65PGXjOF2vg+C4nnO1G01fV482owlb2ieXHezKpx4OX+3Dt7eGNRqN9d6hcu4vK0q1VpLLSWEupJcEdYrM5d1rhoo7575AAVdL//2Q==">
  <style>
    .auth-split {
      display: flex;
      width: 100%;
      max-width: 900px;
      background: var(--surface);
      border-radius: var(--r-xl);
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      border: 2px solid var(--border);
    }
    .auth-panel-left {
      background: var(--teal);
      padding: 52px 44px;
      width: 45%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .auth-panel-left::after {
      content: '';
      position: absolute;
      bottom: -60px; right: -60px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
    }
    .auth-panel-left::before {
      content: '';
      position: absolute;
      top: -40px; left: -40px;
      width: 120px; height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
    }
    .auth-panel-left .logo { font-size: 32px; color: #fff; margin-bottom: 28px; }
    .auth-left-heading {
      font-family: 'Montserrat', sans-serif;
      font-style: normal;
      font-size: 32px;
      font-weight: 900;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 14px;
    }
    .auth-left-heading span { color: var(--orange); font-style: normal; }
    .auth-left-sub {
      font-size: 13.5px;
      color: rgba(255,255,255,.55);
      line-height: 1.7;
      font-weight: 500;
      margin-bottom: 36px;
    }
    .auth-left-pills { display: flex; flex-direction: column; gap: 10px; }
    .auth-left-pill {
      display: flex; align-items: center; gap: 10px;
      font-size: 12.5px; font-weight: 700;
      color: rgba(255,255,255,.8);
    }
    .auth-left-pill-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--orange); flex-shrink: 0;
    }
    .auth-panel-right {
      flex: 1;
      padding: 52px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .auth-panel-right .auth-brand { text-align: left; }
    .auth-panel-right .auth-brand .logo { font-size: 26px; }
    @media (max-width: 680px) {
      .auth-split { flex-direction: column; }
      .auth-panel-left { width: 100%; padding: 36px 28px; }
      .auth-panel-right { padding: 36px 28px; }
    }
  </style>
</head>
<body class="auth-body">
  <div class="auth-split">
    <div class="auth-panel-left">
      <a href="index.php" class="logo">AssignmentTracker</a>
      <div class="auth-left-heading">Welcome</div>
      <div class="auth-left-sub">Your tasks are waiting. Log in with your username to pick up right where you left off.</div>
      <div class="auth-left-pills">
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Board &amp; List view</div>
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Deadline tracking</div>
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Firebase cloud sync</div>
      </div>
    </div>
    <div class="auth-panel-right">
      <div class="auth-brand" style="margin-bottom:28px;">
        <p class="auth-card-sub" style="font-size:22px;font-weight:800;color:var(--text);margin:0;">Log in to your account</p>
        <p class="auth-card-sub" style="margin-top:4px;">Enter your credentials below</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
      <?php endif; ?>
      <form method="POST" action="auth.php">
        <input type="hidden" name="auth_action" value="login">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="your_username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus
                 autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">
            Password
            <a href="forgot.php" class="label-link">Forgot password?</a>
          </label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-label="Show password">
              <!-- Eye icon (shown when password is hidden) -->
              <svg id="password-eye-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <!-- Eye-off icon (shown when password is visible) -->
              <svg id="password-eye-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.07-3.346M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.423 5.337M3 3l18 18"/>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Log in</button>
      </form>
      <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up</a></p>
    </div>
  </div>

  <script>
    function togglePw(inputId, btn) {
      const input   = document.getElementById(inputId);
      const showIcon = document.getElementById(inputId + '-eye-show');
      const hideIcon = document.getElementById(inputId + '-eye-hide');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      showIcon.style.display = isHidden ? 'none'  : '';
      hideIcon.style.display = isHidden ? ''      : 'none';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    }
  </script>
</body>
</html>