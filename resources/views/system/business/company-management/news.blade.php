{{-- Template: News Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'News')
@push('styles')

<style>
     .icon-lg {
        font-size: 22px; /* Increase/decrease as needed */
    }
    </style>
    @endpush
@push('scripts')
<script>
// Helper function to calculate relative time
function getTimeAgo(date) {
	const now = new Date();
	const diffInSeconds = Math.floor((now - date) / 1000);
	
	if (diffInSeconds < 60) {
		return 'just now';
	}
	
	const diffInMinutes = Math.floor(diffInSeconds / 60);
	if (diffInMinutes < 60) {
		return diffInMinutes === 1 ? '1 minute ago' : `${diffInMinutes} minutes ago`;
	}
	
	const diffInHours = Math.floor(diffInMinutes / 60);
	if (diffInHours < 24) {
		return diffInHours === 1 ? '1 hour ago' : `${diffInHours} hours ago`;
	}
	
	const diffInDays = Math.floor(diffInHours / 24);
	if (diffInDays < 7) {
		return diffInDays === 1 ? '1 day ago' : `${diffInDays} days ago`;
	}
	
	const diffInWeeks = Math.floor(diffInDays / 7);
	if (diffInWeeks < 4) {
		return diffInWeeks === 1 ? '1 week ago' : `${diffInWeeks} weeks ago`;
	}
	
	const diffInMonths = Math.floor(diffInDays / 30);
	if (diffInMonths < 12) {
		return diffInMonths === 1 ? '1 month ago' : `${diffInMonths} months ago`;
	}
	
	const diffInYears = Math.floor(diffInDays / 365);
	return diffInYears === 1 ? '1 year ago' : `${diffInYears} years ago`;
}

// Image preview functions
function previewImage(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview-container').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('create-post-image').value = '';
    document.getElementById('image-preview-container').style.display = 'none';
    document.getElementById('image-preview').src = '';
}

class NewsManager {
    constructor() {
        this.isCreatingPost = false;
        this.pendingComments = new Set();
        this.trackedViewIds = new Set();
        this.bindEvents();
        this.trackViews();
    }
    
    bindEvents() {
        // Create post form
        document.addEventListener('submit', (e) => {
            // only handle comment forms here; let create-post-form submit natively to avoid double
            if (e.target.classList.contains('comment-form')) {
                e.preventDefault();
                const newsId = e.target.dataset.newsId;
                if (this.pendingComments.has(newsId)) return;
                this.pendingComments.add(newsId);
                this.handleComment(e.target).finally(() => this.pendingComments.delete(newsId));
            }
        });
        
        // Like functionality
        document.addEventListener('click', (e) => {
            // Trigger hidden file input for create-post image
            const trigger = e.target.closest('.create-image-trigger');
            if (trigger) {
                e.preventDefault();
                const form = trigger.closest('form');
                if (form) {
                    const input = form.querySelector('.create-post-image-input');
                    if (input) input.click();
                }
                return;
            }
            const btn = e.target.closest('.like-btn');
            if (btn) {
                e.preventDefault();
                if (btn.dataset.busy === '1') return;
                btn.dataset.busy = '1';
                this.handleLike(btn).finally(() => { btn.dataset.busy = '0'; });
            }
        });
        
        // Comment submission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('comment-form')) {
                e.preventDefault();
                const newsId = e.target.dataset.newsId;
                if (this.pendingComments.has(newsId)) return;
                this.pendingComments.add(newsId);
                this.handleComment(e.target).finally(() => this.pendingComments.delete(newsId));
            }
        });

        // Delete post
        document.addEventListener('click', (e) => {
            const del = e.target.closest('.delete-post-btn');
            if (!del) return;
            e.preventDefault();
            if (del.dataset.busy === '1') return;
            if (!confirm('Delete this post?')) return;
            del.dataset.busy = '1';
            this.handleDelete(del).finally(() => { del.dataset.busy = '0'; });
        });

		// Comment toggle
		document.addEventListener('click', (e) => {
			const toggle = e.target.closest('.comment-toggle');
			if (!toggle) return;
			e.preventDefault();
			const card = toggle.closest('.card');
			const form = card ? card.querySelector('.comment-form') : null;
			const likersPanel = card ? card.querySelector('.likers-panel') : null;
			const commentsPanel = card ? card.querySelector('.comments-panel') : null;
			const viewsPanel = card ? card.querySelector('.views-panel') : null;
			if (!form) return;
			const isVisible = form.style.display === 'block';
			// Hide likers panel when showing comment form
			if (likersPanel) likersPanel.style.display = 'none';
			if (isVisible) {
				// Hide both form and comments panel
				form.style.display = 'none';
				if (commentsPanel) commentsPanel.style.display = 'none';
			} else {
				// Show form and fetch comments
				form.style.display = 'block';
				// Hide other panels when showing comment form
				if (viewsPanel) viewsPanel.style.display = 'none';
				if (commentsPanel && commentsPanel.dataset.loaded !== '1') {
					// Fetch comments if not already loaded
					commentsPanel.dataset.loading = '1';
					commentsPanel.innerHTML = '<span class="text-muted small"><i class="ti ti-loader ti-spin me-1"></i>Loading comments...</span>';
					commentsPanel.style.display = 'block';
					const newsId = card.dataset.newsId;
					const ac = new AbortController();
					setTimeout(() => ac.abort(), 8000);
					const fd = new FormData();
					fd.append('news_id', newsId);
					fd.append('list', '1');
					fd.append('save_token', '@skeletonToken("business_company_news_comment")');
					fd.append('form_type', 'business_company_news_comment');
					fetch('/skeleton-action/@skeletonToken("business_company_news_comment")_f', {
						method: 'POST',
						headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
						body: fd,
						signal: ac.signal
					}).then(r => r.json()).then(data => {
						commentsPanel.dataset.loaded = '1';
						commentsPanel.dataset.loading = '0';
						const comments = (data && data.status && Array.isArray(data.comments)) ? data.comments : [];
						if (!comments.length) {
							commentsPanel.innerHTML = '<span class="text-muted small">No comments yet</span>';
						} else {
							const items = comments.map(c => {
								const name = (c && c.user_name) ? c.user_name : 'Unknown';
								const content = (c && c.content) ? c.content : '';
								const admin = c && c.is_admin;
								const time = c && c.created_at ? new Date(c.created_at).toLocaleDateString() : '';
								// Calculate relative time
								const timeAgo = c && c.created_at ? getTimeAgo(new Date(c.created_at)) : '';
								return `<div class="d-flex align-items-start mb-2"><div class="flex-grow-1"><div class="d-flex align-items-center mb-1"><span class="fw-medium me-2 ${admin ? 'text-warning' : ''}">${name}</span><span class="text-muted small">${timeAgo}</span></div><div class="text-dark">${content}</div></div></div>`;
							}).join('');
							commentsPanel.innerHTML = `<div class="border-top pt-2">${items}</div>`;
						}
					}).catch(() => {
						commentsPanel.innerHTML = '<span class="text-danger small">Failed to load comments</span>';
						commentsPanel.dataset.loading = '0';
					});
				} else if (commentsPanel) {
					// Show cached comments
					commentsPanel.style.display = 'block';
				}
			}
		});

		// Views toggle
		document.addEventListener('click', async (e) => {
			const toggle = e.target.closest('.views-toggle');
			if (!toggle) return;
			e.preventDefault();
			const card = toggle.closest('.card');
			if (!card) return;
			const newsId = card.dataset.newsId;
			const panel = card.querySelector('.views-panel');
			const commentForm = card.querySelector('.comment-form');
			const commentsPanel = card.querySelector('.comments-panel');
			const likersPanel = card.querySelector('.likers-panel');
			if (!newsId || !panel) return;
			if (panel.style.display === 'block') { 
				panel.style.display = 'none'; 
				return; 
			}
			// Hide other panels when showing views panel
			if (commentForm) commentForm.style.display = 'none';
			if (commentsPanel) commentsPanel.style.display = 'none';
			if (likersPanel) likersPanel.style.display = 'none';
			// If already loaded, just show the cached content
			if (panel.dataset.loaded === '1') {
				panel.style.display = 'block';
				return;
			}
			if (panel.dataset.loading === '1') return;
			panel.dataset.loading = '1';
			panel.innerHTML = '<span class="text-muted small"><i class="ti ti-loader ti-spin me-1"></i>Loading views...</span>';
			panel.style.display = 'block';
			const ac = new AbortController();
			setTimeout(() => ac.abort(), 8000);
			try {
				const fd = new FormData();
				fd.append('news_id', newsId);
				fd.append('list', '1');
				fd.append('save_token', '@skeletonToken("business_company_news_view")');
				fd.append('form_type', 'business_company_news_view');
				const res = await fetch('/skeleton-action/@skeletonToken("business_company_news_view")_f', {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
					body: fd,
					signal: ac.signal
				});
				const data = await res.json();
				panel.dataset.loaded = '1';
				const viewers = (data && data.status && Array.isArray(data.viewers)) ? data.viewers : [];
				if (!viewers.length) {
					panel.innerHTML = '<span class="text-muted small">No views yet</span>';
				} else {
					const items = viewers.map(v => {
						const name = (v && v.user_name) ? v.user_name : 'Unknown';
						const admin = v && v.is_admin;
						const timeAgo = v && v.viewed_at ? getTimeAgo(new Date(v.viewed_at)) : '';
						return `<li class="list-group-item d-flex align-items-center p-2"><span class="me-2 ti ti-eye text-primary"></span><span class="${admin ? 'text-warning' : ''}">${name}</span><span class="text-muted small ms-auto">${timeAgo}</span></li>`;
					}).join('');
					panel.innerHTML = `<ul class="list-group list-group-flush">${items}</ul>`;
				}
			} catch (err) {
				panel.innerHTML = '<span class="text-danger small">Failed to load views</span>';
			} finally {
				panel.dataset.loading = '0';
			}
		});

        // Click to show likers panel when clicking the Likes text (not the heart icon)
        document.addEventListener('click', async (e) => {
            const toggle = e.target.closest('.likers-toggle');
            if (!toggle) return;
            e.preventDefault();
            const card = toggle.closest('.card');
            if (!card) return;
            const newsId = card.dataset.newsId;
            const panel = card.querySelector('.likers-panel');
            const commentForm = card.querySelector('.comment-form');
            const commentsPanel = card.querySelector('.comments-panel');
            const viewsPanel = card.querySelector('.views-panel');
            if (!newsId || !panel) return;
            if (panel.style.display === 'block') { 
                panel.style.display = 'none'; 
                return; 
            }
            // Hide comment form when showing likers panel
            if (commentForm) commentForm.style.display = 'none';
            if (commentsPanel) commentsPanel.style.display = 'none';
            if (viewsPanel) viewsPanel.style.display = 'none';
            // If already loaded, just show the cached content
            if (panel.dataset.loaded === '1') {
                panel.style.display = 'block';
                return;
            }
            if (panel.dataset.loading === '1') return;
            panel.dataset.loading = '1';
            panel.innerHTML = '<span class="text-muted small"><i class="ti ti-loader ti-spin me-1"></i>Loading likes...</span>';
            panel.style.display = 'block';
            const ac = new AbortController();
            setTimeout(() => ac.abort(), 8000);
            try {
                const fd = new FormData();
                fd.append('news_id', newsId);
                fd.append('list', '1');
                fd.append('save_token', '@skeletonToken("business_company_news_like")');
                fd.append('form_type', 'business_company_news_like');
                const res = await fetch('/skeleton-action/@skeletonToken("business_company_news_like")_f', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: fd,
                    signal: ac.signal
                });
                const data = await res.json();
                const likers = (data && data.status && Array.isArray(data.likers)) ? data.likers : [];
                panel.dataset.loaded = '1';
                if (!likers.length) {
                    panel.innerHTML = '<span class="text-muted small">No likes yet</span>';
                } else {
                    const items = likers.map(u => {
                        const name = (u && u.name) ? u.name : 'Unknown';
                        const admin = u && u.is_admin;
                        return `<li class="list-group-item d-flex align-items-center p-2"><span class="me-2 ti ti-heart text-danger"></span><span class="${admin ? 'text-warning' : ''}">${name}</span><span class="text-muted small ms-auto">${getTimeAgo(new Date(u.liked_at))}</span></li>`;
                    }).join('');
                    panel.innerHTML = `<ul class="list-group list-group-flush">${items}</ul>`;
                }
            } catch (err) {
                panel.innerHTML = '<span class="text-danger small">Failed to load likes</span>';
            } finally {
                panel.dataset.loading = '0';
            }
        });
    }
    
    async handleCreatePost(form) {
        if (this.isCreatingPost) return;
        const content = form.querySelector('.post-textarea').value.trim();
        if (!content) {
            alert('Please enter some content');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="ti ti-loader ti-spin me-2"></i>Creating...';
        submitBtn.disabled = true;
        this.isCreatingPost = true;
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status) {
                form.reset();
                if (data.reload_page) window.location.reload();
            } else {
                alert(data.message || 'Failed to create post');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error creating post');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            this.isCreatingPost = false;
        }
    }
    
    async handleLike(button) {
        const newsId = button.dataset.newsId;
        const card = button.closest('.card');
        const likeCount = card ? card.querySelector('.like-count') : null;
        const likeIcon = button.querySelector('i');
        const ac = new AbortController();
        const t = setTimeout(() => ac.abort(), 8000);
        
        try {
            const formData = new FormData();
            formData.append('news_id', newsId);
            formData.append('save_token', '@skeletonToken("business_company_news_like")');
            formData.append('form_type', 'business_company_news_like');
            
            const response = await fetch('/skeleton-action/@skeletonToken("business_company_news_like")_f', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: formData, signal: ac.signal });
            const data = await response.json();
            if (data.status) {
                const liked = typeof data.liked === 'boolean' ? data.liked : (data.message || '').toLowerCase().includes('liked');
                if (likeIcon) likeIcon.className = liked ? 'ti ti-heart-filled text-danger' : 'ti ti-heart';
                if (likeCount) {
                    if (typeof data.likes_count === 'number') {
                        likeCount.textContent = data.likes_count;
                    } else {
                        const current = parseInt(likeCount.textContent || '0') || 0;
                        likeCount.textContent = liked ? current + 1 : Math.max(0, current - 1);
                    }
                }
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            clearTimeout(t);
        }
    }
    
    async handleComment(form) {
        const newsId = form.dataset.newsId;
        const content = form.querySelector('input[name="content"]').value.trim();
        if (!content) { alert('Please enter a comment'); return; }
        const ac = new AbortController();
        const t = setTimeout(() => ac.abort(), 8000);
        
        try {
            const formData = new FormData();
            formData.append('news_id', newsId);
            formData.append('content', content);
            formData.append('save_token', '@skeletonToken("business_company_news_comment")');
            formData.append('form_type', 'business_company_news_comment');
            
            const response = await fetch('/skeleton-action/@skeletonToken("business_company_news_comment")_f', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: formData, signal: ac.signal });
            const data = await response.json();
            if (data.status) {
                form.reset();
                const commentCount = form.closest('.card').querySelector('.comment-count');
                const currentCount = parseInt(commentCount.textContent);
                commentCount.textContent = currentCount + 1;
            } else {
                alert(data.message || 'Failed to add comment');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error adding comment');
        } finally {
            clearTimeout(t);
        }
    }

    async handleDelete(button) {
        const newsId = button.dataset.newsId;
        if (!newsId) return;
        const ac = new AbortController();
        const t = setTimeout(() => ac.abort(), 8000);
        try {
            const formData = new FormData();
            formData.append('save_token', '@skeletonToken("business_company_news")');
            formData.append('form_type', 'business_company_news');
            formData.append('delete', '1');
            formData.append('news_id', newsId);
            const res = await fetch('/skeleton-action/@skeletonToken("business_company_news")_f', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData,
                signal: ac.signal
            });
            const data = await res.json();
            if (data.status) {
                const card = button.closest('.card');
                if (card) card.remove();
            } else {
                alert(data.message || 'Failed to delete post');
            }
        } catch (err) {
            console.error('Delete failed', err);
            alert('Error deleting post');
        } finally {
            clearTimeout(t);
        }
    }
    
    trackViews() {
        // Track only when a post enters viewport; avoid spamming network
        const cards = document.querySelectorAll('[data-news-id]');
        if (!('IntersectionObserver' in window)) {
            // Fallback: track first 5 items only
            let count = 0;
            cards.forEach(card => {
                if (count >= 5) return;
                const id = card.dataset.newsId;
                if (!id || this.trackedViewIds.has(id)) return;
                this.trackedViewIds.add(id);
                this.sendView(id);
                count++;
            });
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.dataset.newsId;
                    if (!id || this.trackedViewIds.has(id)) return;
                    this.trackedViewIds.add(id);
                    this.sendView(id);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px 200px 0px', threshold: 0.1 });
        cards.forEach(card => io.observe(card));
    }

    sendView(newsId) {
        const formData = new FormData();
        formData.append('news_id', newsId);
        formData.append('save_token', '@skeletonToken("business_company_news_view")');
        formData.append('form_type', 'business_company_news_view');
        const ac = new AbortController();
        setTimeout(() => ac.abort(), 5000);
        fetch('/skeleton-action/@skeletonToken("business_company_news_view")_f', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: formData, signal: ac.signal }).catch(() => {});
    }
}

// Initialize when DOM is loaded (guard against duplicate init)
document.addEventListener('DOMContentLoaded', () => {
    if (window.__newsManagerInitialized) return;
    window.__newsManagerInitialized = true;
    new NewsManager();
});
</script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Announcements</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item "><a href="{{ url('/company-management') }}">Company Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Announcements</a></li>
                    </ol>
                </nav>
            </div>
            <div></div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons"> <span class="live-time-icon me-2"><i
                            class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>
                <div class="ms-2 head-icons"> <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a> </div>
            </div>
        </div> 
        
        {{-- ************************************************************************************************ * * * >>> MODIFY THIS SECTION (START) <<< * * * ************************************************************************************************ --}} 
        <div class="row">
            <div class="col-xl-8">
                <div> 
                    {{-- Create Post Form --}} 
                    <div class="card">
                        <div class="card-body">
                            <form class="create-post-form" action="{{ url('/skeleton-action/') }}/@skeletonToken('business_company_news')_f" method="POST" onsubmit="(function(f){var b=f.querySelector('button[type=submit]'); if(b){ b.disabled=true; b.innerHTML='<i class=\'ti ti-loader ti-spin me-2\'></i>Posting...'; } })(this)">
                                @csrf
                                <input type="hidden" name="save_token" value="@skeletonToken('business_company_news')">
                                <input type="hidden" name="form_type" value="business_company_news">
                                @php
                                    $system = \App\Facades\Skeleton::authUser('system');
                                    $userId = \App\Facades\Skeleton::authUser()->user_id ?? null;
                                    $scopeId = '';
                                    if ($system && $userId) {
                                        $scopeResult = \App\Facades\Data::fetch($system, 'scope_mapping', [
                                            'columns' => ['scope_id'],
                                            'where' => ['user_id' => $userId],
                                            'limit' => 1
                                        ]);
                                        if ($scopeResult['status'] && !empty($scopeResult['data'])) {
                                            $scopeId = $scopeResult['data'][0]['scope_id'] ?? '';
                                        }
                                    }
                                @endphp
                                <input type="hidden" name="scope_id" value="{{ $scopeId }}">
                                
                                <div class="mb-3"> 
                                    <label class="form-label fs-16">Create Post</label>
                                    <div class="position-relative">
                                        <textarea class="form-control post-textarea" name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                                    </div>
                                </div>
                                <div class="mb-3" id="image-preview-container" style="display: none;">
                                    <div class="position-relative d-inline-block">
                                        <img id="image-preview" class="img-fluid rounded" style="max-width: 300px; max-height: 200px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeImage()" title="Remove image">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <div class="d-flex align-items-center"> 
                                        <input type="file" id="create-post-image" name="image" accept="image/*" class="d-none create-post-image-input" onchange="previewImage(this)">
                                        <label for="create-post-image" class="btn btn-icon btn-sm rounded-circle create-image-trigger" title="Add image">
                                            <i class="ti ti-photo fs-16"></i>
                                        </label>
                                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-paperclip fs-16"></i>
                                        </a>
                                        <div class="form-check form-switch ms-3">
                                            <input class="form-check-input" type="checkbox" id="highPrioritySwitch" onchange="document.getElementById('highPriorityInput').value = this.checked ? 'yes' : 'no';">
                                            <label class="form-check-label" for="highPrioritySwitch">High Priority</label>
                                        </div>
                                        <input type="hidden" name="high_priority" id="highPriorityInput" value="no">
                                    </div>
                                    <div class="d-flex align-items-center"> 
                                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center ms-2">
                                            <i class="ti ti-circle-plus fs-16 me-2"></i>Share Post
                                        </button> 
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div> 
                    
                    {{-- Dynamic Posts --}} 
                    @php($posts = $data['posts'] ?? []) 
                    @forelse ($posts as $post)
                        <div class="card shadow-sm mb-4" data-news-id="{{ $post['news_id'] ?? '' }}">
                            <div class="card-header border-0 pb-0 {{ !empty($post['is_admin']) ? 'border-warning' : '' }}">
                                <div class="d-flex align-items-center justify-content-between border-bottom flex-wrap row-gap-3 pb-3">
                                    <div class="d-flex align-items-center"> 
                                        <a href="javascript:void(0);" class="avatar avatar-lg avatar-rounded flex-shrink-0 me-3" style="width:56px;height:56px;overflow:hidden;">
                                            @if (!empty($post['author_avatar']))
                                                <img src="{{ $post['author_avatar'] }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                <img src="{{ asset('treasury/img/common/default/profile/default-1.svg') }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @endif
                                        </a>
                                        <div class="d-flex flex-column">
                                            <h5 class="mb-1 d-flex align-items-center gap-2">
                                                <a href="javascript:void(0);" class="text-reset">{{ $post['author_name'] ?? 'Unknown' }}</a>
                                                @if (!empty($post['is_mine']))
                                                    <span class="badge bg-secondary align-middle">(me)</span>
                                                @endif
                                                @if (!empty($post['is_admin']))
                                                    <span class="badge bg-warning text-dark align-middle">Admin</span>
                                                @endif
                                            </h5>
                                            <div class="text-muted small">{{ \Carbon\Carbon::parse($post['created_at'] ?? now())->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center ms-auto">
                                        @if (!empty($post['can_delete']))
                                        <a href="javascript:void(0);" class="btn btn-icon btn-sm rounded-circle delete-post-btn" data-news-id="{{ $post['news_id'] }}" title="Delete">
                                            <i class="ti ti-trash-x"></i>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                @if (!empty($post['content']))
                                    <div class="mb-3">
                                        <p class="text-dark fw-medium mb-0">{!! nl2br(e($post['content'])) !!}</p>
                                    </div>
                                @endif 
                                
                                @if (!empty($post['file_url']))
                                    <div class="mb-3"> 
                                        <img src="{{ $post['file_url'] }}" class="img-fluid rounded" alt="Attachment" style="width:100%;max-height:420px;object-fit:cover;"> 
                                    </div>
                                    @endif
                                
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2 mb-2">
                                    <div class="d-flex align-items-center flex-wrap row-gap-2">
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center me-2 small text-muted like-btn" data-news-id="{{ $post['news_id'] ?? '' }}"> 
                                                @if (!empty($post['is_liked']))
                                                <i class="ti ti-heart-filled text-danger icon-lg "></i>
                                                @else
                                                <i class="ti ti-heart icon-lg "></i>
                                                @endif
                                        </a> 
                                        <span class="likers-toggle small text-muted me-3" data-news-id="{{ $post['news_id'] ?? '' }}" style="cursor: pointer;">
                                            <span class="like-count fw-semibold">{{ (int) ($post['likes_count'] ?? 0) }}</span>&nbsp;Likes
                                        </span>
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center me-3 small text-muted comment-toggle" data-news-id="{{ $post['news_id'] ?? '' }}"> 
                                            <i class="ti ti-message-dots me-2 icon-lg"></i>
                                            <span class="comment-count fw-semibold">{{ (int) ($post['comments_count'] ?? 0) }}</span>&nbsp;Comments
                                        </a> 
                                        <span class="d-inline-flex align-items-center small text-muted"> 
                                            <i class="ti ti-eye me-2 icon-lg"></i>
                                            <span class="view-count fw-semibold">{{ (int) ($post['views_count'] ?? 0) }}</span>&nbsp;<span class="views-toggle" data-news-id="{{ $post['news_id'] ?? '' }}" style="cursor: pointer;">Views</span>
                                        </span> 
                                    </div>
                                    <div class="d-flex align-items-center"></div>
                                </div>
                                <div class="likers-panel mt-2" data-news-id="{{ $post['news_id'] ?? '' }}" style="display:none;"></div>
                                
                                <form class="comment-form" data-news-id="{{ $post['news_id'] ?? '' }}" action="javascript:void(0);" style="display:none;">
                                    @csrf
                                    <div class="d-flex align-items-center gap-2"> 
                                        <input type="text" name="content" class="form-control" placeholder="Add a comment..." required> 
                                        <button type="submit" class="btn btn-sm btn-primary">Comment</button>
                                    </div>
                                </form>
                                <div class="comments-panel mt-2" data-news-id="{{ $post['news_id'] ?? '' }}" style="display:none;"></div>
                                <div class="views-panel mt-2" data-news-id="{{ $post['news_id'] ?? '' }}" style="display:none;"></div>
                            </div>
                        </div> 
                    @empty 
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <p class="mb-0">No announcements yet. Be the first to post!</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
            
            <div class="col-xl-4 theiaStickySidebar">
                {{-- High Priority Posts --}}
                @php($highPriorityPosts = $data['high_priority_posts'] ?? [])
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="ti ti-alert-triangle me-2"></i>
                            High Priority Posts
                        </h5>
                                            </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        @if(!empty($highPriorityPosts))
                            @foreach($highPriorityPosts as $post)
                            <div class="border-bottom pb-3 mb-3 {{ $loop->last ? 'border-0 pb-0 mb-0' : '' }}">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm avatar-rounded" style="width:40px;height:40px;overflow:hidden;">
                                            @if (!empty($post['author_avatar']))
                                                <img src="{{ $post['author_avatar'] }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                <img src="{{ asset('treasury/img/common/default/profile/default-1.svg') }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @endif
                                            </div>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-medium text-truncate">{{ $post['author_name'] ?? 'Unknown' }}</h6>
                                            @if (!empty($post['is_admin']))
                                                <span class="badge bg-warning text-dark badge-sm">Admin</span>
                                            @endif
                                            </div>
                                        <p class="text-muted small mb-1 text-truncate">{{ \Carbon\Carbon::parse($post['created_at'] ?? now())->diffForHumans() }}</p>
                                        <p class="mb-0 small text-dark">{{ Str::limit($post['content'] ?? '', 80) }}</p>
                                        @if (!empty($post['file_url']))
                                            <div class="mt-2">
                                                <img src="{{ $post['file_url'] }}" class="img-fluid rounded" alt="Attachment" style="max-width:100%;max-height:80px;object-fit:cover;">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="ti ti-check-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No high priority posts at the moment</p>
                                            </div>
                        @endif
                                    </div>
                                            </div>
                
                {{-- Highest Liked Posts --}}
                @php($highestLikedPosts = $data['highest_liked_posts'] ?? [])
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="ti ti-heart me-2"></i>
                            Most Liked Posts
                        </h5>
                                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        @if(!empty($highestLikedPosts))
                            @foreach($highestLikedPosts as $post)
                            <div class="border-bottom pb-3 mb-3 {{ $loop->last ? 'border-0 pb-0 mb-0' : '' }}">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm avatar-rounded" style="width:40px;height:40px;overflow:hidden;">
                                            @if (!empty($post['author_avatar']))
                                                <img src="{{ $post['author_avatar'] }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                <img src="{{ asset('treasury/img/common/default/profile/default-1.svg') }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                            @endif
                                            </div>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-medium text-truncate">{{ $post['author_name'] ?? 'Unknown' }}</h6>
                                            @if (!empty($post['is_admin']))
                                                <span class="badge bg-warning text-dark badge-sm">Admin</span>
                                            @endif
                                            </div>
                                        <p class="text-muted small mb-1 text-truncate">{{ \Carbon\Carbon::parse($post['created_at'] ?? now())->diffForHumans() }}</p>
                                        <p class="mb-0 small text-dark">{{ Str::limit($post['content'] ?? '', 80) }}</p>
                                        @if (!empty($post['file_url']))
                                            <div class="mt-2">
                                                <img src="{{ $post['file_url'] }}" class="img-fluid rounded" alt="Attachment" style="max-width:100%;max-height:80px;object-fit:cover;">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="ti ti-heart text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No posts with likes yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- ************************************************************************************************ * * * >>> MODIFY THIS SECTION (END) <<< * * * ************************************************************************************************ --}}
        </div>
    </div> 
@endsection
