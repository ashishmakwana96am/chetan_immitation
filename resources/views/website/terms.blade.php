@extends('layouts.website')

@section('title', 'Terms & Conditions | Chetan Imitation')

@section('page-css')
<style>
    .terms-content h1,
    .terms-content h2,
    .terms-content h3,
    .terms-content h4 {
        font-size: 18px;
        font-weight: 600;
        color: #131615;
        margin-bottom: 8px;
    }
    .terms-content h1 { font-size: 24px; }
    .terms-content h2 { font-size: 20px; }
    .terms-content p {
        font-size: 16px;
        line-height: 24px;
        color: #3D403F;
        margin-bottom: 16px;
    }
    .terms-content ul,
    .terms-content ol {
        list-style: disc;
        padding-left: 20px;
        margin-bottom: 16px;
    }
    .terms-content ul li,
    .terms-content ol li {
        font-size: 16px;
        line-height: 24px;
        color: #3D403F;
        margin-bottom: 6px;
    }
    .terms-content ul li:last-child,
    .terms-content ol li:last-child {
        margin-bottom: 0;
    }
    .terms-content a {
        color: #B4771E;
        text-decoration: underline;
    }
    .terms-content a:hover {
        color: #9a6318;
    }
    .terms-content strong {
        font-weight: 600;
        color: #131615;
    }
    @media (min-width: 768px) {
        .terms-content p,
        .terms-content ul li,
        .terms-content ol li {
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
                Terms & Conditions
            </h1>

            <p class="hero-para">
                Please read these terms and conditions carefully before using our website and purchasing our products.
            </p>

        </div>

        <div class="bg-white border border-[#D5D5D5] p-5 lg:p-6 rounded-[2px] terms-content">

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
