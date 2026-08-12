@php
    $posts = $instagramPosts ?? $sharedInstagramPosts ?? \App\Models\Setting::getInstagramPosts();
    $profileUrl = $instagramProfileUrl ?? $sharedInstagramProfileUrl ?? \App\Models\Setting::getInstagramProfileUrl();
    $sectionClass = $sectionClass ?? 'section-space-bottom';
@endphp

<!-- Follow Our Jewellery Journey Section -->
<section class="{{ $sectionClass }}">
    <div>
        <div class="text-center px-5">
            <h2 class="hero-title">
                Follow Our Jewellery Journey
            </h2>
        </div>
        <div class="mt-8 md:mt-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($posts as $post)
                <a href="{{ $post['link'] ?? $profileUrl }}" target="_blank" rel="noopener noreferrer" class="group overflow-hidden relative block w-full aspect-square">
                    <img src="{{ $post['image'] }}" alt="{{ $post['caption'] ?? 'Instagram Post' }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center text-white text-2xl">
                        <i class="fa-brands fa-instagram"></i>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="text-center mt-8 lg:mt-10">
            <a href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer" class="common-btn">
                Follow Us on Instagram
            </a>
        </div>
    </div>
</section>
