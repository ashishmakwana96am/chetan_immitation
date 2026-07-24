@extends('layouts.website')

@section('title', 'Contact Us | Chetan Imitation')

@section('content')

<section class="relative bg-center bg-no-repeat overflow-hidden" style="background-image: url('{{ asset('website/assets/images/about_banner.png') }}'); background-size: 100% 100%;">

    <div class="container-1440">

        <div class="grid lg:grid-cols-2 items-center">

            <div class="relative z-10 py-16 lg:py-0 text-center md:text-left">

                <span class="inline-flex items-center bg-white text-[#B4771E] text-base md:text-xl px-[15px] py-[10px] mb-2">
                    Get In Touch
                </span>

                <h1 class="font-moglan hero-heading">
                    We'd Love to Hear
                    <br>
                    From You
                </h1>

                <p class="hero-para max-w-[550px]">
                    Whether you have questions about our jewellery collections, need assistance with an order, or want
                    styling recommendations, our team is here to help.
                </p>

            </div>

            <div class="relative flex justify-center lg:justify-end">
                <img src="{{ asset('website/assets/images/beauty.png') }}" alt="" class="w-full">
            </div>

        </div>

    </div>
</section>

<section class="section-space">

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

                    <div class="w-[54px] h-[54px] bg-[#B4771E] flex items-center justify-center shrink-0">
                      <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="white"
                            class="w-9 h-9">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                    </div>

                    <div>
                        <h4 class="text-lg md:text-xl font-medium text-[#131615] mb-2">Call Us</h4>
                        <a href="tel:+917725978871" class="text-base md:text-lg text-[#3D403F] hover:text-[#B4771E] transition">+91 77259 78871</a>
                    </div>

                </div>

                <div class="flex gap-4 pb-6 mb-6 border-b border-[#e5e5e5]">

                    <div class="w-[54px] h-[54px] bg-[#B4771E] flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-9 h-9 text-white">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />

                            </svg>
                    </div>

                    <div>
                        <h4 class="text-lg md:text-xl font-medium text-[#131615] mb-2">Email Us</h4>
                        <a href="mailto:rawalshaitan@gmail.com" class="block text-base md:text-lg text-[#3D403F] hover:text-[#B4771E] transition break-all">rawalshaitan@gmail.com</a>
                    </div>

                </div>

                <div class="flex gap-4 pb-6 mb-6 border-b border-[#e5e5e5]">

                    <div class="w-[54px] h-[54px] bg-[#B4771E] flex items-center justify-center shrink-0">
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-9 h-9 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>

                    <div>
                        <h4 class="text-lg md:text-xl font-medium text-[#131615] mb-2">Visit Us</h4>

                        <a href="https://maps.app.goo.gl/aAs5DaPUi4WsVdxM7" target="_blank" rel="noopener noreferrer" class="block text-base md:text-lg text-[#3D403F] leading-7 mb-3 hover:text-[#B4771E] transition">
                            <span class="font-normal text-[#3D403F]">Branch - 1:</span>
                            G-14 Abc market, Abc circle, Sudama chowk,
                            Mota Varachha, Surat, Gujarat 394101, India
                        </a>

                        <a href="https://maps.app.goo.gl/XnRf9ToFDSEdEGRv9" target="_blank" rel="noopener noreferrer" class="block text-base md:text-lg text-[#3D403F] leading-7 hover:text-[#B4771E] transition">
                            <span class="font-normal text-[#3D403F]">Branch - 2:</span>
                            Shop No. 4, Narayan Flats,
                            Narayannagar Chok, Singanpore Road,
                            Katargam, Surat, Gujarat 395004, India
                        </a>
                    </div>

                </div>

                <div class="flex gap-4 mb-6">

                    <div class="w-[54px] h-[54px] bg-[#B4771E] flex items-center justify-center shrink-0">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-9 h-9 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

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

                <form id="contactForm" action="{{ route('contact.submit') }}" method="POST" novalidate>
                    @csrf
                    <div class="grid md:grid-cols-2 gap-3 md:gap-5 mb-3 md:mb-5">
                        <div>
                            <label class="block text-base md:text-xl mb-3 text-[#131615]">Full Name <span class="text-red-600">*</span></label>
                            <input type="text" name="full_name" placeholder="Enter Your Full Name" class="contact-input w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                            <p class="contact-error mt-2 text-sm text-red-600" data-error-for="full_name"></p>
                        </div>
                        <div>
                            <label class="block text-base md:text-xl mb-3 text-[#131615]">Email Address <span class="text-red-600">*</span></label>
                            <input type="email" name="email" placeholder="Enter Your Email" class="contact-input w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                            <p class="contact-error mt-2 text-sm text-red-600" data-error-for="email"></p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-3 md:gap-5 mb-3 md:mb-5">
                        <div>
                            <label class="block text-base md:text-xl mb-3 text-[#131615]">Phone Number <span class="text-red-600">*</span></label>
                            <input type="text" name="phone" placeholder="Enter Your Phone" maxlength="10" inputmode="numeric" class="contact-input w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                            <p class="contact-error mt-2 text-sm text-red-600" data-error-for="phone"></p>
                        </div>
                        <div>
                            <label class="block text-base md:text-xl mb-3 text-[#131615]">Subject <span class="text-red-600">*</span></label>
                            <input type="text" name="subject" placeholder="Enter Subject" class="contact-input w-full h-[52px] border border-[#dcdcdc] px-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E]">
                            <p class="contact-error mt-2 text-sm text-red-600" data-error-for="subject"></p>
                        </div>
                    </div>

                    <div class="mb-7">
                        <label class="block text-base md:text-xl mb-3 text-[#131615]">Message <span class="text-red-600">*</span></label>
                        <textarea rows="5" name="message" placeholder="Type Your Message Here...." class="contact-input w-full min-h-36 border border-[#DCDCDC] p-4 text-lg placeholder:text-lg outline-none focus:border-[#B4771E] resize-y"></textarea>
                        <p class="contact-error mt-2 text-sm text-red-600" data-error-for="message"></p>
                    </div>

                    <div id="contactSuccess" class="hidden mb-5 border border-green-200 bg-green-50 px-4 py-3 text-green-700"></div>
                    <div id="contactFailure" class="hidden mb-5 border border-red-200 bg-red-50 px-4 py-3 text-red-700"></div>

                    <button type="submit" id="contactSubmitBtn" class="w-full h-[56px] bg-[#B4771E] text-white text-xl font-medium hover:bg-[#b17820] duration-300">
                        Send Message
                    </button>
                </form>

            </div>

        </div>

    </div>

</section>

<section class="section-space-bottom">

    <div class="container-1440">

        <div class="text-center mb-10 lg:mb-12">
            <h2 class="font-moglan hero-title">Visit Our Store</h2>
            <p class="hero-para">Discover our latest jewellery collections and receive personalized assistance at our store.</p>
        </div>

        <div class="border border-[#D5D5D5] overflow-hidden bg-white">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3718.7814914346445!2d72.8780558!3d21.2405117!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be04fb74838640f%3A0xf07f814d770fed04!2sChetan%20imitation!5e0!3m2!1sen!2sin!4v1784891107042!5m2!1sen!2sin" class="w-full h-[300px] md:h-[450px] lg:h-[520px]" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>

    </div>

</section>

@endsection

@section('page-js')
<script>
    $(document).ready(function () {
        const form = $('#contactForm');
        const submitBtn = $('#contactSubmitBtn');
        const successBox = $('#contactSuccess');
        const failureBox = $('#contactFailure');

        function setFieldError(field, message) {
            const input = form.find('[name="' + field + '"]');
            const error = form.find('[data-error-for="' + field + '"]');
            input.toggleClass('border-red-500', Boolean(message));
            error.text(message || '');
        }

        function clearErrors() {
            form.find('.contact-error').text('');
            form.find('.contact-input').removeClass('border-red-500');
            successBox.addClass('hidden').text('');
            failureBox.addClass('hidden').text('');
        }

        function validateContactForm() {
            const errors = {};
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^[0-9]{10}$/;

            const fullName = $.trim(form.find('[name="full_name"]').val());
            const email = $.trim(form.find('[name="email"]').val());
            const phone = $.trim(form.find('[name="phone"]').val());
            const subject = $.trim(form.find('[name="subject"]').val());
            const message = $.trim(form.find('[name="message"]').val());

            if (!fullName) errors.full_name = 'Please enter your full name.';
            if (!email) errors.email = 'Please enter your email address.';
            else if (!emailRegex.test(email)) errors.email = 'Please enter a valid email address.';
            if (!phone) errors.phone = 'Please enter your phone number.';
            else if (!phoneRegex.test(phone)) errors.phone = 'Please enter a valid 10 digit phone number.';
            if (!subject) errors.subject = 'Please enter subject.';
            if (!message) errors.message = 'Please enter your message.';

            Object.keys(errors).forEach(function (field) {
                setFieldError(field, errors[field]);
            });

            return Object.keys(errors).length === 0;
        }

        form.find('[name="phone"]').on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });

        form.find('.contact-input').on('input', function () {
            setFieldError($(this).attr('name'), '');
        });

        form.on('submit', function (e) {
            e.preventDefault();
            clearErrors();

            if (!validateContactForm()) return;

            submitBtn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function (res) {
                    form[0].reset();
                    successBox.removeClass('hidden').text(res.message || 'Your message has been submitted successfully.');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                        $.each(xhr.responseJSON.message, function (field, messages) {
                            setFieldError(field, messages[0]);
                        });
                    } else {
                        failureBox.removeClass('hidden').text('Something went wrong. Please try again.');
                    }
                },
                complete: function () {
                    submitBtn.prop('disabled', false).text('Send Message');
                }
            });
        });
    });
</script>
@endsection
