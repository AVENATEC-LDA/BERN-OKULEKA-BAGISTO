<!--
    This code needs to be refactored to reduce the amount of PHP in the Blade
    template as much as possible.
-->
@php
    $showCompare = (bool) core()->getConfigData('catalog.products.settings.compare_option');

    $showWishlist = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');
@endphp

<div class="flex flex-wrap gap-4 px-4 pb-4 pt-6 shadow-sm lg:hidden">
    <div class="flex w-full items-center justify-between">
        <!-- Left Navigation -->
        <div class="flex items-center gap-x-1.5">
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.before') !!}

            <!-- Drawer -->
            <v-mobile-drawer></v-mobile-drawer>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.before') !!}

            <a
                href="{{ route('shop.home.index') }}"
                class="max-h-[30px]"
                aria-label="@lang('shop::app.components.layouts.header.mobile.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.after') !!}
        </div>

        <!-- Right Navigation -->
        <div>
            <div class="flex items-center gap-x-5 max-md:gap-x-4">
                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.before') !!}

                @if($showCompare)
                    <a
                        href="{{ route('shop.compare.index') }}"
                        aria-label="@lang('shop::app.components.layouts.header.mobile.compare')"
                    >
                        <span class="icon-compare cursor-pointer text-2xl"></span>
                    </a>
                @endif

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.after') !!}

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.before') !!}

                @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                    @include('shop::checkout.cart.mini-cart')
                @endif

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.after') !!}

                <!-- For Large screens -->
                <div class="max-md:hidden">
                    <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                        <x-slot:toggle>
                            <span class="icon-users cursor-pointer text-2xl"></span>
                        </x-slot>

                        <!-- Guest Dropdown -->
                        @guest('customer')
                            <x-slot:content>
                                <div class="grid gap-2.5">
                                    <p class="font-dmserif text-xl">
                                        @lang('shop::app.components.layouts.header.mobile.welcome-guest')
                                    </p>

                                    <p class="text-sm">
                                        @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                    </p>
                                </div>

                                <p class="mt-3 w-full border border-zinc-200"></p>

                                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.before') !!}

                                <div class="mt-6 flex gap-4">
                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.before') !!}

                                    <a
                                        href="{{ route('shop.customer.session.create') }}"
                                        class="m-0 mx-auto block w-max cursor-pointer rounded-2xl bg-navyBlue px-7 py-4 text-center text-base font-medium text-white ltr:ml-0 rtl:mr-0"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.sign-in')
                                    </a>

                                    <a
                                        href="{{ route('shop.customers.register.index') }}"
                                        class="m-0 mx-auto block w-max cursor-pointer rounded-2xl border-2 border-navyBlue bg-white px-7 py-3.5 text-center text-base font-medium text-navyBlue ltr:ml-0 rtl:mr-0"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.sign-up')
                                    </a>

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.after') !!}
                                </div>

                                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.after') !!}
                            </x-slot>
                        @endguest

                        <!-- Customers Dropdown -->
                        @auth('customer')
                            <x-slot:content class="!p-0">
                                <div class="grid gap-2.5 p-5 pb-0">
                                    <p class="font-dmserif text-xl">
                                        @lang('shop::app.components.layouts.header.mobile.welcome')’
                                        {{ auth()->guard('customer')->user()->first_name }}
                                    </p>

                                    <p class="text-sm">
                                        @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                    </p>
                                </div>

                                <p class="mt-3 w-full border border-zinc-200"></p>

                                <div class="mt-2.5 grid gap-1 pb-2.5">
                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.before') !!}

                                    <a
                                        class="cursor-pointer px-5 py-2 text-base"
                                        href="{{ route('shop.customers.account.profile.index') }}"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.profile')
                                    </a>

                                    <a
                                        class="cursor-pointer px-5 py-2 text-base"
                                        href="{{ route('shop.customers.account.orders.index') }}"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.orders')
                                    </a>

                                    @if ($showWishlist)
                                        <a
                                            class="cursor-pointer px-5 py-2 text-base"
                                            href="{{ route('shop.customers.account.wishlist.index') }}"
                                        >
                                            @lang('shop::app.components.layouts.header.mobile.wishlist')
                                        </a>
                                    @endif

                                    <!--Customers logout-->
                                    @auth('customer')
                                        <x-shop::form
                                            method="DELETE"
                                            action="{{ route('shop.customer.session.destroy') }}"
                                            id="customerLogout"
                                        />

                                        <a
                                            class="cursor-pointer px-5 py-2 text-base"
                                            href="{{ route('shop.customer.session.destroy') }}"
                                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                        >
                                            @lang('shop::app.components.layouts.header.mobile.logout')
                                        </a>
                                    @endauth

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.after') !!}
                                </div>
                            </x-slot>
                        @endauth
                    </x-shop::dropdown>
                </div>

                <!-- For Medium and small screen -->
                <div class="md:hidden">
                    @guest('customer')
                        <a
                            href="{{ route('shop.customer.session.create') }}"
                            aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                        >
                            <span class="icon-users cursor-pointer text-2xl"></span>
                        </a>
                    @endguest

                    <!-- Customers Dropdown -->
                    @auth('customer')
                        <a
                            href="{{ route('shop.customers.account.index') }}"
                            aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                        >
                            <span class="icon-users cursor-pointer text-2xl"></span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.before') !!}

    @if (core()->getConfigData('suggestion.suggestion.general.status'))
        <v-suggestion-searchbar-mobile></v-suggestion-searchbar-mobile>
    @else
        <!-- Serach Catalog Form -->
        <form action="{{ route('shop.search.index') }}" class="flex w-full items-center">
            <label
                for="organic-search"
                class="sr-only"
            >
                @lang('shop::app.components.layouts.header.mobile.search')
            </label>

            <div class="relative w-full">
                <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>

                <input
                    type="text"
                    class="block w-full rounded-xl border border-['#E3E3E3'] px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                    name="query"
                    value="{{ request('query') }}"
                    placeholder="@lang('shop::app.components.layouts.header.mobile.search-text')"
                    required
                >

                @if (core()->getConfigData('catalog.products.settings.image_search'))
                    @include('shop::search.images.index')
                @endif
            </div>
        </form>
    @endif

    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.after') !!}
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-mobile-drawer-template">
        <x-shop::drawer
            position="left"
            width="100%"
            @close="onDrawerClose"
        >
            <x-slot:toggle>
                <span class="icon-hamburger cursor-pointer text-2xl"></span>
            </x-slot>

            <x-slot:header>
                <div class="flex items-center justify-between">
                    <a href="{{ route('shop.home.index') }}">
                        <img
                            src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                            alt="{{ config('app.name') }}"
                            width="131"
                            height="29"
                        >
                    </a>
                </div>
            </x-slot>

            <x-slot:content class="!p-0">
                <!-- Account Profile Hero Section -->
                <div class="border-b border-zinc-200 p-4">
                    <div class="grid grid-cols-[auto_1fr] items-center gap-4 rounded-xl border border-zinc-200 p-2.5">
                        <div>
                            <img
                                src="{{ auth()->user()?->image_url ??  bagisto_asset('images/user-placeholder.png') }}"
                                class="h-[60px] w-[60px] rounded-full max-md:rounded-full"
                            >
                        </div>

                        @guest('customer')
                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="flex text-base font-medium"
                            >
                                @lang('shop::app.components.layouts.header.mobile.login')

                                <i class="icon-double-arrow text-2xl ltr:ml-2.5 rtl:mr-2.5"></i>
                            </a>
                        @endguest

                        @auth('customer')
                            <div class="flex flex-col justify-between gap-2.5 max-md:gap-0">
                                <p class="font-mediums break-all text-2xl max-md:text-xl">Hello! {{ auth()->user()?->first_name }}</p>

                                <p class="text-zinc-500 no-underline max-md:text-sm">{{ auth()->user()?->email }}</p>
                            </div>
                        @endauth
                    </div>
                </div>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.before') !!}

                <!-- Mobile category view -->
                <v-mobile-category ref="mobileCategory"></v-mobile-category>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.after') !!}
            </x-slot>

            <x-slot:footer>
                <!-- Localization & Currency Section -->
                @if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 )
                    <div class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-zinc-200 bg-white px-5 ltr:left-0 rtl:right-0">
                        <!-- Filter Drawer -->
                        <x-shop::drawer
                            position="bottom"
                            width="100%"
                        >
                            <!-- Drawer Toggler -->
                            <x-slot:toggle>
                                <div
                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                    role="button"
                                >
                                    {{ core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode() }}
                                </div>
                            </x-slot>

                            <!-- Drawer Header -->
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">
                                        @lang('shop::app.components.layouts.header.mobile.currencies')
                                    </p>
                                </div>
                            </x-slot>

                            <!-- Drawer Content -->
                            <x-slot:content class="!px-0">
                                <div
                                    class="overflow-auto"
                                    :style="{ height: getCurrentScreenHeight }"
                                >
                                    <v-currency-switcher></v-currency-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>

                        <!-- Seperator -->
                        <span class="h-5 w-0.5 bg-zinc-200"></span>

                        <!-- Sort Drawer -->
                        <x-shop::drawer
                            position="bottom"
                            width="100%"
                        >
                            <!-- Drawer Toggler -->
                            <x-slot:toggle>
                                <div
                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                    role="button"
                                >
                                    <img
                                        src="{{ ! empty(core()->getCurrentLocale()->logo_url)
                                                ? core()->getCurrentLocale()->logo_url
                                                : bagisto_asset('images/default-language.svg')
                                            }}"
                                        class="h-full"
                                        alt="Default locale"
                                        width="24"
                                        height="16"
                                    />

                                    {{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}
                                </div>
                            </x-slot>

                            <!-- Drawer Header -->
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">
                                        @lang('shop::app.components.layouts.header.mobile.locales')
                                    </p>
                                </div>
                            </x-slot>

                            <!-- Drawer Content -->
                            <x-slot:content class="!px-0">
                                <div
                                    class="overflow-auto"
                                    :style="{ height: getCurrentScreenHeight }"
                                >
                                    <v-locale-switcher></v-locale-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>
                    </div>
                @endif
            </x-slot>
        </x-shop::drawer>
    </script>

    <script
        type="text/x-template"
        id="v-mobile-category-template"
    >
        <!-- Wrapper with transition effects -->
        <div class="relative h-full overflow-hidden">
            <!-- Sliding container -->
            <div
                class="flex h-full transition-transform duration-300"
                :class="{
                    'ltr:translate-x-0 rtl:translate-x-0': currentViewLevel !== 'third',
                    'ltr:-translate-x-full rtl:translate-x-full': currentViewLevel === 'third'
                }"
            >
                <!-- First level view -->
                <div class="h-full w-full flex-shrink-0 overflow-auto px-6">
                    <div class="py-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            :class="{'mb-2': category.children && category.children.length}"
                        >
                            <div class="flex cursor-pointer items-center justify-between py-2 transition-colors duration-200">
                                <a :href="category.url" class="text-base font-medium text-black">
                                    @{{ category.name }}
                                </a>
                            </div>

                            <!-- Second Level Categories -->
                            <div v-if="category.children && category.children.length" >
                                <div
                                    v-for="secondLevelCategory in category.children"
                                    :key="secondLevelCategory.id"
                                >
                                    <div
                                        class="flex cursor-pointer items-center justify-between py-2 transition-colors duration-200"
                                        @click="showThirdLevel(secondLevelCategory, category, $event)"
                                    >
                                        <a :href="secondLevelCategory.url" class="text-sm font-normal">
                                            @{{ secondLevelCategory.name }}
                                        </a>

                                        <span
                                            v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                            class="icon-arrow-right rtl:icon-arrow-left"
                                        ></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Third level view -->
                <div
                    class="h-full w-full flex-shrink-0"
                    v-if="currentViewLevel === 'third'"
                >
                    <div class="border-b border-gray-200 px-6 py-4">
                        <button
                            @click="goBackToMainView"
                            class="flex items-center justify-center gap-2 focus:outline-none"
                            aria-label="Go back"
                        >
                            <span class="icon-arrow-left rtl:icon-arrow-right text-lg"></span>
                            <div class="text-base font-medium text-black">
                                @lang('shop::app.components.layouts.header.mobile.back-button')
                            </div>
                        </button>
                    </div>

                    <!-- Third Level Content -->
                    <div class="px-6 py-4">
                        <div
                            v-for="thirdLevelCategory in currentSecondLevelCategory?.children"
                            :key="thirdLevelCategory.id"
                            class="mb-2"
                        >
                            <a
                                :href="thirdLevelCategory.url"
                                class="block py-2 text-sm transition-colors duration-200"
                            >
                                @{{ thirdLevelCategory.name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mobile-category', {
            template: '#v-mobile-category-template',

            data() {
                return  {
                    categories: [],
                    currentViewLevel: 'main',
                    currentSecondLevelCategory: null,
                    currentParentCategory: null
                }
            },

            mounted() {
                this.getCategories();
            },

            computed: {
                getCurrentScreenHeight() {
                    return window.innerHeight - (window.innerWidth < 920 ? 61 : 0) + 'px';
                },
            },

            methods: {
                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.categories = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                showThirdLevel(secondLevelCategory, parentCategory, event) {
                    if (secondLevelCategory.children && secondLevelCategory.children.length) {
                        this.currentSecondLevelCategory = secondLevelCategory;
                        this.currentParentCategory = parentCategory;
                        this.currentViewLevel = 'third';

                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }
                },

                goBackToMainView() {
                    this.currentViewLevel = 'main';
                }
            },
        });

        app.component('v-mobile-drawer', {
            template: '#v-mobile-drawer-template',

            methods: {
                onDrawerClose() {
                    this.$refs.mobileCategory.currentViewLevel = 'main';
                }
            },
        });
    </script>
@endPushOnce

@if (core()->getConfigData('suggestion.suggestion.general.status'))
    @pushOnce('scripts')
        <script type="text/x-template" id="v-suggestion-searchbar-mobile-template">
            <form
                action="{{ route('shop.search.index') }}"
                class="mb-4 flex w-full items-center"
            >
                <label
                    for="organic-search"
                    class="sr-only"
                >
                    @lang('shop::app.components.layouts.header.mobile.search')
                </label>

                <div class="relative w-full">
                    <div class="icon-search pointer-events-none absolute left-3 top-3 flex items-center text-[25px]">
                    </div>

                    <input
                        type="text"
                        name="query"
                        value="{{ request('query') }}"
                        class="block w-full rounded-xl border border-['#E3E3E3'] px-11 py-3.5 text-xs font-medium text-gray-900"
                        placeholder="@lang('shop::app.components.layouts.header.mobile.search-text')"
                        aria-label="@lang('shop::app.components.layouts.header.mobile.search-text')"
                        aria-required="true"
                        v-model="term"
                        autocomplete="off"
                        @blur="handleBlur"
                        @focus="handleFocus"
                        @keyup="search()"
                        required
                    >
                    <button
                        type="submit"
                        class="hidden"
                        aria-label="@lang('shop::app.components.layouts.header.mobile.search')"
                    >
                    </button>

                    <div
                        class="absolute z-[70] mt-2 max-h-[calc(100vh-180px)] w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl"
                        v-if="term.length >= config.minSearchTerms && showDropdown"
                    >
                        <div
                            class="flex max-h-[calc(100vh-180px)] flex-col"
                            :class="config.display === 'ar' ? 'ar' : ''"
                            v-if="suggestsResults.length"
                        >
                            <div
                                v-for="(result, index) in suggestsResults"
                                :key="'suggestion-mobile-' + result.id"
                            >
                                <div v-if="index < config.noOfTerms">
                                    <a
                                        :href="result.url_key"
                                        class="flex gap-3 border-b border-zinc-100 bg-white p-3 text-sm transition hover:bg-zinc-50"
                                    >
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded border border-zinc-200 bg-zinc-50">
                                            <img
                                                v-if="getProductImage(result)"
                                                :src="getProductImage(result)"
                                                :alt="stripHtml(result.name)"
                                                class="h-full w-full object-cover"
                                            >

                                            <span
                                                v-else
                                                class="icon-search text-xl text-zinc-500"
                                            ></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                :class="config.display === 'ar' ? 'mr-1' : ''"
                                                class="break-words text-sm font-medium leading-5 text-zinc-900"
                                                v-html="result.highlightedName"
                                            >
                                            </p>

                                            @if (core()->getConfigData('suggestion.suggestion.general.show_categories'))
                                                <p
                                                    v-if="getCategoryNames(result).length"
                                                    class="mt-1 break-words text-xs leading-5 text-zinc-500"
                                                >
                                                    @lang('suggestion::app.shop.search-suggestion.in')

                                                    <span class="font-medium text-zinc-700">
                                                        @{{ getCategoryNames(result).join(', ') }}
                                                    </span>
                                                </p>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            </div>

                            @if(core()->getConfigData('suggestion.suggestion.general.show_searched_terms'))
                                <a :href="'{{ route('shop.search.index') }}?query=' + encodeURIComponent(term) + '&sort=price-desc&limit=12&mode=grid'">
                                    <div class="h-9 border border-blue-100 bg-white p-2 hover:border-red-100 hover:bg-gray-200">
                                        <div v-if="config.display === 'ar'">
                                            @{{ term }}
                                            
                                            <span class="float-left">
                                                @{{ suggestsResults.length }}
                                            </span>
                                        </div>

                                        <p v-else>
                                            @{{ term }}

                                            <span class="float-right ltr:mr-1 rtl:ml-1">
                                                @{{ suggestsResults.length }}
                                            </span>
                                        </p>
                                    </div>
                                </a>
                            @endif

                        </div>

                        <div
                            class="border bg-white p-3 text-sm text-zinc-600"
                            :class="config.display === 'ar' ? 'ar' : ''"
                            v-if="isSearching"
                        >
                            <p>
                                @lang('suggestion::app.shop.search-suggestion.searching')
                            </p>
                        </div>
                        <div
                            class="border bg-white p-3 text-sm text-zinc-600"
                            :class="config.display === 'ar' ? 'ar' : ''"
                            v-if="! isSearching && ! suggestsResults.length"
                        >
                            <p>
                                @lang('suggestion::app.shop.search-suggestion.no-results')
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </script>

        <script type="module">
            app.component('v-suggestion-searchbar-mobile', {
                template: '#v-suggestion-searchbar-mobile-template',

                data() {
                    return {
                        term: '',
                        isSearching: false,
                        suggestsResults: [],
                        showDropdown: false,
                        blurTimeout: null,
                        config: {
                            noOfTerms: Number("{{ core()->getConfigData('suggestion.suggestion.general.products_limit') }}") || 5,
                            minSearchTerms: Number("{{ core()->getConfigData('suggestion.suggestion.general.min_search_terms') }}") || 1,
                            display: "{{ core()->getCurrentLocale()->code }}",
                        },
                    };
                },

                methods: {
                    search() {
                        if (this.term.length < this.config.minSearchTerms) {
                            this.suggestsResults = [];

                            return;
                        }

                        this.isSearching = true;

                        this.$axios.get("{{ route('search_suggestion.search.index') }}", {
                                params: { term: this.term },
                            })
                            .then(response => {
                                this.handleResponse(response.data);
                            })
                            .catch(() => {
                                this.suggestsResults = [];
                                this.isSearching = false;
                            });
                    },

                    handleResponse(data) {
                        const searchTerm = this.term.toLowerCase();

                        this.suggestsResults = (data.data || []).map(result => ({
                            ...result,
                            highlightedName: this.highlightMatch(this.stripHtml(result.name), searchTerm),
                        }));

                        this.isSearching = false;
                    },

                    highlightMatch(text, searchTerm) {
                        const safe = this.escapeHtml(text);

                        if (! searchTerm) {
                            return safe;
                        }

                        const lower = text.toLowerCase();
                        const index = lower.indexOf(searchTerm);

                        if (index === -1) {
                            return safe;
                        }

                        const before = this.escapeHtml(text.slice(0, index));
                        const match  = this.escapeHtml(text.slice(index, index + searchTerm.length));
                        const after  = this.escapeHtml(text.slice(index + searchTerm.length));

                        return `${before}<span class="font-semibold">${match}</span>${after}`;
                    },

                    escapeHtml(value) {
                        return String(value ?? '').replace(/[&<>"']/g, c => ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#39;',
                        }[c]));
                    },

                    getProductImage(result) {
                        return result.images?.find(image => image.url)?.url || '';
                    },

                    getCategoryNames(result) {
                        return (result.categories || [])
                            .map(category => category.name)
                            .filter(Boolean);
                    },

                    stripHtml(value) {
                        const element = document.createElement('div');
                        element.innerHTML = value || '';
                        return element.textContent || element.innerText || '';
                    },

                    handleBlur() {
                        this.blurTimeout = setTimeout(() => {
                            this.showDropdown = false;
                        }, 200);
                    },

                    handleFocus() {
                        if (this.blurTimeout) {
                            clearTimeout(this.blurTimeout);
                        }

                        if (this.term.length >= this.config.minSearchTerms) {
                            this.showDropdown = true;
                        }
                    },
                },

                watch: {
                    term(newVal) {
                        this.showDropdown = newVal.length >= this.config.minSearchTerms;
                    },
                },
            });
        </script>
    @endPushOnce
@endif
