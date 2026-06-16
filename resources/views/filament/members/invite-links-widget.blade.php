<div>
    @if(count($invitations) > 0)
        <x-filament::section :heading="__('competition.invite_links_active')">
            <div class="space-y-3">
                @foreach($invitations as $inv)
                    <div class="flex items-center gap-3">
                        <span class="font-mono break-all text-sm flex-1 text-gray-700 dark:text-gray-300">{{ $inv['url'] }}</span>
                        <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ __('competition.invite_expires') }}: {{ $inv['expires_at'] }}
                        </span>
                        <div x-data="{ copied: false }" class="shrink-0">
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText('{{ $inv['url'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                x-text="copied ? '{{ __('competition.invite_copied') }}' : '{{ __('competition.invite_copy') }}'"
                                class="shrink-0 inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 transition-colors"
                            ></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</div>
