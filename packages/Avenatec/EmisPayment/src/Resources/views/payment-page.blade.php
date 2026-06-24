<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pagamento Seguro - {{ $storeName }}</title>

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            background: #111827;
            color: #f9fafb;
            font-family: Arial, sans-serif;
        }

        #emis-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }

        #emis-topbar,
        #emis-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            background: #0f172a;
            border-color: #1f2937;
            padding: 0 18px;
        }

        #emis-topbar {
            height: 56px;
            border-bottom: 1px solid #1f2937;
        }

        #emis-footer {
            height: 36px;
            justify-content: center;
            border-top: 1px solid #1f2937;
            color: #9ca3af;
            font-size: 12px;
        }

        .emis-brand {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 12px;
            font-size: 14px;
            font-weight: 700;
        }

        .emis-logo {
            max-height: 34px;
            max-width: 160px;
            object-fit: contain;
        }

        .emis-summary {
            color: #d1d5db;
            font-size: 13px;
            white-space: nowrap;
        }

        .emis-cancel {
            color: #d1d5db;
            border: 1px solid #374151;
            border-radius: 6px;
            padding: 7px 12px;
            text-decoration: none;
            font-size: 13px;
        }

        .emis-cancel:hover {
            border-color: #ef4444;
            color: #fecaca;
        }

        #emis-frame-area {
            position: relative;
            flex: 1;
            min-height: 0;
            overflow: hidden;
            background: #ffffff;
        }

        #emis-frame-wrap {
            position: relative;
            width: 100%;
            overflow: hidden;
            background: #ffffff;
        }

        #emis-frame {
            position: absolute;
            top: 0;
            left: 0;
            border: 0;
            background: #ffffff;
            transform-origin: top left;
        }

        #emis-loader,
        #emis-status {
            position: absolute;
            inset: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 14px;
            background: #111827;
            text-align: center;
            padding: 28px;
        }

        #emis-loader.gone {
            opacity: 0;
            pointer-events: none;
        }

        #emis-status {
            display: none;
            z-index: 10;
        }

        #emis-status.show {
            display: flex;
        }

        .emis-spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #374151;
            border-top-color: #38bdf8;
            border-radius: 999px;
            animation: emis-spin .8s linear infinite;
        }

        @keyframes emis-spin {
            to {
                transform: rotate(360deg);
            }
        }

        #emis-status-title {
            font-size: 20px;
            font-weight: 700;
        }

        #emis-status-message {
            max-width: 380px;
            color: #d1d5db;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            #emis-topbar {
                padding: 0 12px;
            }

            .emis-summary {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div id="emis-shell">
        <div id="emis-topbar">
            <div class="emis-brand">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="emis-logo">
                @else
                    <span>{{ $storeName }}</span>
                @endif
            </div>

            <div class="emis-summary">
                Pedido #{{ $orderId }} - {{ $orderTotal }}
            </div>

            <a href="{{ $cancelUrl }}" class="emis-cancel">Cancelar</a>
        </div>

        <div id="emis-frame-area">
            <div id="emis-loader">
                <div class="emis-spinner"></div>
                <p>A carregar pagamento seguro...</p>
            </div>

            <div id="emis-status">
                <div id="emis-status-title"></div>
                <div id="emis-status-message"></div>
            </div>

            <div id="emis-frame-wrap">
                <iframe
                    id="emis-frame"
                    src="{{ $iframeSrc }}"
                    allow="payment"
                    title="Pagamento EMIS Multicaixa Express"
                ></iframe>
            </div>
        </div>

        <div id="emis-footer">
            Processado pela EMIS - Multicaixa Express
        </div>
    </div>

    <script>
        (function () {
            var STATUS_URL = @json($statusUrl);
            var SUCCESS_URL = @json($successUrl);
            var CANCEL_URL = @json($cancelUrl);
            var processed = false;
            var polling = false;
            var completed = false;
            var statusTimer = null;

            var area = document.getElementById('emis-frame-area');
            var wrap = document.getElementById('emis-frame-wrap');
            var frame = document.getElementById('emis-frame');
            var loader = document.getElementById('emis-loader');
            var statusBox = document.getElementById('emis-status');
            var statusTitle = document.getElementById('emis-status-title');
            var statusMessage = document.getElementById('emis-status-message');

            var EMIS_W = 480;
            var EMIS_H = 800;

            function scaleFrame() {
                var topbarHeight = document.getElementById('emis-topbar').offsetHeight;
                var footerHeight = document.getElementById('emis-footer').offsetHeight;
                var availW = area.offsetWidth;
                var availH = window.innerHeight - topbarHeight - footerHeight;
                var scale = Math.min(availW / EMIS_W, availH / EMIS_H, 1);

                frame.style.width = EMIS_W + 'px';
                frame.style.height = EMIS_H + 'px';
                frame.style.transform = 'scale(' + scale + ')';
                frame.style.transformOrigin = 'top left';
                frame.style.left = Math.max(0, (availW - EMIS_W * scale) / 2) + 'px';

                wrap.style.height = (EMIS_H * scale) + 'px';
            }

            function showResult(title, message, redirectUrl) {
                if (completed) {
                    return;
                }

                statusTitle.textContent = title;
                statusMessage.textContent = message;
                statusBox.classList.add('show');

                if (redirectUrl) {
                    completed = true;

                    if (statusTimer) {
                        window.clearInterval(statusTimer);
                    }

                    window.setTimeout(function () {
                        window.location.href = redirectUrl;
                    }, 3000);
                }
            }

            function pollOrderStatus() {
                if (polling) {
                    return;
                }

                polling = true;

                statusTimer = window.setInterval(function () {
                    fetch(STATUS_URL, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            if (! response.ok) {
                                throw new Error('status_unavailable');
                            }

                            return response.json();
                        })
                        .then(function (payload) {
                            if (payload.order_status === 'processing' || payload.order_status === 'completed') {
                                showResult(
                                    'Pagamento confirmado',
                                    'O pagamento foi confirmado com sucesso.',
                                    SUCCESS_URL
                                );

                                return;
                            }

                            if (payload.order_status === 'canceled') {
                                showResult(
                                    'Pagamento nao aprovado',
                                    'O pagamento nao foi aprovado. Pode tentar novamente.',
                                    CANCEL_URL
                                );
                            }
                        })
                        .catch(function () {});
                }, 3000);
            }

            window.addEventListener('resize', scaleFrame);

            frame.addEventListener('load', function () {
                scaleFrame();
                loader.classList.add('gone');
                pollOrderStatus();
            });

            window.addEventListener('message', function (event) {
                if (event.origin.indexOf('pagamentonline.emis.co.ao') === -1 || processed) {
                    return;
                }

                processed = true;

                var data = event.data;
                var status = 'PENDING_WEBHOOK';

                if (typeof data === 'object' && data !== null && data.status) {
                    status = String(data.status).toUpperCase();
                }

                if (status === 'SUCCESS' || status === 'ACCEPTED') {
                    showResult(
                        'A verificar pagamento',
                        'Estamos a aguardar a confirmacao segura da EMIS.',
                        null
                    );

                    pollOrderStatus();

                    return;
                }

                if (status === 'REJECTED' || status === 'FAILED') {
                    showResult(
                        'A verificar pagamento',
                        'Estamos a aguardar a confirmacao final da EMIS.',
                        null
                    );

                    pollOrderStatus();

                    return;
                }

                showResult(
                    'A verificar pagamento',
                    'Estamos a aguardar a confirmacao segura da EMIS.',
                    null
                );

                pollOrderStatus();
            });

            scaleFrame();
            pollOrderStatus();
        })();
    </script>
</body>
</html>
