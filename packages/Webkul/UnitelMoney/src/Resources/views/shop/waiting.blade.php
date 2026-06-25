<x-shop::layouts>
    <x-slot:title>
        @lang('unitel_money::app.shop.waiting.title')
    </x-slot:title>

    @php
        $isProcessing = $order->status === 'processing';
    @endphp

    <div class="container mt-10 max-lg:px-8 max-sm:mt-6">
        <div class="mx-auto max-w-[720px] rounded bg-white p-6 shadow">
            <h1 class="text-2xl font-semibold text-gray-900">
                @lang('unitel_money::app.shop.waiting.title')
            </h1>

            <p class="mt-3 text-base text-gray-700">
                @lang('unitel_money::app.shop.waiting.message')
            </p>

            <p class="mt-4 text-sm text-gray-600">
                @lang('unitel_money::app.shop.waiting.order', ['order' => $order->increment_id])
            </p>

            <p class="mt-4 text-sm text-amber-600" id="unitel-status-message">
                @if ($isProcessing)
                    Pagamento já confirmado. A redirecionar para a página de sucesso...
                @else
                    Aguardando confirmação do pagamento via Unitel Money. O estado será verificado automaticamente.
                @endif
            </p>

            @if (! $isProcessing)
                <div class="mt-6" id="unitel-store-return">
                    <a href="{{ route('shop.home.index') }}" class="primary-button inline-flex">
                        Voltar para a loja
                    </a>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script type="module">
            const orderId = {{ $order->id }};
            const statusMessage = document.getElementById('unitel-status-message');
            const storeReturn = document.getElementById('unitel-store-return');
            let polling = true;

            if (storeReturn) {
                storeReturn.style.display = 'inline-flex';
            }

            const pollStatus = () => {
                if (! polling) {
                    return;
                }

                fetch('{{ route('unitel-money.query-status', ['orderId' => $order->id]) }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => null);

                        if (response.ok && data?.status === 'ok') {
                            statusMessage.textContent = 'Pagamento confirmado. A redirecionar para a página de sucesso...';

                            if (storeReturn) {
                                storeReturn.style.display = 'none';
                            }

                            window.setTimeout(() => window.location.href = '{{ route('shop.checkout.onepage.success') }}', 1200);

                            polling = false;

                            return;
                        }

                        if (statusMessage) {
                            statusMessage.textContent = 'Ainda estamos à espera da confirmação do pagamento via Unitel Money...';
                        }

                        if (storeReturn) {
                            storeReturn.style.display = 'inline-flex';
                        }
                    })
                    .catch(() => {
                        if (statusMessage) {
                            statusMessage.textContent = 'Ainda estamos a verificar o estado do pagamento...';
                        }
                    });
            };

            window.setTimeout(pollStatus, 5000);
            window.setInterval(pollStatus, 15000);
        </script>
    @endpush
</x-shop::layouts>
