<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cardápio — {{ config('app.name', 'Bar Shalom') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #111111;
            --bg2:     #1a1a1a;
            --bg3:     #252525;
            --orange:  #d97706;
            --orange2: #f59e0b;
            --text:    #e8e8e8;
            --muted:   #8a8a7a;
            --border:  #2e2e2e;
            --radius:  10px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        /* scroll-behavior gerenciado via JS para evitar conflitos */

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── HEADER ── */
        .site-header {
            background: rgba(13,13,13,.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--orange);
            position: sticky;
            top: 0;
            z-index: 200;
            transform: translateY(-100%);
            animation: slideDown .5s .1s cubic-bezier(.22,1,.36,1) forwards;
        }
        @keyframes slideDown {
            to { transform: translateY(0); }
        }
        .header-inner {
            max-width: 1000px;
            margin: 0 auto;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--orange);
            letter-spacing: .05em;
            text-decoration: none;
        }
        .logo span { color: var(--text); }
        .header-nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: .82rem;
            padding: .35rem .75rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            transition: color .15s, border-color .15s, background .15s;
        }
        .header-nav a:hover {
            color: var(--text);
            border-color: var(--muted);
            background: var(--bg3);
        }

        /* ── HERO ── */
        .hero {
            background: #0b0b0b;
            border-bottom: 1px solid var(--border);
            text-align: center;
            padding: 1rem 1.5rem .75rem;
        }
        .hero-logo {
            max-width: 480px;
            width: 90%;
            height: auto;
            display: inline-block;
            opacity: 0;
            transform: translateY(24px);
            animation: fadeUp .7s .3s cubic-bezier(.22,1,.36,1) forwards;
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── CATEGORY NAV ── */
        .cat-nav-wrap {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            opacity: 0;
            animation: fadeIn .5s .7s ease forwards;
            /* sticky abaixo do header — top ajustado via JS */
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .cat-nav-wrap::-webkit-scrollbar { display: none; }
        @keyframes fadeIn { to { opacity: 1; } }
        .cat-nav {
            max-width: 1000px;
            margin: 0 auto;
            padding: .65rem 1.5rem;
            display: flex;
            gap: .35rem;
            white-space: nowrap;
        }
        .cat-nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            padding: .3rem .8rem;
            border-radius: 20px;
            border: 1px solid transparent;
            transition: color .2s, border-color .2s, background .2s;
        }
        .cat-nav a:hover,
        .cat-nav a.active {
            color: var(--orange);
            border-color: var(--orange);
            background: rgba(217,119,6,.08);
        }

        /* ── MAIN ── */
        .menu-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 5rem;
        }

        /* ── CATEGORY SECTION ── */
        .cat-section {
            margin-bottom: 3.5rem;
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .55s cubic-bezier(.22,1,.36,1), transform .55s cubic-bezier(.22,1,.36,1);
        }
        .cat-section.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .cat-header {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .cat-header h2 {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #0d0d0d;
            background: var(--orange);
            padding: .3rem 1rem;
            border-radius: 4px;
            white-space: nowrap;
        }
        .cat-header-line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── ITEM ROW ── */
        .item-list { display: flex; flex-direction: column; }
        .item-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .7rem .5rem;
            border-bottom: 1px solid var(--border);
            border-radius: 6px;
            cursor: default;
            opacity: 0;
            transform: translateX(-18px);
            transition:
                opacity .4s ease,
                transform .4s ease,
                background .18s ease,
                border-color .18s ease;
            position: relative;
        }
        .item-row:last-child { border-bottom: none; }
        .item-row.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .item-row:hover {
            background: var(--bg3);
            border-color: rgba(217,119,6,.25);
        }
        .item-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            border-radius: 3px;
            background: var(--orange);
            transform: scaleY(0);
            transition: transform .2s ease;
        }
        .item-row:hover::before { transform: scaleY(1); }

        /* ── THUMBNAIL ── */
        .item-thumb {
            width: 52px;
            height: 52px;
            border-radius: var(--radius);
            overflow: hidden;
            flex-shrink: 0;
            background: var(--bg3);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
        }
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .item-row:hover .item-thumb {
            transform: scale(1.1) rotate(-2deg);
            box-shadow: 0 4px 16px rgba(217,119,6,.3);
        }

        /* ── ITEM INFO ── */
        .item-info { flex: 1; min-width: 0; }
        .item-name {
            font-size: .98rem;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-desc {
            font-size: .76rem;
            color: var(--muted);
            margin-top: .1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-price {
            font-size: .98rem;
            font-weight: 800;
            color: var(--orange2);
            white-space: nowrap;
            flex-shrink: 0;
            transition: transform .2s ease, color .2s ease;
        }
        .item-row:hover .item-price {
            color: #fbbf24;
            transform: scale(1.05);
        }
        /* status badges */
        .status-badge {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .15rem .5rem;
            border-radius: 20px;
            flex-shrink: 0;
        }
        .status-unavailable {
            background: rgba(239,68,68,.15);
            color: #f87171;
            border: 1px solid rgba(239,68,68,.3);
        }
        .status-coming-soon {
            background: rgba(245,158,11,.12);
            color: var(--orange2);
            border: 1px solid rgba(245,158,11,.3);
        }
        .item-row.is-unavailable .item-name,
        .item-row.is-unavailable .item-price { opacity: .45; }
        .item-row.is-coming-soon .item-price { opacity: 0; width: 0; overflow: hidden; }

        /* ── FOOTER ── */
        footer {
            background: #0d0d0d;
            border-top: 2px solid var(--orange);
            text-align: center;
            padding: 1.25rem 1rem;
            color: var(--muted);
            font-size: .82rem;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .hero-title { font-size: 2.2rem; }
            .header-inner { flex-direction: column; align-items: flex-start; gap: .4rem; }
            .item-thumb { width: 44px; height: 44px; font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a class="logo" href="/">Bar <span>Shalom</span></a>
        <nav class="header-nav">
            @auth
                <a href="{{ url('/home') }}">Painel Admin</a>
            @else
                <a href="{{ route('login') }}">Entrar</a>
            @endauth
        </nav>
    </div>
</header>

<div class="hero">
    <img class="hero-logo"
         src="{{ Storage::url('logo-shalom.png') }}?t={{ filemtime(storage_path('app/public/logo-shalom.png')) }}"
         alt="{{ config('app.name', 'Bar Shalom') }}">
</div>

<nav class="cat-nav-wrap" id="cat-nav">
    <div class="cat-nav">
        @foreach($items->keys() as $cat)
            <a href="#cat-{{ Str::slug($cat) }}" data-target="cat-{{ Str::slug($cat) }}">{{ $cat }}</a>
        @endforeach
    </div>
</nav>

@php
$emojis = [
    'PORÇÕES'        => '🍽️',
    'COMIDA'         => '🍚',
    'SALGADOS'       => '🥟',
    'CERVEJAS'       => '🍺',
    'BEBIDAS'        => '🥤',
    'BEBIDAS QUENTES'=> '🥃',
    'BORBONS'        => '🥃',
    'CAIPIRINHAS'    => '🍹',
    'DRINKS'         => '🍸',
];
@endphp

<main class="menu-wrap">
    @forelse($items as $category => $catItems)
        <section class="cat-section" id="cat-{{ Str::slug($category) }}">
            <div class="cat-header">
                <h2>{{ $category }}</h2>
                <div class="cat-header-line"></div>
            </div>
            <div class="item-list">
                @foreach($catItems as $item)
                    <div class="item-row {{ $item->status === 'unavailable' ? 'is-unavailable' : ($item->status === 'coming_soon' ? 'is-coming-soon' : '') }}">
                        <div class="item-thumb">
                            @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" loading="lazy">
                            @else
                                {{ $emojis[$category] ?? '🍴' }}
                            @endif
                        </div>
                        <div class="item-info">
                            <div class="item-name">{{ $item->name }}</div>
                            @if($item->description)
                                <div class="item-desc">{{ $item->description }}</div>
                            @endif
                        </div>
                        @if($item->status === 'unavailable')
                            <span class="status-badge status-unavailable">Indisponível</span>
                        @elseif($item->status === 'coming_soon')
                            <span class="status-badge status-coming-soon">Em breve</span>
                        @else
                            <div class="item-price">{{ $item->price_formatted }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <p style="color:var(--muted); text-align:center; margin-top:4rem;">Cardápio em breve.</p>
    @endforelse
</main>

<footer>
    &copy; {{ date('Y') }} {{ config('app.name', 'Bar Shalom') }} &mdash; Todos os direitos reservados
</footer>

<script>
(function () {
    const siteHeader = document.querySelector('.site-header');
    const catNavWrap = document.getElementById('cat-nav');

    /* ── 0. Mantém o cat-nav grudado abaixo do header ── */
    function syncNavTop() {
        catNavWrap.style.top = siteHeader.offsetHeight + 'px';
    }
    syncNavTop();
    new ResizeObserver(syncNavTop).observe(siteHeader);

    /* ── 1. Scroll-reveal: seções e itens ── */
    const sectionObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('visible');
            entry.target.querySelectorAll('.item-row').forEach((row, i) => {
                setTimeout(() => row.classList.add('visible'), i * 45);
            });
            sectionObs.unobserve(entry.target);
        });
    }, { threshold: 0.06 });

    document.querySelectorAll('.cat-section').forEach(sec => sectionObs.observe(sec));

    /* ── 2. Destaque do link ativo conforme scroll ──
       Usa scrollTo no CONTAINER da nav para centralizar o link horizontalmente.
       Nunca toca no scroll da página — elimina o bug de voltar ao topo. ── */
    const navLinks = document.querySelectorAll('.cat-nav a[data-target]');

    const navObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const id = entry.target.id;
            navLinks.forEach(a => a.classList.toggle('active', a.dataset.target === id));

            const activeLink = catNavWrap.querySelector(`.cat-nav a[data-target="${id}"]`);
            if (activeLink) {
                const linkMid = activeLink.offsetLeft + activeLink.offsetWidth / 2;
                catNavWrap.scrollTo({ left: linkMid - catNavWrap.clientWidth / 2, behavior: 'smooth' });
            }
        });
    }, { threshold: 0, rootMargin: '-35% 0px -60% 0px' });

    document.querySelectorAll('.cat-section').forEach(sec => navObs.observe(sec));

    /* ── 3. Clique suave na nav com offset correto ── */
    navLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const target = document.getElementById(link.dataset.target);
            if (!target) return;
            const offset = siteHeader.offsetHeight + catNavWrap.offsetHeight + 12;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        });
    });

})();
</script>

</body>
</html>
