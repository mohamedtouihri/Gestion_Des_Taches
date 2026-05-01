<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- Makes the page look good on mobile screens too -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tâches</title>

    <!-- Google Font: clean and readable for beginners -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ===== GLOBAL RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; /* Makes sizing easier to understand */
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #f0f4f8; /* Light gray-blue background */
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ===== CARD CONTAINER (used for forms) ===== */
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* ===== HEADINGS ===== */
        h2 {
            font-size: 1.6rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 28px;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #2d3748;
            transition: border-color 0.2s;
            background: #f7fafc;
        }

        /* Highlight the field when the user clicks on it */
        input:focus {
            outline: none;
            border-color: #4299e1;
            background: #fff;
        }

        /* ===== PASSWORD WRAPPER (for show/hide button) ===== */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px; /* Space for the eye button */
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #a0aec0;
            font-size: 1rem;
            padding: 0;
        }

        .toggle-password:hover {
            color: #4299e1;
        }

        /* ===== HINT TEXT (shown below password field) ===== */
        .hint {
            font-size: 0.78rem;
            color: #a0aec0;
            margin-top: 5px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            margin-top: 6px;
        }

        .btn:active {
            transform: scale(0.98); /* Tiny press effect */
        }

        .btn-primary {
            background-color: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3182ce;
        }

        /* ===== MESSAGES (error / success) ===== */
        .msg {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 18px;
        }

        /* Red box for errors */
        .msg-error {
            background-color: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        /* Green box for success */
        .msg-success {
            background-color: #f0fff4;
            color: #276749;
            border: 1px solid #9ae6b4;
        }

        /* ===== LINK AT BOTTOM OF FORM ===== */
        .form-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 0.88rem;
            color: #718096;
        }

        .form-footer a {
            color: #4299e1;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* ===== DIVIDER ===== */
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        /* ===== NAVBAR (for dashboard) ===== */
        .navbar {
            width: 100%;
            background: white;
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: fixed;
            top: 0;
        }

        .navbar .logo {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
        }

        .navbar a {
            color: #e53e3e;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ===== DASHBOARD SPECIFIC ===== */
        .dashboard-wrapper {
            width: 100%;
            max-width: 620px;
            margin-top: 80px;
            padding: 24px 16px;
        }

        .task-list {
            list-style: none;
            margin-top: 16px;
        }

        .task-list li {
            background: white;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .task-actions a {
            font-size: 0.82rem;
            margin-left: 10px;
            text-decoration: none;
            font-weight: 500;
        }

        .task-actions .edit { color: #4299e1; }
        .task-actions .delete { color: #e53e3e; }

        .empty-state {
            text-align: center;
            color: #a0aec0;
            padding: 40px 0;
        }
    </style>
</head>
<body>
