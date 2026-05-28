@if(!empty($reviews) && count($reviews) > 0)
<div class="mb-10">
    <div class="section-header">
        <h2 class="text-xl font-bold text-white mb-3">Reviews</h2>
        <div class="section-divider"></div>
    </div>
    <div class="flex flex-col gap-4 mt-4">
        @foreach($reviews as $review)
        @php
            $review    = (object) $review;
            $details   = (object) ($review->author_details ?? []);
            $rating    = $details->rating ?? null;
            $avatar    = $details->avatar_path ?? null;
            $avatarUrl = null;
            if ($avatar) {
                $avatarUrl = str_starts_with($avatar, '/https')
                    ? ltrim($avatar, '/')
                    : 'https://image.tmdb.org/t/p/w45' . $avatar;
            }
            $body     = $review->content ?? '';
            $truncate = mb_strlen($body) > 280;
            $excerpt  = $truncate ? mb_substr($body, 0, 280) . '…' : $body;
            $date     = !empty($review->created_at)
                ? \Carbon\Carbon::parse($review->created_at)->format('M Y')
                : null;
        @endphp
        <div class="card p-4">
            <div class="flex items-center gap-3 mb-3">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                @else
                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-xs font-bold text-gray-400 flex-shrink-0">
                        {{ strtoupper(substr($review->author ?? '?', 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-white">{{ $review->author ?? 'Anonymous' }}</span>
                    @if($date)
                        <span class="text-xs text-gray-600 ml-2">{{ $date }}</span>
                    @endif
                </div>
                @if($rating)
                    <span class="text-xs font-semibold text-accent flex-shrink-0">{{ $rating }}/10</span>
                @endif
            </div>
            <p class="text-sm text-gray-300 leading-relaxed">{{ $excerpt }}</p>
            @if($truncate)
                <a href="{{ $review->url ?? '#' }}" target="_blank"
                   class="text-xs text-gray-500 hover:text-accent transition-colors mt-2 inline-block">
                    Read full review →
                </a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
