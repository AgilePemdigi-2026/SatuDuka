<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-line']) }}>
    <table
        class="min-w-full divide-y divide-line text-left text-sm [&_td]:px-4 [&_td]:py-3 [&_th]:px-4 [&_th]:py-3 [&_th]:text-xs [&_th]:font-bold [&_th]:uppercase [&_th]:tracking-wider [&_th]:text-ink-soft">
        <thead class="bg-[#e8edf4]">
            <tr>
                {{ $head ?? '' }}
            </tr>
        </thead>
        <tbody class="divide-y divide-line bg-surface [&>tr:hover]:bg-canvas">
            {{ $slot }}
        </tbody>
    </table>
</div>
