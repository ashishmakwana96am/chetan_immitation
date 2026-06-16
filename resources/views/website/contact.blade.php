@extends('layouts.website')

@section('title', 'Contact Us | Chetan Imitation')

@section('content')

<section class="relative bg-center bg-no-repeat overflow-hidden" style="background-image: url('{{ asset('website/assets/images/about_banner.png') }}'); background-size: 100% 100%;">

    <div class="container-1440">

        <div class="grid lg:grid-cols-2 items-center">

            <div class="relative z-10 py-16 lg:py-0">

                <span class="inline-flex items-center bg-white text-[#B4771E] text-xl px-[15px] py-[10px]">
                    Get In Touch
                </span>

                <h1 class="font-moglan hero-heading mt-5">
                    We'd Love to Hear
                    <br>
                    From You
                </h1>

                <p class="hero-para max-w-[550px]">
                    Whether you have questions about our jewelry collections, need assistance with an order, or want
                    styling recommendations, our team is here to help.
                </p>

            </div>

            <div class="relative flex justify-center lg:justify-end">
                <img src="{{ asset('website/assets/images/beauty.png') }}" alt="" class="w-full">
            </div>

        </div>

    </div>
</section>

<section class="py-[80px]">

    <div class="container-1440">

        <div class="border border-[#dcdcdc] bg-[#ffffff] flex flex-col lg:flex-row p-5 xl:p-10">

            <div class="w-full lg:w-[36%] lg:pr-8 border-b lg:border-b-0 lg:border-r border-[#dcdcdc]">

                <h2 class="text-[22px] font-medium text-[#131615]">
                    Contact Information
                </h2>

                <div class="w-[62px] h-[2px] bg-[#B4771E] mt-2 mb-5"></div>

                <p class="text-xl text-[#3D403F] leading-7 mb-[30px]">
                    Reach out to us through any of the following channels.
                </p>

                <div class="flex gap-4 pb-6 mb-6 border-b border-[#e5e5e5]">

                    <div class="w-[50px] md:w-[62px] h-[50px] md:h-[62px] bg-[#B4771E] flex items-center justify-center shrink-0">
                        <img src="{{ asset('website/assets/images/icon1.png') }}" class="w-5 md:w-auto"/>
                    </div>

                    <div>
                        <h4 class="text-[22px] leading-[22px] font-medium text-[#131615] mb-2">Call Us</h4>
                        <a href="tel:+917725978871" class="text-base md:text-lg text-[#3D403F] hover:text-[#B4771E] transition">+91 77259 78871</a>
                    </div>

                </div>

                <div class="flex gap-4 pb-6 mb-6 border-b border-[#e5e5e5]">

                    <div class="w-[50px] md:w-[62px] h-[50px] md:h-[62px] bg-[#B4771E] flex items-center justify-center shrink-0">
                        <img src="{{ asset('website/assets/images/icon2.png') }}" class="w-5 md:w-auto"/>
                    </div>

                    <div>
                        <h4 class="text-[22px] leading-[22px] font-medium text-[#131615] mb-2">Email Us</h4>
                        <a href="mailto:info@chetanimitation.com" class="block text-base md:text-lg text-[#3D403F] hover:text-[#B4771E] transition break-all">info@chetanimitation.com</a>
                        <a href="mailto:support@chetanimitation.com" class="block text-base md:text-lg text-[#3D403F] hover:text-[#B4771E] transition break-all">support@chetanimitation.com</a>
                    </div>

                </div>

                <div class="flex gap-4 pb-6 mb-6 border-b border-[#e5e5e5]">

                    <div class="w-[50px] md:w-[62px] h-[50px] md:h-[62px] bg-[#B4771E] flex items-center justify-center shrink-0">
                        <img src="{{ asset('website/assets/images/icon3.png') }}" class="w-5 md:w-auto"/>
                    </div>

                    <div>
                        <h4 class="text-[22px] leading-[22px] font-medium text-[#131615] mb-2">Visit Us</h4>

                        <a href="https://maps.google.com/?q=G-14+Abc+market+Sudama+chowk+Mota+Varachha+Surat" target="_blank" rel="noopener noreferrer" class="block text-base md:text-lg text-[#3D403F] leading-7 mb-3 hover:text-[#B4771E] transition">
                            <span class="font-normal text-[#3D403F]">Branch - 1:</span>
                            G-14 Abc market, Abc circle, Sudama chowk,
                            Mota Varachha, Surat, Gujarat 394101, India
                        </a>

                        <a href="https://maps.google.com/?q=Narayannagar+Chok+Singanpore+Road+Katargam+Surat" target="_blank" rel="noopener noreferrer" class="block text-base md:text-lg text-[#3D403F] leading-7 hover:text-[#B4771E] transition">
                            <span class="font-normal text-[#3D403F]">Branch - 2:</span>
                            Shop No. 4, Narayan Flats,
                            Narayannagar Chok, Singanpore Road,
                            Katargam, Surat, Gujarat 395004, India
                        </a>
                    </div>

                </div>

                <div class="flex gap-4 mb-6">

                    <div class="w-[54px] h-[54px] bg-[#B4771E] flex items-center justify-center shrink-0">
                        <img src="{{ asset('website/assets/images/icon4.png') }}" class="w-5 md:w-auto"/>
                    </div>

                    <div>
                        <h4 class="text-[22px] font-medium text-[#131615] mb-1">Business Hours</h4>
                        <p class="text-base md:text-lg text-[#3D403F]">Monday – Sunday</p>
                        <p class="text-base md:text-lg text-[#3D403F]">09:00 AM – 08:00 PM</p>
                    </div>

                </div>

            </div>

            <div class="w-full lg:w-[64%] lg:pl-8 mt-5 lg:mt-0">

                <h2 class="text-[22px] font-medium text-[#131615]">Send Us A Message</h2>

                <div class="w-[55px] h-[2px] bg-[#B4771E] mt-2 mb-5"></div>

                <p class="text-base md:text-lg text-[#3D403F] leading-7 mb-[30px]">
                    Fill out the form below and our team will get back to you as soon as possible.
                </p>

                <form>
                    <div class="grid md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xl mb-3 text-[#131615]">Full Name</label>
                            <input type="text" placeholder="Enter Your Full Name" class="w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                        </div>
                        <div>
                            <label class="block text-xl mb-3 text-[#131615]">Email Address</label>
                            <input type="text" placeholder="Enter Your Email" class="w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xl mb-3 text-[#131615]">Phone Number</label>
                            <input type="text" placeholder="Enter Your Phone" class="w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                        </div>
                        <div>
                            <label class="block text-xl mb-3 text-[#131615]">Subject</label>
                            <input type="text" placeholder="Enter Subject" class="w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                        </div>
                    </div>

                    <div class="mb-7">
                        <label class="block text-xl mb-3 text-[#131615]">Message</label>
                        <textarea rows="5" placeholder="Type Your Message Here...." class="w-full min-h-36 border border-[#DCDCDC] p-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E] resize-y"></textarea>
                    </div>

                    <button type="submit" class="w-full h-[56px] bg-[#B4771E] text-white text-xl font-medium hover:bg-[#b17820] duration-300">
                        Send Message
                    </button>
                </form>

            </div>

        </div>

    </div>

</section>

<section class="py-[80px] lg:py-[100px]">

    <div class="container-1440">

        <div class="text-center mb-10 lg:mb-12">
            <h2 class="font-moglan hero-title">Visit Our Store</h2>
            <p class="hero-para">Discover our latest jewelry collections and receive personalized assistance at our store.</p>
        </div>

        <div class="border border-[#D5D5D5] overflow-hidden bg-white">
            <iframe src="https://www.google.com/maps?q=Sudama%20Chowk%20Mota%20Varachha%20Surat&output=embed" class="w-full h-[300px] md:h-[450px] lg:h-[520px]" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

    </div>

</section>

@endsection
