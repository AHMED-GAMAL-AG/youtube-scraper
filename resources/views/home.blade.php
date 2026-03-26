@extends('layouts.app')

@section('content')
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">جمع الدورات التعليمية من يوتيوب</h1>
        <p class="hero-subtitle">أدخل التصنيفات واضغط ابدأ — النظام سيجمع الدورات تلقائياً باستخدام الذكاء الاصطناعي</p>

        <div class="hero-card">
            <div class="row g-4">
                <div class="col-md-7">
                    <label class="form-label text-muted">أدخل التصنيفات (كل تصنيف في سطر جديد)</label>
                    <textarea id="categoriesInput" class="form-control" rows="6" placeholder="التسويق&#10;البرمجة&#10;الجرافيكس&#10;الهندسة&#10;إدارة الأعمال"></textarea>
                </div>
                <div class="col-md-5 d-flex flex-column justify-content-end">
                    <button id="startBtn" class="btn btn-start mb-3" onclick="startFetching()">
                        <i class="bi bi-play-fill me-1"></i> ابدأ الجمع
                    </button>
                    <div class="form-check stop-check">
                        <input class="form-check-input" type="checkbox" id="stopCheck" onchange="toggleStop()">
                        <label class="form-check-label" for="stopCheck">إيقاف</label>
                    </div>
                </div>
            </div>

            <div id="progressArea" class="mt-4 d-none">
                <div class="progress" style="height: 8px;">
                    <div id="progressBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-muted mt-2 mb-0 small text-center"></p>
            </div>
        </div>
    </div>
</section>

<section class="courses-section py-5">
    <div class="container">
        <div class="courses-header mb-4">
            <h2 class="courses-title">الدورات المكتشفة</h2>
            <p class="courses-subtitle" id="coursesCount">
                تم العثور على <strong>{{ $totalCount }}</strong> دورة في <strong>{{ count($categories) }}</strong> تصنيفات
            </p>
        </div>

        <div class="category-tabs mb-4" id="categoryTabs">
            <button class="cat-tab active" data-category="all" onclick="filterByCategory('all', this)">
                الكل (<span class="cat-count">{{ $totalCount }}</span>)
            </button>
            @foreach($categories as $cat => $count)
                <button class="cat-tab" data-category="{{ $cat }}" onclick="filterByCategory('{{ $cat }}', this)">
                    {{ $cat }} (<span class="cat-count">{{ $count }}</span>)
                </button>
            @endforeach
        </div>

        <div class="row g-4" id="playlistsGrid">
            @foreach($playlists as $playlist)
                @include('partials.playlist-card', ['playlist' => $playlist])
            @endforeach
        </div>

        <div id="paginationArea" class="d-flex justify-content-center mt-5">
            {{ $playlists->links('partials.pagination') }}
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    let currentJobId = null;
    let pollingInterval = null;
    let currentCategory = 'all';
    let currentPage = 1;

    function startFetching() {
        const categories = document.getElementById('categoriesInput').value.trim();
        if (!categories) {
            alert('يرجى إدخال تصنيف واحد على الأقل');
            return;
        }

        const btn = document.getElementById('startBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري البدء...';

        document.getElementById('progressArea').classList.remove('d-none');
        document.getElementById('stopCheck').checked = false;

        fetch('/fetch/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ categories }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                resetButton();
                return;
            }
            currentJobId = data.id;
            startPolling();
        })
        .catch(err => {
            console.error(err);
            alert('حدث خطأ أثناء بدء العملية');
            resetButton();
        });
    }

    function startPolling() {
        pollingInterval = setInterval(() => {
            if (!currentJobId) return;

            fetch(`/fetch/${currentJobId}/status`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('progressBar').style.width = data.progress + '%';
                    document.getElementById('progressText').textContent =
                        data.current_step + ' — تم العثور على ' + data.total_found + ' دورة';

                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                        resetButton();
                        if (data.status === 'completed') {
                            document.getElementById('progressBar').style.width = '100%';
                            loadPlaylists();
                        }
                    }
                });
        }, 2000);
    }

    function toggleStop() {
        if (!currentJobId) return;
        const checked = document.getElementById('stopCheck').checked;
        if (checked) {
            fetch(`/fetch/${currentJobId}/stop`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
        }
    }

    function resetButton() {
        const btn = document.getElementById('startBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-fill me-1"></i> ابدأ الجمع';
    }

    function filterByCategory(category, el) {
        currentCategory = category;
        currentPage = 1;

        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');

        loadPlaylists();
    }

    function loadPlaylists(page) {
        if (page) currentPage = page;

        let url = `/api/playlists?page=${currentPage}`;
        if (currentCategory !== 'all') {
            url += `&category=${encodeURIComponent(currentCategory)}`;
        }

        fetch(url)
            .then(r => r.json())
            .then(data => {
                renderPlaylists(data.playlists);
                updateCategoryTabs(data.categories, data.totalCount);
            });
    }

    function renderPlaylists(paginatedData) {
        const grid = document.getElementById('playlistsGrid');
        const items = paginatedData.data;

        if (items.length === 0) {
            grid.innerHTML = '<div class="col-12 text-center text-muted py-5"><h5>لا توجد دورات بعد</h5></div>';
            document.getElementById('paginationArea').innerHTML = '';
            return;
        }

        grid.innerHTML = items.map(p => `
            <div class="col-lg-3 col-md-6">
                <div class="playlist-card">
                    <div class="card-thumb">
                        <img src="${p.thumbnail}" alt="${p.title}" loading="lazy">
                        <span class="badge-video-count">${p.video_count} درس</span>
                        <span class="badge-duration">${p.total_duration || ''}</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">${p.title}</h6>
                        <p class="card-channel"><i class="bi bi-music-note-beamed"></i> ${p.channel_name}</p>
                        <div class="card-footer-row">
                            <span class="view-count">${formatViews(p.view_count)} مشاهدة</span>
                            <span class="category-tag tag-${getCategoryClass(p.category)}">${p.category}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        renderPagination(paginatedData);
    }

    function renderPagination(data) {
        const area = document.getElementById('paginationArea');
        if (data.last_page <= 1) { area.innerHTML = ''; return; }

        let html = '<nav><ul class="pagination-custom">';

        html += `<li class="${data.current_page === 1 ? 'disabled' : ''}">
            <a href="#" onclick="event.preventDefault(); loadPlaylists(${data.current_page - 1})">→</a>
        </li>`;

        for (let i = data.last_page; i >= 1; i--) {
            if (i === 1 || i === data.last_page || Math.abs(i - data.current_page) <= 2) {
                html += `<li class="${i === data.current_page ? 'active' : ''}">
                    <a href="#" onclick="event.preventDefault(); loadPlaylists(${i})">${i}</a>
                </li>`;
            } else if (Math.abs(i - data.current_page) === 3) {
                html += '<li class="dots">...</li>';
            }
        }

        html += `<li class="${data.current_page === data.last_page ? 'disabled' : ''}">
            <a href="#" onclick="event.preventDefault(); loadPlaylists(${data.current_page + 1})">←</a>
        </li>`;

        html += '</ul></nav>';
        area.innerHTML = html;
    }

    function updateCategoryTabs(categories, totalCount) {
        const tabs = document.getElementById('categoryTabs');
        let html = `<button class="cat-tab ${currentCategory === 'all' ? 'active' : ''}"
            data-category="all" onclick="filterByCategory('all', this)">
            الكل (<span class="cat-count">${totalCount}</span>)
        </button>`;

        for (const [cat, count] of Object.entries(categories)) {
            html += `<button class="cat-tab ${currentCategory === cat ? 'active' : ''}"
                data-category="${cat}" onclick="filterByCategory('${cat}', this)">
                ${cat} (<span class="cat-count">${count}</span>)
            </button>`;
        }

        tabs.innerHTML = html;

        document.getElementById('coursesCount').innerHTML =
            `تم العثور على <strong>${totalCount}</strong> دورة في <strong>${Object.keys(categories).length}</strong> تصنيفات`;
    }

    function formatViews(count) {
        if (count >= 1000000) return (count / 1000000).toFixed(1) + 'M';
        if (count >= 1000) return (count / 1000).toFixed(0) + 'K';
        return count;
    }

    function getCategoryClass(category) {
        const map = {
            'التسويق': 'marketing',
            'البرمجة': 'programming',
            'الجرافيكس': 'graphics',
            'الهندسة': 'engineering',
            'إدارة الأعمال': 'business',
        };
        return map[category] || 'default';
    }
</script>
@endsection
