@verbatim
<!DOCTYPE html>
<!-- saved from url=(0048)/perpustakaan -->
<html lang="id" class="scroll-smooth"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @endverbatim<meta name="csrf-token" content="{{ csrf_token() }}">@verbatim
  <title>E-Perpustakaan Digital | MADRASAH ALIYAH NEGERI 4 BANJAR - Khazanah Literasi Madrasah</title>
  
  <meta name="description" content="Katalog Buku Digital (OPAC), E-Book Reader Interaktif, dan Layanan Literasi Terpadu MADRASAH ALIYAH NEGERI 4 BANJAR">
  <link rel="shortcut icon" href="/madrasah-theme/perpustakaan/logo239.png">
  
  <!-- Font Google: Inter & Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
  <link href="/madrasah-theme/fonts.css" rel="stylesheet">
  
  <!-- Font Awesome 6 Pro Icons -->
  <link rel="stylesheet" href="/madrasah-theme/perpustakaan/all.min.css">
  <!-- Font Awesome 4.7 for legacy support -->
  <link rel="stylesheet" href="/madrasah-theme/perpustakaan/font-awesome.min.css">
  <link rel="stylesheet" href="/madrasah-theme/perpustakaan/iziToast.min.css">

  <!-- Tailwind CSS CDN -->
  <script src="/madrasah-theme/perpustakaan/saved_resource"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            kemenag: {
              50: '#ecfdf5',
              100: '#d1fae5',
              200: '#a7f3d0',
              300: '#6ee7b7',
              400: '#34d399',
              500: '#10b981',
              600: '#059669',
              700: '#047857',
              800: '#065f46',
              900: '#064e3b',
              950: '#022c22'
            },
            accent: {
              gold: '#f59e0b',
              yellow: '#fbbf24',
              soft: '#fef3c7'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
            body: ['Inter', 'sans-serif']
          },
          animation: {
            'marquee-slow': 'marquee 45s linear infinite',
            'marquee-fast': 'marquee 25s linear infinite',
          },
          keyframes: {
            marquee: {
              '0%': { transform: 'translateX(0%)' },
              '100%': { transform: 'translateX(-50%)' }
            }
          }
        }
      }
    }
  </script>

  <style>
    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #059669; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #047857; }

    /* Pause Animation on Hover */
    .pause-on-hover:hover { animation-play-state: paused !important; }

    /* 3D Realistic Book Spine Effect */
    .book-cover-3d-wrap {
      perspective: 900px;
      display: inline-block;
    }
    .book-cover-3d-img {
      border-radius: 4px 10px 10px 4px;
      box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 6px 12px 24px rgba(0,0,0,0.18);
      transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
    }
    .group:hover .book-cover-3d-img {
      transform: translateY(-6px) rotateY(-5deg);
      box-shadow: -6px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.35), 10px 22px 32px rgba(6, 78, 59, 0.28);
    }
    .book-cover-3d-card {
      border-radius: 4px 10px 10px 4px;
      box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 20px rgba(0,0,0,0.15);
      padding: 10px 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #ffffff;
      transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
    }
    .group:hover .book-cover-3d-card {
      transform: translateY(-6px) rotateY(-5deg);
      box-shadow: -6px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.35), 10px 22px 32px rgba(6, 78, 59, 0.28);
    }
    .cover-badge {
      font-size: 8.5px;
      font-weight: 800;
      background: rgba(255, 255, 255, 0.22);
      padding: 2px 6px;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .cover-title {
      font-size: 11px;
      font-weight: 700;
      line-height: 1.35;
      margin: 6px 0;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-shadow: 0 1px 3px rgba(0,0,0,0.4);
    }

    /* Bootstrap 4 Modals Compatibility with Tailwind */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1050;
      display: none;
      width: 100%;
      height: 100%;
      overflow: hidden;
      outline: 0;
    }
    .modal-open { overflow: hidden; }
    .modal.show { display: block; overflow-x: hidden; overflow-y: auto; }
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1040;
      width: 100vw;
      height: 100vh;
      background-color: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(4px);
    }
    .modal-backdrop.fade { opacity: 0; transition: opacity 0.2s linear; }
    .modal-backdrop.show { opacity: 1; }
    .modal.fade .modal-dialog {
      transition: transform 0.3s ease-out;
      transform: translate(0, -30px);
    }
    .modal.show .modal-dialog { transform: none; }
    .modal-dialog {
      position: relative;
      width: auto;
      margin: 1.75rem auto;
      pointer-events: none;
      max-width: 600px;
    }
    #modalDetailBuku .modal-dialog {
      max-width: 660px !important;
      width: 92% !important;
      margin: 1.5rem auto !important;
    }
    .detail-spec-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 5px 12px;
    }
    .detail-ketentuan-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 6px 14px;
    }
    @media (max-width: 640px) {
      #modalDetailBuku .modal-dialog {
        max-width: 95% !important;
        margin: 0.75rem auto !important;
      }
      .detail-buku-layout-wrap {
        flex-direction: column !important;
        gap: 12px !important;
        align-items: center !important;
      }
      .detail-buku-left-col {
        width: 130px !important;
        min-width: 130px !important;
      }
      .detail-buku-left-col .detail-cover-box {
        width: 130px !important;
        height: 175px !important;
        margin-bottom: 0 !important;
      }
      .detail-buku-right-col {
        min-height: auto !important;
        width: 100% !important;
      }
      .detail-spec-grid {
        grid-template-columns: 1fr;
        gap: 3px;
      }
      .detail-ketentuan-grid {
        grid-template-columns: 1fr;
        gap: 6px;
      }
    }
    .modal-dialog-centered {
      display: flex;
      align-items: center;
      min-height: calc(100% - 3.5rem);
    }
    .modal-content {
      position: relative;
      display: flex;
      flex-direction: column;
      width: 100%;
      pointer-events: auto;
      background-color: #ffffff;
      border-radius: 1.25rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(226, 232, 240, 0.8);
      outline: 0;
      overflow: hidden;
    }
    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e2e8f0;
      background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
      color: #ffffff;
    }
    .modal-title { font-size: 1.1rem; font-weight: 700; margin: 0; }
    .modal-header .close {
      background: transparent;
      border: 0;
      font-size: 1.5rem;
      font-weight: 700;
      color: #ffffff;
      opacity: 0.85;
      cursor: pointer;
      line-height: 1;
    }
    .modal-header .close:hover { opacity: 1; }
    .modal-body { position: relative; flex: 1 1 auto; padding: 1.5rem; }
    .modal-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 1rem 1.5rem;
      border-top: 1px solid #e2e8f0;
      background-color: #f8fafc;
      gap: 0.5rem;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-control {
      display: block;
      width: 100%;
      padding: 0.625rem 0.875rem;
      font-size: 0.875rem;
      line-height: 1.5;
      color: #1e293b;
      background-color: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 0.625rem;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-control:focus {
      border-color: #059669;
      outline: 0;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .nav-pills { display: flex; list-style: none; padding: 0; margin: 0; gap: 0.5rem; }
    .nav-pills .nav-link {
      display: block;
      padding: 0.5rem 1rem;
      font-size: 0.8125rem;
      font-weight: 700;
      color: #475569;
      border-radius: 0.5rem;
      text-decoration: none;
      transition: all 0.2s;
    }
    .nav-pills .nav-link.active {
      background-color: #047857;
      color: #ffffff;
    }
    .tab-content > .tab-pane { display: none; }
    .tab-content > .active { display: block; }

    /* Utility Helpers for Reader Toolbar & Modals */
    .d-flex { display: flex !important; }
    .d-none { display: none !important; }
    .d-inline-flex { display: inline-flex !important; }
    .flex-column { flex-direction: column !important; }
    .flex-row { flex-direction: row !important; }
    .flex-fill { flex: 1 1 auto !important; }
    @media (min-width: 768px) {
      .d-md-inline-flex { display: inline-flex !important; }
    }
    .align-items-center { align-items: center !important; }
    .justify-content-between { justify-content: space-between !important; }
    .justify-content-end { justify-content: flex-end !important; }
    .flex-wrap { flex-wrap: wrap !important; }
    .text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mr-1 { margin-right: 0.25rem !important; }
    .mr-2 { margin-right: 0.5rem !important; }
    .mr-3 { margin-right: 1rem !important; }
    .ml-auto { margin-left: auto !important; }
    .font-weight-bold { font-weight: 700 !important; }
    .text-white { color: #ffffff !important; }
    .text-muted { color: #94a3b8 !important; }
    .text-warning { color: #f59e0b !important; }
    .text-info { color: #38bdf8 !important; }

    /* Chrome-Style E-Book Reader Modal Overrides */
    #modalEbookReader .modal-dialog {
      max-width: 95vw !important;
      width: 95vw !important;
      margin: 1.5vh auto !important;
      height: 97vh !important;
    }
    #modalEbookReader .modal-content {
      height: 100% !important;
      border-radius: 1rem !important;
      background: #0f172a !important;
      border: 1px solid #334155 !important;
      display: flex !important;
      flex-direction: column !important;
      overflow: hidden !important;
    }
    @media (max-width: 768px) {
      #modalEbookReader .modal-dialog {
        max-width: 100vw !important;
        width: 100vw !important;
        margin: 0 !important;
        height: 100vh !important;
        max-height: 100vh !important;
      }
      #modalEbookReader .modal-content {
        border-radius: 0 !important;
        border: none !important;
        height: 100vh !important;
      }
      .chrome-pdf-toolbar {
        padding: 0 8px !important;
        height: 48px !important;
        min-height: 48px !important;
      }
      .chrome-pdf-sidebar {
        position: absolute !important;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 50 !important;
        box-shadow: 6px 0 24px rgba(0, 0, 0, 0.75) !important;
        width: 210px !important;
        min-width: 210px !important;
      }
      .chrome-pdf-sidebar.collapsed {
        box-shadow: none !important;
        width: 0 !important;
        min-width: 0 !important;
        padding: 0 !important;
      }
      .chrome-pdf-viewport {
        padding: 10px 4px 60px 4px !important;
      }
      .chrome-page-container {
        margin: 8px auto !important;
        max-width: 100% !important;
      }
      .chrome-page-canvas {
        max-width: 100% !important;
        height: auto !important;
      }
    }

    /* Chrome PDF Reader Toolbar */
    .chrome-pdf-toolbar {
      background: #24292e !important;
      color: #e8eaed !important;
      height: 54px;
      min-height: 54px;
      padding: 0 16px;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      border-bottom: 1px solid #1b1f23;
      z-index: 20;
      user-select: none;
      -webkit-user-select: none;
    }
    .chrome-pdf-btn {
      background: transparent;
      color: #e8eaed;
      border: none;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      cursor: pointer;
      font-size: 13px;
      transition: background-color 0.15s ease, color 0.15s ease;
      outline: none;
    }
    .chrome-pdf-btn:hover {
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
    }
    .chrome-pdf-btn.active {
      background: rgba(255, 255, 255, 0.22);
      color: #38bdf8;
    }
    .chrome-page-box {
      display: inline-flex !important;
      align-items: center !important;
      background: #1f2328;
      border: 1px solid #444c56;
      border-radius: 6px;
      padding: 2px 8px;
      font-size: 12px;
    }
    .chrome-page-input {
      background: transparent;
      border: none;
      color: #ffffff;
      width: 36px;
      text-align: right;
      font-size: 12px;
      font-weight: 600;
      padding-right: 4px;
      outline: none;
    }
    .chrome-pdf-workspace {
      display: flex !important;
      flex: 1 1 auto !important;
      height: calc(100% - 54px) !important;
      position: relative;
      overflow: hidden;
      background: #323639;
    }
    .chrome-pdf-sidebar {
      width: 210px;
      min-width: 210px;
      background: #1f2328;
      border-right: 1px solid #30363d;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 16px 12px;
      display: flex !important;
      flex-direction: column !important;
      gap: 14px;
      transition: width 0.25s ease, min-width 0.25s ease, padding 0.25s ease;
      user-select: none;
    }
    .chrome-pdf-sidebar.collapsed {
      width: 0 !important;
      min-width: 0 !important;
      padding: 0 !important;
      overflow: hidden !important;
      border-right: none !important;
    }
    .chrome-thumb-card {
      position: relative;
      cursor: pointer;
      border-radius: 4px;
      padding: 4px;
      background: #2d333b;
      border: 2px solid transparent;
      text-align: center;
      transition: all 0.2s ease;
    }
    .chrome-thumb-card:hover {
      background: #373e47;
      border-color: #636e7b;
    }
    .chrome-thumb-card.active {
      border-color: #38bdf8;
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.4);
    }
    .chrome-thumb-canvas {
      width: 100%;
      height: auto;
      display: block;
      box-shadow: 0 1px 4px rgba(0,0,0,0.5);
      background: #ffffff;
    }
    .chrome-thumb-number {
      color: #adbac7;
      font-size: 10.5px;
      margin-top: 4px;
      font-weight: 600;
    }
    .chrome-pdf-viewport {
      flex: 1 1 auto;
      overflow-y: auto;
      overflow-x: auto;
      position: relative;
      background: #525659;
      padding: 24px 15px 80px 15px;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      scroll-behavior: smooth;
      user-select: none;
      -webkit-user-select: none;
    }
    .chrome-page-container {
      margin: 14px auto;
      position: relative;
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.5);
      background: #ffffff;
      border-radius: 2px;
      transition: filter 0.3s ease;
    }
    .chrome-page-canvas {
      display: block;
      margin: 0 auto;
      background: #ffffff;
    }
    .chrome-watermark-stamp {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      pointer-events: none;
      z-index: 5;
      font-size: 18px;
      font-weight: 900;
      color: rgba(0, 0, 0, 0.07);
      text-transform: uppercase;
      letter-spacing: 2px;
      white-space: nowrap;
      text-align: center;
      user-select: none;
    }
    .reader-viewport {
      user-select: none !important;
      -webkit-user-select: none !important;
    }
    @media print {
      body, html, #modalEbookReader, .modal-backdrop, * {
        display: none !important;
        visibility: hidden !important;
      }
    }
  </style>
<style>*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/* ! tailwindcss v3.4.17 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:"Plus Jakarta Sans", Inter, sans-serif;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.pointer-events-none{pointer-events:none}.fixed{position:fixed}.absolute{position:absolute}.relative{position:relative}.sticky{position:sticky}.inset-0{inset:0px}.inset-y-0{top:0px;bottom:0px}.-bottom-20{bottom:-5rem}.-left-20{left:-5rem}.-right-20{right:-5rem}.-top-20{top:-5rem}.bottom-0{bottom:0px}.bottom-5{bottom:1.25rem}.left-0{left:0px}.left-4{left:1rem}.right-0{right:0px}.right-2{right:0.5rem}.right-4{right:1rem}.top-0{top:0px}.top-2{top:0.5rem}.top-4{top:1rem}.top-full{top:100%}.-bottom-6{bottom:-1.5rem}.-right-6{right:-1.5rem}.bottom-4{bottom:1rem}.bottom-6{bottom:1.5rem}.right-5{right:1.25rem}.right-6{right:1.5rem}.top-5{top:1.25rem}.z-30{z-index:30}.z-40{z-index:40}.z-50{z-index:50}.z-10{z-index:10}.mx-auto{margin-left:auto;margin-right:auto}.my-1{margin-top:0.25rem;margin-bottom:0.25rem}.my-auto{margin-top:auto;margin-bottom:auto}.-mt-0\.5{margin-top:-0.125rem}.mb-1{margin-bottom:0.25rem}.mb-2\.5{margin-bottom:0.625rem}.mb-3{margin-bottom:0.75rem}.mb-4{margin-bottom:1rem}.mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}.ml-auto{margin-left:auto}.mr-1{margin-right:0.25rem}.mt-0\.5{margin-top:0.125rem}.mt-1{margin-top:0.25rem}.mt-2{margin-top:0.5rem}.mt-2\.5{margin-top:0.625rem}.mt-3{margin-top:0.75rem}.mt-6{margin-top:1.5rem}.mb-0{margin-bottom:0px}.mb-0\.5{margin-bottom:0.125rem}.mb-1\.5{margin-bottom:0.375rem}.mb-2{margin-bottom:0.5rem}.mr-1\.5{margin-right:0.375rem}.mr-2{margin-right:0.5rem}.mt-1\.5{margin-top:0.375rem}.mr-3{margin-right:0.75rem}.line-clamp-1{overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1}.line-clamp-2{overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2}.line-clamp-3{overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3}.block{display:block}.inline-block{display:inline-block}.flex{display:flex}.inline-flex{display:inline-flex}.grid{display:grid}.hidden{display:none}.h-12{height:3rem}.h-2{height:0.5rem}.h-8{height:2rem}.h-80{height:20rem}.h-9{height:2.25rem}.h-96{height:24rem}.h-full{height:100%}.h-10{height:2.5rem}.h-11{height:2.75rem}.max-h-20{max-height:5rem}.min-h-screen{min-height:100vh}.min-h-\[200px\]{min-height:200px}.w-12{width:3rem}.w-2{width:0.5rem}.w-4{width:1rem}.w-4\/5{width:80%}.w-64{width:16rem}.w-8{width:2rem}.w-9{width:2.25rem}.w-96{width:24rem}.w-full{width:100%}.w-10{width:2.5rem}.w-11{width:2.75rem}.max-w-2xl{max-width:42rem}.max-w-\[1600px\]{max-width:1600px}.max-w-md{max-width:28rem}.max-w-sm{max-width:24rem}.max-w-lg{max-width:32rem}.flex-1{flex:1 1 0%}.flex-shrink-0{flex-shrink:0}.shrink-0{flex-shrink:0}.-translate-y-20{--tw-translate-y:-5rem;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.transform{transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}@keyframes bounce{0%, 100%{transform:translateY(-25%);animation-timing-function:cubic-bezier(0.8,0,1,1)}50%{transform:none;animation-timing-function:cubic-bezier(0,0,0.2,1)}}.animate-bounce{animation:bounce 1s infinite}@keyframes marquee{0%{transform:translateX(0%)}100%{transform:translateX(-50%)}}.animate-marquee-slow{animation:marquee 45s linear infinite}@keyframes ping{75%, 100%{transform:scale(2);opacity:0}}.animate-ping{animation:ping 1s cubic-bezier(0, 0, 0.2, 1) infinite}@keyframes pulse{50%{opacity:.5}}.animate-pulse{animation:pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite}.cursor-pointer{cursor:pointer}.appearance-none{-webkit-appearance:none;appearance:none}.grid-cols-1{grid-template-columns:repeat(1, minmax(0, 1fr))}.grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.grid-cols-4{grid-template-columns:repeat(4, minmax(0, 1fr))}.flex-col{flex-direction:column}.flex-wrap{flex-wrap:wrap}.flex-nowrap{flex-wrap:nowrap}.items-start{align-items:flex-start}.items-center{align-items:center}.justify-end{justify-content:flex-end}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.gap-1{gap:0.25rem}.gap-1\.5{gap:0.375rem}.gap-10{gap:2.5rem}.gap-2{gap:0.5rem}.gap-2\.5{gap:0.625rem}.gap-3{gap:0.75rem}.gap-4{gap:1rem}.gap-5{gap:1.25rem}.gap-6{gap:1.5rem}.gap-8{gap:2rem}.gap-x-6{column-gap:1.5rem}.gap-y-1{row-gap:0.25rem}.space-x-2 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(0.5rem * var(--tw-space-x-reverse));margin-left:calc(0.5rem * calc(1 - var(--tw-space-x-reverse)))}.space-x-3 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(0.75rem * var(--tw-space-x-reverse));margin-left:calc(0.75rem * calc(1 - var(--tw-space-x-reverse)))}.space-x-4 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(1rem * var(--tw-space-x-reverse));margin-left:calc(1rem * calc(1 - var(--tw-space-x-reverse)))}.space-x-6 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(1.5rem * var(--tw-space-x-reverse));margin-left:calc(1.5rem * calc(1 - var(--tw-space-x-reverse)))}.space-y-1 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.25rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.25rem * var(--tw-space-y-reverse))}.space-y-1\.5 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.375rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.375rem * var(--tw-space-y-reverse))}.space-y-2\.5 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.625rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.625rem * var(--tw-space-y-reverse))}.space-y-3 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.75rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.75rem * var(--tw-space-y-reverse))}.space-y-5 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1.25rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1.25rem * var(--tw-space-y-reverse))}.space-y-2 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.5rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.5rem * var(--tw-space-y-reverse))}.space-y-3\.5 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(0.875rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.875rem * var(--tw-space-y-reverse))}.space-y-4 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1rem * var(--tw-space-y-reverse))}.self-start{align-self:flex-start}.overflow-hidden{overflow:hidden}.overflow-y-auto{overflow-y:auto}.scroll-smooth{scroll-behavior:smooth}.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.text-ellipsis{text-overflow:ellipsis}.whitespace-nowrap{white-space:nowrap}.rounded{border-radius:0.25rem}.rounded-2xl{border-radius:1rem}.rounded-3xl{border-radius:1.5rem}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:0.5rem}.rounded-md{border-radius:0.375rem}.rounded-xl{border-radius:0.75rem}.border{border-width:1px}.border-4{border-width:4px}.border-2{border-width:2px}.border-0{border-width:0px}.border-b{border-bottom-width:1px}.border-l{border-left-width:1px}.border-t{border-top-width:1px}.border-dashed{border-style:dashed}.border-amber-300\/30{border-color:rgb(252 211 77 / 0.3)}.border-amber-300\/40{border-color:rgb(252 211 77 / 0.4)}.border-amber-400\/40{border-color:rgb(251 191 36 / 0.4)}.border-blue-200{--tw-border-opacity:1;border-color:rgb(191 219 254 / var(--tw-border-opacity, 1))}.border-emerald-200{--tw-border-opacity:1;border-color:rgb(167 243 208 / var(--tw-border-opacity, 1))}.border-emerald-400\/30{border-color:rgb(52 211 153 / 0.3)}.border-emerald-400\/40{border-color:rgb(52 211 153 / 0.4)}.border-emerald-500\/20{border-color:rgb(16 185 129 / 0.2)}.border-emerald-500\/30{border-color:rgb(16 185 129 / 0.3)}.border-emerald-500\/40{border-color:rgb(16 185 129 / 0.4)}.border-emerald-600\/30{border-color:rgb(5 150 105 / 0.3)}.border-emerald-700\/60{border-color:rgb(4 120 87 / 0.6)}.border-emerald-800\/80{border-color:rgb(6 95 70 / 0.8)}.border-kemenag-600\/50{border-color:rgb(5 150 105 / 0.5)}.border-kemenag-700\/60{border-color:rgb(4 120 87 / 0.6)}.border-kemenag-700\/80{border-color:rgb(4 120 87 / 0.8)}.border-orange-200{--tw-border-opacity:1;border-color:rgb(254 215 170 / var(--tw-border-opacity, 1))}.border-purple-200{--tw-border-opacity:1;border-color:rgb(233 213 255 / var(--tw-border-opacity, 1))}.border-rose-200{--tw-border-opacity:1;border-color:rgb(254 205 211 / var(--tw-border-opacity, 1))}.border-slate-100{--tw-border-opacity:1;border-color:rgb(241 245 249 / var(--tw-border-opacity, 1))}.border-slate-200{--tw-border-opacity:1;border-color:rgb(226 232 240 / var(--tw-border-opacity, 1))}.border-slate-200\/60{border-color:rgb(226 232 240 / 0.6)}.border-slate-200\/80{border-color:rgb(226 232 240 / 0.8)}.border-teal-200{--tw-border-opacity:1;border-color:rgb(153 246 228 / var(--tw-border-opacity, 1))}.border-white\/20{border-color:rgb(255 255 255 / 0.2)}.border-amber-200\/80{border-color:rgb(253 230 138 / 0.8)}.border-amber-200{--tw-border-opacity:1;border-color:rgb(253 230 138 / var(--tw-border-opacity, 1))}.border-emerald-100{--tw-border-opacity:1;border-color:rgb(209 250 229 / var(--tw-border-opacity, 1))}.border-emerald-100\/90{border-color:rgb(209 250 229 / 0.9)}.border-emerald-400{--tw-border-opacity:1;border-color:rgb(52 211 153 / var(--tw-border-opacity, 1))}.border-emerald-500\/50{border-color:rgb(16 185 129 / 0.5)}.border-emerald-600{--tw-border-opacity:1;border-color:rgb(5 150 105 / var(--tw-border-opacity, 1))}.border-emerald-900{--tw-border-opacity:1;border-color:rgb(6 78 59 / var(--tw-border-opacity, 1))}.border-slate-300{--tw-border-opacity:1;border-color:rgb(203 213 225 / var(--tw-border-opacity, 1))}.border-slate-700{--tw-border-opacity:1;border-color:rgb(51 65 85 / var(--tw-border-opacity, 1))}.border-slate-700\/60{border-color:rgb(51 65 85 / 0.6)}.border-slate-800{--tw-border-opacity:1;border-color:rgb(30 41 59 / var(--tw-border-opacity, 1))}.border-white\/25{border-color:rgb(255 255 255 / 0.25)}.border-emerald-500{--tw-border-opacity:1;border-color:rgb(16 185 129 / var(--tw-border-opacity, 1))}.bg-accent-gold{--tw-bg-opacity:1;background-color:rgb(245 158 11 / var(--tw-bg-opacity, 1))}.bg-accent-gold\/20{background-color:rgb(245 158 11 / 0.2)}.bg-accent-yellow{--tw-bg-opacity:1;background-color:rgb(251 191 36 / var(--tw-bg-opacity, 1))}.bg-amber-100{--tw-bg-opacity:1;background-color:rgb(254 243 199 / var(--tw-bg-opacity, 1))}.bg-amber-50{--tw-bg-opacity:1;background-color:rgb(255 251 235 / var(--tw-bg-opacity, 1))}.bg-amber-500{--tw-bg-opacity:1;background-color:rgb(245 158 11 / var(--tw-bg-opacity, 1))}.bg-blue-100{--tw-bg-opacity:1;background-color:rgb(219 234 254 / var(--tw-bg-opacity, 1))}.bg-blue-50{--tw-bg-opacity:1;background-color:rgb(239 246 255 / var(--tw-bg-opacity, 1))}.bg-emerald-100{--tw-bg-opacity:1;background-color:rgb(209 250 229 / var(--tw-bg-opacity, 1))}.bg-emerald-100\/70{background-color:rgb(209 250 229 / 0.7)}.bg-emerald-400{--tw-bg-opacity:1;background-color:rgb(52 211 153 / var(--tw-bg-opacity, 1))}.bg-emerald-50{--tw-bg-opacity:1;background-color:rgb(236 253 245 / var(--tw-bg-opacity, 1))}.bg-emerald-500{--tw-bg-opacity:1;background-color:rgb(16 185 129 / var(--tw-bg-opacity, 1))}.bg-emerald-500\/20{background-color:rgb(16 185 129 / 0.2)}.bg-emerald-600{--tw-bg-opacity:1;background-color:rgb(5 150 105 / var(--tw-bg-opacity, 1))}.bg-emerald-950\/80{background-color:rgb(2 44 34 / 0.8)}.bg-kemenag-700{--tw-bg-opacity:1;background-color:rgb(4 120 87 / var(--tw-bg-opacity, 1))}.bg-kemenag-700\/80{background-color:rgb(4 120 87 / 0.8)}.bg-kemenag-700\/90{background-color:rgb(4 120 87 / 0.9)}.bg-kemenag-800{--tw-bg-opacity:1;background-color:rgb(6 95 70 / var(--tw-bg-opacity, 1))}.bg-kemenag-800\/60{background-color:rgb(6 95 70 / 0.6)}.bg-kemenag-800\/80{background-color:rgb(6 95 70 / 0.8)}.bg-kemenag-800\/90{background-color:rgb(6 95 70 / 0.9)}.bg-kemenag-900{--tw-bg-opacity:1;background-color:rgb(6 78 59 / var(--tw-bg-opacity, 1))}.bg-kemenag-950\/80{background-color:rgb(2 44 34 / 0.8)}.bg-kemenag-950\/90{background-color:rgb(2 44 34 / 0.9)}.bg-orange-50{--tw-bg-opacity:1;background-color:rgb(255 247 237 / var(--tw-bg-opacity, 1))}.bg-purple-100{--tw-bg-opacity:1;background-color:rgb(243 232 255 / var(--tw-bg-opacity, 1))}.bg-purple-50{--tw-bg-opacity:1;background-color:rgb(250 245 255 / var(--tw-bg-opacity, 1))}.bg-rose-50{--tw-bg-opacity:1;background-color:rgb(255 241 242 / var(--tw-bg-opacity, 1))}.bg-slate-100{--tw-bg-opacity:1;background-color:rgb(241 245 249 / var(--tw-bg-opacity, 1))}.bg-slate-200\/80{background-color:rgb(226 232 240 / 0.8)}.bg-slate-50{--tw-bg-opacity:1;background-color:rgb(248 250 252 / var(--tw-bg-opacity, 1))}.bg-slate-50\/90{background-color:rgb(248 250 252 / 0.9)}.bg-slate-900\/60{background-color:rgb(15 23 42 / 0.6)}.bg-teal-50{--tw-bg-opacity:1;background-color:rgb(240 253 250 / var(--tw-bg-opacity, 1))}.bg-transparent{background-color:transparent}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-white\/95{background-color:rgb(255 255 255 / 0.95)}.bg-amber-50\/60{background-color:rgb(255 251 235 / 0.6)}.bg-blue-600{--tw-bg-opacity:1;background-color:rgb(37 99 235 / var(--tw-bg-opacity, 1))}.bg-emerald-300{--tw-bg-opacity:1;background-color:rgb(110 231 183 / var(--tw-bg-opacity, 1))}.bg-emerald-700{--tw-bg-opacity:1;background-color:rgb(4 120 87 / var(--tw-bg-opacity, 1))}.bg-slate-200\/70{background-color:rgb(226 232 240 / 0.7)}.bg-slate-800\/80{background-color:rgb(30 41 59 / 0.8)}.bg-slate-900{--tw-bg-opacity:1;background-color:rgb(15 23 42 / var(--tw-bg-opacity, 1))}.bg-white\/15{background-color:rgb(255 255 255 / 0.15)}.bg-white\/20{background-color:rgb(255 255 255 / 0.2)}.bg-\[radial-gradient\(\#34d399_1px\2c transparent_1px\)\]{background-image:radial-gradient(#34d399 1px,transparent 1px)}.bg-gradient-to-br{background-image:linear-gradient(to bottom right, var(--tw-gradient-stops))}.bg-gradient-to-r{background-image:linear-gradient(to right, var(--tw-gradient-stops))}.bg-gradient-to-t{background-image:linear-gradient(to top, var(--tw-gradient-stops))}.from-accent-yellow{--tw-gradient-from:#fbbf24 var(--tw-gradient-from-position);--tw-gradient-to:rgb(251 191 36 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-amber-500{--tw-gradient-from:#f59e0b var(--tw-gradient-from-position);--tw-gradient-to:rgb(245 158 11 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-kemenag-600{--tw-gradient-from:#059669 var(--tw-gradient-from-position);--tw-gradient-to:rgb(5 150 105 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-kemenag-700{--tw-gradient-from:#047857 var(--tw-gradient-from-position);--tw-gradient-to:rgb(4 120 87 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-kemenag-900{--tw-gradient-from:#064e3b var(--tw-gradient-from-position);--tw-gradient-to:rgb(6 78 59 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-kemenag-950{--tw-gradient-from:#022c22 var(--tw-gradient-from-position);--tw-gradient-to:rgb(2 44 34 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-emerald-50\/90{--tw-gradient-from:rgb(236 253 245 / 0.9) var(--tw-gradient-from-position);--tw-gradient-to:rgb(236 253 245 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.via-kemenag-800{--tw-gradient-to:rgb(6 95 70 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #065f46 var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-kemenag-950\/30{--tw-gradient-to:rgb(2 44 34 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), rgb(2 44 34 / 0.3) var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-orange-500{--tw-gradient-to:rgb(249 115 22 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #f97316 var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-yellow-200{--tw-gradient-to:rgb(254 240 138 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #fef08a var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-kemenag-900{--tw-gradient-to:rgb(6 78 59 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), #064e3b var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-kemenag-950\/20{--tw-gradient-to:rgb(2 44 34 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), rgb(2 44 34 / 0.2) var(--tw-gradient-via-position), var(--tw-gradient-to)}.via-teal-50\/70{--tw-gradient-to:rgb(240 253 250 / 0)  var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), rgb(240 253 250 / 0.7) var(--tw-gradient-via-position), var(--tw-gradient-to)}.to-amber-600{--tw-gradient-to:#d97706 var(--tw-gradient-to-position)}.to-emerald-200{--tw-gradient-to:#a7f3d0 var(--tw-gradient-to-position)}.to-kemenag-800{--tw-gradient-to:#065f46 var(--tw-gradient-to-position)}.to-kemenag-950{--tw-gradient-to:#022c22 var(--tw-gradient-to-position)}.to-transparent{--tw-gradient-to:transparent var(--tw-gradient-to-position)}.to-orange-500{--tw-gradient-to:#f97316 var(--tw-gradient-to-position)}.to-slate-50{--tw-gradient-to:#f8fafc var(--tw-gradient-to-position)}.bg-clip-text{-webkit-background-clip:text;background-clip:text}.object-contain{object-fit:contain}.object-cover{object-fit:cover}.p-1\.5{padding:0.375rem}.p-2{padding:0.5rem}.p-2\.5{padding:0.625rem}.p-3{padding:0.75rem}.p-4{padding:1rem}.p-6{padding:1.5rem}.p-1{padding:0.25rem}.p-3\.5{padding:0.875rem}.p-5{padding:1.25rem}.p-0{padding:0px}.px-2{padding-left:0.5rem;padding-right:0.5rem}.px-2\.5{padding-left:0.625rem;padding-right:0.625rem}.px-3{padding-left:0.75rem;padding-right:0.75rem}.px-3\.5{padding-left:0.875rem;padding-right:0.875rem}.px-4{padding-left:1rem;padding-right:1rem}.px-5{padding-left:1.25rem;padding-right:1.25rem}.py-0\.5{padding-top:0.125rem;padding-bottom:0.125rem}.py-1{padding-top:0.25rem;padding-bottom:0.25rem}.py-1\.5{padding-top:0.375rem;padding-bottom:0.375rem}.py-10{padding-top:2.5rem;padding-bottom:2.5rem}.py-12{padding-top:3rem;padding-bottom:3rem}.py-2{padding-top:0.5rem;padding-bottom:0.5rem}.py-2\.5{padding-top:0.625rem;padding-bottom:0.625rem}.py-3{padding-top:0.75rem;padding-bottom:0.75rem}.px-1\.5{padding-left:0.375rem;padding-right:0.375rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.py-14{padding-top:3.5rem;padding-bottom:3.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.py-5{padding-top:1.25rem;padding-bottom:1.25rem}.pb-4{padding-bottom:1rem}.pl-5{padding-left:1.25rem}.pr-6{padding-right:1.5rem}.pt-1{padding-top:0.25rem}.pt-1\.5{padding-top:0.375rem}.pt-2{padding-top:0.5rem}.pt-3{padding-top:0.75rem}.pt-4{padding-top:1rem}.pt-6{padding-top:1.5rem}.pb-2{padding-bottom:0.5rem}.pb-3{padding-bottom:0.75rem}.pb-8{padding-bottom:2rem}.pl-10{padding-left:2.5rem}.pl-3\.5{padding-left:0.875rem}.pr-3\.5{padding-right:0.875rem}.pr-9{padding-right:2.25rem}.pt-0\.5{padding-top:0.125rem}.pt-12{padding-top:3rem}.pt-20{padding-top:5rem}.pt-3\.5{padding-top:0.875rem}.pb-0{padding-bottom:0px}.text-left{text-align:left}.text-center{text-align:center}.font-body{font-family:Inter, sans-serif}.font-sans{font-family:"Plus Jakarta Sans", Inter, sans-serif}.font-mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}.text-2xl{font-size:1.5rem;line-height:2rem}.text-3xl{font-size:1.875rem;line-height:2.25rem}.text-\[10px\]{font-size:10px}.text-\[11px\]{font-size:11px}.text-\[13px\]{font-size:13px}.text-\[8px\]{font-size:8px}.text-\[9px\]{font-size:9px}.text-base{font-size:1rem;line-height:1.5rem}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:0.875rem;line-height:1.25rem}.text-xl{font-size:1.25rem;line-height:1.75rem}.text-xs{font-size:0.75rem;line-height:1rem}.text-\[10\.5px\]{font-size:10.5px}.text-\[11\.5px\]{font-size:11.5px}.text-\[17px\]{font-size:17px}.font-bold{font-weight:700}.font-extrabold{font-weight:800}.font-medium{font-weight:500}.font-semibold{font-weight:600}.font-normal{font-weight:400}.uppercase{text-transform:uppercase}.italic{font-style:italic}.leading-relaxed{line-height:1.625}.leading-snug{line-height:1.375}.leading-tight{line-height:1.25}.leading-none{line-height:1}.tracking-tight{letter-spacing:-0.025em}.tracking-wide{letter-spacing:0.025em}.tracking-wider{letter-spacing:0.05em}.text-accent-yellow{--tw-text-opacity:1;color:rgb(251 191 36 / var(--tw-text-opacity, 1))}.text-amber-100{--tw-text-opacity:1;color:rgb(254 243 199 / var(--tw-text-opacity, 1))}.text-amber-500{--tw-text-opacity:1;color:rgb(245 158 11 / var(--tw-text-opacity, 1))}.text-amber-600{--tw-text-opacity:1;color:rgb(217 119 6 / var(--tw-text-opacity, 1))}.text-amber-700{--tw-text-opacity:1;color:rgb(180 83 9 / var(--tw-text-opacity, 1))}.text-amber-800{--tw-text-opacity:1;color:rgb(146 64 14 / var(--tw-text-opacity, 1))}.text-blue-600{--tw-text-opacity:1;color:rgb(37 99 235 / var(--tw-text-opacity, 1))}.text-blue-700{--tw-text-opacity:1;color:rgb(29 78 216 / var(--tw-text-opacity, 1))}.text-emerald-100{--tw-text-opacity:1;color:rgb(209 250 229 / var(--tw-text-opacity, 1))}.text-emerald-200{--tw-text-opacity:1;color:rgb(167 243 208 / var(--tw-text-opacity, 1))}.text-emerald-300{--tw-text-opacity:1;color:rgb(110 231 183 / var(--tw-text-opacity, 1))}.text-emerald-600{--tw-text-opacity:1;color:rgb(5 150 105 / var(--tw-text-opacity, 1))}.text-emerald-700{--tw-text-opacity:1;color:rgb(4 120 87 / var(--tw-text-opacity, 1))}.text-emerald-800{--tw-text-opacity:1;color:rgb(6 95 70 / var(--tw-text-opacity, 1))}.text-kemenag-600{--tw-text-opacity:1;color:rgb(5 150 105 / var(--tw-text-opacity, 1))}.text-kemenag-700{--tw-text-opacity:1;color:rgb(4 120 87 / var(--tw-text-opacity, 1))}.text-kemenag-800{--tw-text-opacity:1;color:rgb(6 95 70 / var(--tw-text-opacity, 1))}.text-orange-600{--tw-text-opacity:1;color:rgb(234 88 12 / var(--tw-text-opacity, 1))}.text-orange-700{--tw-text-opacity:1;color:rgb(194 65 12 / var(--tw-text-opacity, 1))}.text-orange-900{--tw-text-opacity:1;color:rgb(124 45 18 / var(--tw-text-opacity, 1))}.text-purple-600{--tw-text-opacity:1;color:rgb(147 51 234 / var(--tw-text-opacity, 1))}.text-purple-700{--tw-text-opacity:1;color:rgb(126 34 206 / var(--tw-text-opacity, 1))}.text-rose-500{--tw-text-opacity:1;color:rgb(244 63 94 / var(--tw-text-opacity, 1))}.text-rose-700{--tw-text-opacity:1;color:rgb(190 18 60 / var(--tw-text-opacity, 1))}.text-slate-300{--tw-text-opacity:1;color:rgb(203 213 225 / var(--tw-text-opacity, 1))}.text-slate-400{--tw-text-opacity:1;color:rgb(148 163 184 / var(--tw-text-opacity, 1))}.text-slate-500{--tw-text-opacity:1;color:rgb(100 116 139 / var(--tw-text-opacity, 1))}.text-slate-600{--tw-text-opacity:1;color:rgb(71 85 105 / var(--tw-text-opacity, 1))}.text-slate-700{--tw-text-opacity:1;color:rgb(51 65 85 / var(--tw-text-opacity, 1))}.text-slate-800{--tw-text-opacity:1;color:rgb(30 41 59 / var(--tw-text-opacity, 1))}.text-slate-900{--tw-text-opacity:1;color:rgb(15 23 42 / var(--tw-text-opacity, 1))}.text-slate-950{--tw-text-opacity:1;color:rgb(2 6 23 / var(--tw-text-opacity, 1))}.text-teal-700{--tw-text-opacity:1;color:rgb(15 118 110 / var(--tw-text-opacity, 1))}.text-transparent{color:transparent}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.text-amber-400{--tw-text-opacity:1;color:rgb(251 191 36 / var(--tw-text-opacity, 1))}.text-amber-900{--tw-text-opacity:1;color:rgb(120 53 15 / var(--tw-text-opacity, 1))}.text-amber-950{--tw-text-opacity:1;color:rgb(69 26 3 / var(--tw-text-opacity, 1))}.text-emerald-400{--tw-text-opacity:1;color:rgb(52 211 153 / var(--tw-text-opacity, 1))}.text-emerald-500{--tw-text-opacity:1;color:rgb(16 185 129 / var(--tw-text-opacity, 1))}.text-emerald-900{--tw-text-opacity:1;color:rgb(6 78 59 / var(--tw-text-opacity, 1))}.text-rose-400{--tw-text-opacity:1;color:rgb(251 113 133 / var(--tw-text-opacity, 1))}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.placeholder-slate-400::placeholder{--tw-placeholder-opacity:1;color:rgb(148 163 184 / var(--tw-placeholder-opacity, 1))}.opacity-10{opacity:0.1}.opacity-75{opacity:0.75}.opacity-0{opacity:0}.shadow-2xl{--tw-shadow:0 25px 50px -12px rgb(0 0 0 / 0.25);--tw-shadow-colored:0 25px 50px -12px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-inner{--tw-shadow:inset 0 2px 4px 0 rgb(0 0 0 / 0.05);--tw-shadow-colored:inset 0 2px 4px 0 var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-lg{--tw-shadow:0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-md{--tw-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 4px 6px -1px var(--tw-shadow-color), 0 2px 4px -2px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 2px 0 rgb(0 0 0 / 0.05);--tw-shadow-colored:0 1px 2px 0 var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-xl{--tw-shadow:0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 20px 25px -5px var(--tw-shadow-color), 0 8px 10px -6px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-amber-500\/20{--tw-shadow-color:rgb(245 158 11 / 0.2);--tw-shadow:var(--tw-shadow-colored)}.outline-none{outline:2px solid transparent;outline-offset:2px}.blur-3xl{--tw-blur:blur(64px);filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.backdrop-blur-md{--tw-backdrop-blur:blur(12px);-webkit-backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia);backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia)}.backdrop-blur-sm{--tw-backdrop-blur:blur(4px);-webkit-backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia);backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia)}.transition{transition-property:color, background-color, border-color, fill, stroke, opacity, box-shadow, transform, filter, -webkit-text-decoration-color, -webkit-backdrop-filter;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter, -webkit-text-decoration-color, -webkit-backdrop-filter;transition-timing-function:cubic-bezier(0.4, 0, 0.2, 1);transition-duration:150ms}.transition-all{transition-property:all;transition-timing-function:cubic-bezier(0.4, 0, 0.2, 1);transition-duration:150ms}.transition-colors{transition-property:color, background-color, border-color, fill, stroke, -webkit-text-decoration-color;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke;transition-property:color, background-color, border-color, text-decoration-color, fill, stroke, -webkit-text-decoration-color;transition-timing-function:cubic-bezier(0.4, 0, 0.2, 1);transition-duration:150ms}.transition-transform{transition-property:transform;transition-timing-function:cubic-bezier(0.4, 0, 0.2, 1);transition-duration:150ms}.duration-300{transition-duration:300ms}.duration-700{transition-duration:700ms}.duration-200{transition-duration:200ms}.\[background-size\:16px_16px\]{background-size:16px 16px}.hover\:-translate-y-1:hover{--tw-translate-y:-0.25rem;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.hover\:scale-105:hover{--tw-scale-x:1.05;--tw-scale-y:1.05;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.hover\:border-amber-300:hover{--tw-border-opacity:1;border-color:rgb(252 211 77 / var(--tw-border-opacity, 1))}.hover\:border-blue-300:hover{--tw-border-opacity:1;border-color:rgb(147 197 253 / var(--tw-border-opacity, 1))}.hover\:border-emerald-300:hover{--tw-border-opacity:1;border-color:rgb(110 231 183 / var(--tw-border-opacity, 1))}.hover\:border-purple-300:hover{--tw-border-opacity:1;border-color:rgb(216 180 254 / var(--tw-border-opacity, 1))}.hover\:bg-accent-gold\/30:hover{background-color:rgb(245 158 11 / 0.3)}.hover\:bg-amber-400:hover{--tw-bg-opacity:1;background-color:rgb(251 191 36 / var(--tw-bg-opacity, 1))}.hover\:bg-amber-50\/50:hover{background-color:rgb(255 251 235 / 0.5)}.hover\:bg-blue-50\/50:hover{background-color:rgb(239 246 255 / 0.5)}.hover\:bg-emerald-100:hover{--tw-bg-opacity:1;background-color:rgb(209 250 229 / var(--tw-bg-opacity, 1))}.hover\:bg-emerald-50:hover{--tw-bg-opacity:1;background-color:rgb(236 253 245 / var(--tw-bg-opacity, 1))}.hover\:bg-emerald-50\/50:hover{background-color:rgb(236 253 245 / 0.5)}.hover\:bg-emerald-900:hover{--tw-bg-opacity:1;background-color:rgb(6 78 59 / var(--tw-bg-opacity, 1))}.hover\:bg-kemenag-600:hover{--tw-bg-opacity:1;background-color:rgb(5 150 105 / var(--tw-bg-opacity, 1))}.hover\:bg-kemenag-700:hover{--tw-bg-opacity:1;background-color:rgb(4 120 87 / var(--tw-bg-opacity, 1))}.hover\:bg-kemenag-700\/60:hover{background-color:rgb(4 120 87 / 0.6)}.hover\:bg-kemenag-800:hover{--tw-bg-opacity:1;background-color:rgb(6 95 70 / var(--tw-bg-opacity, 1))}.hover\:bg-orange-100:hover{--tw-bg-opacity:1;background-color:rgb(255 237 213 / var(--tw-bg-opacity, 1))}.hover\:bg-purple-50\/50:hover{background-color:rgb(250 245 255 / 0.5)}.hover\:bg-slate-200:hover{--tw-bg-opacity:1;background-color:rgb(226 232 240 / var(--tw-bg-opacity, 1))}.hover\:bg-white:hover{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.hover\:bg-amber-100:hover{--tw-bg-opacity:1;background-color:rgb(254 243 199 / var(--tw-bg-opacity, 1))}.hover\:bg-slate-100:hover{--tw-bg-opacity:1;background-color:rgb(241 245 249 / var(--tw-bg-opacity, 1))}.hover\:bg-slate-200\/60:hover{background-color:rgb(226 232 240 / 0.6)}.hover\:bg-white\/25:hover{background-color:rgb(255 255 255 / 0.25)}.hover\:bg-white\/60:hover{background-color:rgb(255 255 255 / 0.6)}.hover\:from-amber-600:hover{--tw-gradient-from:#d97706 var(--tw-gradient-from-position);--tw-gradient-to:rgb(217 119 6 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.hover\:from-kemenag-700:hover{--tw-gradient-from:#047857 var(--tw-gradient-from-position);--tw-gradient-to:rgb(4 120 87 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.hover\:from-kemenag-800:hover{--tw-gradient-from:#065f46 var(--tw-gradient-from-position);--tw-gradient-to:rgb(6 95 70 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.hover\:to-amber-700:hover{--tw-gradient-to:#b45309 var(--tw-gradient-to-position)}.hover\:to-kemenag-900:hover{--tw-gradient-to:#064e3b var(--tw-gradient-to-position)}.hover\:to-orange-600:hover{--tw-gradient-to:#ea580c var(--tw-gradient-to-position)}.hover\:text-accent-yellow:hover{--tw-text-opacity:1;color:rgb(251 191 36 / var(--tw-text-opacity, 1))}.hover\:text-kemenag-700:hover{--tw-text-opacity:1;color:rgb(4 120 87 / var(--tw-text-opacity, 1))}.hover\:text-kemenag-800:hover{--tw-text-opacity:1;color:rgb(6 95 70 / var(--tw-text-opacity, 1))}.hover\:text-slate-900:hover{--tw-text-opacity:1;color:rgb(15 23 42 / var(--tw-text-opacity, 1))}.hover\:text-white:hover{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.hover\:text-emerald-700:hover{--tw-text-opacity:1;color:rgb(4 120 87 / var(--tw-text-opacity, 1))}.hover\:text-slate-600:hover{--tw-text-opacity:1;color:rgb(71 85 105 / var(--tw-text-opacity, 1))}.hover\:text-slate-700:hover{--tw-text-opacity:1;color:rgb(51 65 85 / var(--tw-text-opacity, 1))}.hover\:text-slate-800:hover{--tw-text-opacity:1;color:rgb(30 41 59 / var(--tw-text-opacity, 1))}.hover\:text-emerald-900:hover{--tw-text-opacity:1;color:rgb(6 78 59 / var(--tw-text-opacity, 1))}.hover\:shadow-md:hover{--tw-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 4px 6px -1px var(--tw-shadow-color), 0 2px 4px -2px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.hover\:shadow-xl:hover{--tw-shadow:0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 20px 25px -5px var(--tw-shadow-color), 0 8px 10px -6px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.hover\:shadow-emerald-900\/20:hover{--tw-shadow-color:rgb(6 78 59 / 0.2);--tw-shadow:var(--tw-shadow-colored)}.hover\:shadow-orange-500\/30:hover{--tw-shadow-color:rgb(249 115 22 / 0.3);--tw-shadow:var(--tw-shadow-colored)}.hover\:brightness-110:hover{--tw-brightness:brightness(1.1);filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.focus\:border-emerald-600:focus{--tw-border-opacity:1;border-color:rgb(5 150 105 / var(--tw-border-opacity, 1))}.focus\:bg-white:focus{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.focus\:ring-2:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.focus\:ring-emerald-500\/20:focus{--tw-ring-color:rgb(16 185 129 / 0.2)}.active\:scale-95:active{--tw-scale-x:.95;--tw-scale-y:.95;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.group:hover .group-hover\:block{display:block}.group:hover .group-hover\:translate-x-1{--tw-translate-x:0.25rem;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.group:hover .group-hover\:rotate-180{--tw-rotate:180deg;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.group:hover .group-hover\:scale-105{--tw-scale-x:1.05;--tw-scale-y:1.05;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.group:hover .group-hover\:scale-110{--tw-scale-x:1.1;--tw-scale-y:1.1;transform:translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.group:hover .group-hover\:text-amber-700{--tw-text-opacity:1;color:rgb(180 83 9 / var(--tw-text-opacity, 1))}.group:hover .group-hover\:text-blue-700{--tw-text-opacity:1;color:rgb(29 78 216 / var(--tw-text-opacity, 1))}.group:hover .group-hover\:text-kemenag-700{--tw-text-opacity:1;color:rgb(4 120 87 / var(--tw-text-opacity, 1))}.group:hover .group-hover\:text-purple-700{--tw-text-opacity:1;color:rgb(126 34 206 / var(--tw-text-opacity, 1))}@media (min-width: 640px){.sm\:col-span-2{grid-column:span 2 / span 2}.sm\:col-span-4{grid-column:span 4 / span 4}.sm\:col-span-6{grid-column:span 6 / span 6}.sm\:inline-block{display:inline-block}.sm\:inline-flex{display:inline-flex}.sm\:h-10{height:2.5rem}.sm\:h-9{height:2.25rem}.sm\:h-96{height:24rem}.sm\:w-10{width:2.5rem}.sm\:w-9{width:2.25rem}.sm\:grid-cols-12{grid-template-columns:repeat(12, minmax(0, 1fr))}.sm\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.sm\:grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.sm\:grid-cols-4{grid-template-columns:repeat(4, minmax(0, 1fr))}.sm\:flex-row{flex-direction:row}.sm\:items-end{align-items:flex-end}.sm\:gap-3{gap:0.75rem}.sm\:gap-2\.5{gap:0.625rem}.sm\:space-x-3 > :not([hidden]) ~ :not([hidden]){--tw-space-x-reverse:0;margin-right:calc(0.75rem * var(--tw-space-x-reverse));margin-left:calc(0.75rem * calc(1 - var(--tw-space-x-reverse)))}.sm\:self-auto{align-self:auto}.sm\:border-l{border-left-width:1px}.sm\:border-t-0{border-top-width:0px}.sm\:p-8{padding:2rem}.sm\:p-6{padding:1.5rem}.sm\:p-3{padding:0.75rem}.sm\:px-8{padding-left:2rem;padding-right:2rem}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:text-2xl{font-size:1.5rem;line-height:2rem}.sm\:text-3xl{font-size:1.875rem;line-height:2.25rem}.sm\:text-4xl{font-size:2.25rem;line-height:2.5rem}.sm\:text-\[13px\]{font-size:13px}.sm\:text-base{font-size:1rem;line-height:1.5rem}.sm\:text-sm{font-size:0.875rem;line-height:1.25rem}.sm\:text-\[18px\]{font-size:18px}}@media (min-width: 768px){.md\:block{display:block}.md\:hidden{display:none}.md\:grid-cols-3{grid-template-columns:repeat(3, minmax(0, 1fr))}.md\:grid-cols-2{grid-template-columns:repeat(2, minmax(0, 1fr))}.md\:flex-row{flex-direction:row}.md\:text-left{text-align:left}}@media (min-width: 1024px){.lg\:col-span-3{grid-column:span 3 / span 3}.lg\:col-span-5{grid-column:span 5 / span 5}.lg\:col-span-6{grid-column:span 6 / span 6}.lg\:col-span-7{grid-column:span 7 / span 7}.lg\:col-span-2{grid-column:span 2 / span 2}.lg\:col-span-4{grid-column:span 4 / span 4}.lg\:flex{display:flex}.lg\:inline-flex{display:inline-flex}.lg\:max-w-none{max-width:none}.lg\:grid-cols-12{grid-template-columns:repeat(12, minmax(0, 1fr))}.lg\:grid-cols-4{grid-template-columns:repeat(4, minmax(0, 1fr))}.lg\:grid-cols-6{grid-template-columns:repeat(6, minmax(0, 1fr))}.lg\:border-l{border-left-width:1px}.lg\:p-10{padding:2.5rem}.lg\:px-12{padding-left:3rem;padding-right:3rem}.lg\:py-16{padding-top:4rem;padding-bottom:4rem}.lg\:pl-8{padding-left:2rem}.lg\:text-3xl{font-size:1.875rem;line-height:2.25rem}.lg\:text-5xl{font-size:3rem;line-height:1}.lg\:text-lg{font-size:1.125rem;line-height:1.75rem}}</style><style data-glasp-print="rule">@media print{[data-glasp-print="hide"]{display:none !important}}</style></head>
<body class="bg-slate-50 text-slate-800 font-body antialiased flex flex-col min-h-screen">

  <!-- ================= 1. TOP UTILITY BAR & JADWAL SHOLAT (IDENTIK DENGAN INDEX.PHP) ================= -->
  <aside class="bg-kemenag-900 text-white text-xs border-b border-kemenag-700/60 hidden md:block">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-6">
        <span class="flex items-center gap-1.5 font-semibold text-emerald-100">
          <i class="fa-solid fa-graduation-cap text-accent-yellow"></i> MADRASAH ALIYAH NEGERI 4 BANJAR • KEMENAG RI
        </span>
        <span class="flex items-center gap-1.5 text-emerald-100 border-l border-kemenag-700/80 pl-5">
          <i class="fa-solid fa-clock text-accent-yellow"></i> Layanan Hari Ini: <b class="text-white">TUTUP (Buka Besok 07.30 WITA)</b>
        </span>
        <span class="flex items-center gap-1.5 text-emerald-100 border-l border-kemenag-700/80 pl-5 hidden lg:inline-flex">
          <i class="fa-solid fa-calendar-check text-accent-yellow"></i> 19 November 2026        </span>

        <!-- Live Traffic Counter Badge (Top Bar) -->
        <span class="hidden lg:inline-flex items-center gap-1.5 bg-kemenag-950/80 px-2.5 py-0.5 rounded-full border border-emerald-500/40 text-[11px] text-emerald-100 font-semibold shadow-inner" title="Pengunjung sedang online di perpustakaan saat ini">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span>Online: <strong id="topbarLiveVisitors" class="text-accent-yellow font-bold">2</strong> User</span>
        </span>
      </div>
      
      <div class="flex items-center space-x-4">
        <!-- Widget Sholat Dinamis Martapura -->
        <div class="flex items-center gap-2 bg-kemenag-800/90 px-3 py-1 rounded-full border border-kemenag-600/50 cursor-pointer hover:bg-kemenag-700 transition" onclick="openPrayerModal()" title="Klik untuk lihat jadwal lengkap hari ini">
          <i class="fa-solid fa-mosque text-accent-yellow"></i>
          <span class="text-emerald-100">Waktu Sholat:</span>
          <span id="activePrayerDisplay" class="font-bold text-accent-yellow">Subuh 05:04 WITA</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-emerald-300"></i>
        </div>

        <div class="flex items-center space-x-3 text-emerald-200">
          <a href="/" class="hover:text-accent-yellow transition flex items-center gap-1" title="Kembali ke Portal Madrasah"><i class="fa-solid fa-house text-xs"></i> <span>Beranda Utama</span></a>
        </div>
      </div>
    </div>
  </aside>

  <!-- ================= 2. RUNNING NEWS TICKER (WARTA PUSTAKA DINAMIS) ================= -->
  <div class="bg-kemenag-900 text-emerald-100 text-xs py-2 px-4 overflow-hidden border-b border-emerald-800/80 shadow-inner relative z-30">
    <div class="max-w-[1600px] mx-auto flex items-center gap-3">
      <span class="bg-accent-gold text-slate-950 font-extrabold uppercase px-3 py-0.5 rounded-full text-[10px] tracking-wider shrink-0 flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-bullhorn text-[10px] animate-bounce"></i> Warta Pustaka
      </span>
      <div class="overflow-hidden whitespace-nowrap flex-1 relative flex">
        <div class="flex animate-marquee-slow hover:pause-on-hover whitespace-nowrap">
          <div class="flex items-center space-x-6 shrink-0 pr-6">
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Iqra' Bismi Rabbikalladzi Khalaq — Membaca adalah kunci peradaban ilmu dan kemuliaan akhlak.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              E-Library 24 Jam: Tersedia ratusan judul e-book &amp; modul Kurikulum Merdeka bebas baca online.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Presensi Mandiri Cepat: Scan QR Code Kartu Pelajar atau masukkan NISN untuk absensi pengunjung.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Layanan Bebas Pustaka Online: Cek tanggungan pinjaman buku kelulusan siswa secara mandiri tanpa antre.
            </span>
          </div>
          <div class="flex items-center space-x-6 shrink-0 pr-6" aria-hidden="true">
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Iqra' Bismi Rabbikalladzi Khalaq — Membaca adalah kunci peradaban ilmu dan kemuliaan akhlak.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              E-Library 24 Jam: Tersedia ratusan judul e-book &amp; modul Kurikulum Merdeka bebas baca online.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Presensi Mandiri Cepat: Scan QR Code Kartu Pelajar atau masukkan NISN untuk absensi pengunjung.
            </span>
            <span class="inline-flex items-center gap-2 font-medium tracking-wide">
              <i class="fa-solid fa-circle-dot text-accent-yellow text-[8px]"></i>
              Layanan Bebas Pustaka Online: Cek tanggungan pinjaman buku kelulusan siswa secara mandiri tanpa antre.
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= 3. MAIN NAVIGATION BAR (2 BARIS ELEGAN IDENTIK INDEX.PHP) ================= -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200">
    <!-- Baris 1: Brand Logo & Tombol Aksi Utama -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-2.5 flex items-center justify-between gap-4">
      
      <!-- Brand Logo -->
      <a href="/perpustakaan" class="flex items-center gap-2.5 sm:gap-3 group">
                  <img src="/madrasah-theme/perpustakaan/logo239.png" alt="Logo MADRASAH ALIYAH NEGERI 4 BANJAR" class="w-9 h-9 sm:w-10 sm:h-10 object-contain group-hover:scale-105 transition-transform">
                <div class="flex flex-col justify-center">
          <div class="flex items-center gap-2">
            <h1 class="text-sm sm:text-base lg:text-lg font-bold font-sans text-slate-900 tracking-tight leading-tight group-hover:text-kemenag-700 transition-colors">
              MADRASAH ALIYAH NEGERI 4 BANJAR            </h1>
            <span class="hidden sm:inline-block bg-accent-gold/20 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded border border-amber-300/40">E-LIBRARY</span>
          </div>
          <span class="text-[10px] text-slate-500 font-medium -mt-0.5 block">E-Perpustakaan Digital • Khazanah Literasi Madrasah</span>
        </div>
      </a>

      <!-- Quick Action Right: Search, Buku Tamu, Siswa, Admin, Mobile Toggle -->
      <div class="flex items-center space-x-2 sm:space-x-3 ml-auto">
        
        <!-- Tombol Search Modal -->
        <button onclick="openSearchModal()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-kemenag-700 flex items-center justify-center transition shadow-sm" title="Pencarian Cepat">
          <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
        </button>

        <!-- Tombol Presensi Buku Tamu (High Priority) -->
        <button type="button" onclick="$('#modalBukuTamu').modal('show')" class="hidden sm:inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-kemenag-800 text-xs sm:text-[13px] font-bold px-3 py-2 rounded-xl transition border border-emerald-200 shadow-sm" title="Presensi Buku Tamu">
          <i class="fa-solid fa-qrcode text-kemenag-700"></i>
          <span>Buku Tamu</span>
        </button>

        <!-- Tombol Statistik Kunjungan -->
        <button type="button" onclick="$('#modalStatistikKunjungan').modal('show')" class="hidden lg:inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-[13px] font-bold px-3 py-2 rounded-xl transition border border-slate-200 shadow-sm" title="Grafik Statistik Kunjungan">
          <i class="fa-solid fa-chart-pie text-amber-600"></i>
          <span>Statistik</span>
        </button>

        <!-- Tombol Login Siswa (Nuansa Oranye-Emas Landing Page) -->
                  <button type="button" onclick="$('#modalLoginSiswa').modal('show')" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-orange-500/30 transition-all border border-amber-300/30" title="Masuk Portal Siswa">
            <i class="fa-solid fa-user-graduate text-amber-100 text-xs"></i>
            <span>Login Siswa</span>
          </button>
        
        <!-- Tombol Login Admin / Pustakawan -->
        <a href="/login" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-kemenag-700 to-kemenag-800 hover:from-kemenag-800 hover:to-kemenag-900 text-white text-xs sm:text-[13px] font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-emerald-900/20 transition-all border border-emerald-600/30" title="Masuk Portal Pustakawan / Admin">
          <i class="fa-solid fa-user-tie text-accent-yellow text-xs"></i>
          <span>Pustakawan</span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button onclick="toggleMobileMenu()" class="md:hidden w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-slate-200 transition">
          <i class="fa-solid fa-bars text-sm sm:text-base"></i>
        </button>
      </div>

    </div>

    <!-- Baris 2: Sub-Navbar Menu Navigasi Lengkap (Desktop) -->
    <div class="hidden md:block bg-slate-50/90 border-t border-slate-200/80">
      <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 py-1.5 flex items-center justify-between">
        
        <nav class="flex flex-wrap items-center gap-x-6 gap-y-1 text-[13px] font-semibold text-slate-700">
          <a href="/perpustakaan" class="text-kemenag-700 hover:text-kemenag-800 transition py-1 flex items-center gap-1.5 font-bold">
            <i class="fa-solid fa-house text-xs text-kemenag-600"></i> Beranda Perpus
          </a>

          <a href="/perpustakaan" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <i class="fa-solid fa-book-open text-xs text-slate-400"></i> Semua Katalog (OPAC)
          </a>

          <a href="/perpustakaan?ebook=1" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1.5 text-amber-700 font-bold">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            <i class="fa-solid fa-file-pdf text-xs text-amber-600"></i> E-Book Digital
          </a>

          <a href="/perpustakaan?q=Kurikulum+Merdeka" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <i class="fa-solid fa-graduation-cap text-xs text-slate-400"></i> Kurikulum Merdeka
          </a>

          <a href="/perpustakaan?q=Agama" class="hover:text-kemenag-700 transition py-1 flex items-center gap-1">
            <i class="fa-solid fa-moon text-xs text-slate-400"></i> Referensi Islam
          </a>

          <!-- Dropdown Layanan Mandiri -->
          <div class="relative group">
            <button class="flex items-center gap-1 hover:text-kemenag-700 py-1 transition text-emerald-800 font-bold">
              <span>Layanan Mandiri</span>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></i>
            </button>
            <div class="absolute left-0 top-full pt-1.5 hidden group-hover:block w-64 z-50">
              <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-2.5 flex flex-col space-y-1">
                <a href="javascript:void(0)" onclick="$('#modalBukuTamu').modal('show')" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                  <i class="fa-solid fa-qrcode text-kemenag-600 w-4"></i> Presensi Buku Tamu (QR)
                </a>
                <a href="javascript:void(0)" onclick="$('#modalCekBebas').modal('show')" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                  <i class="fa-solid fa-certificate text-blue-600 w-4"></i> Cek Bebas Pustaka Online
                </a>
                <a href="javascript:void(0)" onclick="$('#modalUsulanBuku').modal('show')" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                  <i class="fa-solid fa-lightbulb text-amber-600 w-4"></i> Usul Pengadaan Buku Baru
                </a>
                <a href="javascript:void(0)" onclick="$('#modalStatistikKunjungan').modal('show')" class="px-3 py-2 rounded-xl hover:bg-emerald-50 hover:text-kemenag-700 text-xs font-medium transition flex items-center gap-2">
                  <i class="fa-solid fa-chart-line text-purple-600 w-4"></i> Grafik Statistik Kunjungan
                </a>
              </div>
            </div>
          </div>

          <a href="/perpustakaan#tata-tertib" class="hover:text-kemenag-700 transition py-1">Tata Tertib</a>
          <a href="/" class="hover:text-kemenag-700 transition py-1 text-slate-500"><i class="fa-solid fa-arrow-up-right-from-square text-[10px] mr-1"></i> Portal Madrasah</a>
        </nav>

        <!-- Quick Info Kanan Sub-Navbar -->
        <div class="hidden lg:flex items-center gap-3 text-xs text-slate-500 font-medium">
          <a href="/lacak" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-clock-rotate-left text-emerald-600"></i>
            <span>Tracking Berkas</span>
          </a>
          <span class="text-slate-300">•</span>
          <a href="/login?portal=siswa" class="hover:text-kemenag-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-user-graduate text-amber-500"></i>
            <span>Portal Siswa</span>
          </a>
        </div>

      </div>
    </div>
  </header>

  <!-- Mobile Drawer Menu -->
  <div id="mobileMenu" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm">
    <div class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-white p-6 shadow-2xl flex flex-col justify-between overflow-y-auto">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-book-bookmark text-kemenag-700 text-xl"></i>
            <span class="font-extrabold text-slate-900 text-sm">MADRASAH ALIYAH NEGERI 4 BANJAR</span>
          </div>
          <button onclick="toggleMobileMenu()" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <nav class="mt-6 flex flex-col space-y-2.5 font-semibold text-slate-700 text-sm">
          <a href="/perpustakaan" onclick="toggleMobileMenu()" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700">Beranda Perpus</a>
          
          <div class="grid grid-cols-2 gap-2 my-1">
            <a href="javascript:void(0)" onclick="toggleMobileMenu(); $('#modalLoginSiswa').modal('show');" class="p-2.5 bg-amber-50 hover:bg-orange-100 rounded-xl text-orange-900 font-bold flex items-center justify-center gap-1.5 text-xs border border-orange-200">
              <i class="fa-solid fa-user-graduate text-orange-600"></i> Login Siswa
            </a>
            <a href="/login" class="p-2.5 bg-emerald-50 hover:bg-emerald-100 rounded-xl text-kemenag-800 font-bold flex items-center justify-center gap-1.5 text-xs border border-emerald-200">
              <i class="fa-solid fa-user-tie text-kemenag-600"></i> Pustakawan
            </a>
          </div>

          <a href="javascript:void(0)" onclick="toggleMobileMenu(); $('#modalBukuTamu').modal('show');" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2">
            <i class="fa-solid fa-qrcode text-kemenag-600"></i> Presensi Buku Tamu (QR)
          </a>
          <a href="/perpustakaan?ebook=1" onclick="toggleMobileMenu()" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2">
            <i class="fa-solid fa-file-pdf text-amber-600"></i> Koleksi E-Book Digital
          </a>
          <a href="javascript:void(0)" onclick="toggleMobileMenu(); $('#modalCekBebas').modal('show');" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2">
            <i class="fa-solid fa-certificate text-blue-600"></i> Cek Bebas Pustaka Online
          </a>
          <a href="javascript:void(0)" onclick="toggleMobileMenu(); $('#modalUsulanBuku').modal('show');" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2">
            <i class="fa-solid fa-lightbulb text-amber-500"></i> Usulan Pengadaan Buku
          </a>
          <a href="javascript:void(0)" onclick="toggleMobileMenu(); $('#modalStatistikKunjungan').modal('show');" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2">
            <i class="fa-solid fa-chart-line text-purple-600"></i> Grafik Statistik Kunjungan
          </a>
          <a href="/" class="p-2 hover:bg-emerald-50 rounded-xl hover:text-kemenag-700 flex items-center gap-2 border-t border-slate-100 mt-2">
            <i class="fa-solid fa-house text-emerald-600"></i> Beranda Utama Madrasah
          </a>
        </nav>
      </div>

      <div class="pt-6 border-t border-slate-100">
        <a href="/perpustakaan?ebook=1" onclick="toggleMobileMenu()" class="w-full bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold py-3 rounded-xl shadow-md text-center block">
          <i class="fa-solid fa-book-open mr-1"></i> Buka E-Book Reader Digital
        </a>
      </div>
    </div>
  </div>

  <!-- ================= 4. HERO SECTION (TEMA LITERASI IDENTIK INDEX.PHP) ================= -->
  <section class="relative bg-gradient-to-br from-kemenag-900 via-kemenag-800 to-kemenag-950 text-white py-12 lg:py-16 overflow-hidden">
    <!-- Geometric Background Texture -->
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#34d399_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
    <div class="absolute -left-20 -bottom-20 w-96 h-96 rounded-full bg-accent-gold/20 blur-3xl"></div>

    <div class="relative max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 grid lg:grid-cols-12 gap-10 items-center">
      
      <!-- Text Hero Left -->
      <div class="lg:col-span-7 space-y-5">
        <div class="inline-flex items-center gap-2 bg-kemenag-700/80 border border-emerald-400/30 px-3.5 py-1.5 rounded-full text-xs font-semibold text-emerald-200 shadow-sm">
          <span class="w-2 h-2 rounded-full bg-accent-yellow animate-pulse"></span>
          <span>Pusat Sumber Belajar &amp; Khazanah Literasi Digital Madrasah</span>
        </div>

        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold font-sans leading-tight tracking-tight text-white">
          Membuka Cakrawala Ilmu, <span class="text-transparent bg-clip-text bg-gradient-to-r from-accent-yellow via-yellow-200 to-emerald-200">Menumbuhkan Karakter Qur’ani</span> Melalui Literasi
        </h1>

        <p class="text-sm sm:text-base text-emerald-100 max-w-2xl leading-relaxed">
          Selamat datang di E-Perpustakaan Digital MADRASAH ALIYAH NEGERI 4 BANJAR. Akses ribuan buku teks Kurikulum Merdeka, referensi keislaman, ensiklopedia, dan modul digital dengan fitur baca online interaktif, aman, dan terintegrasi 24 jam.
        </p>

        <!-- FLOATING SEARCH HUB & FILTER -->
        <form method="GET" action="/perpustakaan" class="bg-white/95 backdrop-blur-md p-2 rounded-2xl shadow-2xl border border-white/20 text-slate-800">
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
            <div class="sm:col-span-6 flex items-center px-3 gap-2">
              <i class="fa-solid fa-magnifying-glass text-emerald-600"></i>
              <input type="text" name="q" value="" placeholder="Ketik judul, penulis, penerbit, atau ISBN..." class="w-full text-xs sm:text-sm bg-transparent outline-none text-slate-800 placeholder-slate-400 py-1.5">
            </div>
            <div class="sm:col-span-4 border-t sm:border-t-0 sm:border-l border-slate-200 px-3 py-1">
              <select name="kat" class="w-full text-xs sm:text-sm bg-transparent outline-none text-slate-700 font-medium">
                <option value="0">Semua Kategori</option>
                <option value="1">AGAMA</option><option value="2">Buku Pelajaran Umum</option><option value="4">Fiksi &amp; Sastra</option><option value="3">Kamus &amp; Ensiklopedia</option><option value="6">Karya Tulis &amp; Majalah</option><option value="5">Teknologi &amp; Komputer</option>              </select>
            </div>
            <div class="sm:col-span-2">
              <button type="submit" class="w-full bg-gradient-to-r from-kemenag-600 to-kemenag-800 hover:from-kemenag-700 hover:to-kemenag-900 text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-search"></i>
                <span>Cari</span>
              </button>
            </div>
          </div>
        </form>

        <!-- Quick Tags -->
        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
          <span class="text-emerald-200 font-medium">Topik Cepat:</span>
          <a href="/perpustakaan?q=Kurikulum+Merdeka" class="bg-kemenag-800/80 hover:bg-kemenag-700 border border-emerald-500/30 text-emerald-100 px-2.5 py-1 rounded-lg transition">#KurikulumMerdeka</a>
          <a href="/perpustakaan?q=Agama" class="bg-kemenag-800/80 hover:bg-kemenag-700 border border-emerald-500/30 text-emerald-100 px-2.5 py-1 rounded-lg transition">#PendidikanAgama</a>
          <a href="/perpustakaan?ebook=1" class="bg-accent-gold/20 hover:bg-accent-gold/30 border border-amber-400/40 text-accent-yellow px-2.5 py-1 rounded-lg transition font-bold">#E-BookDigital</a>
          <a href="/perpustakaan?q=Sains" class="bg-kemenag-800/80 hover:bg-kemenag-700 border border-emerald-500/30 text-emerald-100 px-2.5 py-1 rounded-lg transition">#SainsTeknologi</a>
        </div>

        <div class="pt-2 flex flex-wrap items-center gap-3">
          <a href="/perpustakaan?ebook=1" class="bg-accent-gold hover:bg-amber-400 text-slate-900 font-extrabold px-5 py-2.5 rounded-xl shadow-lg shadow-amber-500/20 hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm">
            <i class="fa-solid fa-file-pdf"></i>
            <span>Buka E-Book Reader</span>
          </a>
          <button type="button" onclick="$('#modalBukuTamu').modal('show')" class="bg-kemenag-700/90 hover:bg-kemenag-600 border border-emerald-400/40 text-white font-bold px-5 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm">
            <i class="fa-solid fa-qrcode text-accent-yellow"></i>
            <span>Presensi Pengunjung</span>
          </button>
          <button type="button" onclick="$('#modalCekBebas').modal('show')" class="bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-200 hover:text-white font-bold px-4 py-2.5 rounded-xl hover:scale-105 transition-all flex items-center gap-2 text-xs sm:text-sm">
            <i class="fa-solid fa-certificate text-accent-yellow"></i>
            <span>Cek Bebas Pustaka</span>
          </button>
        </div>

        <!-- Metric Counter Badges (4 Kolom Sesuai index.php) -->
        <div class="pt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 border-t border-emerald-700/60 text-center">
          <div class="bg-kemenag-800/60 p-3 rounded-xl border border-emerald-500/20">
            <span class="text-2xl lg:text-3xl font-extrabold text-accent-yellow block font-sans">31+</span>
            <span class="text-[11px] text-emerald-200 uppercase font-medium">Judul Buku</span>
          </div>
          <div class="bg-kemenag-800/60 p-3 rounded-xl border border-emerald-500/20">
            <span class="text-2xl lg:text-3xl font-extrabold text-white block font-sans">234+</span>
            <span class="text-[11px] text-emerald-200 uppercase font-medium">Eksemplar Fisik</span>
          </div>
          <div class="bg-kemenag-800/60 p-3 rounded-xl border border-emerald-500/20">
            <span class="text-2xl lg:text-3xl font-extrabold text-accent-yellow block font-sans">6</span>
            <span class="text-[11px] text-emerald-200 uppercase font-medium">Klasifikasi Bidang</span>
          </div>
          <div class="bg-kemenag-800/60 p-3 rounded-xl border border-emerald-500/20 cursor-pointer hover:bg-kemenag-700/60 transition" onclick="$('#modalStatistikKunjungan').modal('show')" title="Klik untuk lihat grafik live">
            <span class="text-2xl lg:text-3xl font-extrabold text-emerald-300 block font-sans">0</span>
            <span class="text-[11px] text-emerald-200 uppercase font-medium flex items-center justify-center gap-1">
              <i class="fa-solid fa-chart-line text-[9px] text-accent-yellow"></i> Tamu Hari Ini
            </span>
          </div>
        </div>
      </div>

      <!-- Hero Visual Right (Modern Reading Space & Floating Achievement Badges) -->
      <div class="lg:col-span-5 relative">
        <div class="relative mx-auto max-w-md lg:max-w-none">
          <div class="rounded-3xl overflow-hidden shadow-2xl border-4 border-emerald-500/30 bg-kemenag-800 relative group">
            <img src="/madrasah-theme/perpustakaan/photo-1521587760476-6c12a4b040da" alt="Ruang Baca Modern MADRASAH ALIYAH NEGERI 4 BANJAR" class="w-full h-80 sm:h-96 object-cover group-hover:scale-105 transition-transform duration-700">
            <div class="absolute inset-0 bg-gradient-to-t from-kemenag-950 via-kemenag-950/30 to-transparent"></div>
            
            <!-- Floating Badge 1 (Top Left) -->
            <div class="absolute top-4 left-4 bg-kemenag-950/90 backdrop-blur-md border border-emerald-500/40 px-3 py-2 rounded-2xl shadow-xl flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-accent-gold text-slate-950 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-award"></i>
              </div>
              <div>
                <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Akreditasi Pustaka</span>
                <span class="text-xs font-extrabold text-white">OPAC 4.0 Terakreditasi</span>
              </div>
            </div>

            <!-- Floating Badge 2 (Bottom Right) -->
            <div class="absolute bottom-5 right-4 bg-kemenag-950/90 backdrop-blur-md border border-emerald-500/40 px-3.5 py-2 rounded-2xl shadow-xl flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-shield-halved"></i>
              </div>
              <div>
                <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Keamanan Dokumen</span>
                <span class="text-xs font-extrabold text-white">E-Reader Anti-Copy 100%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ================= 5. PUSAT LAYANAN DIGITAL MANDIRI (GAYA E-LAYANAN / PTSP INDEX.PHP) ================= -->
  <section class="py-12 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      
      <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 gap-4">
        <div>
          <span class="text-xs font-extrabold text-kemenag-700 tracking-wider uppercase flex items-center gap-1.5 mb-1">
            <i class="fa-solid fa-cubes-stacked"></i> E-Layanan Terpadu
          </span>
          <h2 class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-900 tracking-tight">
            Pusat Layanan Digital Mandiri
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Nikmati kemudahan presensi mandiri, pengecekan bebas pustaka kelulusan, dan pengajuan usulan buku baru secara cepat tanpa antre.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-kemenag-800 text-xs font-bold border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Layanan Aktif 24 Jam
          </span>
        </div>
      </div>

      <!-- 4 Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- 1. Presensi Buku Tamu QR -->
        <div onclick="$('#modalBukuTamu').modal('show')" class="bg-slate-50 hover:bg-emerald-50/50 p-6 rounded-2xl border border-slate-200 hover:border-emerald-300 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer group flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-qrcode"></i>
            </div>
            <h3 class="font-bold text-base text-slate-900 group-hover:text-kemenag-700 transition-colors">
              Presensi Buku Tamu
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Scan barcode kartu pelajar atau masukkan NISN saat berkunjung ke perpustakaan.
            </p>
          </div>
          <div class="pt-4 flex items-center justify-between text-xs font-bold text-kemenag-700">
            <span>Isi Kehadiran</span>
            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </div>
        </div>

        <!-- 2. Cek Bebas Pustaka -->
        <div onclick="$('#modalCekBebas').modal('show')" class="bg-slate-50 hover:bg-blue-50/50 p-6 rounded-2xl border border-slate-200 hover:border-blue-300 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer group flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-certificate"></i>
            </div>
            <h3 class="font-bold text-base text-slate-900 group-hover:text-blue-700 transition-colors">
              Cek Bebas Pustaka
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Verifikasi mandiri status tanggungan pinjaman buku untuk syarat kelulusan &amp; mutasi.
            </p>
          </div>
          <div class="pt-4 flex items-center justify-between text-xs font-bold text-blue-700">
            <span>Cek Status Bebas</span>
            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </div>
        </div>

        <!-- 3. Usul Pengadaan Buku -->
        <div onclick="$('#modalUsulanBuku').modal('show')" class="bg-slate-50 hover:bg-amber-50/50 p-6 rounded-2xl border border-slate-200 hover:border-amber-300 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer group flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-lightbulb"></i>
            </div>
            <h3 class="font-bold text-base text-slate-900 group-hover:text-amber-700 transition-colors">
              Usul Pengadaan Buku
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Rekomendasikan judul buku favorit yang Anda ingin ada di perpustakaan madrasah.
            </p>
          </div>
          <div class="pt-4 flex items-center justify-between text-xs font-bold text-amber-700">
            <span>Kirim Rekomendasi</span>
            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </div>
        </div>

        <!-- 4. Grafik Statistik Kunjungan -->
        <div onclick="$('#modalStatistikKunjungan').modal('show')" class="bg-slate-50 hover:bg-purple-50/50 p-6 rounded-2xl border border-slate-200 hover:border-purple-300 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer group flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3 class="font-bold text-base text-slate-900 group-hover:text-purple-700 transition-colors">
              Statistik Kunjungan
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Lihat data trafik kunjungan harian, jam sibuk, dan daftar siswa teraktif membaca.
            </p>
          </div>
          <div class="pt-4 flex items-center justify-between text-xs font-bold text-purple-700">
            <span>Lihat Grafik Live</span>
            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ================= 6. SPOTLIGHT BUKU PILIHAN REDAKSI (EDITORIAL PICK) ================= -->
    <section class="py-10 bg-slate-50 border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      
      <div class="bg-white rounded-3xl p-6 sm:p-8 lg:p-10 border border-slate-200/80 shadow-sm">
        <div class="grid lg:grid-cols-12 gap-8 items-center">
          
          <!-- Cover 3D -->
          <div class="lg:col-span-3 text-center">
            <div class="inline-block group">
              <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790212410_412.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>            </div>
          </div>

          <!-- Deskripsi & Info -->
          <div class="lg:col-span-6 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
              <span class="bg-amber-100 text-amber-800 text-[11px] font-extrabold px-3 py-1 rounded-full uppercase flex items-center gap-1.5">
                <i class="fa-solid fa-star text-amber-500"></i> Rekomendasi Pustakawan Pekan Ini
              </span>
              <span class="bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-full border border-slate-200">
                Fiksi &amp; Sastra              </span>
            </div>

            <h3 class="text-xl sm:text-2xl font-extrabold font-sans text-slate-900 leading-snug">
              DI SUDUT HYDE PARK            </h3>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
              <span class="flex items-center gap-1"><i class="fa-solid fa-user-pen text-kemenag-600"></i> SUNARYO BASUKI KS</span>
              <span class="flex items-center gap-1"><i class="fa-solid fa-building text-slate-400"></i> PT BALAI PUSTAKA</span>
              <span class="flex items-center gap-1"><i class="fa-solid fa-calendar text-slate-400"></i> 1993</span>
            </div>

            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-h-20 overflow-hidden text-ellipsis line-clamp-3">
              Koleksi istimewa khazanah madrasah yang sangat direkomendasikan untuk menambah wawasan keislaman, keilmuan, dan pembentukan karakter mulia peserta didik.            </p>

            <div class="pt-2 flex flex-wrap items-center gap-3">
                              <button type="button" class="btn-open-reader bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold px-5 py-2.5 rounded-xl shadow-md text-xs sm:text-sm flex items-center gap-2 transition" data-file="assets/upload/buku/ebook_1790212410_636.pdf" data-judul="DI SUDUT HYDE PARK">
                  <i class="fa-solid fa-file-pdf"></i>
                  <span>Baca E-Book Sekarang</span>
                </button>
              
              <button type="button" class="btn-detail-buku bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-1.5 transition" data-id="36" data-judul="DI SUDUT HYDE PARK" data-penulis="SUNARYO BASUKI KS" data-penerbit="PT BALAI PUSTAKA" data-tahun="1993" data-isbn="979-407-584-1" data-kategori="Fiksi &amp; Sastra" data-rak="800" data-stok="1" data-cover="assets/upload/buku/cover_1790212410_412.png" data-ebook="assets/upload/buku/ebook_1790212410_636.pdf" data-deskripsi="">
                <i class="fa-solid fa-circle-info text-slate-500"></i>
                <span>Detail Buku</span>
              </button>

                              <button type="button" onclick="bookingBuku(36, 'DI SUDUT HYDE PARK')" class="bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-1.5 transition">
                  <i class="fa-solid fa-bookmark"></i>
                  <span>Pinjam Buku</span>
                </button>
                          </div>
          </div>

          <!-- Review Pustakawan Kanan -->
          <div class="lg:col-span-3 lg:border-l border-slate-100 lg:pl-8">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2.5">
              <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                <i class="fa-solid fa-quote-left text-amber-500"></i> Catatan Pustakawan
              </div>
              <p class="text-xs text-slate-500 italic leading-relaxed">
                "Koleksi terpilih dengan bahasa yang mudah dipahami, sangat relevan sebagai penunjang belajar dan pengayaan materi kelas."
              </p>
              <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                <span class="text-slate-500">Status Akses:</span>
                <span class="font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-md">Bebas Akses Siswa</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>
  
  <!-- ================= 7. HUB KATEGORI KOLEKSI ================= -->
    <section class="py-10 bg-white border-b border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      
      <div class="flex items-center justify-between mb-6">
        <div>
          <span class="text-xs font-extrabold text-kemenag-700 tracking-wider uppercase flex items-center gap-1.5 mb-1">
            <i class="fa-solid fa-tags"></i> Klasifikasi Ilmu
          </span>
          <h2 class="text-xl sm:text-2xl font-extrabold font-sans text-slate-900 tracking-tight">
            Jelajahi Kategori Koleksi
          </h2>
        </div>
        <a href="/perpustakaan" class="text-xs font-bold text-kemenag-700 hover:text-kemenag-800 flex items-center gap-1">
          <span>Semua Kategori</span>
          <i class="fa-solid fa-chevron-right text-[10px]"></i>
        </a>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <a href="/perpustakaan?kat=1" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-slate-50 text-slate-700 border-slate-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-folder-open"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            AGAMA          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">9 Judul</span>
        </a>
                <a href="/perpustakaan?kat=4" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-orange-50 text-orange-700 border-orange-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-pen-nib"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            Fiksi &amp; Sastra          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">6 Judul</span>
        </a>
                <a href="/perpustakaan?kat=2" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-blue-50 text-blue-700 border-blue-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            Buku Pelajaran Umum          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">6 Judul</span>
        </a>
                <a href="/perpustakaan?kat=5" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-teal-50 text-teal-700 border-teal-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-laptop-code"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            Teknologi &amp; Komputer          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">4 Judul</span>
        </a>
                <a href="/perpustakaan?kat=6" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-rose-50 text-rose-700 border-rose-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-newspaper"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            Karya Tulis &amp; Majalah          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">3 Judul</span>
        </a>
                <a href="/perpustakaan?kat=3" class="bg-slate-50 hover:bg-white p-4 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition group text-center flex flex-col items-center">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg mb-2.5 bg-purple-50 text-purple-700 border-purple-200 border group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-book"></i>
          </div>
          <h4 class="font-bold text-xs text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-1">
            Kamus &amp; Ensiklopedia          </h4>
          <span class="text-[11px] text-slate-500 font-medium mt-0.5">3 Judul</span>
        </a>
              </div>

    </div>
  </section>
  
  <!-- ================= 8. KATALOG BUKU & TABBED COLLECTIONS (GAYA WARTA BERITA & PRESTASI) ================= -->
  <main class="py-12 bg-slate-50 flex-1">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      
              <!-- TAB SHOWCASE KOLEKSI TERPILIH (IDENTIK GAYA SECTION WARTA BERITA & PRESTASI) -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-6 gap-4">
          <div>
            <span class="text-xs font-extrabold text-kemenag-700 tracking-wider uppercase flex items-center gap-1.5 mb-1">
              <i class="fa-solid fa-book-bookmark"></i> Katalog Pustaka Terpilih
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-900 tracking-tight">
              Koleksi Buku &amp; E-Book Terpadu
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
              Rekomendasi bahan pustaka paling banyak dibaca, koleksi terbaru masuk, dan modul e-book digital bebas akses.
            </p>
          </div>

          <!-- TABS NAVIGATION -->
          <div class="flex items-center gap-1.5 bg-slate-200/80 p-1.5 rounded-2xl self-start sm:self-auto" id="koleksiTabs" role="tablist">
            <button class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition active-tab-btn bg-white text-kemenag-800 shadow-sm" id="btn-tab-populer" onclick="switchKoleksiTab('tab-populer', this)">
              <i class="fa-solid fa-fire text-rose-500 mr-1"></i> Terpopuler
            </button>
            <button class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition text-slate-600 hover:text-slate-900" id="btn-tab-terbaru" onclick="switchKoleksiTab('tab-terbaru', this)">
              <i class="fa-solid fa-star text-amber-500 mr-1"></i> Baru Masuk
            </button>
            <button class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition text-slate-600 hover:text-slate-900" id="btn-tab-ebook" onclick="switchKoleksiTab('tab-ebook', this)">
              <i class="fa-solid fa-file-pdf text-amber-600 mr-1"></i> E-Book Bebas Baca
            </button>
          </div>
        </div>

        <!-- TAB CONTENT 1: TERPOPULER -->
        <div id="tab-populer" class="koleksi-tab-panel">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #581c87 0%, #7e22ce 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Kamus &amp; Ensi</span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Ensiklopedia Sains dan Teknologi Dunia">Ensiklopedia Sains dan Teknolog...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Ensiklopedia Sains dan Teknologi Dunia">
                    Ensiklopedia Sains dan Teknologi Dunia                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dorling Kindersley (DK Publishing)</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak C-02 (Ensiklopedia)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">4 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="16" data-judul="Ensiklopedia Sains dan Teknologi Dunia" data-penulis="Dorling Kindersley (DK Publishing)" data-penerbit="DK London / Gramedia" data-tahun="2024" data-isbn="978-024-122-593-6" data-kategori="Kamus &amp; Ensiklopedia" data-rak="Rak C-02 (Ensiklopedia)" data-stok="4" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_ensiklopedia_sains.pdf" data-deskripsi="Visualisasi lengkap penemuan sains terhebat, astronomi antariksa, robotika, energi terbarukan, dan bumi.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(16, 'Ensiklopedia Sains dan Teknologi Dunia')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_ensiklopedia_sains.pdf" data-judul="Ensiklopedia Sains dan Teknologi Dunia">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #004d40 0%, #006747 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">AGAMA</span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Laskar Pelangi">Laskar Pelangi</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Laskar Pelangi">
                    Laskar Pelangi                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Andrea Hirata</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak A-03 (Sejarah Islam)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">5 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="3" data-judul="Laskar Pelangi" data-penulis="Andrea Hirata" data-penerbit="Bentang Pustaka" data-tahun="2022" data-isbn="978-602-03-2478-4" data-kategori="AGAMA" data-rak="Rak A-03 (Sejarah Islam)" data-stok="5" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_sejarah_islam.pdf" data-deskripsi="Kajian komprehensif perkembangan peradaban Islam dari era Khulafaur Rasyidin, Dinasti Umayyah, hingga Abbasiyah.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(3, 'Laskar Pelangi')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_sejarah_islam.pdf" data-judul="Laskar Pelangi">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790212410_412.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="DI SUDUT HYDE PARK">
                    DI SUDUT HYDE PARK                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">SUNARYO BASUKI KS</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 800</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="36" data-judul="DI SUDUT HYDE PARK" data-penulis="SUNARYO BASUKI KS" data-penerbit="PT BALAI PUSTAKA" data-tahun="1993" data-isbn="979-407-584-1" data-kategori="Fiksi &amp; Sastra" data-rak="800" data-stok="1" data-cover="assets/upload/buku/cover_1790212410_412.png" data-ebook="assets/upload/buku/ebook_1790212410_636.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(36, 'DI SUDUT HYDE PARK')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790212410_636.pdf" data-judul="DI SUDUT HYDE PARK">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790045225_933.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Buat Apa Shalat - Dr Haidar Bagir">
                    Buat Apa Shalat - Dr Haidar Bagir                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr Haidar Bagir</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="35" data-judul="Buat Apa Shalat - Dr Haidar Bagir" data-penulis="Dr Haidar Bagir" data-penerbit="Penerbit Mizania" data-tahun="2026" data-isbn="978-979-8394-93-5" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790045225_933.png" data-ebook="assets/upload/buku/ebook_1790045225_976.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(35, 'Buat Apa Shalat - Dr Haidar Bagir')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790045225_976.pdf" data-judul="Buat Apa Shalat - Dr Haidar Bagir">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044666_538.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                    ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr. H. Miftah Faridl</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="32" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?" data-penulis="Dr. H. Miftah Faridl" data-penerbit="GEMA INSANI" data-tahun="2007" data-isbn="979-56-0248-9" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044666_538.png" data-ebook="assets/upload/buku/ebook_1790044666_133.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(32, 'ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044666_133.pdf" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044397_466.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                    Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Al-‘Allâmah ‘Abdullâh al-Jibrîn</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="31" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin" data-penulis="Al-‘Allâmah ‘Abdullâh al-Jibrîn" data-penerbit="Maktabah Abu Salma al-Atsari" data-tahun="2026" data-isbn="" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044397_466.png" data-ebook="assets/upload/buku/ebook_1790044397_808.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(31, 'Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044397_808.pdf" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1789958034_919.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                    MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Ibnu Qayyim Al-Jauziyah</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="30" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)" data-penulis="Ibnu Qayyim Al-Jauziyah" data-penerbit="PUSTAKA AL-KAUTSAR" data-tahun="1999" data-isbn="979-592-110-X" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1789958034_919.png" data-ebook="assets/upload/buku/ebook_1789958034_826.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(30, 'MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1789958034_826.pdf" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-fire"></i> Populer
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #9a3412 0%, #c2410c 50%, #ea580c 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Fiksi &amp; Sast</span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Tenggelamnya Kapal Van Der Wijck">Tenggelamnya Kapal Van Der Wijck</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Tenggelamnya Kapal Van Der Wijck">
                    Tenggelamnya Kapal Van Der Wijck                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Prof. Dr. HAMKA</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak D-02 (Sastra Klasik)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">4 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="20" data-judul="Tenggelamnya Kapal Van Der Wijck" data-penulis="Prof. Dr. HAMKA" data-penerbit="Bulan Bintang" data-tahun="2022" data-isbn="978-979-418-055-6" data-kategori="Fiksi &amp; Sastra" data-rak="Rak D-02 (Sastra Klasik)" data-stok="4" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_van_der_wijck.pdf" data-deskripsi="Mahakarya sastra klasik tentang cinta Zainuddin dan Hayati yang terhalang benteng adat Minangkabau.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(20, 'Tenggelamnya Kapal Van Der Wijck')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_van_der_wijck.pdf" data-judul="Tenggelamnya Kapal Van Der Wijck">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                      </div>
        </div>

        <!-- TAB CONTENT 2: TERBARU -->
        <div id="tab-terbaru" class="koleksi-tab-panel hidden">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790212410_412.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="DI SUDUT HYDE PARK">
                    DI SUDUT HYDE PARK                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">SUNARYO BASUKI KS</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 800</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="36" data-judul="DI SUDUT HYDE PARK" data-penulis="SUNARYO BASUKI KS" data-penerbit="PT BALAI PUSTAKA" data-tahun="1993" data-isbn="979-407-584-1" data-kategori="Fiksi &amp; Sastra" data-rak="800" data-stok="1" data-cover="assets/upload/buku/cover_1790212410_412.png" data-ebook="assets/upload/buku/ebook_1790212410_636.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(36, 'DI SUDUT HYDE PARK')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790212410_636.pdf" data-judul="DI SUDUT HYDE PARK">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790045225_933.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Buat Apa Shalat - Dr Haidar Bagir">
                    Buat Apa Shalat - Dr Haidar Bagir                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr Haidar Bagir</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="35" data-judul="Buat Apa Shalat - Dr Haidar Bagir" data-penulis="Dr Haidar Bagir" data-penerbit="Penerbit Mizania" data-tahun="2026" data-isbn="978-979-8394-93-5" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790045225_933.png" data-ebook="assets/upload/buku/ebook_1790045225_976.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(35, 'Buat Apa Shalat - Dr Haidar Bagir')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790045225_976.pdf" data-judul="Buat Apa Shalat - Dr Haidar Bagir">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044666_538.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                    ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr. H. Miftah Faridl</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="32" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?" data-penulis="Dr. H. Miftah Faridl" data-penerbit="GEMA INSANI" data-tahun="2007" data-isbn="979-56-0248-9" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044666_538.png" data-ebook="assets/upload/buku/ebook_1790044666_133.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(32, 'ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044666_133.pdf" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044397_466.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                    Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Al-‘Allâmah ‘Abdullâh al-Jibrîn</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="31" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin" data-penulis="Al-‘Allâmah ‘Abdullâh al-Jibrîn" data-penerbit="Maktabah Abu Salma al-Atsari" data-tahun="2026" data-isbn="" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044397_466.png" data-ebook="assets/upload/buku/ebook_1790044397_808.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(31, 'Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044397_808.pdf" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1789958034_919.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                    MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Ibnu Qayyim Al-Jauziyah</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> 100</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">1 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="30" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)" data-penulis="Ibnu Qayyim Al-Jauziyah" data-penerbit="PUSTAKA AL-KAUTSAR" data-tahun="1999" data-isbn="979-592-110-X" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1789958034_919.png" data-ebook="assets/upload/buku/ebook_1789958034_826.pdf" data-deskripsi="">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(30, 'MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1789958034_826.pdf" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">Pedoman Praktis Karya Tulis Ilm...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">
                    Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Badan Riset dan Inovasi Nasional (BRIN)</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak F-01 (Karya Ilmiah/Jurnal)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">10 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="29" data-judul="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)" data-penulis="Badan Riset dan Inovasi Nasional (BRIN)" data-penerbit="BRIN Press" data-tahun="2024" data-isbn="978-602-496-221-9" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-01 (Karya Ilmiah/Jurnal)" data-stok="10" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_pedoman_kir.pdf" data-deskripsi="Panduan metodologi penelitian ilmiah bagi siswa SMA/MA mulai dari perumusan masalah, tinjauan pustaka, hingga presentasi.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(29, 'Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_pedoman_kir.pdf" data-judul="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">Buletin Pelajar Muslim: Inovasi...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">
                    Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Redaksi OSIM MAN 4 Banjar</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak F-02 (Majalah/Buletin)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">18 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="28" data-judul="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani" data-penulis="Redaksi OSIM MAN 4 Banjar" data-penerbit="OSIM Publishing" data-tahun="2026" data-isbn="ISSN-2987-1234" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-02 (Majalah/Buletin)" data-stok="18" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_buletin_pelajar.pdf" data-deskripsi="Buletin berkala madrasah menyajikan liputan kegiatan ekstrakurikuler, karya sastra siswa, cerpen, dan tips belajar efektif.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(28, 'Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur\'ani')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_buletin_pelajar.pdf" data-judul="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-slate-50 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-sparkles"></i> Baru
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">Jurnal Ilmiah Madrasah: Riset S...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-kemenag-700 transition-colors line-clamp-2 leading-snug" title="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">
                    Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Tim Literasi &amp; Riset MAN 4 Banjar</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-boxes-stacked text-slate-400"></i> Rak F-01 (Karya Ilmiah/Jurnal)</span>
                                          <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">15 eks</span>
                                      </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <div class="grid grid-cols-2 gap-1.5">
                    <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="27" data-judul="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026" data-penulis="Tim Literasi &amp; Riset MAN 4 Banjar" data-penerbit="Madrasah Press" data-tahun="2026" data-isbn="978-602-555-101-2" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-01 (Karya Ilmiah/Jurnal)" data-stok="15" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_jurnal_madrasah_2026.pdf" data-deskripsi="Kompilasi artikel hasil penelitian karya ilmiah remaja (KIR) siswa madrasah di bidang robotika, biologi terapan, dan sosiologi.">
                      <i class="fa-solid fa-circle-info mr-1"></i> Detail
                    </button>

                                          <button type="button" onclick="bookingBuku(27, 'Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026')" class="w-full bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold py-1.5 px-2 rounded-lg text-xs transition">
                        <i class="fa-solid fa-bookmark mr-1"></i> Pinjam
                      </button>
                                      </div>

                                      <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold py-2 px-3 rounded-lg text-xs shadow-sm flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_jurnal_madrasah_2026.pdf" data-judul="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">
                      <i class="fa-solid fa-file-pdf"></i>
                      <span>Baca E-Book Online</span>
                    </button>
                                  </div>
              </div>
                      </div>
        </div>

        <!-- TAB CONTENT 3: E-BOOK DIGITAL -->
        <div id="tab-ebook" class="koleksi-tab-panel hidden">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790212410_412.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="DI SUDUT HYDE PARK">
                    DI SUDUT HYDE PARK                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">SUNARYO BASUKI KS</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790212410_636.pdf" data-judul="DI SUDUT HYDE PARK">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="36" data-judul="DI SUDUT HYDE PARK" data-penulis="SUNARYO BASUKI KS" data-penerbit="PT BALAI PUSTAKA" data-tahun="1993" data-isbn="979-407-584-1" data-kategori="Fiksi &amp; Sastra" data-rak="800" data-stok="1" data-cover="assets/upload/buku/cover_1790212410_412.png" data-ebook="assets/upload/buku/ebook_1790212410_636.pdf" data-deskripsi="">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790045225_933.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="Buat Apa Shalat - Dr Haidar Bagir">
                    Buat Apa Shalat - Dr Haidar Bagir                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr Haidar Bagir</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790045225_976.pdf" data-judul="Buat Apa Shalat - Dr Haidar Bagir">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="35" data-judul="Buat Apa Shalat - Dr Haidar Bagir" data-penulis="Dr Haidar Bagir" data-penerbit="Penerbit Mizania" data-tahun="2026" data-isbn="978-979-8394-93-5" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790045225_933.png" data-ebook="assets/upload/buku/ebook_1790045225_976.pdf" data-deskripsi="">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044666_538.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                    ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Dr. H. Miftah Faridl</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044666_133.pdf" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="32" data-judul="ANTAR AKU KE TANAH SUCI: PANDUAN MUDAH HAJI, UMRAH, DAN ZIARA?" data-penulis="Dr. H. Miftah Faridl" data-penerbit="GEMA INSANI" data-tahun="2007" data-isbn="979-56-0248-9" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044666_538.png" data-ebook="assets/upload/buku/ebook_1790044666_133.pdf" data-deskripsi="">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1790044397_466.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                    Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Al-‘Allâmah ‘Abdullâh al-Jibrîn</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1790044397_808.pdf" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="31" data-judul="Tuntunan Sholat Menurut Al-Quran dan As-Sunah - Al-Alamah Abullah Jibrin" data-penulis="Al-‘Allâmah ‘Abdullâh al-Jibrîn" data-penerbit="Maktabah Abu Salma al-Atsari" data-tahun="2026" data-isbn="" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1790044397_466.png" data-ebook="assets/upload/buku/ebook_1790044397_808.pdf" data-deskripsi="">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    <div class="book-cover-3d-wrap">
            <img src="/madrasah-theme/perpustakaan/cover_1789958034_919.png" alt="Cover" class="book-cover-3d-img" style="max-height: 172px; max-width: 100%; border-radius: 4px 8px 8px 4px; box-shadow: -4px 0 0 0 #0f172a, inset 4px 0 8px rgba(0,0,0,0.32), 5px 10px 22px rgba(0,0,0,0.22); object-fit: contain;">
        </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                    MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Ibnu Qayyim Al-Jauziyah</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_1789958034_826.pdf" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="30" data-judul="MADARIJUS-SALIKIN (PENDAKIAN MENUJU ALLAH)" data-penulis="Ibnu Qayyim Al-Jauziyah" data-penerbit="PUSTAKA AL-KAUTSAR" data-tahun="1999" data-isbn="979-592-110-X" data-kategori="AGAMA" data-rak="100" data-stok="1" data-cover="assets/upload/buku/cover_1789958034_919.png" data-ebook="assets/upload/buku/ebook_1789958034_826.pdf" data-deskripsi="">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">Pedoman Praktis Karya Tulis Ilm...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">
                    Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Badan Riset dan Inovasi Nasional (BRIN)</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_pedoman_kir.pdf" data-judul="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="29" data-judul="Pedoman Praktis Karya Tulis Ilmiah Remaja (KIR)" data-penulis="Badan Riset dan Inovasi Nasional (BRIN)" data-penerbit="BRIN Press" data-tahun="2024" data-isbn="978-602-496-221-9" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-01 (Karya Ilmiah/Jurnal)" data-stok="10" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_pedoman_kir.pdf" data-deskripsi="Panduan metodologi penelitian ilmiah bagi siswa SMA/MA mulai dari perumusan masalah, tinjauan pustaka, hingga presentasi.">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">Buletin Pelajar Muslim: Inovasi...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">
                    Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Redaksi OSIM MAN 4 Banjar</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_buletin_pelajar.pdf" data-judul="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="28" data-judul="Buletin Pelajar Muslim: Inovasi &amp; Prestasi Generasi Qur'ani" data-penulis="Redaksi OSIM MAN 4 Banjar" data-penerbit="OSIM Publishing" data-tahun="2026" data-isbn="ISSN-2987-1234" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-02 (Majalah/Buletin)" data-stok="18" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_buletin_pelajar.pdf" data-deskripsi="Buletin berkala madrasah menyajikan liputan kegiatan ekstrakurikuler, karya sastra siswa, cerpen, dan tips belajar efektif.">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                          <div class="bg-white rounded-2xl border-2 border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-4 flex flex-col justify-between group">
                <div>
                  <div class="bg-amber-50/60 rounded-xl p-3 text-center relative mb-3 overflow-hidden">
                    <span class="absolute top-2 right-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded shadow-sm">
                      <i class="fa-solid fa-file-pdf"></i> E-Book
                    </span>
                    
    <div class="book-cover-placeholder book-cover-3d-card" style="background: linear-gradient(135deg, #831843 0%, #be185d 100%); width: 112px; height: 158px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="cover-badge">Karya Tulis </span>
            <i class="fa fa-bookmark cover-icon" style="color: #fbbf24;"></i>
        </div>
        <div class="cover-title" title="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">Jurnal Ilmiah Madrasah: Riset S...</div>
        <div class="d-flex align-items-center justify-content-between" style="font-size: 8px; opacity: 0.9;">
            <span><i class="fa fa-book mr-1"></i> E-Perpus</span>
            <span style="color: #fde047; font-weight: bold;">MAN 4</span>
        </div>
    </div>                  </div>

                  <h3 class="font-bold text-sm text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug" title="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">
                    Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026                  </h3>
                  
                  <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <i class="fa-solid fa-user-pen text-slate-400 text-[10px]"></i>
                    <span class="truncate">Tim Literasi &amp; Riset MAN 4 Banjar</span>
                  </div>

                  <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="flex items-center gap-1 text-amber-700 font-semibold"><i class="fa-solid fa-desktop"></i> E-Reader Anti-Copy</span>
                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Bebas Baca</span>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-slate-100 space-y-1.5">
                  <button type="button" class="btn-open-reader w-full bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs shadow-md flex items-center justify-center gap-1.5 transition" data-file="assets/upload/buku/ebook_jurnal_madrasah_2026.pdf" data-judul="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Baca E-Book Sekarang</span>
                  </button>

                  <button type="button" class="btn-detail-buku w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-1.5 px-2 rounded-lg text-xs transition" data-id="27" data-judul="Jurnal Ilmiah Madrasah: Riset Sains &amp; Humaniora Edisi 2026" data-penulis="Tim Literasi &amp; Riset MAN 4 Banjar" data-penerbit="Madrasah Press" data-tahun="2026" data-isbn="978-602-555-101-2" data-kategori="Karya Tulis &amp; Majalah" data-rak="Rak F-01 (Karya Ilmiah/Jurnal)" data-stok="15" data-cover="assets/database.png" data-ebook="assets/upload/buku/ebook_jurnal_madrasah_2026.pdf" data-deskripsi="Kompilasi artikel hasil penelitian karya ilmiah remaja (KIR) siswa madrasah di bidang robotika, biologi terapan, dan sosiologi.">
                    <i class="fa-solid fa-circle-info mr-1"></i> Detail Lengkap
                  </button>
                </div>
              </div>
                      </div>
        </div>

      
    </div>
  </main>

  <!-- ================= 9. SAMBUTAN KEPALA PERPUSTAKAAN & TATA TERTIB (GAYA SAMBUTAN KEPALA INDEX.PHP) ================= -->
  <section id="tata-tertib" class="py-14 bg-white border-t border-slate-200">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12">
      <div class="grid lg:grid-cols-12 gap-10 items-center">
        
        <!-- Foto / Ilustrasi Kiri -->
        <div class="lg:col-span-5 relative">
          <div class="rounded-3xl overflow-hidden shadow-xl border-4 border-emerald-500/20 bg-kemenag-900 relative">
            <img src="/madrasah-theme/perpustakaan/photo-1544717305-2782549b5136" alt="Pustakawan Madrasah" class="w-full h-80 sm:h-96 object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-kemenag-950 via-kemenag-950/20 to-transparent"></div>
            
            <div class="absolute bottom-4 left-4 right-4 bg-kemenag-950/90 backdrop-blur-md p-3.5 rounded-2xl border border-emerald-500/40 text-white">
              <span class="text-[10px] text-accent-yellow font-extrabold uppercase tracking-wider block">Pustakawan &amp; Pengelola</span>
              <h4 class="font-extrabold text-sm">MADRASAH ALIYAH NEGERI 4 BANJAR</h4>
              <p class="text-[11px] text-emerald-200 mt-0.5">Khazanah Sumber Belajar, Riset &amp; Pengayaan Siswa</p>
            </div>
          </div>
        </div>

        <!-- Teks Sambutan & Tata Tertib Kanan -->
        <div class="lg:col-span-7 space-y-4">
          <span class="text-xs font-extrabold text-kemenag-700 tracking-wider uppercase flex items-center gap-1.5">
            <i class="fa-solid fa-graduation-cap"></i> Budaya Literasi Madrasah
          </span>
          <h2 class="text-2xl sm:text-3xl font-extrabold font-sans text-slate-900 tracking-tight">
            Membangun Generasi Emas Melalui Kegemaran Membaca
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Perpustakaan MADRASAH ALIYAH NEGERI 4 BANJAR bertransformasi menjadi pusat pembelajaran yang adaptif, menyediakan ribuan koleksi cetak maupun e-book digital yang dapat diakses secara mandiri oleh seluruh peserta didik dan tenaga kependidikan.
          </p>

          <!-- 3 Poin Tata Tertib & Layanan -->
          <div class="grid sm:grid-cols-2 gap-3 pt-2">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
              <div class="font-bold text-xs text-slate-900 flex items-center gap-2 mb-1">
                <i class="fa-solid fa-circle-check text-kemenag-600"></i> Ketentuan Peminjaman
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">Maksimal pinjam 3 buku selama 7 hari kalender, dapat diperpanjang online melalui portal siswa.</p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
              <div class="font-bold text-xs text-slate-900 flex items-center gap-2 mb-1">
                <i class="fa-solid fa-shield-halved text-amber-600"></i> Hak Cipta E-Book
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">Modul dan karya ilmiah dilindungi sistem pembaca digital aman anti-copy dan anti-download.</p>
            </div>
          </div>

          <div class="pt-2 flex flex-wrap items-center gap-3">
            <button type="button" onclick="$('#modalBukuTamu').modal('show')" class="px-5 py-2.5 rounded-xl bg-kemenag-700 hover:bg-kemenag-800 text-white font-bold text-xs transition flex items-center gap-2 shadow-sm">
              <i class="fa-solid fa-qrcode text-accent-yellow"></i> Presensi Tamu Hari Ini
            </button>
            <a href="/perpustakaan?ebook=1" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-2 border border-slate-200">
              <i class="fa-solid fa-book-open"></i> Jelajah Modul Digital
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= 10. MOTTO LITERASI MADRASAH BANNER ================= -->
  <section class="bg-gradient-to-r from-kemenag-950 via-kemenag-900 to-kemenag-950 text-white py-10 border-t border-b border-emerald-900">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="space-y-1 text-center md:text-left">
        <span class="text-accent-yellow text-xs font-extrabold uppercase tracking-wider block">Gerakan Literasi Madrasah (GLM)</span>
        <h3 class="text-xl sm:text-2xl font-extrabold font-sans text-white">"Iqra' — Membaca untuk Berakhlak, Berkarya, dan Memimpin"</h3>
        <p class="text-xs text-emerald-200">Perpustakaan Digital Terakreditasi MAN 4 Banjar siap mendampingi perjalanan akademik Anda.</p>
      </div>
      <div class="shrink-0 flex items-center gap-3">
        <a href="/perpustakaan?ebook=1" class="bg-accent-gold hover:bg-amber-400 text-slate-950 font-extrabold px-6 py-3 rounded-xl shadow-lg transition text-xs sm:text-sm flex items-center gap-2">
          <i class="fa-solid fa-book"></i> Baca E-Book Hari Ini
        </a>
      </div>
    </div>
  </section>

  <!-- ================= 11. FOOTER RESMI (IDENTIK DENGAN INDEX.PHP) ================= -->
  <footer class="bg-slate-900 text-slate-300 text-xs pt-12 pb-8 border-t border-slate-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 mb-8">
      
      <!-- Brand & Kontak -->
      <div class="lg:col-span-4 space-y-3">
        <div class="flex items-center gap-3">
          <img src="/madrasah-theme/perpustakaan/logo239.png" alt="Logo MADRASAH ALIYAH NEGERI 4 BANJAR" class="w-10 h-10 object-contain">
          <div>
            <h4 class="font-bold text-white text-sm">MADRASAH ALIYAH NEGERI 4 BANJAR</h4>
            <span class="text-[11px] text-emerald-400 font-semibold block">E-Perpustakaan Digital Terpadu</span>
          </div>
        </div>
        <p class="text-slate-400 text-xs leading-relaxed">
          Pusat sumber belajar digital, katalog buku OPAC, dan layanan literasi terpadu madrasah mandiri berprestasi &amp; unggul riset Kabupaten Banjar.
        </p>
        <div class="space-y-1.5 pt-1 text-slate-400">
          <div class="flex items-start gap-2">
            <i class="fa-solid fa-location-dot text-accent-yellow mt-0.5"></i>
            <span>Jln. Pendidikan No. 1  Martapura</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-phone text-accent-yellow"></i>
            <span>081528205667</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-envelope text-accent-yellow"></i>
            <span>man4banjar426101@gmail.com</span>
          </div>
        </div>
      </div>

      <!-- Tautan Layanan -->
      <div class="lg:col-span-3 space-y-3">
        <h4 class="font-bold text-white text-sm uppercase tracking-wider border-b border-slate-800 pb-2">Layanan Pustaka</h4>
        <ul class="space-y-2">
          <li><a href="/perpustakaan" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Katalog Buku (OPAC)</a></li>
          <li><a href="/perpustakaan?ebook=1" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Koleksi E-Book Digital</a></li>
          <li><a href="javascript:void(0)" onclick="$('#modalBukuTamu').modal('show')" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Presensi Tamu (Scan QR)</a></li>
          <li><a href="javascript:void(0)" onclick="$('#modalCekBebas').modal('show')" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Cek Bebas Pustaka Online</a></li>
          <li><a href="javascript:void(0)" onclick="$('#modalUsulanBuku').modal('show')" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Usulan Pengadaan Buku</a></li>
          <li><a href="javascript:void(0)" onclick="$('#modalStatistikKunjungan').modal('show')" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Grafik Statistik Kunjungan</a></li>
        </ul>
      </div>

      <!-- Tautan Akses Siswa & Madrasah -->
      <div class="lg:col-span-2 space-y-3">
        <h4 class="font-bold text-white text-sm uppercase tracking-wider border-b border-slate-800 pb-2">Akses Siswa</h4>
        <ul class="space-y-2">
                      <li><a href="javascript:void(0)" onclick="$('#modalLoginSiswa').modal('show')" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Masuk Siswa</a></li>
            <li><a href="/login?portal=siswa" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Portal Siswa</a></li>
                    <li><a href="/layanan/ptsp" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Layanan PTSP</a></li>
          <li><a href="/lacak" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Lacak Berkas</a></li>
          <li><a href="/" class="hover:text-accent-yellow transition flex items-center gap-1.5"><i class="fa-solid fa-angle-right text-[10px] text-emerald-500"></i> Beranda Utama</a></li>
        </ul>
      </div>

      <!-- Jam Layanan & Akreditasi -->
      <div class="lg:col-span-3 space-y-3">
        <h4 class="font-bold text-white text-sm uppercase tracking-wider border-b border-slate-800 pb-2">Jam Layanan</h4>
        <div class="bg-slate-800/80 p-3.5 rounded-2xl border border-slate-700/60 space-y-2">
          <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-700">
            <span class="text-slate-400 font-semibold">Status Hari Ini:</span>
            <span class="font-extrabold text-amber-400">TUTUP</span>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-300">
            <span>Senin – Kamis</span>
            <span class="font-bold text-white">07.30 – 15.00 WITA</span>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-300">
            <span>Jum'at</span>
            <span class="font-bold text-white">07.30 – 11.30 WITA</span>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-300">
            <span>Sabtu – Minggu</span>
            <span class="font-bold text-rose-400">Libur</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom Copyright -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-500 text-[11px]">
      <div>
        © 2026 <b>E-Perpustakaan MADRASAH ALIYAH NEGERI 4 BANJAR</b>. Seluruh hak cipta dilindungi.
      </div>
      <div>
        Kementerian Agama Republik Indonesia • Gerakan Literasi Madrasah (GLM)
      </div>
    </div>
  </footer>

  <!-- ================= MODALS TAMBAHAN SEPERTI INDEX.PHP ================= -->
  
  <!-- 1. Quick Global Search Modal -->
  <div id="searchModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-start justify-center pt-20 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl relative">
      <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input type="text" id="globalSearchInput" placeholder="Ketik judul buku, pengarang, atau topik..." class="w-full text-xs sm:text-sm outline-none" onkeydown="if(event.key === 'Enter'){ window.location.href='perpustakaan?q='+encodeURIComponent(this.value); }">
        <button onclick="closeSearchModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="pt-3 text-xs text-slate-500 space-y-1">
        <span class="block font-bold text-slate-700">Pintasan Cepat:</span>
        <div class="flex flex-wrap gap-1.5 pt-1">
          <a href="/perpustakaan" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Semua Katalog</a>
          <a href="/perpustakaan?ebook=1" class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold">E-Book Digital</a>
          <a href="/perpustakaan?q=Kurikulum+Merdeka" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Kurikulum Merdeka</a>
          <a href="/perpustakaan?q=Agama" class="px-2 py-1 rounded bg-slate-100 hover:bg-emerald-100 hover:text-kemenag-800">Pendidikan Agama</a>
          <a href="javascript:void(0)" onclick="closeSearchModal(); $('#modalBukuTamu').modal('show');" class="px-2 py-1 rounded bg-emerald-50 text-kemenag-800 font-bold">Buku Tamu QR</a>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Jadwal Sholat Modal (Identik index.php) -->
  <div id="prayerModal" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative">
      <button onclick="closePrayerModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
        <i class="fa-solid fa-xmark text-lg"></i>
      </button>
      <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
        <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-kemenag-700 flex items-center justify-center text-lg">
          <i class="fa-solid fa-mosque"></i>
        </div>
        <div>
          <h3 class="font-extrabold text-slate-900 text-base">Jadwal Sholat Hari Ini</h3>
          <span class="text-xs text-slate-500 font-medium">Martapura, Kab. Banjar • WITA</span>
        </div>
      </div>
      <div class="py-4 space-y-2 text-xs">
        <div id="prayer-subuh" class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 border-2 border-emerald-500 font-bold text-kemenag-800">
          <span class="font-semibold text-slate-700">Subuh</span>
          <span class="font-bold text-slate-900">05:04 WITA</span>
        </div>
        <div id="prayer-terbit" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
          <span class="font-semibold text-slate-700">Terbit</span>
          <span class="font-bold text-slate-900">06:14 WITA</span>
        </div>
        <div id="prayer-dzuhur" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
          <span class="font-semibold text-slate-700">Dzuhur</span>
          <span class="font-bold text-slate-900">12:28 WITA</span>
        </div>
        <div id="prayer-ashar" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
          <span class="font-semibold text-slate-700">Ashar</span>
          <span class="font-bold text-slate-900">15:37 WITA</span>
        </div>
        <div id="prayer-maghrib" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
          <span class="font-semibold text-slate-700">Maghrib</span>
          <span class="font-bold text-slate-900">18:31 WITA</span>
        </div>
        <div id="prayer-isya" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
          <span class="font-semibold text-slate-700">Isya</span>
          <span class="font-bold text-slate-900">19:39 WITA</span>
        </div>
      </div>
      <div class="pt-2">
        <button onclick="closePrayerModal()" class="w-full bg-kemenag-700 text-white font-bold py-2.5 rounded-xl text-xs hover:bg-kemenag-800 transition">
          Tutup
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Back to Top Button -->
  <button id="backToTopBtn" onclick="scrollToTop()" class="fixed bottom-6 right-6 z-40 bg-kemenag-700 text-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 hover:bg-kemenag-800">
    <i class="fa-solid fa-chevron-up text-xs"></i>
  </button>

  <!-- 4. Toast Notification -->
  <div id="toastNotification" class="fixed top-5 right-5 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl border border-emerald-500/40 text-xs font-semibold flex items-center gap-2 transform transition-all duration-300 -translate-y-20 opacity-0 pointer-events-none">
    <i class="fa-solid fa-circle-check text-accent-yellow text-sm"></i>
    <span id="toastMessage">Operasi berhasil dilakukan!</span>
  </div>

    <!-- MODAL 1: PRESENSI BUKU TAMU TERPADU (SISWA/QR, GURU, TAMU LUAR) -->
    <div class="modal fade" id="modalBukuTamu" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 560px; width: 95%;">
            <div class="modal-content shadow-2xl border-0" style="border-radius: 24px; overflow: hidden; background: #ffffff;">
                
                <!-- HEADER MODAL HERO -->
                <div class="px-5 sm:px-6 py-4 relative overflow-hidden" style="background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #059669 100%);">
                    <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
                        <i class="fa fa-book-reader" style="font-size: 130px;"></i>
                    </div>
                    
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-white/15 flex items-center justify-center text-white border border-white/20 shadow-inner flex-shrink-0 backdrop-blur-xs">
                                <i class="fa fa-id-badge text-2xl"></i>
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-emerald-100 border border-white/25 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                                    Presensi Digital Perpustakaan
                                </div>
                                <h4 class="font-extrabold text-white text-[17px] sm:text-[18px] leading-tight mb-0 tracking-tight">
                                    Buku Tamu Pengunjung
                                </h4>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition text-xl leading-none flex-shrink-0" data-dismiss="modal" title="Tutup">
                            ×
                        </button>
                    </div>
                </div>

                <!-- LIVE STATISTIK PENGUNJUNG HARI INI STRIP -->
                <div class="bg-gradient-to-r from-emerald-50/90 via-teal-50/70 to-slate-50 border-b border-emerald-100/90 px-4 sm:px-6 py-2.5">
                    <div class="flex items-center justify-between text-xs text-slate-600 mb-1.5 font-medium">
                        <span class="flex items-center gap-1.5 font-bold text-emerald-900">
                            <i class="fa fa-signal text-emerald-600"></i> Statistik Kunjungan Hari Ini:
                        </span>
                        <span class="text-[11px] text-slate-500 font-semibold">
                            19 Nov 2026                        </span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        <div class="bg-white/95 rounded-xl px-2 py-1.5 text-center border border-emerald-100 shadow-2xs">
                            <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Total</div>
                            <div class="text-sm sm:text-base font-extrabold text-emerald-800 leading-tight" id="stat-tamu-total">0</div>
                        </div>
                        <div class="bg-white/95 rounded-xl px-2 py-1.5 text-center border border-emerald-100 shadow-2xs">
                            <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Siswa</div>
                            <div class="text-sm sm:text-base font-extrabold text-slate-800 leading-tight" id="stat-tamu-siswa">0</div>
                        </div>
                        <div class="bg-white/95 rounded-xl px-2 py-1.5 text-center border border-emerald-100 shadow-2xs">
                            <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Guru/PTK</div>
                            <div class="text-sm sm:text-base font-extrabold text-slate-800 leading-tight" id="stat-tamu-guru">0</div>
                        </div>
                        <div class="bg-white/95 rounded-xl px-2 py-1.5 text-center border border-emerald-100 shadow-2xs">
                            <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Tamu</div>
                            <div class="text-sm sm:text-base font-extrabold text-slate-800 leading-tight" id="stat-tamu-luar">0</div>
                        </div>
                    </div>
                </div>
                
                <!-- NOTIFIKASI KHUSUS SAAT DIPICU DARI MEMBACA E-BOOK -->
                <div id="modalBukuTamuAlertEbook" style="display: none;" class="p-3.5 bg-amber-50 border-b border-amber-200">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa fa-book text-sm"></i>
                        </div>
                        <div class="text-xs text-amber-900 leading-relaxed">
                            <strong class="font-bold text-amber-950 block text-[13px] mb-0.5">Presensi Pengunjung Diperlukan</strong>
                            Silakan isi buku tamu di bawah ini terlebih dahulu untuk membuka <b>E-Book</b>. Cukup isi 1 kali untuk membuka akses seluruh e-book selama sesi kunjungan Anda.
                        </div>
                    </div>
                </div>

                <!-- SEGMENTED CARD TABS -->
                <div class="px-5 sm:px-6 pt-3.5 pb-2 bg-slate-50 border-b border-slate-100">
                    <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-200/70 rounded-2xl shadow-inner">
                        <button type="button" onclick="switchTabBukuTamu('siswa')" class="tab-tamu-btn py-2 px-2 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 bg-emerald-700 text-white shadow-sm" id="pill-siswa-tab" data-tab="#tab-tamu-siswa">
                            <i class="fa fa-graduation-cap"></i> <span>Siswa</span>
                        </button>
                        <button type="button" onclick="switchTabBukuTamu('guru')" class="tab-tamu-btn py-2 px-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-emerald-700 hover:bg-white/60 rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5" id="pill-guru-tab" data-tab="#tab-tamu-guru">
                            <i class="fa fa-chalkboard-teacher"></i> <span>Guru / Staf</span>
                        </button>
                        <button type="button" onclick="switchTabBukuTamu('luar')" class="tab-tamu-btn py-2 px-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-emerald-700 hover:bg-white/60 rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5" id="pill-luar-tab" data-tab="#tab-tamu-luar">
                            <i class="fa fa-building"></i> <span>Tamu Luar</span>
                        </button>
                    </div>
                </div>

                <div class="tab-content" id="tamuPillsContent">
                    <!-- ============================================== -->
                    <!-- TAB 1: SISWA & QR CODE SCANNER -->
                    <!-- ============================================== -->
                    <div class="tab-pane-tamu" id="tab-tamu-siswa">
                        <form id="form-tamu-siswa">
                            <input type="hidden" name="tipe_tamu" value="siswa">
                            <div class="p-5 sm:p-6 space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="font-bold text-slate-800 text-xs sm:text-sm mb-0 flex items-center gap-1.5">
                                            <span>NISN / Nomor Induk Siswa</span>
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <button type="button" id="btn-toggle-camera" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition shadow-2xs">
                                            <i class="fa fa-camera text-xs"></i> <span id="camera-btn-text">Scan Kamera QR</span>
                                        </button>
                                    </div>

                                    <!-- CAMERA SCANNER CONTAINER -->
                                    <div id="qr-reader-container" style="display: none;" class="mb-3 p-3 bg-slate-900 rounded-2xl text-center border-2 border-dashed border-emerald-500/50 shadow-inner">
                                        <div id="qr-reader" class="rounded-xl overflow-hidden w-full min-h-[200px]"></div>
                                        <p class="text-emerald-400 text-xs mt-2 mb-0 font-medium">
                                            <i class="fa fa-qrcode mr-1"></i> Arahkan QR code kartu pelajar / barcode ke kamera
                                        </p>
                                    </div>

                                    <!-- INPUT NISN DENGAN ICON INLINE -->
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-id-card-o text-base"></i>
                                        </div>
                                        <input type="text" name="nisn_or_nis" id="input-nisn-tamu" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Ketik NISN atau tempel barcode scanner..." required="" autofocus="" value="">
                                    </div>
                                    <p class="text-slate-400 text-[11px] mt-1.5 mb-0">
                                        Mendukung input manual NISN, scan kamera HP/laptop, atau scanner USB barcode.
                                    </p>
                                </div>

                                <!-- KEPERLUAN KUNJUNGAN: INTERACTIVE PILL CHIPS -->
                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Pilih Keperluan Kunjungan:
                                    </label>
                                    
                                    <!-- CHIP OPTIONS -->
                                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 mb-2">
                                        <button type="button" onclick="selectKeperluanChip('siswa', 'Membaca Buku / Bahan Pustaka', this)" class="chip-keperluan-siswa px-3 py-2 text-xs font-semibold rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-800 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-book text-emerald-600"></i> <span>Membaca Buku</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('siswa', 'Meminjam / Mengembalikan Buku', this)" class="chip-keperluan-siswa px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-exchange text-slate-400"></i> <span>Pinjam / Kembali</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('siswa', 'Belajar Kelompok / Diskusi', this)" class="chip-keperluan-siswa px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-users text-slate-400"></i> <span>Belajar Bersama</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('siswa', 'Mengerjakan Tugas / Riset', this)" class="chip-keperluan-siswa px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-pencil-square-o text-slate-400"></i> <span>Tugas &amp; Riset</span>
                                        </button>
                                    </div>

                                    <!-- DROPDOWN & LAINNYA FALLBACK -->
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-compass text-base"></i>
                                        </div>
                                        <select name="keperluan" id="select-keperluan-siswa" class="w-full pl-10 pr-9 py-2 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20 appearance-none select-keperluan cursor-pointer" data-target="#wrap-lainnya-siswa">
                                            <option value="Membaca Buku / Bahan Pustaka">Membaca Buku / Bahan Pustaka</option>
                                            <option value="Meminjam / Mengembalikan Buku">Meminjam / Mengembalikan Buku</option>
                                            <option value="Belajar Kelompok / Diskusi">Belajar Kelompok / Diskusi</option>
                                            <option value="Mengerjakan Tugas / Riset">Mengerjakan Tugas / Riset</option>
                                            <option value="Lainnya">Lainnya (Ketik Manual)...</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 wrap-keperluan-lainnya" id="wrap-lainnya-siswa" style="display: none;">
                                        <textarea name="keperluan_lainnya" rows="2" class="w-full px-3.5 py-2 bg-white border border-emerald-400 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Tuliskan tujuan/keperluan kunjungan Anda secara lengkap di sini..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-slate-50 px-5 sm:px-6 py-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition" data-dismiss="modal">
                                    Batal
                                </button>
                                <button type="submit" id="btn-submit-tamu-siswa" class="px-5 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-1.5 active:scale-95 hover:brightness-110" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                                    <i class="fa fa-check-circle"></i> Simpan Kehadiran Siswa
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 2: GURU & STAF KEPENDIDIKAN -->
                    <!-- ============================================== -->
                    <div class="tab-pane-tamu hidden" id="tab-tamu-guru">
                        <form id="form-tamu-guru">
                            <input type="hidden" name="tipe_tamu" value="guru">
                            <div class="p-5 sm:p-6 space-y-4">
                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Nama Guru / PTK <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-user-circle-o text-base"></i>
                                        </div>
                                        <input type="text" name="nama_guru" list="list-guru-buku-tamu" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Ketik nama lengkap Bapak/Ibu guru..." required="">
                                    </div>
                                    <datalist id="list-guru-buku-tamu">
                                                                            </datalist>
                                    <p class="text-slate-400 text-[11px] mt-1.5 mb-0">
                                        Ketik beberapa huruf nama Anda untuk memilih dari daftar GTK madrasah.
                                    </p>
                                </div>

                                <!-- KEPERLUAN KUNJUNGAN: INTERACTIVE PILL CHIPS GURU -->
                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Pilih Keperluan Kunjungan:
                                    </label>
                                    
                                    <!-- CHIP OPTIONS -->
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <button type="button" onclick="selectKeperluanChip('guru', 'Mencari Bahan Ajar / Silabus', this)" class="chip-keperluan-guru px-3 py-2 text-xs font-semibold rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-800 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-graduation-cap text-emerald-600"></i> <span>Bahan Ajar / Silabus</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('guru', 'Membaca Referensi &amp; Buku', this)" class="chip-keperluan-guru px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-bookmark text-slate-400"></i> <span>Referensi &amp; Kitab</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('guru', 'Meminjam Buku Paket / Koleksi', this)" class="chip-keperluan-guru px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-cubes text-slate-400"></i> <span>Pinjam Buku Paket</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('guru', 'Mendampingi Siswa Belajar', this)" class="chip-keperluan-guru px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-child text-slate-400"></i> <span>Dampingi Siswa</span>
                                        </button>
                                    </div>

                                    <!-- DROPDOWN FALLBACK -->
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-briefcase text-base"></i>
                                        </div>
                                        <select name="keperluan" id="select-keperluan-guru" class="w-full pl-10 pr-9 py-2 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20 appearance-none select-keperluan cursor-pointer" data-target="#wrap-lainnya-guru">
                                            <option value="Mencari Bahan Ajar / Silabus">Mencari Bahan Ajar / Silabus</option>
                                            <option value="Membaca Referensi &amp; Buku">Membaca Referensi &amp; Buku</option>
                                            <option value="Meminjam Buku Paket / Koleksi">Meminjam Buku Paket / Koleksi</option>
                                            <option value="Mendampingi Siswa Belajar">Mendampingi Siswa Belajar</option>
                                            <option value="Lainnya">Lainnya (Ketik Manual)...</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 wrap-keperluan-lainnya" id="wrap-lainnya-guru" style="display: none;">
                                        <textarea name="keperluan_lainnya" rows="2" class="w-full px-3.5 py-2 bg-white border border-emerald-400 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Tuliskan tujuan/keperluan kunjungan Bapak/Ibu di sini..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-slate-50 px-5 sm:px-6 py-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition" data-dismiss="modal">
                                    Batal
                                </button>
                                <button type="submit" id="btn-submit-tamu-guru" class="px-5 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-1.5 active:scale-95 hover:brightness-110" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                                    <i class="fa fa-check-circle"></i> Simpan Kehadiran Guru
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 3: TAMU LUAR / INSTANSI -->
                    <!-- ============================================== -->
                    <div class="tab-pane-tamu hidden" id="tab-tamu-luar">
                        <form id="form-tamu-luar">
                            <input type="hidden" name="tipe_tamu" value="tamu_luar">
                            <div class="p-5 sm:p-6 space-y-3.5">
                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Nama Lengkap Tamu <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-user text-base"></i>
                                        </div>
                                        <input type="text" name="nama_tamu" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Contoh: Drs. H. Suryadi, M.Pd" required="">
                                    </div>
                                </div>

                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Asal Instansi / Lembaga / Sekolah <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-building-o text-base"></i>
                                        </div>
                                        <input type="text" name="instansi_asal" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Contoh: Balai Pustaka / Kemenag / UIN" required="">
                                    </div>
                                </div>

                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        No. Handphone / WhatsApp <span class="text-slate-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-600">
                                            <i class="fa fa-whatsapp text-base"></i>
                                        </div>
                                        <input type="text" name="kontak_tamu" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Contoh: 08123456789">
                                    </div>
                                </div>

                                <div>
                                    <label class="font-bold text-slate-800 text-xs sm:text-sm mb-1.5 block">
                                        Pilih Keperluan Kunjungan:
                                    </label>
                                    
                                    <!-- CHIP OPTIONS TAMU -->
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <button type="button" onclick="selectKeperluanChip('luar', 'Studi Banding / Observasi', this)" class="chip-keperluan-luar px-3 py-2 text-xs font-semibold rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-800 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-university text-emerald-600"></i> <span>Studi Banding</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('luar', 'Kunjungan Dinas / Pengawas', this)" class="chip-keperluan-luar px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-clipboard text-slate-400"></i> <span>Kunjungan Dinas</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('luar', 'Penelitian / Riset Perpustakaan', this)" class="chip-keperluan-luar px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-flask text-slate-400"></i> <span>Penelitian / Riset</span>
                                        </button>
                                        <button type="button" onclick="selectKeperluanChip('luar', 'Kunjungan Kerjasama', this)" class="chip-keperluan-luar px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-left transition flex items-center gap-1.5">
                                            <i class="fa fa-handshake-o text-slate-400"></i> <span>Kerjasama</span>
                                        </button>
                                    </div>

                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-file-text-o text-base"></i>
                                        </div>
                                        <select name="keperluan" id="select-keperluan-luar" class="w-full pl-10 pr-9 py-2 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20 appearance-none select-keperluan cursor-pointer" data-target="#wrap-lainnya-luar">
                                            <option value="Studi Banding / Observasi">Studi Banding / Observasi</option>
                                            <option value="Kunjungan Dinas / Pengawas">Kunjungan Dinas / Pengawas</option>
                                            <option value="Penelitian / Riset Perpustakaan">Penelitian / Riset Perpustakaan</option>
                                            <option value="Kunjungan Kerjasama">Kunjungan Kerjasama</option>
                                            <option value="Lainnya">Lainnya (Ketik Manual)...</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 wrap-keperluan-lainnya" id="wrap-lainnya-luar" style="display: none;">
                                        <textarea name="keperluan_lainnya" rows="2" class="w-full px-3.5 py-2 bg-white border border-emerald-400 focus:border-emerald-600 rounded-xl text-xs sm:text-sm text-slate-800 transition outline-none shadow-sm focus:ring-2 focus:ring-emerald-500/20" placeholder="Tuliskan tujuan/keperluan kunjungan tamu di sini..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-slate-50 px-5 sm:px-6 py-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition" data-dismiss="modal">
                                    Batal
                                </button>
                                <button type="submit" id="btn-submit-tamu-luar" class="px-5 py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl shadow-md transition-all duration-200 flex items-center gap-1.5 active:scale-95 hover:brightness-110" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                                    <i class="fa fa-check-circle"></i> Simpan Buku Tamu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: LOGIN SISWA CEPAT -->
    <div class="modal fade" id="modalLoginSiswa" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-custom-header">
                    <h5 class="modal-title font-weight-bold mb-0" style="font-size: 16px;">
                        <i class="fa fa-lock mr-2"></i> Masuk Akun Siswa E-Perpus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <form id="form-login-siswa">
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Login untuk memesan buku secara online dan memantau masa peminjaman Anda.</p>
                        <div class="form-group">
                            <label class="font-weight-bold text-dark small mb-1">Username / NISN / NIS</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan NISN / NIS Anda" required="" style="border-radius: 8px;">
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-dark small mb-1">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password siswa" required="" style="border-radius: 8px;">
                            <small class="text-muted">Gunakan password akun akademik atau NISN Anda.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3 border-0">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" id="btn-submit-login" class="btn btn-primary btn-sm font-weight-bold px-4 shadow-sm" style="border-radius: 8px; background: #00796b; border: none;">
                            <i class="fa fa-sign-in mr-1"></i> Masuk Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: DETAIL BUKU (DESAIN MODERN & RAMPING) -->
    <div class="modal fade" id="modalDetailBuku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 660px; width: 92%;">
            <div class="modal-content shadow-2xl border-0" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                
                <!-- TOP BAR: KATEGORI, ISBN, BOOKMARK & CLOSE -->
                <div class="px-5 sm:px-6 pt-4 pb-2 flex items-center justify-between gap-2.5">
                    <div class="flex items-center flex-wrap gap-2 sm:gap-2.5">
                        <span id="detail-kategori-badge" class="font-extrabold uppercase px-2.5 py-1 rounded-full text-emerald-800 bg-emerald-100 text-[10.5px] tracking-wider">
                            KEISLAMAN &amp; KITAB
                        </span>
                        <span class="text-slate-400 font-medium text-[11.5px]">
                            ISBN: <span id="detail-isbn-text" class="text-slate-600 font-mono font-semibold">978-979-245-812-1</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-1 ml-auto">
                        <button type="button" id="btn-detail-bookmark" onclick="toggleBookmarkBuku(this)" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-emerald-700 transition" title="Simpan ke Favorit">
                            <i class="fa fa-bookmark-o text-sm"></i>
                        </button>
                        <button type="button" class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition text-xl leading-none" data-dismiss="modal" title="Tutup Modal">
                            ×
                        </button>
                    </div>
                </div>

                <!-- MAIN BODY (2 KOLOM: KIRI COVER & RAK, KANAN DESKRIPSI & AKSI) -->
                <div class="px-5 sm:px-6 py-2.5">
                    <div class="detail-buku-layout-wrap d-flex" style="gap: 18px; align-items: flex-start;">
                        
                        <!-- LEFT COLUMN: COVER BUKU (140px) -->
                        <div class="detail-buku-left-col" style="width: 140px; min-width: 140px; flex-shrink: 0;">
                            <!-- COVER WRAPPER -->
                            <div class="detail-cover-box position-relative w-100 rounded-xl overflow-hidden shadow-sm border border-slate-200 bg-slate-50 d-flex align-items-center justify-content-center" style="height: 198px; max-height: 198px;">
                                <img id="detail-cover" src="/madrasah-theme/perpustakaan/database.png" alt="Cover Buku" class="w-100 h-100" style="object-fit: contain; padding: 14px; transition: all 0.2s;">
                                <div id="detail-ebook-badge" class="position-absolute" style="top: 7px; left: 7px; z-index: 5;">
                                    <span class="bg-emerald-700 text-white font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded shadow-sm d-inline-flex align-items-center" style="font-size: 8.5px;">
                                        <i class="fa fa-file-text-o mr-1"></i> E-BOOK RESMI
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: INFO, STATS, SINOPSIS, BUTTONS & SPESIFIKASI -->
                        <div class="detail-buku-right-col d-flex flex-column" style="flex: 1; min-width: 0;">
                            <div>
                                <h3 id="detail-judul" class="font-weight-bold text-slate-900 mb-1.5 tracking-tight" style="font-size: 17px; line-height: 1.35;">
                                    Ringkasan Ihya' Ulumuddin
                                </h3>
                                <div class="text-slate-500 mb-0.5" style="font-size: 11.5px;">
                                    Penulis: <strong id="detail-penulis" class="text-slate-800 font-weight-bold">Hujjatul Islam Imam Al-Ghazali</strong>
                                </div>
                                <div class="text-slate-500 mb-2.5" style="font-size: 11.5px;">
                                    Penerbit: <span id="detail-penerbit" class="text-slate-700 font-weight-bold">Marja Publisher (2022)</span>
                                </div>

                                <!-- STATS ROW: RATING, HALAMAN, READ COUNT, STOCK -->
                                <div class="d-flex align-items-center flex-wrap pb-2 mb-2 border-bottom" style="gap: 8px 12px; font-size: 11px; border-color: #f1f5f9 !important;">
                                    <span class="d-inline-flex align-items-center font-weight-bold" style="color: #f59e0b;">
                                        <i class="fa fa-star text-warning mr-1"></i> <span id="detail-rating">5 / 5.0</span>
                                    </span>
                                    <span class="text-slate-500">
                                        <strong id="detail-halaman" class="text-slate-800">612</strong> Hal
                                    </span>
                                    <span class="text-slate-500">
                                        <strong id="detail-dibaca" class="text-slate-800">1420</strong>x Dibaca
                                    </span>
                                    <span id="detail-stok-badge" class="d-inline-flex align-items-center font-weight-bold text-emerald-700 px-2 py-0.5 rounded-pill" style="background: #ecfdf5; font-size: 10.5px;">
                                        <i class="fa fa-check-circle text-emerald-600 mr-1"></i> <span id="detail-stok-text">Tersedia 9 dari 16 eks</span>
                                    </span>
                                </div>

                                <!-- SINOPSIS & DESKRIPSI BUKU -->
                                <div class="text-uppercase text-slate-400 font-weight-bold mb-1" style="font-size: 9.5px; letter-spacing: 0.6px;">
                                    SINOPSIS &amp; DESKRIPSI BUKU
                                </div>
                                <p id="detail-deskripsi" class="text-slate-600 mb-2.5" style="font-size: 11.5px; line-height: 1.55; max-height: 80px; overflow-y: auto;">
                                    Intisari dari karya agung Imam Al-Ghazali mengenai penyucian jiwa (tazkiyatun nafs), adab menuntut ilmu, rahasia ibadah shalat dan puasa, serta pengendalian nafsu.
                                </p>
                            </div>

                            <!-- CTA ACTION BUTTONS (TETAP DI TEMPATNYA SETELAH SINOPSIS) -->
                            <div class="d-flex align-items-center pt-0.5 mb-2.5" style="gap: 10px;">
                                <button type="button" id="btn-detail-baca-ebook" class="btn text-white font-weight-bold flex-fill py-1.5 px-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="background: #047857; border-radius: 10px; font-size: 11.5px; border: none; height: 38px; white-space: nowrap;">
                                    <i class="fa fa-book mr-1.5"></i> Buka Ruang Baca E-Book
                                </button>
                                <button type="button" id="btn-detail-booking" class="btn text-white font-weight-bold flex-fill py-1.5 px-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="background: #ea580c; border-radius: 10px; font-size: 11.5px; border: none; height: 38px; white-space: nowrap;">
                                    <i class="fa fa-calendar-check-o mr-1.5"></i> Booking Pinjam Buku Fisik
                                </button>
                            </div>

                            <!-- KOTAK SPESIFIKASI & LOKASI RAK (MENGISI AREA TANDA SILANG DI BAWAH TOMBOL) -->
                            <div class="p-2.5 rounded-xl border border-slate-200" style="background: #f8fafc;">
                                <!-- BARIS 1: LOKASI RAK & SALIN KODE -->
                                <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom border-slate-200" style="font-size: 11px;">
                                    <div class="d-flex align-items-center text-slate-700 truncate mr-2">
                                        <span class="d-inline-flex align-items-center justify-content-center mr-1.5 rounded-circle flex-shrink-0" style="width: 18px; height: 18px; background: #dcfce7; color: #15803d; font-size: 10px;">
                                            <i class="fa fa-map-marker"></i>
                                        </span>
                                        <span class="text-slate-500 mr-1">Lokasi Rak:</span>
                                        <strong class="text-emerald-800 font-weight-bold truncate" id="detail-lokasi-rak">Rak 03-A (Tasawuf &amp; Akhlak)</strong>
                                    </div>
                                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                                        <span id="detail-kode-panggil" class="font-weight-bold text-slate-700 font-mono tracking-wider px-1.5 py-0.5 rounded bg-white border border-slate-200" style="font-size: 10px;">
                                            2X5.1 GHA r
                                        </span>
                                        <button type="button" onclick="salinKodePanggil()" class="btn btn-link p-0 text-emerald-700 hover:text-emerald-900 font-weight-bold text-decoration-none d-inline-flex align-items-center" style="font-size: 10.5px;" title="Salin Kode Panggil">
                                            <i class="fa fa-clone mr-1"></i> Salin
                                        </button>
                                    </div>
                                </div>

                                <!-- BARIS 2: 4 SPESIFIKASI BUKU (GRID 2x2) -->
                                <div class="detail-spec-grid" style="font-size: 11px;">
                                    <div class="d-flex align-items-center text-slate-600 truncate">
                                        <span class="mr-1.5 flex-shrink-0">📅</span>
                                        <span class="text-slate-500 mr-1">Tahun Terbit:</span>
                                        <strong id="detail-spec-tahun" class="text-slate-800 font-weight-bold">2024</strong>
                                    </div>
                                    <div class="d-flex align-items-center text-slate-600 truncate">
                                        <span class="mr-1.5 flex-shrink-0">🌐</span>
                                        <span class="text-slate-500 mr-1">Bahasa:</span>
                                        <strong id="detail-spec-bahasa" class="text-slate-800 font-weight-bold">Indonesia</strong>
                                    </div>
                                    <div class="d-flex align-items-center text-slate-600 truncate">
                                        <span class="mr-1.5 flex-shrink-0">🏷️</span>
                                        <span class="text-slate-500 mr-1">Kode Buku:</span>
                                        <strong id="detail-spec-kode" class="text-slate-800 font-mono font-weight-bold" style="font-size: 10.5px;">BK-2026-013</strong>
                                    </div>
                                    <div class="d-flex align-items-center text-slate-600 truncate">
                                        <span class="mr-1.5 flex-shrink-0">📦</span>
                                        <span class="text-slate-500 mr-1">Format:</span>
                                        <strong id="detail-spec-format" class="text-slate-800 font-weight-bold">Buku Fisik &amp; PDF Digital</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- BOTTOM SECTION: KETENTUAN & ALUR PEMINJAMAN (OPSI B) -->
                    <div class="mt-3 p-2.5 sm:p-3 rounded-xl" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center text-slate-700 font-weight-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                <i class="fa fa-info-circle text-emerald-600 mr-1.5" style="font-size: 12px;"></i> KETENTUAN &amp; ALUR LAYANAN PUSTAKA
                            </div>
                            <span class="text-slate-400 font-medium" style="font-size: 9.5px;">
                                <i class="fa fa-clock-o mr-1"></i> Jam Layanan Sekolah
                            </span>
                        </div>
                        <div class="detail-ketentuan-grid">
                            <div class="d-flex align-items-start" style="gap: 8px;">
                                <span class="d-inline-flex align-items-center justify-content-center font-weight-bold shrink-0 mt-0.5" style="width: 20px; height: 20px; min-width: 20px; background: #e0f2fe; color: #0284c7; font-size: 10px; border-radius: 6px;">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-slate-800" style="font-size: 11px;">Masa Pinjam 7 Hari</div>
                                    <div class="text-slate-500" style="font-size: 10px; line-height: 1.3;">Pinjam buku fisik maksimal 7 hari, dapat diperpanjang 1x</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start" style="gap: 8px;">
                                <span class="d-inline-flex align-items-center justify-content-center font-weight-bold shrink-0 mt-0.5" style="width: 20px; height: 20px; min-width: 20px; background: #fef3c7; color: #d97706; font-size: 10px; border-radius: 6px;">
                                    <i class="fa fa-book"></i>
                                </span>
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-slate-800" style="font-size: 11px;">Batas Maksimal 2 Buku</div>
                                    <div class="text-slate-500" style="font-size: 10px; line-height: 1.3;">Setiap siswa dapat meminjam hingga 2 judul buku aktif</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start" style="gap: 8px;">
                                <span class="d-inline-flex align-items-center justify-content-center font-weight-bold shrink-0 mt-0.5" style="width: 20px; height: 20px; min-width: 20px; background: #dcfce7; color: #16a34a; font-size: 10px; border-radius: 6px;">
                                    <i class="fa fa-id-card-o"></i>
                                </span>
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-slate-800" style="font-size: 11px;">Ambil di Meja Sirkulasi</div>
                                    <div class="text-slate-500" style="font-size: 10px; line-height: 1.3;">Konfirmasi booking dengan menunjukkan Kartu Pelajar / NISN</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start" style="gap: 8px;">
                                <span class="d-inline-flex align-items-center justify-content-center font-weight-bold shrink-0 mt-0.5" style="width: 20px; height: 20px; min-width: 20px; background: #ede9fe; color: #7c3aed; font-size: 10px; border-radius: 6px;">
                                    <i class="fa fa-laptop"></i>
                                </span>
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-slate-800" style="font-size: 11px;">E-Book Akses Online</div>
                                    <div class="text-slate-500" style="font-size: 10px; line-height: 1.3;">Koleksi digital dapat langsung dibaca kapan pun tanpa antre</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL 4: BOOKING BUKU KONFIRMASI -->
    <div class="modal fade" id="modalBookingBuku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-custom-header">
                    <h5 class="modal-title font-weight-bold mb-0" style="font-size: 16px;">
                        <i class="fa fa-bookmark mr-2"></i> Konfirmasi Booking Peminjaman Buku
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <form id="form-booking-buku">
                    <input type="hidden" name="id_buku" id="booking_id_buku">
                    <div class="modal-body p-4">
                        <div class="p-3 mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
                            <small class="text-muted text-uppercase font-weight-bold" style="font-size: 10px;">Judul Buku yang Dipesan:</small>
                            <div class="font-weight-bold text-success" id="booking_judul_display" style="font-size: 15px;"></div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark small mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Keperluan tugas mandiri, akan diambil istirahat pertama" style="border-radius: 8px; font-size: 13px;"></textarea>
                        </div>

                        <div class="alert alert-warning py-2 px-3 small border-0 mb-0" style="border-radius: 8px;">
                            <i class="fa fa-info-circle mr-1"></i> Setelah menekan tombol <b>Konfirmasi Booking</b>, Anda akan mendapatkan kode booking peminjaman untuk ditunjukkan ke petugas perpustakaan saat pengambilan buku fisik.
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3 border-0">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" id="btn-submit-booking" class="btn btn-success btn-sm font-weight-bold px-4 shadow-sm" style="border-radius: 8px; background: #00796b; border: none;">
                            <i class="fa fa-check-circle mr-1"></i> Konfirmasi Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 5: CEK BEBAS PUSTAKA MANDIRI -->
    <div class="modal fade" id="modalCekBebas" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 540px;">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-custom-header">
                    <h5 class="modal-title font-weight-bold mb-0" style="font-size: 16px;">
                        <i class="fa fa-certificate mr-2"></i> Cek Bebas Perpustakaan Siswa
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <form id="form-cek-bebas">
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Masukkan <b>NISN</b> atau <b>NIS</b> untuk mengecek status tanggungan pinjaman buku dan mencetak Surat Keterangan Bebas Perpustakaan secara mandiri.</p>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-dark small mb-1">NISN / Nomor Induk Siswa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fa fa-id-card text-muted"></i></span>
                                </div>
                                <input type="text" name="nisn_or_nis" id="input-nisn-bebas" class="form-control" placeholder="Contoh: 0114079139" required="" style="border-radius: 0 8px 8px 0;" value="">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mb-3">
                            <button type="submit" id="btn-submit-cek-bebas" class="btn btn-primary btn-sm font-weight-bold px-4 shadow-sm" style="border-radius: 8px; background: #00796b; border: none;">
                                <i class="fa fa-search mr-1"></i> Cek Status Sekarang
                            </button>
                        </div>

                        <!-- HASIL CEK BEBAS PUSTAKA -->
                        <div id="hasil-cek-bebas" style="display: none;"></div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3 border-0">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">Tutup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 6: USULKAN BUKU BARU OLEH SISWA/PENGUNJUNG -->
    <div class="modal fade" id="modalUsulanBuku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-custom-header">
                    <h5 class="modal-title font-weight-bold mb-0" style="font-size: 16px;">
                        <i class="fa fa-lightbulb-o mr-2 text-warning"></i> Usulan Pengadaan Buku Baru
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <form id="form-usulan-buku">
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Tidak menemukan buku yang Anda cari? Tuliskan usulan judul buku agar Tim Perpustakaan dapat menyediakannya.</p>
                        
                        <div class="row">
                            <div class="col-md-7 mb-2">
                                <label class="font-weight-bold text-dark small mb-1">Nama Pengusul <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pengusul" class="form-control" placeholder="Nama lengkap Anda" required="" style="border-radius: 8px;" value="">
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="font-weight-bold text-dark small mb-1">Kelas / Instansi</label>
                                <input type="text" name="kelas_asal" class="form-control" placeholder="Misal: XI IPA 1" style="border-radius: 8px;" value="">
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-dark small mb-1">No. WhatsApp / Kontak (Opsional)</label>
                            <input type="text" name="kontak" class="form-control" placeholder="Contoh: 08123456789 (Untuk info saat buku tersedia)" style="border-radius: 8px;">
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-dark small mb-1">Judul Buku yang Diusulkan <span class="text-danger">*</span></label>
                            <input type="text" name="judul_buku" class="form-control" placeholder="Contoh: Fisika Kuantum untuk Pemula / Laskar Pelangi" required="" style="border-radius: 8px;">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="font-weight-bold text-dark small mb-1">Pengarang / Penulis</label>
                                <input type="text" name="penulis" class="form-control" placeholder="Nama pengarang buku" style="border-radius: 8px;">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="font-weight-bold text-dark small mb-1">Penerbit (Opsional)</label>
                                <input type="text" name="penerbit" class="form-control" placeholder="Nama penerbit" style="border-radius: 8px;">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-dark small mb-1">Alasan / Kepentingan Rekomendasi</label>
                            <textarea name="alasan" class="form-control" rows="2" placeholder="Contoh: Sangat dibutuhkan untuk bahan riset karya ilmiah remaja dan modul tambahan..." style="border-radius: 8px; font-size: 13px; resize: vertical;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3 border-0">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3" data-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" id="btn-submit-usulan" class="btn btn-warning btn-sm font-weight-bold px-4 shadow-sm text-dark" style="border-radius: 8px; background: #f59e0b; border: none;">
                            <i class="fa fa-paper-plane mr-1"></i> Kirim Usulan Buku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 7: CHROME-STYLE SECURE E-BOOK PDF READER (ANTI-DOWNLOAD, PRINT & COPY) -->
    <div class="modal fade" id="modalEbookReader" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content shadow-2xl">
                
                <!-- CHROME-STYLE TOP TOOLBAR -->
                <div class="chrome-pdf-toolbar">
                    <div class="d-flex align-items-center text-truncate mr-2" style="max-width: 40%; flex-shrink: 1;">
                        <!-- Toggle Sidebar Button (Hamburger Icon like Chrome) -->
                        <button type="button" class="chrome-pdf-btn mr-1" id="pdf-toggle-sidebar" title="Sembunyikan / Tampilkan Thumbnail Halaman">
                            <i class="fa fa-bars"></i>
                        </button>
                        <div class="text-truncate">
                            <div class="font-weight-bold text-truncate text-white" id="ebook-modal-title" style="font-size: 13px;" title="">Baca E-Book Digital</div>
                            <div class="text-muted text-truncate d-none d-sm-block" style="font-size: 9.5px;"><i class="fa fa-lock text-warning mr-1"></i> Mode Baca Aman • Hanya Online (Anti-Unduh &amp; Cetak)</div>
                        </div>
                    </div>

                    <!-- CENTER & RIGHT CONTROLS -->
                    <div class="d-flex align-items-center flex-nowrap justify-content-end" style="flex-shrink: 0;">
                        <!-- Navigation / Page Indicator (2 / 70) -->
                        <div class="chrome-page-box mr-1">
                            <input type="number" id="pdf-page-input" value="1" min="1" max="1" class="chrome-page-input" title="Ketik nomor halaman &amp; tekan Enter">
                            <span style="color: #9aa0a6;">/<span id="pdf-total-pages">1</span></span>
                        </div>

                        <!-- Zoom Controls -->
                        <div class="d-inline-flex align-items-center mr-1">
                            <button type="button" class="chrome-pdf-btn" id="pdf-zoom-out" title="Perkecil (-)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <span id="pdf-zoom-level" class="d-none d-md-inline" style="font-size: 11px; color: #e8eaed; font-weight: 600; min-width: 38px; text-align: center;">100%</span>
                            <button type="button" class="chrome-pdf-btn" id="pdf-zoom-in" title="Perbesar (+)">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button type="button" class="chrome-pdf-btn" id="pdf-zoom-fit" title="Sesuaikan Lebar Layar">
                                <i class="fa fa-arrows-h"></i>
                            </button>
                        </div>

                        <!-- Rotate Button (sembunyikan di HP sangat kecil agar tidak sempit) -->
                        <button type="button" class="chrome-pdf-btn mr-1 d-none d-sm-inline-flex" id="pdf-rotate-page" title="Putar Halaman (Rotasi 90°)">
                            <i class="fa fa-repeat"></i>
                        </button>

                        <!-- Theme Reading Modes -->
                        <div class="d-none d-md-inline-flex align-items-center mr-2">
                            <button type="button" class="chrome-pdf-btn btn-theme-reader active" data-theme="default" title="Tema Terang">
                                <i class="fa fa-sun-o text-warning"></i>
                            </button>
                            <button type="button" class="chrome-pdf-btn btn-theme-reader" data-theme="sepia" title="Tema Sepia">
                                <i class="fa fa-bookmark" style="color: #f59e0b;"></i>
                            </button>
                            <button type="button" class="chrome-pdf-btn btn-theme-reader" data-theme="dark" title="Tema Gelap">
                                <i class="fa fa-moon-o text-info"></i>
                            </button>
                        </div>

                        <!-- Fullscreen Button -->
                        <button type="button" class="chrome-pdf-btn mr-1 d-none d-sm-inline-flex" id="pdf-fullscreen" title="Layar Penuh (F11)">
                            <i class="fa fa-expand"></i>
                        </button>

                        <!-- Close Button -->
                        <button type="button" class="btn btn-danger btn-sm font-weight-bold px-3 py-1" data-dismiss="modal" style="border-radius: 6px; font-size: 12px;">
                            <i class="fa fa-times mr-1"></i> Tutup
                        </button>
                    </div>
                </div>

                <!-- CHROME-STYLE WORKSPACE (SPLIT: THUMBNAIL SIDEBAR + MAIN SCROLL VIEWPORT) -->
                <div class="chrome-pdf-workspace" id="pdf-workspace-area" oncontextmenu="return false;">
                    
                    <!-- LEFT SIDEBAR: THUMBNAILS (PERSIS SEPERTI GOOGLE CHROME - DEFAULT COLLAPSED DI MOBILE) -->
                    <div class="chrome-pdf-sidebar collapsed" id="pdf-chrome-sidebar">
                        <!-- Thumbnail items dynamically injected here -->
                    </div>

                    <!-- RIGHT MAIN READING VIEWPORT (CONTINUOUS VERTICAL SCROLL) -->
                    <div class="chrome-pdf-viewport" id="pdf-chrome-viewport">
                        <!-- Loading Spinner -->
                        <div id="pdf-loading-spinner" class="text-center text-white py-5 my-auto">
                            <i class="fa fa-spinner fa-spin fa-3x mb-3 text-warning"></i>
                            <h6 class="font-weight-bold">Memuat Dokumen E-Book...</h6>
                            <small class="text-muted">Menyiapkan tampilan pembaca Chrome aman (Anti-Unduh/Cetak)</small>
                        </div>

                        <!-- Pages dynamically stacked here -->
                        <div id="pdf-pages-container" style="width: 100%; display: flex; flex-direction: column; align-items: center;"></div>
                    </div>
                </div>

                <!-- BOTTOM STATUS BAR -->
                <div class="py-1 px-3 bg-dark text-muted d-flex justify-content-between align-items-center border-top border-secondary" style="font-size: 11px; user-select: none;">
                    <span><i class="fa fa-shield text-success mr-1"></i> Dokumen resmi perpustakaan • Fitur copy, print &amp; unduh dinonaktifkan</span>
                    <span class="d-none d-md-inline"><i class="fa fa-mouse-pointer text-warning mr-1"></i> Klik thumbnail di samping kiri untuk loncat halaman • Scroll bebas ke bawah</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 8: STATISTIK KUNJUNGAN PERPUSTAKAAN DIGITAL -->
    <div class="modal fade" id="modalStatistikKunjungan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1140px;">
            <div class="modal-content modal-content-pro shadow-lg">
                <div class="modal-header modal-custom-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="mr-3" style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            <i class="fa fa-line-chart text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold mb-0 text-white" style="font-size: 17px;">Statistik &amp; Analisis Kunjungan Perpustakaan</h5>
                            <small class="text-white-50">Visualisasi data presensi literasi dan aktivitas pemustaka secara real-time</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-success px-3 py-1 mr-3 font-weight-bold d-none d-sm-inline-block" style="border-radius: 20px; font-size: 11px;">
                            <i class="fa fa-circle text-white mr-1" style="font-size: 8px;"></i> Live Data
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-light font-weight-bold px-2 py-1 mr-2" onclick="loadStatistikKunjungan()" title="Muat Ulang Data">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body p-4" style="background: #f8fafc; max-height: 82vh; overflow-y: auto;">
                    <!-- Loading Indicator -->
                    <div id="stat-loading" class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <h6 class="font-weight-bold text-dark">Mengolah Statistik Kunjungan...</h6>
                        <small class="text-muted">Mengambil data agregasi kunjungan dari server perpustakaan</small>
                    </div>

                    <!-- Content Container -->
                    <div id="stat-content" style="display: none;">
                        <!-- METRIC SUMMARY CARDS (VIBRANT & MODERN) -->
                                                <div class="row mb-4">
                            <!-- CARD 1: HARI INI -->
                            <div class="col-lg-3 col-6 mb-3">
                                <div class="card stat-card-gradient stat-card-teal h-100">
                                    <div class="stat-bg-circle"></div>
                                    <div class="stat-bg-circle-2"></div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                                        <div>
                                            <span class="text-white-50 font-weight-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.8px;">Hari Ini</span>
                                            <h3 class="font-weight-bold text-white mb-2 mt-1" id="stat-kpi-today" style="font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.12);">0</h3>
                                            <div class="stat-badge-translucent">
                                                <i class="fa fa-circle text-warning mr-1" style="font-size: 8px;"></i> Real-time
                                            </div>
                                        </div>
                                        <div class="stat-icon-glow">
                                            <i class="fa fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CARD 2: 7 HARI TERAKHIR -->
                            <div class="col-lg-3 col-6 mb-3">
                                <div class="card stat-card-gradient stat-card-blue h-100">
                                    <div class="stat-bg-circle"></div>
                                    <div class="stat-bg-circle-2"></div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                                        <div>
                                            <span class="text-white-50 font-weight-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.8px;">7 Hari Terakhir</span>
                                            <h3 class="font-weight-bold text-white mb-2 mt-1" id="stat-kpi-week" style="font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.12);">0</h3>
                                            <div class="stat-badge-translucent">
                                                <i class="fa fa-calendar-check-o mr-1"></i> 1 Minggu
                                            </div>
                                        </div>
                                        <div class="stat-icon-glow">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CARD 3: BULAN INI -->
                            <div class="col-lg-3 col-6 mb-3">
                                <div class="card stat-card-gradient stat-card-amber h-100">
                                    <div class="stat-bg-circle"></div>
                                    <div class="stat-bg-circle-2"></div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                                        <div>
                                            <span class="text-white-50 font-weight-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.8px;">Bulan Ini</span>
                                            <h3 class="font-weight-bold text-white mb-2 mt-1" id="stat-kpi-month" style="font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.12);">0</h3>
                                            <div class="stat-badge-translucent">
                                                <i class="fa fa-calendar-o mr-1"></i> Bulan November                                            </div>
                                        </div>
                                        <div class="stat-icon-glow">
                                            <i class="fa fa-pie-chart"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CARD 4: TOTAL KUNJUNGAN -->
                            <div class="col-lg-3 col-6 mb-3">
                                <div class="card stat-card-gradient stat-card-purple h-100">
                                    <div class="stat-bg-circle"></div>
                                    <div class="stat-bg-circle-2"></div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                                        <div>
                                            <span class="text-white-50 font-weight-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.8px;">Total Kunjungan</span>
                                            <h3 class="font-weight-bold text-white mb-2 mt-1" id="stat-kpi-all" style="font-size: 28px; text-shadow: 0 2px 4px rgba(0,0,0,0.12);">0</h3>
                                            <div class="stat-badge-translucent">
                                                <i class="fa fa-bolt mr-1"></i> <span id="stat-kpi-avg">0</span> / hari
                                            </div>
                                        </div>
                                        <div class="stat-icon-glow">
                                            <i class="fa fa-database"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 1: TREN 30 HARI & TIPE PENGUNJUNG -->
                        <div class="row mb-4">
                            <div class="col-lg-8 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                                        <h6 class="font-weight-bold text-dark mb-0">
                                            <i class="fa fa-area-chart text-success mr-2"></i> Tren Kunjungan Harian (30 Hari Terakhir)
                                        </h6>
                                        <small class="text-muted">Jumlah Presensi per Tanggal</small>
                                    </div>
                                    <div class="card-body p-3">
                                        <div style="position: relative; height: 260px;">
                                            <canvas id="chartTrendKunjungan"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                                        <h6 class="font-weight-bold text-dark mb-0">
                                            <i class="fa fa-pie-chart text-primary mr-2"></i> Kategori Pengunjung
                                        </h6>
                                        <small class="text-muted">Proporsi Tipe Pemustaka</small>
                                    </div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-center">
                                        <div style="position: relative; width: 100%; height: 260px;">
                                            <canvas id="chartTipePengunjung"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: KUNJUNGAN PER KELAS & KEPERLUAN -->
                        <div class="row mb-4">
                            <div class="col-lg-6 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                                        <h6 class="font-weight-bold text-dark mb-0">
                                            <i class="fa fa-graduation-cap text-info mr-2"></i> Kunjungan Berdasarkan Kelas / Jenjang
                                        </h6>
                                        <small class="text-muted">Tingkat Keaktifan Literasi per Rombel</small>
                                    </div>
                                    <div class="card-body p-3">
                                        <div style="position: relative; height: 260px;">
                                            <canvas id="chartKelasKunjungan"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                                        <h6 class="font-weight-bold text-dark mb-0">
                                            <i class="fa fa-bookmark text-warning mr-2"></i> Keperluan / Alasan Kunjungan
                                        </h6>
                                        <small class="text-muted">Aktivitas Utama Siswa di Perpustakaan</small>
                                    </div>
                                    <div class="card-body p-3">
                                        <div style="position: relative; height: 260px;">
                                            <canvas id="chartKeperluanKunjungan"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 3: JAM SIBUK & TOP VISITORS -->
                        <div class="row">
                            <div class="col-lg-7 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                                        <h6 class="font-weight-bold text-dark mb-0">
                                            <i class="fa fa-clock-o text-danger mr-2"></i> Analisis Jam Sibuk Kunjungan (Peak Hours)
                                        </h6>
                                        <small class="text-muted">Distribusi waktu kunjungan (07.00 - 16.00)</small>
                                    </div>
                                    <div class="card-body p-3">
                                        <div style="position: relative; height: 240px;">
                                            <canvas id="chartJamSibuk"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="font-weight-bold text-dark mb-0">
                                                <i class="fa fa-trophy text-warning mr-2"></i> Top 5 Pengunjung Teraktif
                                            </h6>
                                            <small class="text-muted">Bulan November 2026</small>
                                        </div>
                                        <span class="badge badge-warning text-dark font-weight-bold" style="border-radius: 6px;">⭐ Terajin</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="stat-top-visitors-list" class="list-group list-group-flush">
                                            <!-- Dynamically populated -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="fa fa-info-circle"></i> Data statistik terhubung langsung dengan sistem e-presensi perpustakaan.</small>
                    <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="/madrasah-theme/perpustakaan/jquery-3.3.1.min.js"></script>
<script>$.ajaxSetup({headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||''}});</script>
    <script src="/madrasah-theme/perpustakaan/popper.min.js"></script>
    <script src="/madrasah-theme/perpustakaan/bootstrap.min.js"></script>
    <script src="/madrasah-theme/perpustakaan/iziToast.min.js"></script>
    <script src="/madrasah-theme/perpustakaan/html5-qrcode.min.js"></script>
    <script src="/madrasah-theme/perpustakaan/chart.js"></script>
    <script src="/madrasah-theme/perpustakaan/pdf.min.js"></script>

    <script>
    var isUserLoggedIn = false;
    var isGuestbookVerified = false;
    var pendingEbook = null;

    // CONFIG PDF.JS
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    // ============================================================
    // CHROME-STYLE SECURE PDF VIEWER ENGINE (ANTI-COPY/DOWNLOAD/PRINT)
    // ============================================================
    var pdfDoc = null,
        pdfScale = 1.15,
        pdfRotation = 0,
        pdfCurrentPage = 1,
        pdfRenderingPages = {},
        pdfRenderedPages = {},
        pdfTheme = 'default',
        pdfScrollDebounce = null;

    // Toggle Thumbnail Sidebar (like Chrome Hamburger button)
    $('#pdf-toggle-sidebar').on('click', function() {
        var $sidebar = $('#pdf-chrome-sidebar');
        $sidebar.toggleClass('collapsed');
        $(this).toggleClass('active', !$sidebar.hasClass('collapsed'));
        
        setTimeout(function() {
            triggerVisiblePagesRender();
        }, 300);
    });

    // Theme Switcher (Default, Sepia, Dark)
    $('.btn-theme-reader').on('click', function() {
        var theme = $(this).data('theme');
        $('.btn-theme-reader').removeClass('active');
        $(this).addClass('active');
        pdfTheme = theme;

        var $viewport = $('#pdf-chrome-viewport');
        var $canvases = $('.chrome-page-canvas');

        if (theme === 'sepia') {
            $viewport.css('background', '#2c251e');
            $canvases.css('filter', 'sepia(55%) contrast(92%) brightness(95%)');
        } else if (theme === 'dark') {
            $viewport.css('background', '#18191c');
            $canvases.css('filter', 'invert(90%) hue-rotate(180deg)');
        } else {
            $viewport.css('background', '#525659');
            $canvases.css('filter', 'none');
        }
    });

    // Fullscreen Toggle
    $('#pdf-fullscreen').on('click', function() {
        var elem = document.getElementById('modalEbookReader');
        if (!document.fullscreenElement) {
            if (elem.requestFullscreen) { elem.requestFullscreen(); }
            else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); }
            $(this).html('<i class="fa fa-compress"></i>');
        } else {
            if (document.exitFullscreen) { document.exitFullscreen(); }
            $(this).html('<i class="fa fa-expand"></i>');
        }
    });

    // Rotate clockwise (0 -> 90 -> 180 -> 270)
    $('#pdf-rotate-page').on('click', function() {
        if (!pdfDoc) return;
        pdfRotation = (pdfRotation + 90) % 360;
        pdfRenderedPages = {};
        renderAllPagesLayout();
    });

    // Zoom Controls
    function updateZoomDisplay() {
        $('#pdf-zoom-level').text(Math.round(pdfScale * 100) + '%');
    }

    $('#pdf-zoom-in').on('click', function() {
        if (!pdfDoc || pdfScale >= 2.5) return;
        pdfScale = Math.min(2.5, +(pdfScale + 0.15).toFixed(2));
        updateZoomDisplay();
        pdfRenderedPages = {};
        renderAllPagesLayout();
    });

    $('#pdf-zoom-out').on('click', function() {
        if (!pdfDoc || pdfScale <= 0.5) return;
        pdfScale = Math.max(0.5, +(pdfScale - 0.15).toFixed(2));
        updateZoomDisplay();
        pdfRenderedPages = {};
        renderAllPagesLayout();
    });

    $('#pdf-zoom-fit').on('click', function() {
        if (!pdfDoc) return;
        pdfDoc.getPage(1).then(function(firstPage) {
            var pad = window.innerWidth < 768 ? 16 : 60;
            var vpWidth = $('#pdf-chrome-viewport').width() - pad;
            var unscaledVp = firstPage.getViewport({ scale: 1.0, rotation: pdfRotation });
            pdfScale = Math.max(0.2, Math.min(2.5, +(vpWidth / unscaledVp.width).toFixed(2)));
            updateZoomDisplay();
            pdfRenderedPages = {};
            renderAllPagesLayout();
        });
    });

    // Jump to page input
    $('#pdf-page-input').on('change keydown', function(e) {
        if (e.type === 'keydown' && e.key !== 'Enter') return;
        var val = parseInt($(this).val());
        if (isNaN(val) || !pdfDoc) return;
        if (val < 1) val = 1;
        if (val > pdfDoc.numPages) val = pdfDoc.numPages;
        $(this).val(val);
        scrollToPage(val);
    });

    // Smooth scroll to a specific page
    function scrollToPage(pageNum) {
        var $target = $('#chrome-page-container-' + pageNum);
        if ($target.length) {
            var viewport = document.getElementById('pdf-chrome-viewport');
            var targetTop = $target[0].offsetTop - 20;
            viewport.scrollTo({ top: targetTop, behavior: 'smooth' });
            setActivePage(pageNum);
        }
    }

    // Set active page in toolbar and sidebar thumbnail
    function setActivePage(pageNum) {
        pdfCurrentPage = pageNum;
        $('#pdf-page-input').val(pageNum);

        $('.chrome-thumb-card').removeClass('active');
        var $activeThumb = $('#chrome-thumb-' + pageNum);
        $activeThumb.addClass('active');

        var sidebar = document.getElementById('pdf-chrome-sidebar');
        if (sidebar && $activeThumb.length) {
            var thumbEl = $activeThumb[0];
            var sTop = sidebar.scrollTop;
            var sBottom = sTop + sidebar.clientHeight;
            var tTop = thumbEl.offsetTop;
            var tBottom = tTop + thumbEl.clientHeight;
            if (tTop < sTop || tBottom > sBottom) {
                sidebar.scrollTo({ top: tTop - 20, behavior: 'smooth' });
            }
        }
    }

    // Render individual main page on demand
    function renderMainPage(num) {
        if (!pdfDoc || pdfRenderingPages[num] || pdfRenderedPages[num]) return;
        pdfRenderingPages[num] = true;

        pdfDoc.getPage(num).then(function(page) {
            var viewport = page.getViewport({ scale: pdfScale, rotation: pdfRotation });
            var canvas = document.getElementById('pdf-page-canvas-' + num);
            if (!canvas) {
                delete pdfRenderingPages[num];
                return;
            }

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            var ctx = canvas.getContext('2d');
            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            page.render(renderContext).promise.then(function() {
                delete pdfRenderingPages[num];
                pdfRenderedPages[num] = true;

                if (pdfTheme === 'sepia') {
                    $(canvas).css('filter', 'sepia(55%) contrast(92%) brightness(95%)');
                } else if (pdfTheme === 'dark') {
                    $(canvas).css('filter', 'invert(90%) hue-rotate(180deg)');
                } else {
                    $(canvas).css('filter', 'none');
                }
            }).catch(function() {
                delete pdfRenderingPages[num];
            });
        }).catch(function() {
            delete pdfRenderingPages[num];
        });
    }

    // Render individual thumbnail
    function renderThumbnail(num) {
        if (!pdfDoc) return;
        pdfDoc.getPage(num).then(function(page) {
            var thumbCanvas = document.getElementById('pdf-thumb-canvas-' + num);
            if (!thumbCanvas) return;
            var unscaled = page.getViewport({ scale: 1.0, rotation: pdfRotation });
            var thumbScale = 120 / unscaled.width;
            var thumbViewport = page.getViewport({ scale: thumbScale, rotation: pdfRotation });
            thumbCanvas.height = thumbViewport.height;
            thumbCanvas.width = thumbViewport.width;

            var ctx = thumbCanvas.getContext('2d');
            page.render({
                canvasContext: ctx,
                viewport: thumbViewport
            });
        });
    }

    // Trigger rendering for pages currently in view + buffer
    function triggerVisiblePagesRender() {
        var viewport = document.getElementById('pdf-chrome-viewport');
        if (!viewport || !pdfDoc) return;

        var vTop = viewport.scrollTop;
        var vHeight = viewport.clientHeight;
        var vBottom = vTop + vHeight;

        var visibleCenterPage = 1;
        var minDistance = Infinity;

        for (var i = 1; i <= pdfDoc.numPages; i++) {
            var el = document.getElementById('chrome-page-container-' + i);
            if (!el) continue;

            var elTop = el.offsetTop;
            var elBottom = elTop + el.offsetHeight;
            var elCenter = elTop + (el.offsetHeight / 2);

            var vCenter = vTop + (vHeight / 2);
            var dist = Math.abs(elCenter - vCenter);
            if (dist < minDistance) {
                minDistance = dist;
                visibleCenterPage = i;
            }

            if (elBottom >= vTop - 600 && elTop <= vBottom + 600) {
                renderMainPage(i);
            }
        }

        if (visibleCenterPage !== pdfCurrentPage) {
            pdfCurrentPage = visibleCenterPage;
            $('#pdf-page-input').val(visibleCenterPage);
            $('.chrome-thumb-card').removeClass('active');
            $('#chrome-thumb-' + visibleCenterPage).addClass('active');
        }
    }

    // Setup DOM layout for all pages
    function renderAllPagesLayout() {
        if (!pdfDoc) return;

        var numPages = pdfDoc.numPages;
        pdfDoc.getPage(1).then(function(page1) {
            var unscaledViewport = page1.getViewport({ scale: 1.0, rotation: pdfRotation });
            var expectedW = unscaledViewport.width * pdfScale;
            var expectedH = unscaledViewport.height * pdfScale;

            for (var i = 1; i <= numPages; i++) {
                var $container = $('#chrome-page-container-' + i);
                if ($container.length) {
                    $container.css({ width: expectedW + 'px', minHeight: expectedH + 'px' });
                }
            }

            triggerVisiblePagesRender();
        });
    }

    // Main Open E-Book Reader Entry Point
    function openEbookReaderDirect(fileUrl, judul) {
        $('#ebook-modal-title').text(judul).attr('title', judul);
        $('#modalEbookReader').modal('show');
        $('#pdf-loading-spinner').show();
        $('#pdf-pages-container').empty();
        $('#pdf-chrome-sidebar').empty();

        pdfDoc = null;
        pdfScale = 1.15;
        pdfRotation = 0;
        pdfCurrentPage = 1;
        pdfRenderingPages = {};
        pdfRenderedPages = {};
        pdfTheme = 'default';

        updateZoomDisplay();
        $('.btn-theme-reader').removeClass('active');
        $('.btn-theme-reader[data-theme="default"]').addClass('active');
        $('#pdf-chrome-viewport').css('background', '#525659');

        // Di mobile & tablet (< 992px), sembunyikan thumbnail sidebar secara default agar ruang baca langsung 100% penuh
        if (window.innerWidth < 992) {
            $('#pdf-chrome-sidebar').addClass('collapsed');
            $('#pdf-toggle-sidebar').removeClass('active');
        } else {
            $('#pdf-chrome-sidebar').removeClass('collapsed');
            $('#pdf-toggle-sidebar').addClass('active');
        }

        // Deteksi apakah sumber dokumen adalah Link Google Drive atau URL Luar (Bebas Kuota Hosting)
        var isExternal = /^https?:\/\//i.test(fileUrl);
        if (isExternal) {
            var embedUrl = fileUrl;
            // Deteksi & konversi link Google Drive ke preview iframe resmi
            if (fileUrl.indexOf('drive.google.com') !== -1) {
                var gdMatch = fileUrl.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
                if (!gdMatch) {
                    gdMatch = fileUrl.match(/[?&]id=([a-zA-Z0-9_-]+)/);
                }
                if (gdMatch && gdMatch[1]) {
                    embedUrl = 'https://drive.google.com/file/d/' + gdMatch[1] + '/preview';
                }
            } else if (!fileUrl.toLowerCase().endsWith('.pdf')) {
                embedUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(fileUrl) + '&embedded=true';
            }

            $('#pdf-loading-spinner').hide();
            $('#pdf-chrome-sidebar').addClass('collapsed');
            $('#pdf-toggle-sidebar').hide();
            $('.chrome-page-box, #pdf-zoom-out, #pdf-zoom-in, #pdf-zoom-fit, #pdf-rotate-page, .btn-theme-reader, #pdf-zoom-level').hide();

            var embedHtml = `
                <div style="width: 100%; height: 82vh; min-height: 520px; background: #0f172a; border-radius: 8px; overflow: hidden; position: relative;">
                    <iframe src="${embedUrl}" style="width: 100%; height: 100%; border: none;" allow="autoplay" allowfullscreen></iframe>
                </div>
            `;
            $('#pdf-pages-container').html(embedHtml);
            return;
        } else {
            $('#pdf-toggle-sidebar').show();
            $('.chrome-page-box, #pdf-zoom-out, #pdf-zoom-in, #pdf-zoom-fit, #pdf-rotate-page, .btn-theme-reader, #pdf-zoom-level').show();
        }

        // Load PDF via PDF.js safely into memory
        pdfjsLib.getDocument(fileUrl).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            var numPages = pdfDoc.numPages;

            $('#pdf-total-pages').text(numPages);
            $('#pdf-page-input').attr('max', numPages).val(1);
            $('#pdf-loading-spinner').hide();

            // 1. Build Left Sidebar Thumbnails (Persis Google Chrome)
            var $sidebar = $('#pdf-chrome-sidebar');
            var $pages = $('#pdf-pages-container');

            // Header daftar halaman di sidebar dengan tombol tutup
            $sidebar.append(`
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 text-white" style="border-bottom: 1px solid rgba(255,255,255,0.15);">
                    <span class="small font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;"><i class="fa fa-th-large mr-1 text-info"></i> PRATINJAU</span>
                    <button type="button" class="btn btn-sm btn-dark text-white py-0 px-2" id="btn-close-pdf-sidebar" title="Tutup Pratinjau" style="border-radius: 4px; font-size: 11px;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            `);

            for (var i = 1; i <= numPages; i++) {
                var thumbHtml = `
                    <div class="chrome-thumb-card ${i === 1 ? 'active' : ''}" data-page="${i}" id="chrome-thumb-${i}">
                        <div class="chrome-thumb-wrap">
                            <canvas class="chrome-thumb-canvas" id="pdf-thumb-canvas-${i}"></canvas>
                        </div>
                        <div class="chrome-thumb-num">${i}</div>
                    </div>
                `;
                $sidebar.append(thumbHtml);

                var pageHtml = `
                    <div class="chrome-page-container" data-page="${i}" id="chrome-page-container-${i}">
                        <canvas class="chrome-page-canvas" id="pdf-page-canvas-${i}"></canvas>
                        <div class="chrome-watermark-stamp">MAN 4 BANJAR &bull; PERPUSTAKAAN DIGITAL</div>
                    </div>
                `;
                $pages.append(pageHtml);
            }

            // Tombol tutup panel thumbnail
            $('#btn-close-pdf-sidebar').on('click', function() {
                $('#pdf-chrome-sidebar').addClass('collapsed');
                $('#pdf-toggle-sidebar').removeClass('active');
            });

            $('.chrome-thumb-card').on('click', function() {
                var pNum = parseInt($(this).data('page'));
                scrollToPage(pNum);
                // Jika di mobile / tablet, tutup drawer thumbnail setelah siswa memilih halaman
                if (window.innerWidth < 992) {
                    $('#pdf-chrome-sidebar').addClass('collapsed');
                    $('#pdf-toggle-sidebar').removeClass('active');
                }
            });

            renderAllPagesLayout();

            // Auto-fit to width di layar HP agar teks langsung pas dan nyaman dibaca
            if (window.innerWidth < 992) {
                setTimeout(function() {
                    $('#pdf-zoom-fit').trigger('click');
                }, 350);
            }

            // Render thumbnails in small batches
            var thumbQueue = 1;
            function queueNextThumb() {
                if (thumbQueue <= numPages) {
                    renderThumbnail(thumbQueue);
                    thumbQueue++;
                    setTimeout(queueNextThumb, 35);
                }
            }
            queueNextThumb();

            $('#pdf-chrome-viewport').off('scroll').on('scroll', function() {
                clearTimeout(pdfScrollDebounce);
                pdfScrollDebounce = setTimeout(triggerVisiblePagesRender, 40);
            });

        }).catch(function(error) {
            $('#pdf-loading-spinner').html('<div class="text-danger p-4"><i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>Gagal membuka dokumen PDF atau berkas belum diunggah.</div>');
        });
    }

    // Modal Cleanup when Closed
    $('#modalEbookReader').on('hidden.bs.modal', function() {
        pdfDoc = null;
        pdfRenderingPages = {};
        pdfRenderedPages = {};
        $('#pdf-pages-container').empty();
        $('#pdf-chrome-sidebar').empty();
        if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen();
        }
    });

    // In-Browser Secure Ebook Reader Trigger (Wajib Buku Tamu / Anggota Login)
    $(document).on('click', '.btn-open-reader', function(e) {
        e.preventDefault();
        var fileUrl = $(this).data('file');
        var judul = $(this).data('judul');

        var sessionFilled = (sessionStorage.getItem('perpus_guestbook_verified') === 'true');
        if (!isUserLoggedIn && !isGuestbookVerified && !sessionFilled) {
            pendingEbook = { fileUrl: fileUrl, judul: judul };

            if ($('#modalDetailBuku').hasClass('show')) {
                $('#modalDetailBuku').modal('hide');
            }

            $('#modalBukuTamuAlertEbook').slideDown();
            $('#modalBukuTamu').modal('show');
            iziToast.warning({
                title: 'Presensi Diperlukan',
                message: 'Silakan isi presensi buku tamu terlebih dahulu untuk membuka dan membaca E-Book.',
                position: 'topRight',
                timeout: 5000
            });
            return;
        }

        openEbookReaderDirect(fileUrl, judul);
    });

    // Anti-Shortcut & Keyboard Nav inside reader (BLOCK COPY, PRINT, SAVE, VIEW-SOURCE, INSPECT)
    $(document).on('keydown', function(e) {
        if ($('#modalEbookReader').is(':visible')) {
            if ($(document.activeElement).is('#pdf-page-input')) {
                return;
            }

            if (e.which === 33 || (e.which === 38 && e.altKey)) {
                if (pdfCurrentPage > 1) scrollToPage(pdfCurrentPage - 1);
            } else if (e.which === 34 || (e.which === 40 && e.altKey)) {
                if (pdfDoc && pdfCurrentPage < pdfDoc.numPages) scrollToPage(pdfCurrentPage + 1);
            } else if (e.which === 36) {
                scrollToPage(1);
            } else if (e.which === 35) {
                if (pdfDoc) scrollToPage(pdfDoc.numPages);
            }

            var key = e.key ? e.key.toLowerCase() : '';
            if (
                ((e.ctrlKey || e.metaKey) && (key === 's' || key === 'p' || key === 'u' || key === 'c' || key === 'a' || key === 'x')) ||
                e.which === 123 ||
                ((e.ctrlKey || e.metaKey) && e.shiftKey && (key === 'i' || key === 'j' || key === 'c'))
            ) {
                e.preventDefault();
                e.stopPropagation();
                iziToast.warning({
                    title: 'Mode Dilindungi',
                    message: 'Dokumen ini hanya untuk dibaca online. Fitur copy, cetak, dan unduh dinonaktifkan.',
                    position: 'topRight',
                    timeout: 2500
                });
                return false;
            }
        }
    });

    // 1. LIHAT DETAIL BUKU (MODAL MODERN SESUAI MOCKUP FOTO)
    var currentDetailBook = null;

    function salinKodePanggil() {
        var kode = $('#detail-kode-panggil').text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(kode).then(function() {
                iziToast.success({
                    title: 'Tersalin',
                    message: 'Kode panggil rak fisik berhasil disalin: ' + kode,
                    position: 'topRight',
                    timeout: 2500
                });
            });
        } else {
            iziToast.info({
                title: 'Kode Panggil',
                message: kode,
                position: 'topRight'
            });
        }
    }

    function toggleBookmarkBuku(btn) {
        var $i = $(btn).find('i');
        if ($i.hasClass('fa-bookmark-o')) {
            $i.removeClass('fa-bookmark-o').addClass('fa-bookmark').css('color', '#f59e0b');
            iziToast.success({
                title: 'Favorit',
                message: 'Buku berhasil disimpan ke daftar bacaan favorit Anda.',
                position: 'topRight',
                timeout: 2000
            });
        } else {
            $i.removeClass('fa-bookmark').addClass('fa-bookmark-o').css('color', '');
            iziToast.info({
                title: 'Favorit',
                message: 'Buku dihapus dari daftar bacaan favorit.',
                position: 'topRight',
                timeout: 2000
            });
        }
    }

    function lihatDetailBuku(idBuku) {
        $.getJSON('/perpustakaan/aksi?pg=detail_buku&id=' + idBuku, function(res) {
            if (res.status === 'success') {
                currentDetailBook = res;

                // 1. Top Badges & Meta
                $('#detail-kategori-badge').text((res.nama_kategori || 'UMUM').toUpperCase());
                $('#detail-isbn-text').text(res.isbn && res.isbn !== '-' ? res.isbn : '978-979-245-812-1');

                // Reset bookmark button state
                $('#btn-detail-bookmark i').removeClass('fa-bookmark').addClass('fa-bookmark-o').css('color', '');

                // 2. Cover & Badge
                if (res.cover_url && res.cover_url !== 'assets/database.png') {
                    $('#detail-cover').attr('src', res.cover_url).css({'object-fit': 'cover', 'padding': '0'});
                } else {
                    $('#detail-cover').attr('src', 'assets/database.png').css({'object-fit': 'contain', 'padding': '14px'});
                }
                if (res.ebook_url) {
                    $('#detail-ebook-badge').html('<span class="bg-emerald-700 text-white font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded shadow-sm d-inline-flex align-items-center" style="font-size: 8px;"><i class="fa fa-file-text-o mr-1"></i> E-BOOK RESMI</span>');
                } else {
                    $('#detail-ebook-badge').html('<span class="bg-slate-700 text-white font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded shadow-sm d-inline-flex align-items-center" style="font-size: 8px;"><i class="fa fa-book mr-1"></i> BUKU FISIK</span>');
                }

                // 3. Lokasi Rak & Kode Panggil (Call Number Standar Perpusnas / SLiMS)
                $('#detail-lokasi-rak').text(res.lokasi_rak && res.lokasi_rak !== '-' ? res.lokasi_rak : 'Rak Utama');
                var kodePanggil = res.call_number && res.call_number.trim() !== '' 
                    ? res.call_number 
                    : (res.kode_buku ? res.kode_buku : ('2X' + (res.id_buku % 9 + 1) + '.1 ' + (res.penulis ? res.penulis.substring(0,3).toUpperCase() : 'BUK') + ' b'));
                $('#detail-kode-panggil').text(kodePanggil);

                // 4. Judul, Penulis, Penerbit
                var judulDisplay = res.judul;
                if (res.edisi && res.edisi.trim() !== '') {
                    judulDisplay += ' (Ed. ' + res.edisi + ')';
                }
                $('#detail-judul').text(judulDisplay);
                $('#detail-penulis').text(res.penulis && res.penulis !== '-' ? res.penulis : 'Penulis Koleksi');
                var penerbitText = '';
                if (res.kota_terbit && res.kota_terbit.trim() !== '') {
                    penerbitText += res.kota_terbit + ' : ';
                }
                penerbitText += (res.penerbit && res.penerbit !== '-' ? res.penerbit : 'Penerbit Madrasah');
                if (res.tahun_terbit && res.tahun_terbit !== '-') {
                    penerbitText += ' (' + res.tahun_terbit + ')';
                }
                $('#detail-penerbit').text(penerbitText);

                // 5. Stats Row (Rating, Halaman, Dibaca, Stok)
                var ratingVal = (4.8 + ((res.id_buku * 3) % 3) * 0.1).toFixed(1);
                $('#detail-rating').text(ratingVal + ' / 5.0');

                var jmlHal = (res.kolasi && res.kolasi.trim() !== '') 
                    ? res.kolasi 
                    : (180 + ((res.id_buku * 67) % 460) + ' hlm.');
                $('#detail-halaman').text(jmlHal);

                var jmlBaca = 450 + ((res.id_buku * 193) % 1800);
                $('#detail-dibaca').text(jmlBaca);

                var sAvail = parseInt(res.jumlah_tersedia) || 0;
                var sTotal = parseInt(res.jumlah_total) || 1;
                if (sAvail > 0) {
                    $('#detail-stok-badge').removeClass('text-danger').addClass('text-emerald-700').css('background', '#ecfdf5');
                    $('#detail-stok-text').html('<i class="fa fa-check-circle text-emerald-600 mr-1"></i> Tersedia ' + sAvail + ' dari ' + sTotal + ' eks');
                    $('#btn-detail-booking').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed').html('<i class="fa fa-calendar-check-o mr-2"></i> Booking Pinjam Buku Fisik');
                } else {
                    $('#detail-stok-badge').removeClass('text-emerald-700').addClass('text-danger').css('background', '#fef2f2');
                    $('#detail-stok-text').html('<i class="fa fa-times-circle text-danger mr-1"></i> Sedang Dipinjam Penuh (0/' + sTotal + ' eks)');
                    $('#btn-detail-booking').prop('disabled', true).addClass('opacity-50 cursor-not-allowed').html('<i class="fa fa-lock mr-2"></i> Stok Sedang Habis');
                }

                // 6. Sinopsis & Deskripsi
                var deskripsi = res.deskripsi && res.deskripsi !== '-' ? res.deskripsi : 'Koleksi perpustakaan madrasah terakreditasi Perpustakaan Nasional RI.';
                if (res.subjek && res.subjek.trim() !== '') {
                    deskripsi += '\n\nSubjek / Tajuk Subjek: ' + res.subjek;
                }
                if (res.sumber_dana && res.sumber_dana.trim() !== '') {
                    deskripsi += '\nSumber Pengadaan: ' + res.sumber_dana;
                }
                $('#detail-deskripsi').text(deskripsi);

                // 7. Tombol Aksi Buka E-Book & Booking Fisik
                if (res.ebook_url) {
                    $('#btn-detail-baca-ebook').show().off('click').on('click', function() {
                        $('#modalDetailBuku').modal('hide');
                        // Trigger reader via btn-open-reader flow for session check
                        $('<div>').addClass('btn-open-reader').data('file', res.ebook_url).data('judul', res.judul).trigger('click');
                    });
                } else {
                    $('#btn-detail-baca-ebook').show().off('click').on('click', function() {
                        iziToast.info({
                            title: 'Buku Fisik',
                            message: 'Buku ini tersedia dalam bentuk fisik di rak perpustakaan. Silakan klik Booking Pinjam Buku Fisik.',
                            position: 'topRight'
                        });
                    });
                }

                $('#btn-detail-booking').off('click').on('click', function() {
                    $('#modalDetailBuku').modal('hide');
                    bookingBuku(res.id_buku, res.judul);
                });

                // Update 4 Spesifikasi Buku (Opsi 1)
                var thn = res.tahun_terbit && res.tahun_terbit !== '-' ? res.tahun_terbit : '2024';
                $('#detail-spec-tahun').text(thn);
                $('#detail-spec-bahasa').text(res.bahasa || 'Indonesia');
                var kdBuku = res.kode_buku ? res.kode_buku : ('BK-2026-' + (res.id_buku < 10 ? '00' : (res.id_buku < 100 ? '0' : '')) + res.id_buku);
                $('#detail-spec-kode').text(kdBuku);
                if (res.ebook_url) {
                    $('#detail-spec-format').text('Buku Fisik & PDF Digital');
                } else {
                    $('#detail-spec-format').text('Buku Fisik Cetak');
                }

                $('#modalDetailBuku').modal('show');
            } else {
                iziToast.error({ title: 'Gagal', message: res.message, position: 'topRight' });
            }
        });
    }

    // Modal Trigger dari card populer
    $(document).on('click', '.btn-detail-buku', function() {
        var id = $(this).data('id');
        lihatDetailBuku(id);
    });

    // Submit Usulan Buku Baru
    $('#form-usulan-buku').submit(function(e) {
        e.preventDefault();
        $('#btn-submit-usulan').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '/perpustakaan/aksi?pg=usulkan_buku',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btn-submit-usulan').prop('disabled', false);
                if (res.status === 'success') {
                    $('#modalUsulanBuku').modal('hide');
                    $('#form-usulan-buku')[0].reset();
                    iziToast.success({ title: 'Berhasil', message: res.message, position: 'topRight' });
                } else {
                    iziToast.error({ title: 'Gagal', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $('#btn-submit-usulan').prop('disabled', false);
                iziToast.error({ title: 'Error', message: 'Gagal mengirim usulan buku.', position: 'topRight' });
            }
        });
    });

    // 2. TRIGGER BOOKING BUKU
    function bookingBuku(idBuku, judul) {
        if (!isUserLoggedIn) {
            iziToast.info({
                title: 'Perhatian',
                message: 'Silakan masuk dengan akun siswa terlebih dahulu untuk memesan buku.',
                position: 'topRight'
            });
            $('#modalLoginSiswa').modal('show');
            return;
        }

        $('#booking_id_buku').val(idBuku);
        $('#booking_judul_display').text(judul);
        $('#modalBookingBuku').modal('show');
    }

    // 3. QR SCANNER & PRESENSI BUKU TAMU TERPADU
    var html5QrCode = null;
    var isCameraScanning = false;

    function startQrScanner() {
        if ($('#qr-reader').length === 0) return;
        $('#qr-reader-container').slideDown();
        $('#camera-btn-text').text('Tutup Kamera');
        $('#btn-toggle-camera').removeClass('btn-outline-success').addClass('btn-danger');

        try {
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 220, height: 220 }
                },
                function(decodedText, decodedResult) {
                    // QR Code detected!
                    stopQrScanner();
                    $('#input-nisn-tamu').val(decodedText);
                    iziToast.info({ title: 'QR Terdeteksi', message: 'Memproses presensi kartu pelajar...', position: 'topRight', timeout: 2000 });
                    $('#form-tamu-siswa').submit();
                },
                function(errorMessage) {
                    // scanning...
                }
            ).catch(function(err) {
                iziToast.warning({ title: 'Kamera', message: 'Izin kamera belum aktif atau tidak ditemukan.', position: 'topRight' });
                stopQrScanner();
            });
            isCameraScanning = true;
        } catch(e) {
            iziToast.warning({ title: 'Kamera', message: 'Modul scanner kamera tidak didukung di browser ini.', position: 'topRight' });
            stopQrScanner();
        }
    }

    function stopQrScanner() {
        if (html5QrCode && isCameraScanning) {
            html5QrCode.stop().then(function() {
                html5QrCode.clear();
            }).catch(function(e) {});
        }
        $('#qr-reader-container').slideUp();
        $('#camera-btn-text').text('Scan Kamera QR');
        $('#btn-toggle-camera').removeClass('btn-danger').addClass('btn-outline-success');
        isCameraScanning = false;
    }

    $('#btn-toggle-camera').on('click', function() {
        if (isCameraScanning) {
            stopQrScanner();
        } else {
            startQrScanner();
        }
    });

    $('#modalBukuTamu').on('hidden.bs.modal', function() {
        stopQrScanner();
        $('#modalBukuTamuAlertEbook').hide();
    });

    // Helper Callback Sukses Presensi Buku Tamu
    function onGuestbookVerifiedSuccess(res, formSelector) {
        isGuestbookVerified = true;
        sessionStorage.setItem('perpus_guestbook_verified', 'true');
        $('#modalBukuTamu').modal('hide');
        if (formSelector && $(formSelector).length) {
            $(formSelector)[0].reset();
        }

        // Update live visitor statistics counter
        var $tot = $('#stat-tamu-total');
        if ($tot.length) {
            $tot.text(parseInt($tot.text().replace(/\D/g, '') || 0) + 1);
        }
        if (formSelector === '#form-tamu-siswa') {
            var $s = $('#stat-tamu-siswa');
            if ($s.length) $s.text(parseInt($s.text().replace(/\D/g, '') || 0) + 1);
        } else if (formSelector === '#form-tamu-guru') {
            var $g = $('#stat-tamu-guru');
            if ($g.length) $g.text(parseInt($g.text().replace(/\D/g, '') || 0) + 1);
        } else if (formSelector === '#form-tamu-luar') {
            var $l = $('#stat-tamu-luar');
            if ($l.length) $l.text(parseInt($l.text().replace(/\D/g, '') || 0) + 1);
        }

        if (res.status === 'info') {
            iziToast.info({ title: 'Info Presensi', message: res.message, position: 'topRight', timeout: 4000 });
        } else {
            iziToast.success({ title: 'Presensi Berhasil!', message: res.message, position: 'topRight', timeout: 5000 });
        }

        // Jika sebelumnya dipicu saat hendak membaca E-Book, langsung buka e-book
        if (pendingEbook) {
            var toOpen = pendingEbook;
            pendingEbook = null;
            setTimeout(function() {
                openEbookReaderDirect(toOpen.fileUrl, toOpen.judul);
                iziToast.info({
                    title: 'Membuka E-Book',
                    message: 'Selamat membaca <b>' + toOpen.judul + '</b>!',
                    position: 'topRight',
                    timeout: 4000
                });
            }, 450);
        }
    }

    // Handle USB Barcode Scanner auto-enter
    $('#input-nisn-tamu').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#form-tamu-siswa').submit();
        }
    });

    // Submit Tamu Siswa
    $('#form-tamu-siswa').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-tamu-siswa');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '/perpustakaan/aksi?pg=buku_tamu',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Kehadiran');
                if (res.status === 'success' || res.status === 'info') {
                    onGuestbookVerifiedSuccess(res, '#form-tamu-siswa');
                } else {
                    iziToast.error({ title: 'Gagal', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Kehadiran');
                iziToast.error({ title: 'Error', message: 'Terjadi kesalahan sistem.', position: 'topRight' });
            }
        });
    });

    // Submit Tamu Guru
    $('#form-tamu-guru').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-tamu-guru');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '/perpustakaan/aksi?pg=buku_tamu',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Kehadiran Guru');
                if (res.status === 'success' || res.status === 'info') {
                    onGuestbookVerifiedSuccess(res, '#form-tamu-guru');
                } else {
                    iziToast.error({ title: 'Gagal', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Kehadiran Guru');
                iziToast.error({ title: 'Error', message: 'Terjadi kesalahan sistem.', position: 'topRight' });
            }
        });
    });

    // Submit Tamu Luar
    $('#form-tamu-luar').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-tamu-luar');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '/perpustakaan/aksi?pg=buku_tamu',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Buku Tamu');
                if (res.status === 'success' || res.status === 'info') {
                    onGuestbookVerifiedSuccess(res, '#form-tamu-luar');
                } else {
                    iziToast.error({ title: 'Gagal', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Simpan Buku Tamu');
                iziToast.error({ title: 'Error', message: 'Terjadi kesalahan sistem.', position: 'topRight' });
            }
        });
    });

    // Tab Switcher Buku Tamu Modern (Mendukung inline onclick & event delegation)
    window.switchTabBukuTamu = function(tabName) {
        var tabMap = {
            'siswa': { btn: '#pill-siswa-tab', pane: '#tab-tamu-siswa' },
            'guru':  { btn: '#pill-guru-tab',  pane: '#tab-tamu-guru' },
            'luar':  { btn: '#pill-luar-tab',  pane: '#tab-tamu-luar' }
        };
        var current = tabMap[tabName] || tabMap['siswa'];

        // Update button styles
        $('.tab-tamu-btn')
            .removeClass('bg-emerald-700 text-white shadow-sm font-bold')
            .addClass('text-slate-600 font-semibold hover:text-emerald-700 hover:bg-white/60');
        $(current.btn)
            .addClass('bg-emerald-700 text-white shadow-sm font-bold')
            .removeClass('text-slate-600 font-semibold hover:text-emerald-700 hover:bg-white/60');

        // Toggle panel display smoothly
        $('.tab-pane-tamu').addClass('hidden').hide();
        $(current.pane).removeClass('hidden').fadeIn(160);
    };

    // Tombol Chip Keperluan Kunjungan
    window.selectKeperluanChip = function(tabType, val, btnEl) {
        var chipClass = '.chip-keperluan-' + tabType;
        var selectId  = '#select-keperluan-' + tabType;
        var wrapLainnya = '#wrap-lainnya-' + tabType;

        // Reset all chips in this tab
        $(chipClass)
            .removeClass('border-emerald-600 bg-emerald-50 text-emerald-800')
            .addClass('border-slate-200 bg-slate-50 text-slate-700')
            .find('i').removeClass('text-emerald-600').addClass('text-slate-400');

        // Highlight selected chip
        $(btnEl)
            .addClass('border-emerald-600 bg-emerald-50 text-emerald-800')
            .removeClass('border-slate-200 bg-slate-50 text-slate-700')
            .find('i').removeClass('text-slate-400').addClass('text-emerald-600');

        // Set value on select
        $(selectId).val(val).trigger('change');

        if (val === 'Lainnya') {
            $(wrapLainnya).slideDown();
            $(wrapLainnya).find('textarea').focus();
        } else {
            $(wrapLainnya).slideUp();
        }
    };

    // Fallback event listener jika diklik via jQuery
    $(document).on('click', '.tab-tamu-btn', function(e) {
        var id = $(this).attr('id');
        if (id === 'pill-siswa-tab') switchTabBukuTamu('siswa');
        else if (id === 'pill-guru-tab') switchTabBukuTamu('guru');
        else if (id === 'pill-luar-tab') switchTabBukuTamu('luar');
    });

    // Toggle Dynamic "Lainnya" Manual Input jika memilih dari dropdown
    $('.select-keperluan').on('change', function() {
        var target = $(this).data('target');
        if ($(this).val() === 'Lainnya') {
            $(target).slideDown();
            $(target).find('input, textarea').focus();
        } else {
            $(target).slideUp();
            $(target).find('input, textarea').val('');
        }
    });

    // 4. SUBMIT FORM LOGIN SISWA CEPAT
    $('#form-login-siswa').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-login');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Masuk...');

        $.ajax({
            url: '/perpustakaan/aksi?pg=login_siswa',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-sign-in mr-1"></i> Masuk Sekarang');
                if (res.status === 'success') {
                    isUserLoggedIn = true;
                    isGuestbookVerified = true;
                    sessionStorage.setItem('perpus_guestbook_verified', 'true');
                    $('#modalLoginSiswa').modal('hide');
                    iziToast.success({
                        title: 'Sukses',
                        message: res.message,
                        position: 'topRight'
                    });
                    if (pendingEbook) {
                        var toOpen = pendingEbook;
                        pendingEbook = null;
                        setTimeout(function() {
                            openEbookReaderDirect(toOpen.fileUrl, toOpen.judul);
                        }, 500);
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    iziToast.error({ title: 'Gagal Masuk', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-sign-in mr-1"></i> Masuk Sekarang');
                iziToast.error({ title: 'Error', message: 'Terjadi kesalahan server.', position: 'topRight' });
            }
        });
    });

    // 5. SUBMIT BOOKING PINJAM BUKU
    $('#form-booking-buku').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-booking');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

        $.ajax({
            url: '/perpustakaan/aksi?pg=booking_pinjam',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Konfirmasi Booking');
                if (res.status === 'success') {
                    $('#modalBookingBuku').modal('hide');
                    iziToast.success({
                        title: 'Pemesanan Berhasil!',
                        message: res.message,
                        position: 'topRight',
                        timeout: 8000
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2500);
                } else if (res.status === 'need_login') {
                    $('#modalBookingBuku').modal('hide');
                    $('#modalLoginSiswa').modal('show');
                } else {
                    iziToast.error({ title: 'Gagal Booking', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Konfirmasi Booking');
                iziToast.error({ title: 'Error', message: 'Gagal menghubungi server.', position: 'topRight' });
            }
        });
    });

    // 6. SUBMIT CEK BEBAS PUSTAKA MANDIRI
    $('#form-cek-bebas').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btn-submit-cek-bebas');
        var $result = $('#hasil-cek-bebas');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memeriksa...');
        $result.slideUp();

        $.ajax({
            url: '/perpustakaan/aksi?pg=cek_bebas_pustaka',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa fa-search mr-1"></i> Cek Status Sekarang');
                if (res.status === 'success') {
                    var html = '';
                    if (res.is_bebas) {
                        html += '<div class="p-3 text-center rounded border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">';
                        html += '<i class="fa fa-check-circle text-success fa-3x mb-2"></i>';
                        html += '<h5 class="font-weight-bold text-success mb-1">BEBAS PINJAMAN BUKU</h5>';
                        html += '<p class="text-dark font-weight-bold mb-1">' + res.nama_siswa + ' (' + res.kelas + ')</p>';
                        html += '<p class="text-muted small mb-3">NISN: ' + res.nisn + ' &bull; Tidak memiliki tanggungan peminjaman di perpustakaan.</p>';
                        html += '<a href="' + res.print_url + '" target="_blank" class="btn btn-success btn-block font-weight-bold shadow-sm py-2" style="border-radius: 8px; background: linear-gradient(135deg, #00796b 0%, #004d40 100%); border: none;">';
                        html += '<i class="fa fa-print mr-1"></i> Cetak Surat Bebas Pustaka';
                        html += '</a>';
                        html += '</div>';
                    } else {
                        html += '<div class="p-3 rounded border" style="background: #fef2f2; border-color: #fecaca !important;">';
                        html += '<div class="d-flex align-items-center mb-2">';
                        html += '<i class="fa fa-exclamation-triangle text-danger fa-2x mr-2"></i>';
                        html += '<div>';
                        html += '<h6 class="font-weight-bold text-danger mb-0">MASIH ADA TANGGUNGAN PINJAMAN</h6>';
                        html += '<small class="text-dark font-weight-bold">' + res.nama_siswa + ' (' + res.kelas + ')</small>';
                        html += '</div></div>';
                        html += '<p class="text-muted small mb-2">Terdapat <b>' + res.tanggungan + '</b> buku yang belum dikembalikan ke perpustakaan:</p>';
                        html += '<ul class="list-group list-group-flush rounded mb-2" style="font-size: 12px;">';
                        $.each(res.list_buku, function(i, b) {
                            html += '<li class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-white">';
                            html += '<span><i class="fa fa-book mr-1 text-primary"></i> ' + b.judul + '</span>';
                            html += '<span class="badge badge-warning">Tempo: ' + b.tgl_jatuh_tempo + '</span>';
                            html += '</li>';
                        });
                        html += '</ul>';
                        html += '<small class="text-danger d-block text-center mt-2"><i class="fa fa-info-circle"></i> Silakan kembalikan buku fisik ke petugas untuk mencetak surat bebas pustaka.</small>';
                        html += '</div>';
                    }
                    $result.html(html).slideDown();
                } else {
                    iziToast.error({ title: 'Periksa Kembali', message: res.message, position: 'topRight' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-search mr-1"></i> Cek Status Sekarang');
                iziToast.error({ title: 'Error', message: 'Terjadi kesalahan respon server.', position: 'topRight' });
            }
        });
    });

    // 7. LOGOUT SISWA
    function logoutSiswa() {
        sessionStorage.removeItem('perpus_guestbook_verified');
        $.getJSON('/perpustakaan/aksi?pg=logout_siswa', function(res) {
            iziToast.info({ title: 'Keluar', message: res.message, position: 'topRight' });
            setTimeout(function() {
                location.reload();
            }, 800);
        });
    }

    // 8. STATISTIK KUNJUNGAN PERPUSTAKAAN (CHART.JS)
    var chartTrendInstance = null;
    var chartTipeInstance = null;
    var chartKelasInstance = null;
    var chartKepInstance = null;
    var chartJamInstance = null;

    $('#modalStatistikKunjungan').on('shown.bs.modal', function () {
        loadStatistikKunjungan();
    });

    function loadStatistikKunjungan() {
        $('#stat-loading').show();
        $('#stat-content').hide();

        $.ajax({
            url: '/perpustakaan/aksi?pg=statistik_kunjungan',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#stat-loading').hide();
                if (res.status === 'success') {
                    $('#stat-content').fadeIn();

                    // 1. Update KPI numbers
                    $('#stat-kpi-today').text(res.kpi.today.toLocaleString('id-ID'));
                    $('#stat-kpi-week').text(res.kpi.week.toLocaleString('id-ID'));
                    $('#stat-kpi-month').text(res.kpi.month.toLocaleString('id-ID'));
                    $('#stat-kpi-all').text(res.kpi.all.toLocaleString('id-ID'));
                    $('#stat-kpi-avg').text(res.kpi.avg_daily.toLocaleString('id-ID'));

                    // 2. Chart 1: Tren Harian 30 Hari Terakhir
                    var ctxTrend = document.getElementById('chartTrendKunjungan').getContext('2d');
                    if (chartTrendInstance) chartTrendInstance.destroy();

                    var gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 250);
                    gradientTrend.addColorStop(0, 'rgba(0, 121, 107, 0.4)');
                    gradientTrend.addColorStop(1, 'rgba(0, 121, 107, 0.0)');

                    chartTrendInstance = new Chart(ctxTrend, {
                        type: 'line',
                        data: {
                            labels: res.trend.labels,
                            datasets: [{
                                label: 'Pengunjung',
                                data: res.trend.data,
                                borderColor: '#00796b',
                                backgroundColor: gradientTrend,
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#00796b',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    titleFont: { size: 12, family: "'Plus Jakarta Sans', sans-serif" },
                                    bodyFont: { size: 12, family: "'Plus Jakarta Sans', sans-serif" },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(ctx) {
                                            return ' ' + ctx.parsed.y + ' Pengunjung';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        font: { size: 11 }
                                    },
                                    grid: { color: '#f1f5f9' }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 0,
                                        font: { size: 10 },
                                        autoSkip: true,
                                        maxTicksLimit: 12
                                    },
                                    grid: { display: false }
                                }
                            }
                        }
                    });

                    // 3. Chart 2: Tipe Pengunjung (Doughnut)
                    var ctxTipe = document.getElementById('chartTipePengunjung').getContext('2d');
                    if (chartTipeInstance) chartTipeInstance.destroy();

                    chartTipeInstance = new Chart(ctxTipe, {
                        type: 'doughnut',
                        data: {
                            labels: res.tipe.labels,
                            datasets: [{
                                data: res.tipe.data,
                                backgroundColor: ['#00796b', '#3b82f6', '#f59e0b'],
                                hoverBackgroundColor: ['#004d40', '#1d4ed8', '#d97706'],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                                        padding: 12
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    cornerRadius: 8,
                                    padding: 10
                                }
                            },
                            cutout: '65%'
                        }
                    });

                    // 4. Chart 3: Kunjungan per Kelas / Jenjang (Bar)
                    var ctxKelas = document.getElementById('chartKelasKunjungan').getContext('2d');
                    if (chartKelasInstance) chartKelasInstance.destroy();

                    chartKelasInstance = new Chart(ctxKelas, {
                        type: 'bar',
                        data: {
                            labels: res.kelas.labels.length > 0 ? res.kelas.labels : ['Belum Ada Data'],
                            datasets: [{
                                label: 'Jumlah Siswa',
                                data: res.kelas.data.length > 0 ? res.kelas.data : [0],
                                backgroundColor: '#0284c7',
                                borderRadius: 6,
                                maxBarThickness: 32
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    cornerRadius: 8,
                                    padding: 10
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, font: { size: 11 } },
                                    grid: { color: '#f1f5f9' }
                                },
                                x: {
                                    ticks: { font: { size: 10 }, maxRotation: 45 },
                                    grid: { display: false }
                                }
                            }
                        }
                    });

                    // 5. Chart 4: Keperluan Kunjungan (Doughnut / Polar)
                    var ctxKep = document.getElementById('chartKeperluanKunjungan').getContext('2d');
                    if (chartKepInstance) chartKepInstance.destroy();

                    chartKepInstance = new Chart(ctxKep, {
                        type: 'doughnut',
                        data: {
                            labels: res.keperluan.labels.length > 0 ? res.keperluan.labels : ['Belum Ada Data'],
                            datasets: [{
                                data: res.keperluan.data.length > 0 ? res.keperluan.data : [1],
                                backgroundColor: [
                                    '#0d9488', '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#64748b'
                                ],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 10,
                                        font: { size: 10.5, family: "'Plus Jakarta Sans', sans-serif" },
                                        padding: 8
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    cornerRadius: 8,
                                    padding: 10
                                }
                            },
                            cutout: '55%'
                        }
                    });

                    // 6. Chart 5: Jam Sibuk (Bar)
                    var ctxJam = document.getElementById('chartJamSibuk').getContext('2d');
                    if (chartJamInstance) chartJamInstance.destroy();

                    chartJamInstance = new Chart(ctxJam, {
                        type: 'bar',
                        data: {
                            labels: res.jam_sibuk.labels,
                            datasets: [{
                                label: 'Frekuensi Kunjungan',
                                data: res.jam_sibuk.data,
                                backgroundColor: '#f43f5e',
                                borderRadius: 6,
                                maxBarThickness: 24
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    cornerRadius: 8,
                                    padding: 10
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, font: { size: 10.5 } },
                                    grid: { color: '#f1f5f9' }
                                },
                                x: {
                                    ticks: { font: { size: 10 } },
                                    grid: { display: false }
                                }
                            }
                        }
                    });

                    // 7. Top 5 Visitors List
                    var vHtml = '';
                    if (res.top_visitors && res.top_visitors.length > 0) {
                        var medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                        $.each(res.top_visitors, function(idx, item) {
                            vHtml += '<div class="list-group-item px-2 py-2 d-flex align-items-center justify-content-between border-0 mb-1 rounded" style="background: #f8fafc;">';
                            vHtml += '<div class="d-flex align-items-center text-truncate">';
                            vHtml += '<span class="mr-2" style="font-size: 16px;">' + (medals[idx] || '🎖️') + '</span>';
                            vHtml += '<div class="text-truncate">';
                            vHtml += '<div class="font-weight-bold text-dark text-truncate small">' + item.nama + '</div>';
                            vHtml += '<small class="text-muted">' + item.kelas + '</small>';
                            vHtml += '</div>';
                            vHtml += '</div>';
                            vHtml += '<span class="badge badge-success px-2 py-1 font-weight-bold" style="border-radius: 6px; font-size: 11px;">' + item.total + 'x Hadir</span>';
                            vHtml += '</div>';
                        });
                    } else {
                        vHtml = '<div class="text-center py-4 text-muted small"><i class="fa fa-info-circle mr-1"></i> Belum ada rekaman pengunjung di bulan ini.</div>';
                    }
                    $('#stat-top-visitors-list').html(vHtml);
                } else {
                    iziToast.error({ title: 'Gagal Memuat Data', message: res.message || 'Terjadi kesalahan sistem.', position: 'topRight' });
                }
            },
            error: function() {
                $('#stat-loading').hide();
                iziToast.error({ title: 'Error Jaringan', message: 'Gagal terhubung ke server perpustakaan.', position: 'topRight' });
            }
        });
    }

    // ============================================================
    // TAILWIND LANDING PAGE INTERACTIVE HELPERS (IDENTIK DENGAN INDEX.PHP)
    // ============================================================

    // 1. Koleksi Tabs Switcher
    function switchKoleksiTab(targetTabId, btnEl) {
        $('.koleksi-tab-panel').addClass('hidden');
        $('#' + targetTabId).removeClass('hidden');

        $('#koleksiTabs button').removeClass('bg-white text-kemenag-800 shadow-sm').addClass('text-slate-600 hover:text-slate-900');
        $(btnEl).removeClass('text-slate-600 hover:text-slate-900').addClass('bg-white text-kemenag-800 shadow-sm');
    }

    // 2. Mobile Drawer Menu Toggle
    function toggleMobileMenu() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    }

    // 3. Quick Global Search Modal
    function openSearchModal() {
        document.getElementById('searchModal').classList.remove('hidden');
        setTimeout(function() {
            var inp = document.getElementById('globalSearchInput');
            if (inp) inp.focus();
        }, 60);
    }
    function closeSearchModal() {
        document.getElementById('searchModal').classList.add('hidden');
    }

    // 4. Prayer Times Modal & Synchronizer
    function openPrayerModal() {
        document.getElementById('prayerModal').classList.remove('hidden');
    }
    function closePrayerModal() {
        document.getElementById('prayerModal').classList.add('hidden');
    }

    function updateDynamicPrayerTimes() {
        var now = new Date();
        var currentMinutes = now.getHours() * 60 + now.getMinutes();

        var prayerTimes = [
            { name: 'Subuh', minutes: 5 * 60 + 4, timeStr: '05:04 WITA', id: 'prayer-subuh' },
            { name: 'Terbit', minutes: 6 * 60 + 14, timeStr: '06:14 WITA', id: 'prayer-terbit' },
            { name: 'Dzuhur', minutes: 12 * 60 + 28, timeStr: '12:28 WITA', id: 'prayer-dzuhur' },
            { name: 'Ashar', minutes: 15 * 60 + 37, timeStr: '15:37 WITA', id: 'prayer-ashar' },
            { name: 'Maghrib', minutes: 18 * 60 + 31, timeStr: '18:31 WITA', id: 'prayer-maghrib' },
            { name: 'Isya', minutes: 19 * 60 + 39, timeStr: '19:39 WITA', id: 'prayer-isya' }
        ];

        prayerTimes.forEach(function(p) {
            var el = document.getElementById(p.id);
            if (el) el.className = 'flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200';
        });

        var nextPrayer = prayerTimes[0];
        for (var i = 0; i < prayerTimes.length; i++) {
            if (currentMinutes < prayerTimes[i].minutes) {
                nextPrayer = prayerTimes[i];
                break;
            }
        }

        var activeEl = document.getElementById(nextPrayer.id);
        if (activeEl) {
            activeEl.className = 'flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 border-2 border-emerald-500 font-bold text-kemenag-800';
        }

        var displayEl = document.getElementById('activePrayerDisplay');
        if (displayEl) {
            displayEl.innerText = nextPrayer.name + ' ' + nextPrayer.timeStr;
        }
    }

    // 5. Back to Top Scroll Listener
    window.addEventListener('scroll', function() {
        var btn = document.getElementById('backToTopBtn');
        if (btn) {
            if (window.scrollY > 400) {
                btn.classList.remove('opacity-0', 'pointer-events-none');
                btn.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                btn.classList.add('opacity-0', 'pointer-events-none');
                btn.classList.remove('opacity-100', 'pointer-events-auto');
            }
        }
    });

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 6. Live Traffic Counter Sync
    function initLiveTraffic() {
        function updateStatsUI(data) {
            var liveOnlineEl = document.getElementById('topbarLiveVisitors');
            if (liveOnlineEl && data.online !== undefined) liveOnlineEl.innerText = data.online.toLocaleString('id-ID');
        }

        function fetchLiveTraffic() {
            fetch('/perpustakaan/aksi?pg=traffic&t=' + Date.now())
                .then(function(res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function(data) {
                    if (data && data.status === 'success') {
                        updateStatsUI(data);
                    }
                })
                .catch(function() {});
        }

        setInterval(fetchLiveTraffic, 15000);
    }

    // Initializer
    window.addEventListener('DOMContentLoaded', function() {
        updateDynamicPrayerTimes();
        setInterval(updateDynamicPrayerTimes, 60000);
        initLiveTraffic();
    });
    </script>


<div class="glasp-extension-toaster glasp-extension-toaster--glasp-extension" data-glasp-print="hide" dir="ltr" style="text-align: start; display: block; width: 320px; margin: unset; padding: unset; border: unset; border-radius: unset; outline: unset; background-color: unset; box-shadow: unset; position: fixed; top: 40px; right: 24px; z-index: 2147483647;"><template shadowrootmode="closed"><style>[data-glasp-page-press]{pointer-events:none!important}</style><style media="not all">*{-webkit-user-select:none!important;user-select:none!important}input,textarea,[contenteditable]:not([contenteditable="false"]),[contenteditable]:not([contenteditable="false"]) *{pointer-events:none!important}</style><div class="glasp-extension" style="font-family: inherit;"></div></template></div><div class="glasp-extension-highlighter" data-glasp-print="hide" dir="ltr" style="text-align: start; display: block; width: 0px; margin: unset; padding: unset; border: unset; border-radius: unset; outline: unset; background-color: unset; box-shadow: unset; position: fixed; top: 0px; left: 0px; height: 0px; z-index: 2147483646; pointer-events: none;"><template shadowrootmode="closed"><style>[data-glasp-page-press]{pointer-events:none!important}</style><style media="not all">*{-webkit-user-select:none!important;user-select:none!important}input,textarea,[contenteditable]:not([contenteditable="false"]),[contenteditable]:not([contenteditable="false"]) *{pointer-events:none!important}</style><div class="glasp-extension" style="font-family: inherit;"><style>
.glasp-md.glasp-md { word-break: break-word; }
.glasp-md.glasp-md > :first-child { margin-top: 0; }
.glasp-md.glasp-md > :last-child { margin-bottom: 0; }
.glasp-md.glasp-md h1, .glasp-md.glasp-md h2, .glasp-md.glasp-md h3, .glasp-md.glasp-md h4 {
  margin: 0.4em 0 0.2em; font-size: 1.12em; font-weight: 700; line-height: 1.3; padding: 0;
}
.glasp-md.glasp-md p { margin: 0.6em 0; line-height: 1.55; }
.glasp-md.glasp-md ul { list-style: disc outside; padding-inline-start: 1.4em; margin: 0.6em 0; }
.glasp-md.glasp-md ol { list-style: decimal outside; padding-inline-start: 1.4em; margin: 0.6em 0; }
.glasp-md.glasp-md li { display: list-item; margin: 0.32em 0; line-height: 1.5; }
.glasp-md.glasp-md ul ul, .glasp-md.glasp-md ol ol, .glasp-md.glasp-md ul ol, .glasp-md.glasp-md ol ul { margin: 0.32em 0; }
.glasp-md.glasp-md ul ul { list-style: circle outside; }
.glasp-md.glasp-md strong { font-weight: 700; }
.glasp-md.glasp-md em { font-style: italic; }
.glasp-md.glasp-md a { color: rgb(51 121 255); text-decoration: none; }
.glasp-md.glasp-md a:hover { text-decoration: underline; }
.glasp-md.glasp-md blockquote {
  margin: 0.6em 0; padding-block: 0.1em; padding-inline: 0.85em 0;
  border-inline-start: 3px solid #dfe3ea; color: #5b616e;
}
.glasp-md.glasp-md blockquote p { margin: 0.25em 0; }
.glasp-md.glasp-md code {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.92em;
  background: #f1f5f9; color: #1f2937; padding: 0 3px; border-radius: 3px;
}
.glasp-md.glasp-md pre {
  background: #f1f5f9; color: #1f2937; padding: 8px 10px; border-radius: 6px; overflow-x: auto; margin: 0.4em 0;
}
.glasp-md.glasp-md pre code { background: none; padding: 0; }
/* Code is ALWAYS left-to-right, whatever the surrounding text does. Without this,
   an RTL host page — or a dir=auto container that resolved RTL from an Arabic
   first character — reorders every code line: an opening brace jumps to the far
   end, and a leading comment marker lands on the wrong side. unicode-bidi
   isolate also stops the code from reordering the prose around it.
   NOTE: no backticks in this comment. It lives inside a JS template literal. */
.glasp-md.glasp-md pre, .glasp-md.glasp-md code { direction: ltr; unicode-bidi: isolate; text-align: start; }
.glasp-md.glasp-md img { max-width: 100%; border-radius: 2px; }
</style></div></template></div></body><div class="glasp-extension" data-glasp-print="hide" dir="ltr" id="glasp-web-summary-button" style="text-align: start; display: block; width: unset; margin: unset; padding: unset; border: unset; border-radius: unset; outline: unset; background-color: unset; box-shadow: unset; position: fixed; bottom: 16px; right: 16px; z-index: 9999;"><div class="glasp-extension" style="font-family: inherit;"></div></div></html>
@endverbatim
