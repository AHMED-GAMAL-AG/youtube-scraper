<div class="col-lg-3 col-md-6">
    <div class="playlist-card">
        <div class="card-thumb">
            <img src="{{ $playlist->thumbnail }}" alt="{{ $playlist->title }}" loading="lazy">
            <span class="badge-video-count">{{ $playlist->video_count }} درس</span>
            <span class="badge-duration">{{ $playlist->total_duration }}</span>
        </div>
        <div class="card-body">
            <h6 class="card-title">{{ $playlist->title }}</h6>
            <p class="card-channel"><i class="bi bi-music-note-beamed"></i> {{ $playlist->channel_name }}</p>
            <div class="card-footer-row">
                <span class="view-count">
                    @if($playlist->view_count >= 1000000)
                        {{ number_format($playlist->view_count / 1000000, 1) }}M
                    @elseif($playlist->view_count >= 1000)
                        {{ number_format($playlist->view_count / 1000, 0) }}K
                    @else
                        {{ $playlist->view_count }}
                    @endif
                    مشاهدة
                </span>
                @php
                    $tagClass = match($playlist->category) {
                        'التسويق' => 'tag-marketing',
                        'البرمجة' => 'tag-programming',
                        'الجرافيكس' => 'tag-graphics',
                        'الهندسة' => 'tag-engineering',
                        'إدارة الأعمال' => 'tag-business',
                        default => 'tag-default',
                    };
                @endphp
                <span class="category-tag {{ $tagClass }}">{{ $playlist->category }}</span>
            </div>
        </div>
    </div>
</div>
