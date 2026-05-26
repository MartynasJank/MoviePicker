<div id="case-overlay" class="hidden fixed inset-0 z-50 flex flex-col items-center justify-center" style="background:rgba(0,0,0,0.88);backdrop-filter:blur(8px)">

    {{-- Sunburst rays (behind everything) --}}
    <div id="case-rays" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0" style="width:900px;height:900px;z-index:1">
        <div id="case-rays-inner" style="width:100%;height:100%;border-radius:50%"></div>
    </div>
    {{-- Central radial glow --}}
    <div id="case-glow" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-0" style="width:320px;height:320px;z-index:2"></div>

    <p class="text-gray-500 text-xs uppercase tracking-widest mb-8" style="position:relative;z-index:20">Good luck...</p>

    <div id="case-viewport" class="relative w-full overflow-hidden" style="height:220px;z-index:20">
        {{-- Fade edges --}}
        <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-black to-transparent z-10 pointer-events-none"></div>
        <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-black to-transparent z-10 pointer-events-none"></div>
        {{-- Center indicator --}}
        <div class="absolute inset-y-0 left-1/2 -translate-x-px w-0.5 bg-accent z-10" style="box-shadow:0 0 14px 3px rgba(192,57,58,0.75)"></div>
        {{-- Card strip --}}
        <div id="case-strip" class="absolute top-3 flex" style="gap:8px"></div>
    </div>

    <div class="mt-8 text-center" style="position:relative;z-index:20">
        <p id="case-winner-tier" class="text-xs font-bold uppercase tracking-widest mb-2 transition-opacity duration-300 opacity-0"></p>
        <p id="case-winner-title" class="text-white font-bold text-2xl px-6 transition-opacity duration-500 opacity-0 max-w-lg"></p>
    </div>
</div>
