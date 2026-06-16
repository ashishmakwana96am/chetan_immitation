@extends('layouts.website')

@section('title', 'Delivery & Returns | Chetan Imitation')

@section('page-css')
<style>
    .page-content h1,
    .page-content h2,
    .page-content h3,
    .page-content h4 {
        font-size: 18px;
        font-weight: 600;
        color: #131615;
        margin-bottom: 8px;
    }
    .page-content h1 { font-size: 24px; }
    .page-content h2 { font-size: 20px; }
    .page-content p {
        font-size: 16px;
        line-height: 24px;
        color: #3D403F;
        margin-bottom: 16px;
    }
    .page-content ul,
    .page-content ol {
        list-style: disc;
        padding-left: 20px;
        margin-bottom: 16px;
    }
    .page-content ul li,
    .page-content ol li {
        font-size: 16px;
        line-height: 24px;
        color: #3D403F;
        margin-bottom: 6px;
    }
    .page-content ul li:last-child,
    .page-content ol li:last-child {
        margin-bottom: 0;
    }
    .page-content a {
        color: #B4771E;
        text-decoration: underline;
    }
    .page-content a:hover {
        color: #9a6318;
    }
    .page-content strong {
        font-weight: 600;
        color: #131615;
    }
    @media (min-width: 768px) {
        .page-content p,
        .page-content ul li,
        .page-content ol li {
            font-size: 17px;
        }
    }
</style>
@endsection

@section('content')

<section class="section-space">

    <div class="max-w-[1440px] mx-auto px-5">

        <div class="text-center mb-6">

            <h1 class="font-moglan hero-title">
                Deliveries & Returns
            </h1>

            <p class="hero-para">
                Learn about our shipping process, delivery timelines, return policy, and exchange guidelines for a smooth shopping experience.
            </p>

        </div>

        <div class="bg-white border border-[#D5D5D5] p-5 lg:p-6 rounded-[2px] page-content">

            @if ($lastUpdated)
                <p class="text-base md:text-lg text-[#131615] mb-4">
                    <span class="font-semibold">Last updated:</span> {{ $lastUpdated->format('d-m-Y') }}
                </p>
            @endif

            {!! $content !!}

        </div>

    </div>

</section>

@endsection
