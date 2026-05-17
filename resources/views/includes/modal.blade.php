<div id="trailer-modal" class="modal-wrap hidden">
    <div class="modal-backdrop"></div>
    <div class="relative z-10 w-full max-w-3xl mx-4">
        <button id="trailer-modal-close" class="modal-close" aria-label="Close">✕</button>
        <div class="bg-[#111] border border-white/10 rounded-xl overflow-hidden shadow-2xl">
            <div class="relative w-full" style="padding-bottom:56.25%">
                <iframe
                    id="trailer"
                    class="absolute inset-0 w-full h-full"
                    src="https://www.youtube.com/embed/{{ $trailer }}"
                    title="Movie Trailer"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
</div>
