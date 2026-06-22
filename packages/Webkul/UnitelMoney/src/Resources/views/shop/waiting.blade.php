<x-shop::layouts>
    <x-slot:title>
        @lang('unitel_money::app.shop.waiting.title')
    </x-slot:title>

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

            <a href="{{ route('shop.checkout.cart.index') }}" class="primary-button mt-6 inline-flex">
                @lang('unitel_money::app.shop.waiting.back-to-cart')
            </a>
        </div>
    </div>
</x-shop::layouts>
