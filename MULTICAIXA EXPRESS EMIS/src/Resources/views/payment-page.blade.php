<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pagamento Seguro — {{ $storeName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: #0d1117;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
        }

        /* ── Layout principal ── */
        #ap-wrap {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }

        /* ── Topbar ── */
        #ap-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 52px;
            min-height: 52px;
            flex-shrink: 0;
            background: #161b22;
            border-bottom: 1px solid #21262d;
            padding: 0 20px;
            z-index: 100;
        }
        .ap-top-left  { display: flex; align-items: center; gap: 10px; }
        .ap-top-center{ display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #f0f6fc; letter-spacing: .3px; }
        .ap-top-right { display: flex; align-items: center; gap: 16px; }

        .ap-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
        }
        .ap-logo-placeholder {
            height: 32px;
            display: flex;
            align-items: center;
            font-size: 15px;
            font-weight: 700;
            color: #f0f6fc;
        }

        .ap-lock { color: #3fb950; font-size: 15px; }

        .ap-ssl {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #3fb950;
        }

        #ap-cancel {
            display: flex;
            align-items: center;
            gap: 5px;
            background: none;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #8b949e;
            font-size: 12px;
            cursor: pointer;
            padding: 5px 12px;
            transition: all .15s;
            text-decoration: none;
        }
        #ap-cancel:hover { border-color: #f85149; color: #f85149; }

        /* ── Área do iframe ── */
        #ap-frame-area {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            background: #fff;
        }

        /* Loading */
        #ap-loading {
            position: absolute;
            inset: 0;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0d1117;
            gap: 14px;
            transition: opacity .3s;
        }
        #ap-loading.gone { opacity: 0; pointer-events: none; }
        .ap-spin {
            width: 38px; height: 38px;
            border: 3px solid #21262d;
            border-top-color: #1f6feb;
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        #ap-loading p { color: #8b949e; font-size: 13px; }

        /* Status overlay */
        #ap-status {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0d1117;
            gap: 14px;
            text-align: center;
            padding: 32px;
        }
        #ap-status.show { display: flex; }
        .ap-st-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .ap-st-icon.ok   { background: #1a3d2a; }
        .ap-st-icon.err  { background: #3d1a1a; }
        .ap-st-icon.warn { background: #3d2e1a; }
        .ap-st-icon.wait { background: #1a2a3d; }
        #ap-st-title { font-size: 20px; font-weight: 700; color: #f0f6fc; }
        #ap-st-msg   { font-size: 13px; color: #8b949e; max-width: 360px; line-height: 1.5; }
        #ap-st-timer { font-size: 11px; color: #6e7681; margin-top: 2px; }
        .ap-bar      { width: 180px; height: 3px; background: #21262d; border-radius: 3px; overflow: hidden; margin-top: 6px; }
        .ap-bar-fill { height: 100%; width: 0%; border-radius: 3px; transition: width linear; }
        .ap-bar-fill.ok   { background: #3fb950; }
        .ap-bar-fill.err  { background: #f85149; }
        .ap-bar-fill.warn { background: #d29922; }

        /* iframe wrapper */
        #ap-frame-wrap {
            position: relative;
            overflow: hidden;
            background: #fff;
            width: 100%;
        }

        /* iframe — dimensões e scale calculados via JS */
        #ap-frame {
            position: absolute;
            top: 0; left: 0;
            border: none;
            display: block;
            background: #fff;
            transform-origin: top left;
        }

        /* ── Footer ── */
        #ap-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            height: 34px;
            min-height: 34px;
            flex-shrink: 0;
            background: #161b22;
            border-top: 1px solid #21262d;
        }
        .ap-foot-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: #6e7681;
        }

        @media (max-width: 600px) {
            .ap-top-center, .ap-ssl span { display: none; }
            #ap-cancel span { display: none; }
            #ap-top { padding: 0 12px; }
        }
    </style>
</head>
<body>
<div id="ap-wrap">

    <!-- ── Topbar ── -->
    <div id="ap-top">
        <div class="ap-top-left">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="ap-logo">
            @else
                <div class="ap-logo-placeholder">{{ $storeName }}</div>
            @endif
            <span style="color:#30363d;font-size:18px;">|</span>
            <img src="https://pagamentonline.emis.co.ao/online-payment-gateway/portal/assets/logo-emis.png"
                 alt="EMIS" class="ap-logo"
                 onerror="this.style.display='none'">
        </div>

        <div class="ap-top-center">
            <span class="ap-lock">🔒</span>
            <span>PAGAMENTO SEGURO</span>
        </div>

        <div class="ap-top-right">
            <div class="ap-ssl">
                <span class="ap-lock">🔒</span>
                <span>Ligação SSL segura</span>
            </div>
            <a href="{{ $cancelUrl }}" id="ap-cancel" title="Cancelar pagamento">
                <span>✕</span>
                <span>Cancelar</span>
            </a>
        </div>
    </div>

    <!-- ── Área do iframe ── -->
    <div id="ap-frame-area">

        <!-- Loading enquanto iframe carrega -->
        <div id="ap-loading">
            <div class="ap-spin"></div>
            <p>A carregar sistema de pagamento…</p>
        </div>

        <!-- Overlay de resultado -->
        <div id="ap-status">
            <div class="ap-st-icon" id="ap-st-icon"></div>
            <div id="ap-st-title"></div>
            <div id="ap-st-msg"></div>
            <div id="ap-st-timer"></div>
            <div class="ap-bar"><div class="ap-bar-fill" id="ap-bar-fill"></div></div>
        </div>

        <!-- Wrapper de escala — o JS aplica transform:scale() aqui -->
        <div id="ap-frame-wrap">
            <iframe
                id="ap-frame"
                src="{{ $iframeSrc }}"
                allow="payment"
                title="Pagamento EMIS — Multicaixa Express"
            ></iframe>
        </div>

    </div>

    <!-- ── Footer ── -->
    <div id="ap-foot">
        <div class="ap-foot-item">
            <span>🔒</span>
            <span>Processado pela EMIS – Multicaixa</span>
        </div>
        <div class="ap-foot-item">
            <span>🛡️</span>
            <span>Os seus dados estão protegidos</span>
        </div>
    </div>

</div>

<script>
(function(){
    var SUCCESS_URL = @json($successUrl);
    var CANCEL_URL  = @json($cancelUrl);
    var ORDER_ID    = @json($orderId);
    var DELAY       = 3000;
    var processed   = false;

    var frame    = document.getElementById('ap-frame');
    var loader   = document.getElementById('ap-loading');
    var statusDiv= document.getElementById('ap-status');
    var iconEl   = document.getElementById('ap-st-icon');
    var titleEl  = document.getElementById('ap-st-title');
    var msgEl    = document.getElementById('ap-st-msg');
    var timerEl  = document.getElementById('ap-st-timer');
    var fill     = document.getElementById('ap-bar-fill');

    // ── Scaling do iframe EMIS ────────────────────────────────────────────
    var EMIS_W = 480;
    var EMIS_H = 800;

    function scaleFrame(){
        var area  = document.getElementById('ap-frame-area');
        var wrap  = document.getElementById('ap-frame-wrap');
        if(!area || !wrap || !frame) return;

        var availW = area.offsetWidth;
        var topH   = (document.getElementById('ap-top')  || {offsetHeight:52}).offsetHeight || 52;
        var footH  = (document.getElementById('ap-foot') || {offsetHeight:34}).offsetHeight || 34;
        var availH = window.innerHeight - topH - footH;

        var scaleW = availW / EMIS_W;
        var scaleH = availH / EMIS_H;
        var scale  = Math.min(scaleW, scaleH, 1);

        var scaledW = Math.round(EMIS_W * scale);
        var scaledH = Math.round(EMIS_H * scale);

        frame.style.width           = EMIS_W + 'px';
        frame.style.height          = EMIS_H + 'px';
        frame.style.transform       = 'scale(' + scale + ')';
        frame.style.transformOrigin = 'top left';
        frame.style.left            = Math.max(0, Math.round((availW - scaledW) / 2)) + 'px';
        frame.style.top             = '0px';

        wrap.style.width    = '100%';
        wrap.style.height   = scaledH + 'px';
        wrap.style.overflow = 'hidden';

        area.style.height = scaledH + 'px';
        area.style.flex   = 'none';
    }

    window.addEventListener('resize', scaleFrame);
    scaleFrame();

    // ── iframe load ────────────────────────────────────────────────────────
    frame.addEventListener('load', function(){
        scaleFrame();
        loader.classList.add('gone');
    });

    // ── Mostrar resultado ──────────────────────────────────────────────────
    function showResult(type, icon, title, msg, redirectUrl){
        statusDiv.classList.add('show');
        iconEl.className  = 'ap-st-icon ' + type;
        iconEl.textContent= icon;
        titleEl.textContent = title;
        msgEl.textContent   = msg;
        titleEl.style.color = type === 'ok' ? '#3fb950' : type === 'err' ? '#f85149' : '#d29922';

        fill.className = 'ap-bar-fill ' + type;

        var seconds  = DELAY / 1000;
        var elapsed  = 0;
        var interval = 100;

        timerEl.textContent = 'A redirecionar em ' + seconds + 's…';
        fill.style.transition = 'width ' + (interval / 1000) + 's linear';
        fill.style.width = '0%';

        var timer = setInterval(function(){
            elapsed += interval;
            fill.style.width = (elapsed / DELAY * 100) + '%';
            var rem = Math.ceil((DELAY - elapsed) / 1000);
            timerEl.textContent = 'A redirecionar em ' + rem + 's…';

            if(elapsed >= DELAY){
                clearInterval(timer);
                window.location.href = redirectUrl;
            }
        }, interval);
    }

    // ── postMessage da EMIS ───────────────────────────────────────────────
    // O browser recebe o transactionId da EMIS mas NÃO altera o status do pedido.
    // O status é SEMPRE actualizado pelo webhook servidor→servidor.
    window.addEventListener('message', function(event){
        if(event.origin.indexOf('pagamentonline.emis.co.ao') === -1) return;
        if(processed) return;

        var data   = event.data;
        var txid   = '';
        var s      = 'PENDING_WEBHOOK';

        if(typeof data === 'string'){
            // EMIS envia string simples com o transactionId
            txid = data.trim();
            s    = 'PENDING_WEBHOOK';
            console.log('[EMIS] postMessage string (txid):', txid);
        } else if(typeof data === 'object' && data !== null){
            txid = data.transactionId || data.id || '';
            s    = (data.status || 'PENDING_WEBHOOK').toUpperCase();
            console.log('[EMIS] postMessage JSON:', data);
        }

        processed = true;

        if(s === 'SUCCESS' || s === 'ACCEPTED'){
            showResult('ok', '✅', 'Pagamento confirmado!',
                'O seu pagamento foi processado com sucesso. Obrigado pela sua compra.',
                SUCCESS_URL);
        } else if(s === 'REJECTED' || s === 'FAILED'){
            showResult('err', '❌', 'Pagamento rejeitado',
                'O pagamento não foi aprovado. Por favor tente novamente.',
                CANCEL_URL);
        } else {
            // PENDING_WEBHOOK — aguardar webhook para confirmar
            showResult('wait', '⏳', 'A verificar pagamento…',
                'O seu pagamento está a ser verificado. Aguarde um momento.',
                SUCCESS_URL);
        }
    }, false);

})();
</script>
</body>
</html>
