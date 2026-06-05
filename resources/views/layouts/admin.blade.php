<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('meta_title', 'Vendly Panel')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-logo.svg') }}">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
        }

        * {
            box-sizing: border-box;
        }

        img,
        table {
            max-width: 100%;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .mobile-topbar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .mobile-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #111827;
        }

        .mobile-brand img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: block;
        }

        .menu-toggle {
            border: 1px solid #d1d5db;
            background: white;
            color: #111827;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .menu-toggle svg {
            width: 20px;
            height: 20px;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #111111 0%, #1a1a1a 100%);
            padding: 24px 18px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-sizing: border-box;
            flex-shrink: 0;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding: 8px 10px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-brand img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            min-width: 0;
        }

        .sidebar-brand-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #ffffff;
        }

        .sidebar-brand-subtitle {
            margin: 4px 0 0;
            font-size: 12px;
            color: #ff8a33;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .sidebar a {
            display: block;
            margin: 8px 0;
            padding: 10px 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .sidebar a:hover {
            background: rgba(255, 106, 0, 0.14);
            color: #ffffff;
            transform: translateX(2px);
        }

        .sidebar-menu-group {
            margin: 8px 0;
        }

        .sidebar-menu-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            list-style: none;
            transition: background .2s ease, color .2s ease;
        }

        .sidebar-menu-group summary::-webkit-details-marker {
            display: none;
        }

        .sidebar-menu-group summary::after {
            content: "v";
            font-size: 16px;
            line-height: 1;
            transition: transform .2s ease;
        }

        .sidebar-menu-group[open] summary {
            background: rgba(255, 106, 0, 0.14);
            color: #ffffff;
        }

        .sidebar-menu-group[open] summary::after {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            display: grid;
            gap: 4px;
            margin: 6px 0 10px;
            padding-left: 10px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .sidebar-submenu a {
            margin: 0;
            padding: 9px 12px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 13px;
        }

        .main {
            flex: 1;
            padding: 24px;
            box-sizing: border-box;
            min-width: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
        }

        .card,
        .list-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .list-card {
            margin-bottom: 16px;
        }

        .panel-list {
            display: grid;
            gap: 14px;
        }

        .panel-empty {
            padding: 28px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            text-align: center;
        }

        .panel-empty h3 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 20px;
        }

        .panel-empty p {
            margin: 0 0 18px;
            color: #6b7280;
            line-height: 1.5;
        }

        .resource-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: start;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .resource-card--with-media {
            grid-template-columns: minmax(120px, 180px) minmax(0, 1fr) auto;
        }

        .resource-card__media {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
            overflow: hidden;
            background: #f3f4f6;
        }

        .resource-card__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .resource-card__main {
            min-width: 0;
        }

        .resource-card__header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
        }

        .resource-card__title {
            margin: 0;
            color: #111827;
            font-size: 18px;
            line-height: 1.2;
        }

        .resource-card__subtitle {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .resource-card__description {
            margin: 12px 0 0;
            color: #4b5563;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .resource-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .resource-badge {
            min-height: 28px;
            padding: 6px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .resource-badge--active,
        .resource-badge--success {
            background: #dcfce7;
            color: #166534;
        }

        .resource-badge--inactive,
        .resource-badge--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .resource-badge--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .resource-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .resource-metric {
            min-width: 0;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
        }

        .resource-metric__label {
            display: block;
            margin-bottom: 6px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .resource-metric__value {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .resource-metric__value--warning {
            color: #b45309;
        }

        .resource-metric__value--danger {
            color: #991b1b;
        }

        .resource-actions {
            min-width: 180px;
            display: grid;
            gap: 10px;
            justify-items: stretch;
        }

        .resource-actions form {
            margin: 0;
        }

        .resource-actions .btn {
            width: 100%;
        }

        .btn-warning {
            background: #f59e0b;
            color: #ffffff;
        }

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-muted {
            background: #6b7280;
            color: #ffffff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            background: #4f46e5;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            line-height: 1.2;
            min-height: 40px;
        }

        .btn-secondary {
            background: #ff6a00;
            color: #ffffff;
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .delete-confirm-modal[hidden] {
            display: none;
        }

        .delete-confirm-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        body.delete-confirm-open {
            overflow: hidden;
        }

        .delete-confirm-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.58);
        }

        .delete-confirm-dialog {
            position: relative;
            width: min(100%, 420px);
            padding: 22px;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
        }

        .delete-confirm-dialog h2 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 22px;
            line-height: 1.15;
        }

        .delete-confirm-dialog p {
            margin: 0;
            color: #4b5563;
            line-height: 1.55;
        }

        .delete-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .admin-pagination {
            overflow-x: auto;
        }

        .admin-pagination nav {
            display: flex;
            justify-content: center;
        }

        .admin-pagination .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
        }

        .admin-pagination .page-link {
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
        }

        .admin-pagination .page-item.active .page-link {
            border-color: #4f46e5;
            background: #4f46e5;
            color: #ffffff;
        }

        .admin-pagination .page-item.disabled .page-link {
            opacity: 0.45;
            cursor: not-allowed;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        textarea.long-textarea {
            min-height: 180px;
            resize: vertical;
            line-height: 1.6;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .order-filter-panel {
            display: grid;
            grid-template-columns: minmax(180px, 260px) minmax(0, 1fr);
            gap: 10px 14px;
            align-items: end;
        }

        .order-filter-panel .field-label {
            grid-column: 1 / -1;
            margin-bottom: 0;
        }

        .order-filter-panel select {
            margin-bottom: 0;
        }

        .order-filter-count {
            color: #6b7280;
            font-size: 14px;
            font-weight: 700;
            padding-bottom: 11px;
        }

        .ai-assistant-panel {
            display: grid;
            gap: 12px;
            margin: 0 0 16px;
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        }

        .ai-assistant-panel__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .ai-assistant-panel__head h3 {
            margin: 0;
            color: #111827;
            font-size: 16px;
        }

        .ai-assistant-panel__head p,
        .ai-assistant-status {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.45;
        }

        .ai-assistant-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ai-assistant-actions .btn {
            width: auto;
            min-height: 36px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .ai-assistant-status.is-error {
            color: #991b1b;
        }

        .ai-assistant-credits,
        .ai-assistant-packages {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            color: #1f2937;
            font-size: 12px;
        }

        .ai-assistant-credits strong,
        .ai-assistant-packages span {
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #fff;
            padding: 5px 9px;
        }

        .ai-assistant-credits span {
            color: #64748b;
        }

        .ai-assistant-preview {
            display: grid;
            gap: 8px;
        }

        .ai-assistant-preview[hidden] {
            display: none;
        }

        .ai-assistant-preview img {
            display: block;
            width: min(100%, 360px);
            max-height: 220px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            object-fit: cover;
        }

        .ai-assistant-preview p {
            margin: 0;
            color: #2563eb;
            font-size: 13px;
        }

        .ai-credit-admin-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .ai-credit-admin-form select {
            width: min(100%, 220px);
            min-height: 38px;
            margin: 0;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-size: 13px;
        }

        .rich-editor {
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .rich-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .rich-toolbar button {
            width: auto;
            min-height: 34px;
            margin: 0;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            font-size: 13px;
        }

        .rich-content {
            min-height: 150px;
            padding: 12px;
            line-height: 1.6;
            outline: none;
        }

        .rich-content:empty::before {
            content: "Escribe caracteristicas, beneficios, materiales, garantias o cuidados del producto...";
            color: #9ca3af;
        }

        input[type="file"] {
            padding: 12px;
            border: 1px dashed #4f46e5;
            background: #eef2ff;
            color: #312e81;
        }

        input[type="file"]::file-selector-button {
            margin-right: 12px;
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #4f46e5;
            color: #ffffff;
            cursor: pointer;
        }

        input[type="file"]::file-selector-button:hover {
            background: #4338ca;
        }

        .flash {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
        }

        .flash.success {
            background: #dcfce7;
            color: #166534;
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .thumb {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            margin-bottom: 10px;
        }

        .product-image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 12px;
            margin: 0 0 18px;
        }

        .product-image-preview[hidden] {
            display: none;
        }

        .product-image-preview-item {
            min-width: 0;
            display: grid;
            gap: 6px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
        }

        .product-image-preview-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
        }

        .product-image-preview-item span {
            overflow: hidden;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            html {
                overflow-x: hidden;
            }

            .mobile-topbar {
                display: flex;
            }

            .container {
                display: block;
                min-width: 0;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(82vw, 320px);
                z-index: 60;
                transform: translateX(-100%);
                transition: transform .22s ease;
                overflow-y: auto;
                overscroll-behavior: contain;
                box-shadow: 14px 0 32px rgba(17, 24, 39, 0.14);
            }

            .sidebar.is-open {
                transform: translateX(0);
            }

            .main {
                padding: 18px 16px 28px;
                width: 100%;
                overflow-x: hidden;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(17, 24, 39, 0.35);
                opacity: 0;
                pointer-events: none;
                transition: opacity .2s ease;
                z-index: 55;
            }

            .sidebar-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card,
            .list-card {
                padding: 16px;
                border-radius: 14px;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .header .btn,
            .list-card .btn {
                width: 100%;
            }

            .list-card form[style*="display:inline-block"],
            .list-card a.btn[style*="display:inline-block"] {
                display: block !important;
                width: 100%;
                margin: 8px 0 0 !important;
            }

            .list-card form[style*="display:inline-block"] .btn {
                width: 100%;
            }

            .resource-card,
            .resource-card--with-media {
                grid-template-columns: 1fr;
            }

            .resource-card__media {
                max-height: 260px;
            }

            .resource-card__header {
                display: grid;
            }

            .resource-badges {
                justify-content: flex-start;
            }

            .resource-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .resource-actions {
                min-width: 0;
            }

            .admin-pagination nav {
                justify-content: flex-start;
            }

            .admin-pagination .pagination {
                justify-content: flex-start;
                flex-wrap: nowrap;
                min-width: max-content;
            }
        }

        @media (max-width: 720px) {
            .mobile-topbar {
                padding: 12px 14px;
            }

            .mobile-brand {
                font-size: 14px;
            }

            .menu-toggle {
                width: 40px;
                height: 40px;
                border-radius: 9px;
            }

            .sidebar {
                width: min(88vw, 320px);
                padding: 18px 14px 24px;
            }

            .sidebar h3 {
                font-size: 18px;
                margin-bottom: 14px;
            }

            .sidebar a {
                margin: 6px 0;
                padding: 12px 12px;
                font-size: 14px;
            }

            .sidebar-menu-group summary {
                padding: 12px;
                font-size: 14px;
            }

            .main {
                padding: 14px 12px 24px;
            }

            .header {
                align-items: stretch;
                flex-direction: column;
                margin-bottom: 16px;
                gap: 10px;
            }

            .header h2 {
                font-size: 22px;
                line-height: 1.15;
            }

            .card,
            .list-card {
                padding: 14px;
                border-radius: 12px;
            }

            input,
            textarea,
            select {
                width: 100%;
                padding: 12px 10px;
                font-size: 16px;
            }

            .order-filter-panel {
                grid-template-columns: 1fr;
            }

            .order-filter-count {
                padding-bottom: 0;
            }

            textarea.long-textarea {
                min-height: 220px;
            }

            .thumb {
                width: 100%;
                height: auto;
                max-height: 240px;
                margin-bottom: 12px;
            }

            input[type="file"]::file-selector-button {
                width: 100%;
                margin: 0 0 10px;
            }

            .delete-confirm-dialog {
                padding: 18px;
            }

            .delete-confirm-actions {
                flex-direction: column-reverse;
            }

            .resource-metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .mobile-topbar {
                padding: 10px 12px;
            }

            .mobile-brand {
                font-size: 13px;
            }

            .sidebar {
                width: 92vw;
                padding: 16px 12px 22px;
            }

            .main {
                padding: 12px 10px 20px;
            }

            .header h2 {
                font-size: 20px;
            }

            .card,
            .list-card {
                padding: 12px;
                border-radius: 10px;
            }

            .sidebar a,
            .btn {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="mobile-topbar">
        <div class="mobile-brand">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
            <span>{{ auth()->user()->isAdmin() ? 'Vendly Admin' : 'Vendly Store' }}</span>
        </div>
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
            </svg>
        </button>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="container">
        <aside class="sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
                <div class="sidebar-brand-text">
                    <p class="sidebar-brand-title">Vendly</p>
                    <p class="sidebar-brand-subtitle">{{ auth()->user()->isAdmin() ? 'Panel admin' : 'Mi tienda' }}</p>
                </div>
            </div>

            <a href="/dashboard">Panel Administrativo</a>

            @if (auth()->user()->isAdmin())
                <details class="sidebar-menu-group" {{ request()->is('admin/users*') ? 'open' : '' }}>
                    <summary>Usuarios</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/users">Ver usuarios</a>
                        <a href="/admin/users/create">Crear usuario</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/banners*') ? 'open' : '' }}>
                    <summary>Banners</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/banners">Ver banners</a>
                        <a href="/admin/banners/create">Crear banner</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/testimonials*') ? 'open' : '' }}>
                    <summary>Testimonios</summary>
                    <div class="sidebar-submenu">
                        <a href="{{ route('admin.testimonials.index') }}">Ver testimonios</a>
                        <a href="{{ route('admin.testimonials.create') }}">Crear testimonio</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/stores*') ? 'open' : '' }}>
                    <summary>Tiendas</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/stores">Ver tiendas</a>
                        <a href="{{ route('admin.stores.visits') }}">Visitas</a>
                        <a href="{{ route('admin.stores.create-with-user') }}">Crear cliente + tienda</a>
                        <a href="/admin/stores/create">Crear tienda</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/products*') ? 'open' : '' }}>
                    <summary>Productos</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/products">Ver productos</a>
                        <a href="/admin/products/create">Crear producto</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/categories*') || request()->is('admin/stores*/categories') ? 'open' : '' }}>
                    <summary>Categorias</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/categories">Ver categorias</a>
                    </div>
                </details>
            @else
                @php
                    $sidebarUser = auth()->user();
                    $sidebarStores = $sidebarUser?->stores()->get() ?? collect();
                    $sidebarStore = $sidebarUser?->store ?? $sidebarStores->first();
                    $sidebarAllowsTemplates = $sidebarStores->contains(fn ($store) => $store->allowsTemplates());
                @endphp
                <details class="sidebar-menu-group" {{ request()->is('admin/onboarding') || request()->is('admin/store-settings') || request()->is('admin/templates*') || request()->is('admin/payments*') || request()->is('admin/categories*') ? 'open' : '' }}>
                    <summary>Tienda</summary>
                    <div class="sidebar-submenu">
                        <a href="{{ route('admin.store.onboarding') }}">Primeros pasos</a>
                        <a href="/admin/store-settings">Configuracion</a>
                        @if($sidebarAllowsTemplates)
                            <a href="{{ route('admin.templates.index') }}">Plantillas</a>
                        @endif
                        @if(($sidebarStore?->allowsOnlinePayments() ?? false))
                            <a href="{{ route('admin.payments.index') }}">Metodos de pago</a>
                        @endif
                        @if(($sidebarStore?->allowsCategories() ?? true))
                            <a href="/admin/categories">Categorias</a>
                        @endif
                        @if(($sidebarStore?->allowsVisitStats() ?? false))
                            <a href="{{ route('admin.store.visits') }}">Visitas</a>
                        @endif
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/products*') ? 'open' : '' }}>
                    <summary>Productos</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/products">Ver productos</a>
                        <a href="/admin/products/create">Crear producto</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/orders*') ? 'open' : '' }}>
                    <summary>Pedidos</summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/orders">Ver pedidos</a>
                    </div>
                </details>
            @endif

            <a href="/profile">Perfil</a>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:18px;">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%;">Cerrar sesión</button>
            </form>
        </aside>

        <main class="main">
            @yield('content')
        </main>
    </div>

    <div class="delete-confirm-modal" data-delete-confirm-modal hidden>
        <div class="delete-confirm-backdrop" data-delete-confirm-cancel></div>
        <div class="delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
            <h2 id="deleteConfirmTitle">Confirmar eliminacion</h2>
            <p id="deleteConfirmMessage" data-delete-confirm-message>Esta accion no se puede deshacer.</p>
            <div class="delete-confirm-actions">
                <button type="button" class="btn btn-secondary" data-delete-confirm-cancel>Cancelar</button>
                <button type="button" class="btn btn-danger" data-delete-confirm-submit>Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (!toggle || !sidebar || !backdrop) {
                return;
            }

            const setOpen = (open) => {
                sidebar.classList.toggle('is-open', open);
                backdrop.classList.toggle('is-visible', open);
                document.body.classList.toggle('sidebar-open', open);
            };

            toggle.addEventListener('click', () => {
                setOpen(!sidebar.classList.contains('is-open'));
            });

            backdrop.addEventListener('click', () => setOpen(false));

            window.addEventListener('resize', () => {
                if (window.innerWidth > 900) {
                    setOpen(false);
                }
            });
        })();
    </script>
    <script>
        (() => {
            const modal = document.querySelector('[data-delete-confirm-modal]');
            const message = document.querySelector('[data-delete-confirm-message]');
            const submitButton = document.querySelector('[data-delete-confirm-submit]');
            const cancelButtons = document.querySelectorAll('[data-delete-confirm-cancel]');
            let pendingForm = null;

            if (!modal || !message || !submitButton) {
                return;
            }

            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('delete-confirm-open');
                pendingForm = null;
            };

            const openModal = (form) => {
                pendingForm = form;
                message.textContent = form.dataset.confirmMessage || 'Esta accion no se puede deshacer.';
                modal.hidden = false;
                document.body.classList.add('delete-confirm-open');
                submitButton.focus();
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-confirm-delete]');

                if (!form) {
                    return;
                }

                event.preventDefault();
                openModal(form);
            });

            submitButton.addEventListener('click', () => {
                if (!pendingForm) {
                    closeModal();
                    return;
                }

                HTMLFormElement.prototype.submit.call(pendingForm);
            });

            cancelButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });
        })();
    </script>
    <script src="{{ asset('js/image-upload-optimizer.js') }}?v={{ filemtime(public_path('js/image-upload-optimizer.js')) }}"></script>
    <script src="{{ asset('js/product-image-preview.js') }}?v={{ filemtime(public_path('js/product-image-preview.js')) }}"></script>
    @stack('scripts')
</body>

</html>
